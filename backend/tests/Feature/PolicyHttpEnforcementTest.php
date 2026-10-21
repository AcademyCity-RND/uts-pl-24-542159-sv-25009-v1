<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyHttpEnforcementTest extends TestCase
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
            'name' => 'Admin Policy Enforcement',
            'email' => 'admin_pol_' . uniqid() . '@example.com',
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
            'email' => 'user_pol_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);
    }

    private function createItem(User $owner, string $status = 'pending'): LostItem
    {
        $item = new LostItem([
            'item_name' => 'Kunci Motor Beat',
            'category' => 'lost',
            'location' => 'Gedung DTETI',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '081234567890',
            'chronology' => 'Jatuh saat parkir',
        ]);
        $item->user_id = $owner->id;
        $item->status = $status;
        if ($status === 'approved') {
            $item->approved_at = now()->subHours(2);
        }
        $item->save();

        return $item;
    }

    /**
     * Memvalidasi LostItemPolicy::update pada HTTP PUT/POST /api/items/{id}.
     */
    public function test_lost_item_policy_update_http_enforcement(): void
    {
        $owner = $this->createUser('Pemilik');
        $otherUser = $this->createUser('Bukan Pemilik');
        $item = $this->createItem($owner, 'pending');

        $tokenOther = $otherUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenOther)
                         ->putJson('/api/items/' . $item->id, [
                             'item_name' => 'Nama Barang Diganti Pembajak',
                         ]);

        $response->assertStatus(403);
    }

    /**
     * Memvalidasi LostItemPolicy::delete pada HTTP DELETE /api/items/{id}.
     */
    public function test_lost_item_policy_delete_http_enforcement(): void
    {
        $owner = $this->createUser('Pemilik');
        $otherUser = $this->createUser('Bukan Pemilik');
        $item = $this->createItem($owner, 'pending');

        $tokenOther = $otherUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenOther)
                         ->deleteJson('/api/items/' . $item->id);

        $response->assertStatus(403);
    }

    /**
     * Memvalidasi LostItemPolicy::repost pada HTTP POST /api/items/{id}/repost.
     */
    public function test_lost_item_policy_repost_http_enforcement(): void
    {
        $owner = $this->createUser('Pemilik');
        $otherUser = $this->createUser('Bukan Pemilik');
        $item = $this->createItem($owner, 'approved');

        $tokenOther = $otherUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenOther)
                         ->postJson('/api/items/' . $item->id . '/repost');

        $response->assertStatus(403);
    }

    /**
     * Memvalidasi ItemReportPolicy::viewAny pada HTTP GET /api/item-reports.
     */
    public function test_item_report_policy_view_any_http_enforcement(): void
    {
        $user = $this->createUser('User Biasa');
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/item-reports');

        $response->assertStatus(403);
    }

    /**
     * Memvalidasi ItemReportPolicy::create pada HTTP POST /api/items/{id}/report (Anti Self-Reporting).
     */
    public function test_item_report_policy_create_http_enforcement(): void
    {
        $owner = $this->createUser('Pemilik');
        $item = $this->createItem($owner, 'approved');

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/items/' . $item->id . '/report', [
                             'message' => 'Laporan dari pemilik sendiri dilarang policy.',
                         ]);

        $response->assertStatus(403);
    }

    /**
     * Memvalidasi ItemReportPolicy::updateStatus pada HTTP PATCH /api/item-reports/{id}/status.
     */
    public function test_item_report_policy_update_status_http_enforcement(): void
    {
        $owner = $this->createUser('Pemilik');
        $finder = $this->createUser('Penemu');
        $userBiasa = $this->createUser('User Lain');

        $item = $this->createItem($owner, 'approved');

        $report = new ItemReport();
        $report->lost_item_id = $item->id;
        $report->reporter_user_id = $finder->id;
        $report->message = 'Ditemukan di lobi DTETI.';
        $report->status = 'pending';
        $report->save();

        $tokenUser = $userBiasa->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenUser)
                         ->patchJson('/api/item-reports/' . $report->id . '/status', [
                             'status' => 'accepted',
                         ]);

        $response->assertStatus(403);
    }
}
