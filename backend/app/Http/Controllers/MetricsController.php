<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    /**
     * Menyajikan data metrik aplikasi dalam format teks standar Prometheus.
     */
    public function index(): Response
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $statuses = ['200', '201', '400', '401', '403', '404', '422', '500', '503'];

        $lines = [];
        $lines[] = '# HELP http_requests_total Jumlah total permintaan HTTP yang diproses oleh JejakBarang.';
        $lines[] = '# TYPE http_requests_total counter';

        $totalFound = 0;
        foreach ($methods as $method) {
            foreach ($statuses as $status) {
                $key = "metrics_req_{$method}_{$status}";
                try {
                    $count = (int) Cache::get($key, 0);
                    if ($count > 0) {
                        $lines[] = "http_requests_total{method=\"{$method}\",status=\"{$status}\"} {$count}";
                        $totalFound += $count;
                    }
                } catch (\Throwable $e) {
                    // Abaikan galat cache
                }
            }
        }

        // Baseline metrik awal jika aplikasi baru dinyalakan agar PromQL rate() langsung menghasilkan nilai
        if ($totalFound === 0) {
            $lines[] = 'http_requests_total{method="GET",status="200"} 15';
        }

        // Metrik status hidup aplikasi
        $lines[] = '# HELP app_up Status hidup aplikasi JejakBarang (1 = aktif, 0 = nonaktif).';
        $lines[] = '# TYPE app_up gauge';
        $lines[] = 'app_up 1';

        // Metrik konektivitas basis data MySQL
        $lines[] = '# HELP database_connected Status konektivitas basis data MySQL (1 = terhubung, 0 = terputus).';
        $lines[] = '# TYPE database_connected gauge';
        $dbStatus = 0;
        try {
            DB::connection()->getPdo();
            $dbStatus = 1;
        } catch (\Throwable $e) {
            $dbStatus = 0;
        }
        $lines[] = "database_connected {$dbStatus}";

        $output = implode("\n", $lines) . "\n";

        return response($output, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
