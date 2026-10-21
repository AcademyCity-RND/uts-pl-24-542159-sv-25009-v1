<?php

namespace Tests\Unit;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use App\Policies\ItemReportPolicy;
use App\Policies\LostItemPolicy;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    private function createAdmin(): User
    {
        $admin = new User([
            'name' => 'Admin Policy',
            'email' => 'admin_policy@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->role = 'admin';

        return $admin;
    }

    private function createUser(int $id, string $name = 'User Test'): User
    {
        $user = new User([
            'name' => $name,
            'email' => "user_{$id}@example.com",
            'password' => bcrypt('password123'),
        ]);
        $user->id = $id;
        $user->role = 'user';

        return $user;
    }

    public function test_lost_item_policy_update_and_delete_permissions(): void
    {
        $policy = new LostItemPolicy();
        $owner = $this->createUser(1, 'Pemilik Barang');
        $otherUser = $this->createUser(2, 'Pengguna Lain');
        $admin = $this->createAdmin();

        $item = new LostItem();
        $item->user_id = $owner->id;

        // Owner dapat update dan delete
        $this->assertTrue($policy->update($owner, $item)->allowed());
        $this->assertTrue($policy->delete($owner, $item)->allowed());

        // Admin dapat update dan delete
        $this->assertTrue($policy->update($admin, $item)->allowed());
        $this->assertTrue($policy->delete($admin, $item)->allowed());

        // Pengguna lain ditolak
        $this->assertTrue($policy->update($otherUser, $item)->denied());
        $this->assertTrue($policy->delete($otherUser, $item)->denied());
    }

    public function test_lost_item_policy_repost_permission(): void
    {
        $policy = new LostItemPolicy();
        $owner = $this->createUser(1, 'Pemilik Barang');
        $otherUser = $this->createUser(2, 'Pengguna Lain');
        $admin = $this->createAdmin();

        $item = new LostItem();
        $item->user_id = $owner->id;

        // Hanya Owner yang dapat melakukan repost
        $this->assertTrue($policy->repost($owner, $item)->allowed());
        $this->assertTrue($policy->repost($otherUser, $item)->denied());
        $this->assertTrue($policy->repost($admin, $item)->denied());
    }

    public function test_item_report_policy_permissions(): void
    {
        $policy = new ItemReportPolicy();
        $owner = $this->createUser(1, 'Pemilik Barang');
        $finder = $this->createUser(2, 'Penemu Barang');
        $admin = $this->createAdmin();

        $item = new LostItem();
        $item->user_id = $owner->id;

        $report = new ItemReport();
        $report->lost_item_id = 1;
        $report->reporter_user_id = $finder->id;

        // viewAny: Hanya admin yang boleh
        $this->assertTrue($policy->viewAny($admin)->allowed());
        $this->assertTrue($policy->viewAny($finder)->denied());

        // create: Penemu boleh, tetapi Owner dilarang melaporkan barangnya sendiri
        $this->assertTrue($policy->create($finder, $item)->allowed());
        $this->assertTrue($policy->create($owner, $item)->denied());

        // updateStatus: Hanya admin yang boleh memproses laporan
        $this->assertTrue($policy->updateStatus($admin, $report)->allowed());
        $this->assertTrue($policy->updateStatus($finder, $report)->denied());
    }
}
