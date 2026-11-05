<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Memeriksa kondisi riil layanan basis data dan cache.
     * Mengembalikan 200 OK jika seluruh layanan sehat, atau 503 jika terdapat layanan yang bermasalah.
     */
    public function check(): JsonResponse
    {
        $timestamp = now()->toIso8601String();
        $services = [];
        $errors = [];

        // 1. Pemeriksaan Riil Konektivitas Basis Data (MySQL)
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $services['database'] = 'healthy';
        } catch (Throwable $e) {
            $services['database'] = 'unhealthy';
            $errors['database'] = $e->getMessage();
        }

        // 2. Pemeriksaan Riil Layanan Cache (Write & Read Test)
        try {
            $probeKey = '_health_probe_' . uniqid();
            Cache::put($probeKey, 'ok', 10);
            $cached = Cache::get($probeKey);
            Cache::forget($probeKey);

            if ($cached !== 'ok') {
                throw new \RuntimeException('Nilai cache yang dibaca kembali tidak valid.');
            }

            $services['cache'] = 'healthy';
        } catch (Throwable $e) {
            $services['cache'] = 'unhealthy';
            $errors['cache'] = $e->getMessage();
        }

        $isHealthy = ($services['database'] === 'healthy' && $services['cache'] === 'healthy');

        if ($isHealthy) {
            return response()->json([
                'status' => 'healthy',
                'timestamp' => $timestamp,
                'services' => $services,
            ], 200);
        }

        // Catat log terstruktur level ERROR saat pemeriksaan mendeteksi kegagalan layanan
        Log::error('Pemeriksaan kesehatan sistem gagal: layanan dependensi tidak sehat', [
            'event' => 'system.health_check_failed',
            'actor' => 'health_probe',
            'action' => 'health_check',
            'services' => $services,
            'errors' => $errors,
            'reason' => 'dependency_service_unavailable',
        ]);

        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => $timestamp,
            'services' => $services,
            'errors' => $errors,
        ], 503);
    }
}
