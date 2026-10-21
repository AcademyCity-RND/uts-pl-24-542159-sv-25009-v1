<?php

namespace Database\Seeders;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database dengan akun demo dan data realistis lengkap.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 1. Akun Pengguna Demo
        // ---------------------------------------------------------------------

        // A. Akun Administrator
        $admin = User::firstOrCreate(
            ['email' => 'admin@jejakbarang.test'],
            [
                'name' => 'Administrator Kampus',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone_number' => '081122334455',
            ]
        );

        // B. Akun Mahasiswa Utama (Pemilik Barang)
        $mahasiswa = User::firstOrCreate(
            ['email' => 'mahasiswa@jejakbarang.test'],
            [
                'name' => 'Hilarius Mahasiswa',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone_number' => '081298765432',
            ]
        );

        // C. Akun Mahasiswa Penemu (Finder Pihak Ketiga)
        $penemu = User::firstOrCreate(
            ['email' => 'penemu@jejakbarang.test'],
            [
                'name' => 'Rian Penemu',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone_number' => '081399887766',
            ]
        );

        // D. Akun Mahasiswa Lain (Bukan Pemilik)
        $userLain = User::firstOrCreate(
            ['email' => 'budi@jejakbarang.test'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone_number' => '081544332211',
            ]
        );

        // ---------------------------------------------------------------------
        // 2. Data Dummy Laporan Barang (Lost & Found)
        // ---------------------------------------------------------------------

        // Bersihkan data barang & laporan lama jika seeding ulang agar fresh
        ItemReport::query()->delete();
        LostItem::query()->delete();

        $seedImage = static function (string $filename): string {
            $source = database_path("seeders/assets/{$filename}");
            $target = "lost_items/{$filename}";

            Storage::disk('public')->put($target, file_get_contents($source));

            return $target;
        };

        // [Item 1] Status: APPROVED (Aktif di Papan Publik, usia 4 jam)
        $item1 = new LostItem([
            'item_name' => 'Laptop ASUS ROG Zephyrus G14',
            'category' => 'lost',
            'location' => 'Perpustakaan Pusat Lantai 2',
            'current_storage' => 'Meja Layanan Sirkulasi',
            'contact_info' => '081298765432',
            'reporter_contact_info' => '081122334455',
            'chronology' => 'Tertinggal di bilik belajar mandiri sebelah timur dekat jendela pada siang hari.',
            'image_path' => $seedImage('laptop.svg'),
        ]);
        $item1->user_id = $mahasiswa->id;
        $item1->status = 'approved';
        $item1->approved_at = now()->subHours(4);
        $item1->save();

        // [Item 2] Status: APPROVED (Aktif di Papan Publik, usia 12 jam)
        $item2 = new LostItem([
            'item_name' => 'Tumbler Corkcicle Putih 16oz',
            'category' => 'found',
            'location' => 'Gedung DTETI Lantai 1',
            'current_storage' => 'Pos Satpam Barat',
            'contact_info' => '081399887766',
            'reporter_contact_info' => null,
            'chronology' => 'Ditemukan tertinggal di atas meja ruang tunggu seminar.',
            'image_path' => $seedImage('tumbler.svg'),
        ]);
        $item2->user_id = $penemu->id;
        $item2->status = 'approved';
        $item2->approved_at = now()->subHours(12);
        $item2->save();

        // [Item 3] Status: PENDING (Menunggu Persetujuan Admin untuk demo tombol ACC)
        $item3 = new LostItem([
            'item_name' => 'Dompet Kulit Eiger Cokelat',
            'category' => 'lost',
            'location' => 'Kantin Gedung Vokasi',
            'current_storage' => 'Dibawa pemilik',
            'contact_info' => '081298765432',
            'reporter_contact_info' => null,
            'chronology' => 'Terjatuh saat membayar makanan di kasir kantin barat.',
            'image_path' => $seedImage('wallet.svg'),
        ]);
        $item3->user_id = $mahasiswa->id;
        $item3->status = 'pending';
        $item3->save();

        // [Item 4] Status: RESOLVE_REQUESTED (Klaim penemuan telah di-accept admin, menunggu konfirmasi pemilik)
        $item4 = new LostItem([
            'item_name' => 'Kunci Motor Honda Vario 160 + Gantungan Perak',
            'category' => 'lost',
            'location' => 'Parkiran Timur SV',
            'current_storage' => 'Pos Keamanan Gerbang Depan',
            'contact_info' => '081298765432',
            'reporter_contact_info' => '081399887766',
            'chronology' => 'Terjatuh saat mengeluarkan karcis parkir motor pagi tadi.',
            'image_path' => $seedImage('keys.svg'),
        ]);
        $item4->user_id = $mahasiswa->id;
        $item4->status = 'resolve_requested';
        $item4->approved_at = now()->subHours(8);
        $item4->save();

        // [Item 5] Status: RESOLVED (Kasus barang sudah selesai dikembalikan)
        $item5 = new LostItem([
            'item_name' => 'Kalkulator Ilmiah Casio FX-991EX',
            'category' => 'found',
            'location' => 'Lab Komputer 3',
            'current_storage' => 'Telah diserahkan ke pemilik',
            'contact_info' => '081544332211',
            'reporter_contact_info' => '081298765432',
            'chronology' => 'Tertinggal di meja PC 08 setelah ujian praktikum basis data.',
            'image_path' => $seedImage('calculator.svg'),
        ]);
        $item5->user_id = $userLain->id;
        $item5->status = 'resolved';
        $item5->approved_at = now()->subDays(2);
        $item5->resolved_at = now()->subHours(3);
        $item5->save();

        // [Item 6] Status: REJECTED (Ditolak oleh admin, siap untuk demo resubmit/edit otomatis ke pending)
        $item6 = new LostItem([
            'item_name' => 'Jaket Hoodie Hitam Polos',
            'category' => 'lost',
            'location' => 'Selasar Gedung A',
            'current_storage' => 'Dibawa pemilik',
            'contact_info' => '081298765432',
            'reporter_contact_info' => null,
            'chronology' => 'Deskripsi lama kurang spesifik tanpa merk.',
            'image_path' => $seedImage('hoodie.svg'),
        ]);
        $item6->user_id = $mahasiswa->id;
        $item6->status = 'rejected';
        $item6->save();

        // [Item 7] Status: APPROVED tapi KEDALUWARSA (> 72 jam, siap untuk demo tombol Repost)
        $item7 = new LostItem([
            'item_name' => 'Payung Lipat Biru Navy',
            'category' => 'lost',
            'location' => 'Lobi Utama Gedung Pengajaran',
            'current_storage' => 'Tempat Payung Lobi',
            'contact_info' => '081298765432',
            'reporter_contact_info' => null,
            'chronology' => 'Tertinggal di rak payung lobi saat hujan deras 4 hari yang lalu.',
            'image_path' => $seedImage('umbrella.svg'),
        ]);
        $item7->user_id = $mahasiswa->id;
        $item7->status = 'approved';
        $item7->approved_at = now()->subHours(80); // Lebih dari 72 jam
        $item7->save();

        // [Item 8] Status: APPROVED (Aktif di Papan Publik, usia 20 jam)
        $item8 = new LostItem([
            'item_name' => 'Headphone Sony WH-CH520 Hitam',
            'category' => 'lost',
            'location' => 'Ruang Baca Gedung Perpustakaan',
            'current_storage' => 'Meja Informasi Perpustakaan',
            'contact_info' => '081544332211',
            'reporter_contact_info' => null,
            'chronology' => 'Tertinggal di kursi dekat colokan listrik setelah mengikuti kelas daring.',
            'image_path' => $seedImage('headphones.svg'),
        ]);
        $item8->user_id = $userLain->id;
        $item8->status = 'approved';
        $item8->approved_at = now()->subHours(20);
        $item8->save();

        // [Item 9] Status: APPROVED (Aktif di Papan Publik, usia 30 jam)
        $item9 = new LostItem([
            'item_name' => 'Tas Ransel Fjallraven Biru Tua',
            'category' => 'found',
            'location' => 'Halaman Student Center',
            'current_storage' => 'Pos Satpam Student Center',
            'contact_info' => '081399887766',
            'reporter_contact_info' => null,
            'chronology' => 'Ditemukan di bangku taman dekat pintu masuk Student Center.',
            'image_path' => $seedImage('backpack.svg'),
        ]);
        $item9->user_id = $penemu->id;
        $item9->status = 'approved';
        $item9->approved_at = now()->subHours(30);
        $item9->save();

        // ---------------------------------------------------------------------
        // 3. Data Dummy Laporan Pihak Ketiga (Item Reports)
        // ---------------------------------------------------------------------

        // A. Laporan Accepted untuk Item 4 (menyebabkan item 4 menjadi resolve_requested)
        $report1 = new ItemReport();
        $report1->lost_item_id = $item4->id;
        $report1->reporter_user_id = $penemu->id;
        $report1->message = 'Kunci motor Honda Vario ditemukan di aspal parkiran timur dan sudah saya serahkan ke pos satpam gerbang depan.';
        $report1->status = 'accepted';
        $report1->save();

        // B. Laporan Pending untuk Item 1 (akan muncul di inbox Admin GET /api/item-reports)
        $report2 = new ItemReport();
        $report2->lost_item_id = $item1->id;
        $report2->reporter_user_id = $penemu->id;
        $report2->message = 'Saya melihat laptop Asus ROG diamankan oleh staf bagian sirkulasi perpustakaan pusat lantai 2.';
        $report2->status = 'pending';
        $report2->save();

        // C. Laporan Pending dari User Lain untuk Item 2
        $report3 = new ItemReport();
        $report3->lost_item_id = $item2->id;
        $report3->reporter_user_id = $userLain->id;
        $report3->message = 'Tumbler ini milik teman saya satu kelompok praktikum, saya bantu konfirmasi.';
        $report3->status = 'pending';
        $report3->save();
    }
}
