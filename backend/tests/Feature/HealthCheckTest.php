<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Gunakan sqlite :memory: untuk isolasi pengujian independen dari service database host
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
    }

    /**
     * Memverifikasi endpoint /health mengembalikan HTTP 200 saat database dan cache sehat.
     */
    public function test_health_check_returns_200_when_services_are_healthy(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'services' => [
                    'database' => 'healthy',
                    'cache' => 'healthy',
                ],
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'cache',
                ],
            ]);
    }

    /**
     * Memverifikasi endpoint /api/health juga mengembalikan HTTP 200 saat sehat.
     */
    public function test_api_health_check_returns_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ]);
    }

    /**
     * Memverifikasi endpoint /health mengembalikan HTTP 503 saat basis data bermasalah.
     */
    public function test_health_check_returns_503_when_database_is_unhealthy(): void
    {
        Log::spy();

        // Simulasikan kegagalan koneksi basis data dengan host tidak valid
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => 59999,
            'database.connections.mysql.options.' . \PDO::ATTR_TIMEOUT => 1,
        ]);
        DB::purge('mysql');

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'services' => [
                    'database' => 'unhealthy',
                ],
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'cache',
                ],
                'errors' => [
                    'database',
                ],
            ]);

        Log::shouldHaveReceived('error')->once();
    }

    /**
     * Memverifikasi endpoint /health mengembalikan HTTP 503 saat cache bermasalah.
     */
    public function test_health_check_returns_503_when_cache_is_unhealthy(): void
    {
        // Simulasikan kegagalan cache
        Cache::shouldReceive('store')
            ->andReturnSelf();
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \RuntimeException('Gagal menulis ke cache store'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'services' => [
                    'cache' => 'unhealthy',
                ],
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services',
                'errors' => [
                    'cache',
                ],
            ]);
    }
}
