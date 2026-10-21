<?php

namespace Tests\Feature;

use App\Models\LostItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skenario Uji 2 (Wajib UTS):
     * Pengguna terautentikasi yang bukan pemilik barang dilarang melihat nomor kontak pengguna lain.
     */
    public function test_authenticated_non_owner_cannot_see_other_user_contact_info(): void
    {
        $owner = User::create([
            'name' => 'Pemilik Barang',
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'phone_number' => '081211112222',
        ]);

        $nonOwner = User::create([
            'name' => 'Bukan Pemilik',
            'email' => 'nonowner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'phone_number' => '081233334444',
        ]);

        $item = new LostItem([
            'item_name' => 'Laptop Asus ROG',
            'category' => 'lost',
            'location' => 'Lab Komputer 3',
            'current_storage' => 'Pos Satpam Gedung B',
            'contact_info' => '081299990000', // Kontak privat
            'reporter_contact_info' => '081288887777', // Kontak privat pelapor
            'chronology' => 'Tertinggal di meja ujung dekat jendela',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(2);
        $item->save();

        $token = $nonOwner->createToken('auth_token')->plainTextToken;

        // 1. Uji pada endpoint detail GET /api/items/{id}
        $responseDetail = $this->withHeader('Authorization', 'Bearer ' . $token)
                               ->getJson('/api/items/' . $item->id);

        $responseDetail->assertStatus(200);
        $dataDetail = $responseDetail->json('data');
        $this->assertArrayNotHasKey('contact_info', $dataDetail, 'User non-owner dilarang melihat contact_info barang orang lain.');
        $this->assertArrayNotHasKey('reporter_contact_info', $dataDetail, 'User non-owner dilarang melihat reporter_contact_info.');

        // 2. Uji pada endpoint list GET /api/items?status=approved
        $responseList = $this->withHeader('Authorization', 'Bearer ' . $token)
                             ->getJson('/api/items?status=approved');

        $responseList->assertStatus(200);
        $dataList = $responseList->json('data');
        $this->assertNotEmpty($dataList);
        $this->assertArrayNotHasKey('contact_info', $dataList[0]);
        $this->assertArrayNotHasKey('reporter_contact_info', $dataList[0]);
    }

    /**
     * Skenario Uji 3 (Wajib UTS):
     * Pemilik barang dan Admin dapat melihat seluruh informasi kontak lengkap.
     */
    public function test_owner_and_admin_can_see_contact_info(): void
    {
        $owner = User::create([
            'name' => 'Pemilik Barang Asli',
            'email' => 'realowner@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $admin = new User([
            'name' => 'Admin Kampus',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->role = 'admin';
        $admin->save();

        $item = new LostItem([
            'item_name' => 'Dompet Kulit Coklat',
            'category' => 'found',
            'location' => 'Masjid Kampus',
            'current_storage' => 'Kantor Takmir',
            'contact_info' => '081999999999',
            'reporter_contact_info' => '081888888888',
            'chronology' => 'Ditemukan di dekat tempat wudhu pria',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'approved';
        $item->approved_at = now()->subHours(1);
        $item->save();

        // 1. Verifikasi Pemilik (Owner) dapat melihat kontaknya sendiri
        $ownerToken = $owner->createToken('auth_token')->plainTextToken;
        $responseOwner = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                              ->getJson('/api/items/' . $item->id);

        $responseOwner->assertStatus(200);
        $this->assertEquals('081999999999', $responseOwner->json('data.contact_info'));
        $this->assertEquals('081888888888', $responseOwner->json('data.reporter_contact_info'));

        // 2. Verifikasi Admin dapat melihat seluruh kontak
        $adminToken = $admin->createToken('auth_token')->plainTextToken;
        $responseAdmin = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                              ->getJson('/api/items/' . $item->id);

        $responseAdmin->assertStatus(200);
        $this->assertEquals('081999999999', $responseAdmin->json('data.contact_info'));
        $this->assertEquals('081888888888', $responseAdmin->json('data.reporter_contact_info'));
    }

    /**
     * Skenario Uji 12 (Wajib UTS):
     * Memperbarui barang yang sebelumnya berstatus rejected secara otomatis mereset status menjadi pending.
     */
    public function test_editing_rejected_item_resets_status_to_pending(): void
    {
        $owner = User::create([
            'name' => 'Mahasiswa Pemilik',
            'email' => 'student@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $item = new LostItem([
            'item_name' => 'Kalkulator Casio FX-991EX',
            'category' => 'lost',
            'location' => 'Gedung Pasca Lantai 2',
            'current_storage' => 'Tas Pelapor',
            'contact_info' => '081234567890',
            'chronology' => 'Kronologi kurang lengkap sebelumnya',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'rejected'; // Status awal ditolak oleh admin
        $item->save();

        $token = $owner->createToken('auth_token')->plainTextToken;

        $updatePayload = [
            '_method' => 'PUT',
            'item_name' => 'Kalkulator Casio FX-991EX (Revisi Lengkap)',
            'chronology' => 'Tertinggal di meja ruang ujian D-201 setelah ujian selesai pukul 11.30 WIB.',
            'category' => 'lost',
            'location' => 'Gedung Pasca Lantai 2',
            'current_storage' => 'Tas Pelapor',
            'contact_info' => '081234567890',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/items/' . $item->id, $updatePayload);

        $response->assertStatus(200);
        $this->assertEquals('pending', $response->json('data.status'), 'Status barang harus otomatis di-reset menjadi pending.');

        $this->assertDatabaseHas('lost_items', [
            'id' => $item->id,
            'item_name' => 'Kalkulator Casio FX-991EX (Revisi Lengkap)',
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    /**
     * Menguji pembuatan laporan barang baru (POST /api/items) dengan upload file gambar.
     */
    public function test_user_can_create_item_with_image_upload(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Pelapor Baru',
            'email' => 'pelapor@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $file = UploadedFile::fake()->image('barang.png', 400, 400);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->post('/api/items', [
                             'item_name' => 'Payung Lipat Biru',
                             'category' => 'found',
                             'location' => 'Lobi Perpustakaan',
                             'current_storage' => 'Meja Sirkulasi',
                             'contact_info' => '081122334455',
                             'chronology' => 'Tertinggal saat hujan deras sore hari',
                             'image' => $file,
                         ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $this->assertEquals('pending', $response->json('data.status'));
        $this->assertNotNull($response->json('data.image_path'));

        Storage::disk('public')->assertExists($response->json('data.image_path'));
    }

    /**
     * Menguji pengguna non-admin dilarang menyetujui barang (403 Forbidden).
     */
    public function test_non_admin_cannot_approve_item(): void
    {
        $user = User::create([
            'name' => 'User Biasa',
            'email' => 'userbiasa@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $item = new LostItem([
            'item_name' => 'Flashdisk Sandisk 64GB',
            'category' => 'found',
            'location' => 'Lab Jaringan',
            'current_storage' => 'Meja Asisten',
            'contact_info' => '081333333333',
            'chronology' => 'Tertancap di PC 05',
        ]);
        $item->user_id = $user->id;
        $item->status = 'pending';
        $item->save();

        $userToken = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $userToken)
                         ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'approved']);

        $response->assertStatus(403);
    }

    /**
     * Menguji admin dapat menyetujui barang dan validasi state rejected (409 Conflict).
     */
    public function test_admin_can_approve_item_and_reject_status_rules(): void
    {
        $admin = new User([
            'name' => 'Admin Validasi',
            'email' => 'adminval@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->role = 'admin';
        $admin->save();

        $user = User::create([
            'name' => 'User Lain',
            'email' => 'userlain@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $item = new LostItem([
            'item_name' => 'Flashdisk Sandisk 64GB',
            'category' => 'found',
            'location' => 'Lab Jaringan',
            'current_storage' => 'Meja Asisten',
            'contact_info' => '081333333333',
            'chronology' => 'Tertancap di PC 05',
        ]);
        $item->user_id = $user->id;
        $item->status = 'pending';
        $item->save();

        $adminToken = $admin->createToken('auth_token')->plainTextToken;

        // 1. Admin sukses approve barang (200 OK + approved_at terisi)
        $respAdminApprove = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                                 ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'approved']);
        $respAdminApprove->assertStatus(200);
        $this->assertEquals('approved', $respAdminApprove->json('data.status'));
        $this->assertNotNull($respAdminApprove->json('data.approved_at'));

        // 2. Status rejected dilarang pada barang yang sudah approved (409 Conflict)
        $respConflict = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                             ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'rejected']);
        $respConflict->assertStatus(409);
    }

    /**
     * Menguji penghapusan barang (DELETE /api/items/{id}).
     */
    public function test_owner_can_delete_item(): void
    {
        $owner = User::create([
            'name' => 'Pemilik Hapus',
            'email' => 'hapus@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $item = new LostItem([
            'item_name' => 'Topi Hitam',
            'category' => 'lost',
            'location' => 'Kantin',
            'current_storage' => 'Dibawa sendiri',
            'contact_info' => '081234567890',
            'chronology' => 'Ketinggalan di kursi',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'pending';
        $item->save();

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->deleteJson('/api/items/' . $item->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Data laporan barang berhasil dihapus.',
                 ]);

        $this->assertDatabaseMissing('lost_items', [
            'id' => $item->id,
        ]);
    }

    /**
     * Menguji penegakan State Transition Matrix ketat pada PATCH /api/items/{id}/status.
     */
    public function test_state_transition_matrix_enforcement(): void
    {
        $admin = new User([
            'name' => 'Admin State',
            'email' => 'adminstate@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->role = 'admin';
        $admin->save();

        $owner = User::create([
            'name' => 'Owner State',
            'email' => 'ownerstate@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $adminToken = $admin->createToken('auth_token')->plainTextToken;
        $ownerToken = $owner->createToken('auth_token')->plainTextToken;

        $item = new LostItem([
            'item_name' => 'Kunci Motor Honda',
            'category' => 'lost',
            'location' => 'Gedung A',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '081234567890',
            'chronology' => 'Jatuh di lobi',
        ]);
        $item->user_id = $owner->id;
        $item->status = 'pending';
        $item->save();

        // 1. Owner dilarang mengubah pending langsung menjadi resolved (409 Conflict)
        $respPendingToResolved = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                                      ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'resolved']);
        $respPendingToResolved->assertStatus(409);

        // 2. Admin approve pending -> approved (200 OK)
        auth('sanctum')->forgetUser();
        $respApprove = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                            ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'approved']);
        $respApprove->assertStatus(200);

        // 3. Transisi ke status identik (approved -> approved) dilarang (409 Conflict)
        $respSameStatus = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                               ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'approved']);
        $respSameStatus->assertStatus(409);

        // 4. Admin dilarang mengubah approved -> resolved (hanya Owner yang boleh: 403 Forbidden)
        auth('sanctum')->forgetUser();
        $respAdminResolved = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                                  ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'resolved']);
        $respAdminResolved->assertStatus(403);

        // 5. Owner mengubah approved -> resolved (200 OK)
        auth('sanctum')->forgetUser();
        $respResolved = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                             ->patchJson('/api/items/' . $item->id . '/status', ['status' => 'resolved']);
        $respResolved->assertStatus(200);
        $this->assertEquals('resolved', $respResolved->json('data.status'));
        $this->assertNotNull($respResolved->json('data.resolved_at'));

        // 6. Admin dilarang mengubah rejected langsung menjadi approved (harus via edit owner ke pending)
        auth('sanctum')->forgetUser();
        $rejectedItem = new LostItem([
            'item_name' => 'Barang Ditolak',
            'category' => 'found',
            'location' => 'Parkiran',
            'current_storage' => 'Meja Satpam',
            'contact_info' => '081234567890',
            'chronology' => 'Ditemukan siang hari',
        ]);
        $rejectedItem->user_id = $owner->id;
        $rejectedItem->status = 'rejected';
        $rejectedItem->save();

        $respRejectedToApproved = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                                       ->patchJson('/api/items/' . $rejectedItem->id . '/status', ['status' => 'approved']);
        $respRejectedToApproved->assertStatus(409);

        // 7. Pengujian state resolve_requested:
        $resolveReqItem = new LostItem([
            'item_name' => 'Kamera Canon EOS',
            'category' => 'lost',
            'location' => 'Auditorium',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '081234567890',
            'chronology' => 'Tertinggal setelah seminar',
        ]);
        $resolveReqItem->user_id = $owner->id;
        $resolveReqItem->status = 'resolve_requested';
        $originalApprovedAt = now()->subHours(5);
        $resolveReqItem->approved_at = $originalApprovedAt;
        $resolveReqItem->save();

        // Admin dilarang mengubah resolve_requested -> approved (hanya Owner yang boleh: 403 Forbidden)
        auth('sanctum')->forgetUser();
        $respAdminRRToApproved = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
                                      ->patchJson('/api/items/' . $resolveReqItem->id . '/status', ['status' => 'approved']);
        $respAdminRRToApproved->assertStatus(403);

        // Owner menolak resolusi: resolve_requested -> approved (200 OK + approved_at TETAP Waktu Asli)
        auth('sanctum')->forgetUser();
        $respOwnerRRToApproved = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
                                      ->patchJson('/api/items/' . $resolveReqItem->id . '/status', ['status' => 'approved']);
        $respOwnerRRToApproved->assertStatus(200);
        $this->assertEquals('approved', $respOwnerRRToApproved->json('data.status'));
        $this->assertEquals($originalApprovedAt->toIso8601String(), $respOwnerRRToApproved->json('data.approved_at'));
    }

    /**
     * Menguji normalisasi pagination per_page minimal 1 dan maksimal 50.
     */
    public function test_items_list_pagination_normalization(): void
    {
        $user = User::create([
            'name' => 'User Pagination',
            'email' => 'userpage@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Uji per_page=0 dinormalisasi menjadi minimal 1
        $respZero = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/items?per_page=0');
        $respZero->assertStatus(200);
        $this->assertEquals(1, $respZero->json('meta.per_page'));

        // Uji per_page=-5 dinormalisasi menjadi minimal 1
        $respNeg = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->getJson('/api/items?per_page=-5');
        $respNeg->assertStatus(200);
        $this->assertEquals(1, $respNeg->json('meta.per_page'));

        // Uji per_page=100 dibatasi maksimal 50
        $respMax = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->getJson('/api/items?per_page=100');
        $respMax->assertStatus(200);
        $this->assertEquals(50, $respMax->json('meta.per_page'));
    }
}
