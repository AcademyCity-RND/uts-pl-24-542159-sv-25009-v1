<?php

namespace Tests\Unit;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use Tests\TestCase;

class ModelSecurityTest extends TestCase
{
    /**
     * Memverifikasi kolom role pada User dilindungi dari mass assignment.
     */
    public function test_user_role_is_guarded_from_mass_assignment(): void
    {
        $user = new User([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'role' => 'admin', // Coba eksploitasi role via mass assignment
            'phone_number' => '08123456789',
        ]);

        $this->assertNull($user->role, 'Kolom role berhasil terlindungi dan tidak boleh terisi via mass assignment.');
    }

    /**
     * Memverifikasi kolom status, approved_at, dan resolved_at pada LostItem dilindungi dari mass assignment.
     */
    public function test_lost_item_sensitive_fields_are_guarded_from_mass_assignment(): void
    {
        $item = new LostItem([
            'item_name' => 'Kunci Motor',
            'category' => 'lost',
            'location' => 'Gedung D',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '08111111111',
            'chronology' => 'Ketinggalan di meja',
            'status' => 'approved', // Coba manipulasi status
            'approved_at' => now(), // Coba bypass timestamp approval
            'resolved_at' => now(), // Coba bypass timestamp resolution
        ]);

        $this->assertNull($item->status, 'Kolom status wajib dilindungi dari mass assignment.');
        $this->assertNull($item->approved_at, 'Kolom approved_at wajib dilindungi dari mass assignment.');
        $this->assertNull($item->resolved_at, 'Kolom resolved_at wajib dilindungi dari mass assignment.');
    }

    /**
     * Memverifikasi kolom status pada ItemReport dilindungi dari mass assignment.
     */
    public function test_item_report_status_is_guarded_from_mass_assignment(): void
    {
        $report = new ItemReport([
            'lost_item_id' => 1,
            'reporter_user_id' => 2,
            'message' => 'Saya menemukan barang ini di perpus',
            'status' => 'accepted', // Coba manipulasi status
        ]);

        $this->assertNull($report->status, 'Kolom status laporan wajib dilindungi dari mass assignment.');
    }

    /**
     * Memverifikasi computed fields TTL dan query scope activePublic.
     */
    public function test_lost_item_accessors_and_active_public_scope(): void
    {
        $item = new LostItem();
        $item->approved_at = now()->subHours(10);
        $item->status = 'approved';

        $this->assertFalse($item->is_expired, 'Barang 10 jam belum boleh kedaluwarsa.');
        $this->assertNotNull($item->expires_at, 'expires_at harus terformat ISO 8601.');
        $this->assertGreaterThan(0, $item->remaining_seconds, 'remaining_seconds harus bernilai positif.');

        // Test scope SQL binding operator
        $sql = LostItem::query()->activePublic()->toSql();
        $this->assertStringContainsString('`status` = ?', $sql);
        $this->assertStringContainsString('`approved_at` > ?', $sql, 'Query scope activePublic wajib menggunakan operator strict >.');
    }
}
