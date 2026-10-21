<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConcurrentResolutionLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_mysql_transaction_cannot_lock_item_while_first_transaction_holds_it(): void
    {
        $setupName = 'mysql_concurrent_setup';
        $firstName = 'mysql_concurrent_first';
        $secondName = 'mysql_concurrent_second';
        $connectionConfig = config('database.connections.mysql');
        config([
            "database.connections.{$setupName}" => $connectionConfig,
            "database.connections.{$firstName}" => $connectionConfig,
            "database.connections.{$secondName}" => $connectionConfig,
        ]);

        DB::purge($setupName);
        DB::purge($firstName);
        DB::purge($secondName);

        $setupConnection = DB::connection($setupName);
        $ownerId = $setupConnection->table('users')->insertGetId([
            'name' => 'Owner Concurrent',
            'email' => 'owner_concurrent@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $finderId = $setupConnection->table('users')->insertGetId([
            'name' => 'Finder Concurrent',
            'email' => 'finder_concurrent@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $itemId = $setupConnection->table('lost_items')->insertGetId([
            'user_id' => $ownerId,
            'item_name' => 'Item Concurrent Lock',
            'category' => 'lost',
            'location' => 'Gedung Utama',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '081234567890',
            'chronology' => 'Tertinggal di ruang kelas',
            'status' => 'approved',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reportId = $setupConnection->table('item_reports')->insertGetId([
            'lost_item_id' => $itemId,
            'reporter_user_id' => $finderId,
            'message' => 'Item ditemukan di pos satpam gedung utama.',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstConnection = DB::connection($firstName);
        $secondConnection = DB::connection($secondName);
        $firstConnection->beginTransaction();
        $secondConnection->beginTransaction();

        try {
            $firstConnection->table('lost_items')
                ->where('id', $itemId)
                ->lockForUpdate()
                ->first();

            $secondConnection->statement('SET SESSION innodb_lock_wait_timeout = 1');

            $lockWaitFailed = false;
            try {
                $secondConnection->table('lost_items')
                    ->where('id', $itemId)
                    ->lockForUpdate()
                    ->first();
            } catch (QueryException $exception) {
                $lockWaitFailed = str_contains(
                    strtolower($exception->getMessage()),
                    'lock wait timeout'
                );
            }

            $this->assertTrue(
                $lockWaitFailed,
                'Transaksi kedua harus gagal memperoleh row lock saat transaksi pertama masih menahannya.'
            );
        } finally {
            $secondConnection->rollBack();
            $firstConnection->rollBack();
            $setupConnection->table('item_reports')->where('id', $reportId)->delete();
            $setupConnection->table('lost_items')->where('id', $itemId)->delete();
            $setupConnection->table('users')->whereIn('id', [$ownerId, $finderId])->delete();
            DB::disconnect($setupName);
            DB::disconnect($firstName);
            DB::disconnect($secondName);
        }
    }
}
