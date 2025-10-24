<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * HealthController
 * 
 * Handles health checks and diagnostic endpoints for the application.
 */
class HealthController extends Controller
{
    /**
     * Test Redis connection
     * 
     * Verifies that Redis is properly connected and working.
     * 
     * @return JsonResponse
     */
    public function redisTest(): JsonResponse
    {
        try {
            // Test setting and getting a value from Redis
            Cache::store('redis')->set('test_key', 'Hello from Redis!');
            $value = Cache::store('redis')->get('test_key');

            return response()->json([
                'status' => 'success',
                'message' => 'Redis connection is working!',
                'test_value' => $value
            ]);
        } catch (\Exception $e) {
            \Log::error('Redis connection error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Redis connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}