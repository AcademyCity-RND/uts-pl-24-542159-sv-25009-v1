<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UtsScenariosTest extends TestCase
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
            'name' => 'Administrator Kampus',
            'email' => 'admin_' . uniqid() . '@jejakbarang.test',
            'password' => bcrypt('password123'),
        ]);
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    private function createUser(string $name = 'Mahasiswa Test'): User
    {
        return User::create([
            'name' => $name,
            'email' => 'user_' . uniqid() . '@jejakbarang.test',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);
    }

    /**
     * Skenario 1: Privasi Guest (GET /api/public/items).
     * Ekspektasi: HTTP 200, kunci contact_info & reporter_contact_info dihilangkan sama sekali (omitted).
     */
    public function test_scenario_01_guest_privacy_omits_contact_info(): void
    {
        $owner = $this->createUser('Pemilik Publik');

        $item = new LostItem([
            'item_name' => 'Dompet Kulit Cokelat',
            'category' => 'lost',
            'location' => 'Kantin Pusat',
            'current_storage' => 'Dibawa pemilik',
            'contact_info' => '081234567890',
            'reporter_contact_info' => '089876543210',
            'chronology' => 'Tertinggal di meja kasir',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(5);
        $item->save();

        $response = $this->getJson('/api/public/items');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'item_name',
                             'category',
                             'location',
                             'current_storage',
                             'chronology',
                             'status',
                             'approved_at',
                             'expires_at',
                             'is_expired',
                             'remaining_seconds',
                         ],
                     ],
                     'links',
                     'meta',
                 ]);

        // Pastikan tidak ada field contact_info dan reporter_contact_info pada seluruh item publik
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $publicItem) {
            $this->assertArrayNotHasKey('contact_info', $publicItem);
            $this->assertArrayNotHasKey('reporter_contact_info', $publicItem);
        }
    }

    /**
     * Skenario 2: Privasi Non-Owner (GET /api/items).
     * Ekspektasi: HTTP 200, kunci kontak pada item milik user lain dihilangkan (omitted).
     */
    public function test_scenario_02_non_owner_privacy_omits_contact_info(): void
    {
        $owner = $this->createUser('Pemilik Asli');
        $viewer = $this->createUser('Mahasiswa Peninjau');

        $item = new LostItem([
            'item_name' => 'Kalkulator Casio FX-991EX',
            'category' => 'found',
            'location' => 'Lab Komputer 3',
            'current_storage' => 'Loker Asisten Lab',
            'contact_info' => '081111111111',
            'reporter_contact_info' => '082222222222',
            'chronology' => 'Tertinggal di meja PC 12',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(2);
        $item->save();

        $token = $viewer->createToken('auth_token')->plainTextToken;

        // Uji pada endpoint list terautentikasi GET /api/items?status=approved
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/items?status=approved');

        $response->assertStatus(200);

        $matchedItem = collect($response->json('data'))->firstWhere('id', $item->id);
        $this->assertNotNull($matchedItem);
        $this->assertArrayNotHasKey('contact_info', $matchedItem);
        $this->assertArrayNotHasKey('reporter_contact_info', $matchedItem);

        // Uji juga pada endpoint detail GET /api/items/{id} oleh non-owner
        $responseDetail = $this->withHeader('Authorization', 'Bearer ' . $token)
                               ->getJson('/api/items/' . $item->id);

        $responseDetail->assertStatus(200);
        $this->assertArrayNotHasKey('contact_info', $responseDetail->json('data'));
        $this->assertArrayNotHasKey('reporter_contact_info', $responseDetail->json('data'));
    }

    /**
     * Skenario 3: Akses Kontak Owner (GET /api/items/{id}).
     * Ekspektasi: HTTP 200, kunci kontak disertakan lengkap saat diakses oleh pemilik barang.
     */
    public function test_scenario_03_owner_contact_access_includes_contact_info(): void
    {
        $owner = $this->createUser('Pemilik Lengkap');

        $item = new LostItem([
            'item_name' => 'Jam Tangan Seiko 5',
            'category' => 'lost',
            'location' => 'Masjid Kampus',
            'current_storage' => 'Disimpan pribadi',
            'contact_info' => '081333444555',
            'reporter_contact_info' => '087777888999',
            'chronology' => 'Dilepas saat mengambil wudhu',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(1);
        $item->save();

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/items/' . $item->id);

        $response->assertStatus(200)
                 ->assertJsonPath('data.contact_info', '081333444555')
                 ->assertJsonPath('data.reporter_contact_info', '087777888999');
    }

    /**
     * Skenario 4: Proteksi Laporan Admin (GET /api/item-reports).
     * Ekspektasi: Login user biasa ditolak dengan HTTP 403 Forbidden.
     */
    public function test_scenario_04_regular_user_forbidden_to_access_admin_reports(): void
    {
        $regularUser = $this->createUser('User Mahasiswa Biasa');
        $token = $regularUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/item-reports');

        $response->assertStatus(403);
    }

    /**
     * Skenario 5: Anti Self-Reporting (POST /api/items/{id}/report).
     * Ekspektasi: Pemilik barang yang mencoba melaporkan barangnya sendiri ditolak HTTP 403 Forbidden.
     */
    public function test_scenario_05_anti_self_reporting_owner_cannot_report_own_item(): void
    {
        $owner = $this->createUser('Pemilik Barang Sendiri');

        $item = new LostItem([
            'item_name' => 'Kunci Motor Honda Vario',
            'category' => 'lost',
            'location' => 'Parkiran Timur',
            'current_storage' => 'Pos Keamanan',
            'contact_info' => '081234567890',
            'chronology' => 'Jatuh dari saku celana',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(3);
        $item->save();

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/items/' . $item->id . '/report', [
                             'message' => 'Saya sendiri yang menemukan kunci saya yang hilang.',
                         ]);

        $response->assertStatus(403);
    }

    /**
     * Skenario 6: Anti Duplicate Report (POST /api/items/{id}/report).
     * Ekspektasi: Pengguna yang mencoba membuat 2 laporan pending pada item yang sama ditolak HTTP 409 Conflict.
     */
    public function test_scenario_06_anti_duplicate_report_cannot_send_two_pending_reports(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder = $this->createUser('Penemu Rajin');

        $item = new LostItem([
            'item_name' => 'Buku Catatan Algoritma',
            'category' => 'lost',
            'location' => 'Ruang 204',
            'current_storage' => 'Meja Dosen',
            'contact_info' => '081234567890',
            'chronology' => 'Ketinggalan di bangku baris ketiga',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(4);
        $item->save();

        $token = $finder->createToken('auth_token')->plainTextToken;

        // Laporan pertama sukses 201 Created
        $resp1 = $this->withHeader('Authorization', 'Bearer ' . $token)
                      ->postJson('/api/items/' . $item->id . '/report', [
                          'message' => 'Buku ada di meja dosen ruangan 204.',
                      ]);
        $resp1->assertStatus(201);

        // Laporan kedua oleh user yang sama pada barang yang sama saat laporan pertama masih pending -> 409 Conflict
        auth('sanctum')->forgetUser();
        $resp2 = $this->withHeader('Authorization', 'Bearer ' . $token)
                      ->postJson('/api/items/' . $item->id . '/report', [
                          'message' => 'Update: buku sudah diamankan staf prodi.',
                      ]);
        $resp2->assertStatus(409);
    }

    /**
     * Skenario 7: Barang Expired Tidak Boleh Dilapor (POST /api/items/{id}/report).
     * Ekspektasi: Melaporkan barang yang telah kedaluwarsa (> 72 jam) ditolak HTTP 409 Conflict.
     */
    public function test_scenario_07_expired_item_cannot_be_reported(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder = $this->createUser('Penemu Barang');

        $item = new LostItem([
            'item_name' => 'Payung Lipat Biru',
            'category' => 'lost',
            'location' => 'Lobi Utama',
            'current_storage' => 'Tempat Payung',
            'contact_info' => '081234567890',
            'chronology' => 'Tertinggal saat hujan deras',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(75); // Melebihi 72 jam (expired)
        $item->save();

        $token = $finder->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/items/' . $item->id . '/report', [
                             'message' => 'Payung ditemukan di dekat pintu keluar.',
                         ]);

        $response->assertStatus(409);
    }

    /**
     * Skenario 8: Anti Double Resolution (DB Lock) (PATCH /api/item-reports/{id}/status).
     * Ekspektasi: Persetujuan pada laporan kedua untuk barang yang sudah diproses ditolak HTTP 409 Conflict.
     * Kompetisi dua transaksi MySQL yang benar-benar bersamaan diuji di ConcurrentResolutionLockTest.
     */
    public function test_scenario_08_anti_double_resolution_parallel_second_report_rejected(): void
    {
        $owner = $this->createUser('Pemilik Barang');
        $finder1 = $this->createUser('Penemu A');
        $finder2 = $this->createUser('Penemu B');
        $admin = $this->createAdmin();

        $item = new LostItem([
            'item_name' => 'Flashdisk Kingston 32GB',
            'category' => 'lost',
            'location' => 'Lab Jaringan',
            'current_storage' => 'Meja Asisten',
            'contact_info' => '081234567890',
            'chronology' => 'Tertancap di PC server',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(2);
        $item->save();

        $report1 = new ItemReport();
        $report1->lost_item_id = $item->id;
        $report1->reporter_user_id = $finder1->id;
        $report1->message = 'Laporan penemuan oleh penemu A.';
        $report1->status = 'pending';
        $report1->save();

        $report2 = new ItemReport();
        $report2->lost_item_id = $item->id;
        $report2->reporter_user_id = $finder2->id;
        $report2->message = 'Laporan penemuan oleh penemu B.';
        $report2->status = 'pending';
        $report2->save();

        $adminToken = $admin->createToken('auth_token')->plainTextToken;

        \Illuminate\Support\Facades\DB::enableQueryLog();

        // 1. Persetujuan laporan 1 dieksekusi dengan Dual Pessimistic Locking (lockForUpdate)
        $resp1 = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                      ->patchJson('/api/item-reports/' . $report1->id . '/status', [
                          'status' => 'accepted',
                      ]);
        $resp1->assertStatus(200);

        // Validasi bahwa query SQL benar-benar menyertakan klausul FOR UPDATE di level engine database MySQL
        $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query');
        $hasItemReportLock = $queries->contains(function ($q) {
            return str_contains(strtolower($q), 'item_reports') && str_contains(strtolower($q), 'for update');
        });
        $hasLostItemLock = $queries->contains(function ($q) {
            return str_contains(strtolower($q), 'lost_items') && str_contains(strtolower($q), 'for update');
        });

        $this->assertTrue($hasItemReportLock, 'Query item_reports wajib mengeksekusi FOR UPDATE (Pessimistic Lock).');
        $this->assertTrue($hasLostItemLock, 'Query lost_items wajib mengeksekusi FOR UPDATE (Pessimistic Lock).');

        // 2. Verifikasi request lanjutan: transaksi kedua dicegah dari double resolution
        // setelah transaksi pertama mengubah status item menjadi resolve_requested.
        auth('sanctum')->forgetUser();
        $resp2 = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                      ->patchJson('/api/item-reports/' . $report2->id . '/status', [
                          'status' => 'accepted',
                      ]);

        $resp2->assertStatus(409)
              ->assertJson([
                  'message' => 'Barang tidak dalam status aktif atau telah kedaluwarsa.',
              ]);

        // 3. Verifikasi integritas state: Hanya 1 laporan yang berstatus accepted
        $this->assertEquals('accepted', $report1->fresh()->status);
        $this->assertEquals('pending', $report2->fresh()->status);
        $this->assertEquals('resolve_requested', $item->fresh()->status);
        $this->assertEquals(1, $item->itemReports()->where('status', 'accepted')->count());
    }

    /**
     * Skenario 9: Persetujuan Laporan Admin (PATCH /api/item-reports/{id}/status).
     * Ekspektasi: Report status menjadi accepted dan item status otomatis berubah menjadi resolve_requested.
     */
    public function test_scenario_09_admin_approval_sets_report_accepted_and_item_resolve_requested(): void
    {
        $owner = $this->createUser('Pemilik Laptop');
        $finder = $this->createUser('Penemu Laptop');
        $admin = $this->createAdmin();

        $item = new LostItem([
            'item_name' => 'MacBook Air M2',
            'category' => 'lost',
            'location' => 'Perpustakaan Lt. 3',
            'current_storage' => 'Meja Layanan',
            'contact_info' => '081234567890',
            'chronology' => 'Tertinggal di meja baca',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(1);
        $item->save();

        $report = new ItemReport();
        $report->lost_item_id = $item->id;
        $report->reporter_user_id = $finder->id;
        $report->message = 'Laptop telah diserahkan ke meja petugas lantai 3.';
        $report->status = 'pending';
        $report->save();

        $adminToken = $admin->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                         ->patchJson('/api/item-reports/' . $report->id . '/status', [
                             'status' => 'accepted',
                         ]);

        $response->assertStatus(200);
        $this->assertEquals('accepted', $response->json('data.status'));
        $this->assertEquals('resolve_requested', $item->fresh()->status);
    }

    /**
     * Skenario 10: Penolakan Resolusi Owner (PATCH /api/items/{id}/status).
     * Ekspektasi: Owner menolak resolusi -> item kembali approved (waktu approved_at tetap), report otomatis closed.
     */
    public function test_scenario_10_owner_rejection_resets_item_approved_original_time_and_closes_report(): void
    {
        $owner = $this->createUser('Pemilik Kamera');
        $finder = $this->createUser('Penemu Kamera');

        $item = new LostItem([
            'item_name' => 'Kamera Sony A6000',
            'category' => 'lost',
            'location' => 'Auditorium',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '081234567890',
            'chronology' => 'Tertinggal setelah wisuda',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'resolve_requested';
        $originalApprovedAt = now()->subHours(12);
        $item->approved_at = $originalApprovedAt;
        $item->save();

        $report = new ItemReport();
        $report->lost_item_id = $item->id;
        $report->reporter_user_id = $finder->id;
        $report->message = 'Kamera sudah diserahkan ke satpam.';
        $report->status = 'accepted';
        $report->save();

        $ownerToken = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                         ->patchJson('/api/items/' . $item->id . '/status', [
                             'status' => 'approved',
                         ]);

        $response->assertStatus(200);
        $this->assertEquals('approved', $response->json('data.status'));
        $this->assertEquals($originalApprovedAt->toIso8601String(), $response->json('data.approved_at'));
        $this->assertEquals('closed', $report->fresh()->status);
    }

    /**
     * Skenario 11: Batas Waktu Repost (POST /api/items/{id}/repost).
     * Ekspektasi: Repost pada barang yang masa aktifnya belum habis (< 72 jam) ditolak HTTP 409 Conflict.
     */
    public function test_scenario_11_repost_cooldown_before_72_hours_rejected(): void
    {
        $owner = $this->createUser('Pemilik Repost');

        // Barang baru disetujui 6 jam lalu (belum 72 jam)
        $item = new LostItem([
            'item_name' => 'Helm KYT TT Course',
            'category' => 'lost',
            'location' => 'Parkiran Belakang',
            'current_storage' => 'Dibawa pemilik',
            'contact_info' => '081234567890',
            'chronology' => 'Tergantung di spion motor',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(6);
        $item->save();

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/items/' . $item->id . '/repost');

        $response->assertStatus(409);
    }

    /**
     * Skenario 12: Resubmit Barang Ditolak (POST /api/items/{id} dengan _method: PUT).
     * Ekspektasi: Update data pada item status rejected berhasil dan status otomatis kembali ke pending.
     */
    public function test_scenario_12_resubmit_rejected_item_method_spoofing_resets_to_pending(): void
    {
        $owner = $this->createUser('Pemilik Revisi');

        $item = new LostItem([
            'item_name' => 'Jaket Angkatan SV',
            'category' => 'lost',
            'location' => 'Kantin Barat',
            'current_storage' => 'Dibawa pemilik',
            'contact_info' => '081234567890',
            'chronology' => 'Deskripsi lama kurang lengkap',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'rejected';
        $item->save();

        $token = $owner->createToken('auth_token')->plainTextToken;

        Storage::fake('public');
        $newPhoto = UploadedFile::fake()->image('jaket_baru.jpg', 600, 600);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->post('/api/items/' . $item->id, [
                             '_method' => 'PUT',
                             'item_name' => 'Jaket Angkatan SV Biru Dongker Size L',
                             'chronology' => 'Tertinggal di kursi kantin barat dekat penjual es teh',
                             'image' => $newPhoto,
                         ]);

        $response->assertStatus(200);
        $this->assertEquals('pending', $response->json('data.status'));
        $this->assertNull($response->json('data.approved_at'));
        $this->assertEquals('Jaket Angkatan SV Biru Dongker Size L', $response->json('data.item_name'));
        $this->assertNotNull($response->json('data.image_path'));
    }
}
