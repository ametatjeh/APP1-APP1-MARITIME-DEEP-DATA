<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    /**
     * Check comprehensive health status of backend infrastructure, PostGIS,
     * private evidence storage, Redis/Cache, and AI microservice.
     */
    public function check(): JsonResponse
    {
        $startTime = microtime(true);
        $checks = [];
        $isHealthy = true;

        // 1. Database & PostGIS
        try {
            $dbStart = microtime(true);
            DB::select('SELECT 1');
            $postgisVersion = null;
            try {
                $postgisRow = DB::select('SELECT PostGIS_Lib_Version() AS ver');
                $postgisVersion = $postgisRow[0]->ver ?? 'Unknown';
            } catch (Throwable $pe) {
                $postgisVersion = 'Not Installed: ' . $pe->getMessage();
            }

            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);
            $checks['database'] = [
                'status' => 'healthy',
                'driver' => config('database.default'),
                'postgis_version' => $postgisVersion,
                'latency_ms' => $dbLatencyMs,
            ];
        } catch (Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'error' => 'Database connection failed',
            ];
        }

        // 2. Cache / Redis
        try {
            $cacheStart = microtime(true);
            $testKey = 'health_ping_' . time();
            Cache::put($testKey, 'ok', 5);
            $val = Cache::get($testKey);
            Cache::forget($testKey);

            $cacheLatencyMs = round((microtime(true) - $cacheStart) * 1000, 2);
            $checks['cache'] = [
                'status' => ($val === 'ok') ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
                'latency_ms' => $cacheLatencyMs,
            ];
        } catch (Throwable $e) {
            $checks['cache'] = [
                'status' => 'degraded',
                'driver' => config('cache.default'),
                'error' => 'Cache access error: ' . $e->getMessage(),
            ];
        }

        // 3. Private Evidence Storage Vault
        try {
            $disk = config('filesystems.default') === 'private' ? 'private' : 'local';
            $storageAvailable = Storage::disk($disk)->exists('');
            $checks['evidence_storage'] = [
                'status' => $storageAvailable ? 'healthy' : 'warning',
                'disk' => $disk,
                'type' => 'Private Object Storage (No Public Access)',
            ];
        } catch (Throwable $e) {
            $checks['evidence_storage'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // 4. Python AI Microservice
        $aiUrl = config('services.ai.url', 'http://127.0.0.1:8100');
        try {
            $aiResponse = Http::connectTimeout(0.3)->timeout(1.0)->get("{$aiUrl}/health");
            if ($aiResponse->successful()) {
                $aiData = $aiResponse->json();
                $checks['ai_service'] = [
                    'status' => 'healthy',
                    'url' => $aiUrl,
                    'details' => $aiData,
                ];
            } else {
                $checks['ai_service'] = [
                    'status' => 'degraded',
                    'url' => $aiUrl,
                    'http_code' => $aiResponse->status(),
                    'fallback_active' => true,
                ];
            }
        } catch (Throwable $e) {
            $checks['ai_service'] = [
                'status' => 'offline',
                'url' => $aiUrl,
                'fallback_active' => true,
                'notice' => 'System operates with PHP native multi-agent fallback engine.',
            ];
        }

        $totalDurationMs = round((microtime(true) - $startTime) * 1000, 2);

        $httpStatus = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'operational' : 'service_degraded',
            'application' => 'Maritime Deep Data (App 1 Standalone)',
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => $totalDurationMs,
            'checks' => $checks,
        ], $httpStatus);
    }
}
