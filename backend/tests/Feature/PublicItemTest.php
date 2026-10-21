<?php

namespace Tests\Feature;

use App\Models\LostItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skenario Uji 1 (Wajib UTS):
     * Guest (tamu tanpa login) dilarang melihat informasi kontak pada katalog publik,
     * serta hanya barang approved < 72 jam yang ditampilkan.
     */
    public function test_guest_cannot_see_contact_info_on_public_list(): void
    {
        $user = User::create([
            'name' => 'Pemilik Barang',
            'email' => 'pemilik@jejakbarang.test',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'phone_number' => '081299998888',
        ]);

        // 1. Barang aktif: approved 10 jam lalu (harus muncul)
        $activeItem = new LostItem([
            'item_name' => 'Kunci Motor Vario',
            'category' => 'lost',
            'location' => 'Parkiran Gedung D',
            'current_storage' => 'Pos Satpam Barat',
            'contact_info' => '081111111111', // Kontak rahasia
            'reporter_contact_info' => '082222222222', // Kontak rahasia pelapor
            'chronology' => 'Tertinggal di dekat gerbang parkir',
        ]);
        $activeItem->user_id = $user->id;
        $activeItem->status = 'approved';
        $activeItem->approved_at = now()->subHours(10);
        $activeItem->save();

        // 2. Barang pending: belum disetujui admin (tidak boleh muncul)
        $pendingItem = new LostItem([
            'item_name' => 'Tumbler Biru',
            'category' => 'found',
            'location' => 'Ruang Teori 2',
            'current_storage' => 'Meja Dosen',
            'contact_info' => '083333333333',
            'chronology' => 'Ditemukan di bawah kursi',
        ]);
        $pendingItem->user_id = $user->id;
        $pendingItem->status = 'pending';
        $pendingItem->save();

        // 3. Barang kedaluwarsa: approved 75 jam lalu (> 72 jam) (tidak boleh muncul)
        $expiredItem = new LostItem([
            'item_name' => 'Jaket Hitam',
            'category' => 'lost',
            'location' => 'Kantin Pusat',
            'current_storage' => 'Loker Penitipan',
            'contact_info' => '084444444444',
            'chronology' => 'Ketinggalan saat makan siang',
        ]);
        $expiredItem->user_id = $user->id;
        $expiredItem->status = 'approved';
        $expiredItem->approved_at = now()->subHours(75);
        $expiredItem->save();

        // 4. Barang ditolak admin (tidak boleh muncul)
        $rejectedItem = new LostItem([
            'item_name' => 'Uang Tunai',
            'category' => 'lost',
            'location' => 'ATM Center',
            'current_storage' => 'Dompet',
            'contact_info' => '085555555555',
            'chronology' => 'Jatuh saat tarik tunai',
        ]);
        $rejectedItem->user_id = $user->id;
        $rejectedItem->status = 'rejected';
        $rejectedItem->save();

        // Akses endpoint publik tanpa autentikasi / tanpa Bearer Token
        $response = $this->getJson('/api/public/items');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'user_id',
                             'item_name',
                             'category',
                             'location',
                             'current_storage',
                             'chronology',
                             'status',
                             'expires_at',
                             'is_expired',
                             'remaining_seconds',
                         ],
                     ],
                     'links',
                     'meta',
                 ]);

        // Verifikasi hanya 1 barang aktif yang ditampilkan
        $data = $response->json('data');
        $this->assertCount(1, $data, 'Hanya barang berstatus approved dengan usia < 72 jam yang boleh muncul.');
        $this->assertEquals('Kunci Motor Vario', $data[0]['item_name']);

        // Verifikasi KUNCI KONTAK PRIVASI TIDAK ADA SAMA SEKALI (OMITTED)
        $this->assertArrayNotHasKey('contact_info', $data[0], 'Kunci contact_info wajib dihilangkan (omitted) dari respons publik.');
        $this->assertArrayNotHasKey('reporter_contact_info', $data[0], 'Kunci reporter_contact_info wajib dihilangkan (omitted) dari respons publik.');
    }

    /**
     * Memverifikasi parameter per_page dibatasi maksimal 50 item.
     */
    public function test_public_list_limits_per_page_to_fifty(): void
    {
        $response = $this->getJson('/api/public/items?per_page=1000');

        $response->assertOk()
                 ->assertJsonPath('meta.per_page', 50);
    }
}
