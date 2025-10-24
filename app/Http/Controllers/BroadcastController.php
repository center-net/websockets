<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;
use Pusher\Pusher;

/**
 * BroadcastController
 * 
 * Handles all WebSocket broadcasting operations including:
 * - Sending messages via Laravel's broadcast facade
 * - Direct HTTP broadcasts to the WebSocket server
 * - Pusher configuration verification
 */
class BroadcastController extends Controller
{
    /**
     * Send a message via Laravel's broadcast system
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $message = $request->input('message');
            \Log::info('Broadcasting message: ' . $message);
            
            // Get broadcaster from container
            $broadcaster = app('Illuminate\Broadcasting\BroadcastManager')->connection('pusher');
            
            \Log::info('Broadcaster type: ' . class_basename($broadcaster));
            \Log::info('About to call broadcast method');
            
            // Call broadcast directly
            $broadcaster->broadcast(
                ['test-channel'],
                'test-event',
                ['message' => $message]
            );
            
            \Log::info('Direct broadcast call completed successfully');
            
            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Broadcast error: ' . $e->getMessage());
            \Log::error('Broadcast error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send message via direct HTTP API to WebSocket server
     * 
     * This method demonstrates direct communication with the WebSocket server
     * using Pusher's HTTP API protocol with HMAC-SHA256 authentication.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testDirectBroadcast(Request $request): JsonResponse
    {
        try {
            $message = $request->input('message', 'Direct broadcast test');
            \Log::info('Testing direct HTTP broadcast to WebSocket server');
            
            $config = config('broadcasting.connections.pusher');
            
            // Extract Pusher configuration
            $appId = $config['app_id'];
            $key = $config['key'];
            $secret = $config['secret'];
            $host = $config['options']['host'];
            $port = $config['options']['port'];
            $scheme = $config['options']['scheme'];
            
            // Build request body
            $body = [
                'name' => 'test-event',
                'channels' => ['test-channel'],
                'data' => json_encode(['message' => $message]),
            ];
            
            $bodyJson = json_encode($body);
            
            // Create authentication parameters
            $params = [
                'auth_key' => $key,
                'auth_timestamp' => time(),
                'auth_version' => '1.0',
            ];
            
            // Add body MD5 for signature calculation
            if ($bodyJson !== '') {
                $params['body_md5'] = md5($bodyJson);
            }
            
            // Build signature string (following Pusher protocol)
            ksort($params);
            $queryString = Pusher::array_implode('=', '&', $params);
            $stringToSign = "POST\n/apps/{$appId}/events\n" . $queryString;
            
            \Log::info('Query string for signature: ' . $queryString);
            \Log::info('String to sign: ' . $stringToSign);
            
            // Generate HMAC-SHA256 signature
            $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $secret);
            
            $url = "{$scheme}://{$host}:{$port}/apps/{$appId}/events?" . http_build_query($params);
            
            \Log::info('Direct broadcast URL: ' . $url);
            
            // Send HTTP request to WebSocket server
            $client = new Client();
            $response = $client->post($url, [
                'json' => $body,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 5,
            ]);
            
            \Log::info('Direct broadcast response status: ' . $response->getStatusCode());
            \Log::info('Direct broadcast response body: ' . $response->getBody());
            
            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Direct broadcast error: ' . $e->getMessage());
            \Log::error('Direct broadcast error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Pusher configuration and connectivity
     * 
     * Returns Pusher configuration details and settings for debugging purposes.
     * 
     * @return JsonResponse
     */
    public function checkPusherSettings(): JsonResponse
    {
        try {
            $config = config('broadcasting.connections.pusher');
            
            // Create Pusher instance
            $pusher = new Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options'] ?? [],
                null
            );
            
            $settings = $pusher->getSettings();
            
            return response()->json([
                'config' => $config,
                'pusher_settings' => $settings,
            ]);
        } catch (\Exception $e) {
            \Log::error('Pusher settings error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}