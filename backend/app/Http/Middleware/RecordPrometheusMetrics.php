<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RecordPrometheusMetrics
{
    /**
     * Menghitung total permintaan HTTP yang masuk untuk metrik Prometheus.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Abaikan endpoint pemantau internal agar tidak membiaskan statistik lalu lintas riil
        if (! $request->is('metrics') && ! $request->is('api/metrics') && ! $request->is('health') && ! $request->is('api/health')) {
            $method = strtoupper($request->method());
            $status = (string) $response->getStatusCode();
            $key = "metrics_req_{$method}_{$status}";

            try {
                // Tambahkan hitungan request ke cache store
                if (Cache::has($key)) {
                    Cache::increment($key);
                } else {
                    Cache::forever($key, 1);
                }
            } catch (\Throwable $e) {
                // Abaikan kesalahan cache agar alur bisnis pengguna tetap berjalan normal
            }
        }

        return $response;
    }
}
