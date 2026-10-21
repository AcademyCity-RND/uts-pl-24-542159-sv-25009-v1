<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemReportResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        auth('sanctum')->forgetUser();
        parent::tearDown();
    }

    private function createAdmin(): User
    {
        $admin = new User([
            'name' => 'Admin Sistem',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    private function createUser(string $name = 'User Test'): User
    {
        return User::create([
            'name' => $name,
            'email' => 'user_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);
    }

    private function createApprovedItem(User $owner, int $approvedHoursAgo = 2): LostItem
    {
        $item = new LostItem([
            'item_name' => 'Laptop Asus ROG Zephyrus',
            'category' => 'lost',
            'location' => 'Perpustakaan Pusat Lt. 2',
            'current_storage' => 'Meja Layanan Sirkulasi',
            'contact_info' => '081234567890',
            'chronology' => 'Ketinggalan di bilik belajar mandiri',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours($approvedHoursAgo);
        $item->save();

        return $item;
    }

    /**
     * Skenario 4: Proteksi Laporan Admin (GET /api/item-reports).
     * User biasa ditolak HTTP 403 Forbidden, Admin berhasil HTTP 200 OK.
     */
    public function test_skenario_4_regular_user_forbidden_to_access_admin_reports(): void
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();

        $userToken = $user->createToken('auth_token')->plainTextToken;
        $adminToken = $admin->createToken('auth_token')->plainTextToken;

        // 1. User biasa mengakses endpoint -> 403 Forbidden
        $responseUser = $this->withHeader('Authorization', 'Bearer ' . $userToken)
                             ->getJson('/api/item-reports');
        $responseUser->assertStatus(403);

        // 2. Admin mengakses endpoint -> 200 OK
        auth('sanctum')->forgetUser();
        $responseAdmin = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                              ->getJson('/api/item-reports');
        $responseAdmin->assertStatus(200);
    }

    /**
     * Skenario 5: Anti Self-Reporting (POST /api/items/{id}/report).
     * Pemilik barang dilarang melaporkan barangnya sendiri (HTTP 403 Forbidden).
     */
    public function test_skenario_5_owner_cannot_report_own_item(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $item = $this->createApprovedItem($owner);

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/items/' . $item->id . '/report', [
                             'message' => 'Ini barang saya sendiri yang hilang dan saya temukan.',
                         ]);

        $response->assertStatus(403);
    }

    /**
     * Skenario 6: Anti Duplicate Report (POST /api/items/{id}/report).
     * Pengguna hanya boleh memiliki maksimal 1 laporan pending pada barang yang sama (HTTP 409 Conflict).
     */
    public function test_skenario_6_cannot_send_duplicate_pending_reports(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder = $this->createUser('Penemu Barang');
        $item = $this->createApprovedItem($owner);

        $finderToken = $finder->createToken('auth_token')->plainTextToken;

        // Laporan pertama sukses (201 Created)
        $resp1 = $this->withHeader('Authorization', 'Bearer ' . $finderToken)
                      ->postJson('/api/items/' . $item->id . '/report', [
                          'message' => 'Barang saya lihat diamankan di ruang satpam lobi timur.',
                      ]);
        $resp1->assertStatus(201);

        // Laporan kedua oleh user yang sama pada barang yang sama saat masih pending -> 409 Conflict
        auth('sanctum')->forgetUser();
        $resp2 = $this->withHeader('Authorization', 'Bearer ' . $finderToken)
                      ->postJson('/api/items/' . $item->id . '/report', [
                          'message' => 'Koreksi: barang sudah diserahkan ke satpam piket malam.',
                      ]);
        $resp2->assertStatus(409);
    }

    /**
     * Skenario 7: Barang Expired Tidak Boleh Dilapor (POST /api/items/{id}/report).
     * Barang yang sudah melewati 72 jam masa tayang aktif ditolak HTTP 409 Conflict.
     */
    public function test_skenario_7_cannot_report_expired_item(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder = $this->createUser('Penemu Barang');
        // Barang approved 73 jam lalu (expired)
        $expiredItem = $this->createApprovedItem($owner, 73);

        $finderToken = $finder->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $finderToken)
                         ->postJson('/api/items/' . $expiredItem->id . '/report', [
                             'message' => 'Saya menemukan barang ini di tempat parkir motor.',
                         ]);

        $response->assertStatus(409);
    }

    /**
     * Skenario 8 & 9: Persetujuan Laporan Admin & Anti Double Resolution (Paralel / DB Lock).
     * Laporan pertama di-accept -> Item status berubah jadi resolve_requested.
     * Laporan kedua pada barang yang sama ditolak HTTP 409 Conflict.
     */
    public function test_skenario_8_and_9_admin_accept_report_and_prevent_double_resolution(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder1 = $this->createUser('Penemu Pertama');
        $finder2 = $this->createUser('Penemu Kedua');
        $admin = $this->createAdmin();

        $item = $this->createApprovedItem($owner);

        // Buat 2 laporan penemuan pending dari dua pelapor berbeda
        $report1 = new ItemReport();
        $report1->lost_item_id = $item->id;
        $report1->reporter_user_id = $finder1->id;
        $report1->message = 'Ditemukan penemu pertama di meja satpam.';
        $report1->status = 'pending';
        $report1->save();

        $report2 = new ItemReport();
        $report2->lost_item_id = $item->id;
        $report2->reporter_user_id = $finder2->id;
        $report2->message = 'Ditemukan penemu kedua di lobi depan.';
        $report2->status = 'pending';
        $report2->save();

        $adminToken = $admin->createToken('auth_token')->plainTextToken;

        \Illuminate\Support\Facades\DB::enableQueryLog();

        // Skenario 9: Admin menyetujui laporan pertama (200 OK, report: accepted, item: resolve_requested)
        $respAccept1 = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                            ->patchJson('/api/item-reports/' . $report1->id . '/status', [
                                'status' => 'accepted',
                            ]);
        $respAccept1->assertStatus(200);
        $this->assertEquals('accepted', $respAccept1->json('data.status'));
        $this->assertEquals('resolve_requested', $item->fresh()->status);

        // Validasi klausul FOR UPDATE di level engine database MySQL untuk kedua tabel
        $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query');
        $hasItemReportLock = $queries->contains(function ($q) {
            return str_contains(strtolower($q), 'item_reports') && str_contains(strtolower($q), 'for update');
        });
        $hasLostItemLock = $queries->contains(function ($q) {
            return str_contains(strtolower($q), 'lost_items') && str_contains(strtolower($q), 'for update');
        });

        $this->assertTrue($hasItemReportLock, 'Query item_reports wajib mengeksekusi FOR UPDATE (Pessimistic Lock).');
        $this->assertTrue($hasLostItemLock, 'Query lost_items wajib mengeksekusi FOR UPDATE (Pessimistic Lock).');

        // Skenario 8: Admin mencoba menyetujui laporan kedua pada item yang sama -> 409 Conflict
        auth('sanctum')->forgetUser();
        $respAccept2 = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                            ->patchJson('/api/item-reports/' . $report2->id . '/status', [
                                'status' => 'accepted',
                            ]);
        $respAccept2->assertStatus(409);
    }

    /**
     * Skenario 10: Penolakan Resolusi Owner (PATCH /api/items/{id}/status).
     * Owner mengubah status dari resolve_requested -> approved.
     * Item kembali approved (approved_at tetap waktu asli), dan laporan accepted otomatis menjadi closed.
     */
    public function test_skenario_10_owner_reject_resolution_closes_report_and_keeps_approved_at(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder = $this->createUser('Penemu Barang');
        $admin = $this->createAdmin();

        $item = $this->createApprovedItem($owner, 10);
        $originalApprovedAt = $item->approved_at;

        // Laporan dibuat dan di-accept oleh Admin
        $report = new ItemReport();
        $report->lost_item_id = $item->id;
        $report->reporter_user_id = $finder->id;
        $report->message = 'Barang diamankan di pos satpam.';
        $report->status = 'accepted';
        $report->save();

        $item->status = 'resolve_requested';
        $item->save();

        $ownerToken = $owner->createToken('auth_token')->plainTextToken;

        // Owner menolak klaim resolusi
        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                         ->patchJson('/api/items/' . $item->id . '/status', [
                             'status' => 'approved',
                         ]);

        $response->assertStatus(200);
        $this->assertEquals('approved', $response->json('data.status'));
        $this->assertEquals($originalApprovedAt->toIso8601String(), $response->json('data.approved_at'));

        // Pastikan laporan accepted otomatis berubah menjadi closed
        $this->assertEquals('closed', $report->fresh()->status);
    }

    /**
     * Skenario 11: Batas Waktu Repost (POST /api/items/{id}/repost).
     * Ditolak HTTP 409 jika belum 72 jam; berhasil HTTP 200 jika sudah > 72 jam atau resolved.
     */
    public function test_skenario_11_repost_cooldown_and_success_flow(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $nonOwner = $this->createUser('Bukan Pemilik');

        // Barang baru approved 5 jam lalu (belum 72 jam)
        $activeItem = $this->createApprovedItem($owner, 5);

        $ownerToken = $owner->createToken('auth_token')->plainTextToken;
        $nonOwnerToken = $nonOwner->createToken('auth_token')->plainTextToken;

        // 1. Non-owner dilarang repost (403 Forbidden)
        $respNonOwner = $this->withHeader('Authorization', 'Bearer ' . $nonOwnerToken)
                             ->postJson('/api/items/' . $activeItem->id . '/repost');
        $respNonOwner->assertStatus(403);

        // 2. Owner repost saat barang belum kedaluwarsa (< 72 jam) -> 409 Conflict
        auth('sanctum')->forgetUser();
        $respEarly = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                          ->postJson('/api/items/' . $activeItem->id . '/repost');
        $respEarly->assertStatus(409);

        // 3. Barang sudah kedaluwarsa (75 jam lalu) -> Repost sukses 200 OK
        $expiredItem = $this->createApprovedItem($owner, 75);

        auth('sanctum')->forgetUser();
        $respExpired = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                            ->postJson('/api/items/' . $expiredItem->id . '/repost');
        $respExpired->assertStatus(200);
        $this->assertEquals('approved', $respExpired->json('data.status'));
        $this->assertFalse($respExpired->json('data.is_expired'));

        // 4. Barang berstatus resolved -> Repost sukses 200 OK
        $resolvedItem = $this->createApprovedItem($owner, 10);
        $resolvedItem->status = 'resolved';
        $resolvedItem->resolved_at = now()->subHours(2);
        $resolvedItem->save();

        auth('sanctum')->forgetUser();
        $respResolved = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                             ->postJson('/api/items/' . $resolvedItem->id . '/repost');
        $respResolved->assertStatus(200);
        $this->assertEquals('approved', $respResolved->json('data.status'));
        $this->assertNull($respResolved->json('data.resolved_at'));

        // 5. Repost kedua sebelum 72 jam sejak repost terakhir -> 409 Conflict
        $resolvedItem->status = 'resolved';
        $resolvedItem->resolved_at = now();
        $resolvedItem->save();

        auth('sanctum')->forgetUser();
        $respCooldown = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                             ->postJson('/api/items/' . $resolvedItem->id . '/repost');
        $respCooldown->assertStatus(409);
    }

    /**
     * Menguji larangan admin mengubah status laporan menjadi 'closed' secara manual (403 Forbidden).
     */
    public function test_admin_cannot_manually_set_report_status_to_closed(): void
    {
        $owner = $this->createUser();
        $finder = $this->createUser();
        $admin = $this->createAdmin();

        $item = $this->createApprovedItem($owner);

        $report = new ItemReport();
        $report->lost_item_id = $item->id;
        $report->reporter_user_id = $finder->id;
        $report->message = 'Laporan penemuan barang.';
        $report->status = 'pending';
        $report->save();

        $adminToken = $admin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                         ->patchJson('/api/item-reports/' . $report->id . '/status', [
                             'status' => 'closed',
                         ]);

        $response->assertStatus(403);
    }
}
