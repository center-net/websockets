<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/redis-test', function () {
    try {
        // Use Cache facade with Redis store
        \Illuminate\Support\Facades\Cache::store('redis')->set('test_key', 'Hello from Redis!');

        // Get the value back
        $value = \Illuminate\Support\Facades\Cache::store('redis')->get('test_key');

        // Return success message
        return response()->json([
            'status' => 'success',
            'message' => 'Redis connection is working!',
            'test_value' => $value
        ]);
    } catch (\Exception $e) {
        // Return error message if connection fails
        return response()->json([
            'status' => 'error',
            'message' => 'Redis connection failed: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/websockets-test', function () {
    return view('websockets-test');
});

Route::get('/check-pusher-settings', function () {
    try {
        $config = config('broadcasting.connections.pusher');
        
        // Manually create Pusher instance like Laravel does
        $pusher = new \Pusher\Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            $config['options'] ?? [],
            null  // no custom client
        );
        
        $settings = $pusher->getSettings();
        
        return response()->json([
            'config' => $config,
            'pusher_settings' => $settings,
        ]);
    } catch (\Exception $e) {
        \Log::error('Pusher settings error: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::post('/send-message', function (\Illuminate\Http\Request $request) {
    try {
        $message = $request->input('message');
        \Log::info('Broadcasting message: ' . $message);
        
        // Get broadcaster directly from container
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
        
        return response()->json(['status' => 'Message sent', 'message' => $message]);
    } catch (\Exception $e) {
        \Log::error('Broadcast error: ' . $e->getMessage());
        \Log::error('Broadcast error trace: ' . $e->getTraceAsString());
        return response()->json(['status' => 'Error', 'error' => $e->getMessage()], 500);
    }
});

Route::post('/test-direct-broadcast', function (\Illuminate\Http\Request $request) {
    try {
        $message = $request->input('message', 'Direct broadcast test');
        \Log::info('Testing direct HTTP broadcast to WebSocket server');
        
        // Get Pusher config
        $config = config('broadcasting.connections.pusher');
        
        // Build the Pusher HTTP request
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
        
        // Create query parameters (without signature yet)
        $params = [
            'auth_key' => $key,
            'auth_timestamp' => time(),
            'auth_version' => '1.0',
        ];
        
        // Add body_md5 to params for signature calculation
        if ($bodyJson !== '') {
            $params['body_md5'] = md5($bodyJson);
        }
        
        // Build the signature string (following Pusher protocol)
        ksort($params);
        
        // Build query string using key=value&key=value format
        $queryString = \Pusher\Pusher::array_implode('=', '&', $params);
        $stringToSign = "POST\n/apps/{$appId}/events\n" . $queryString;
        
        \Log::info('Query string for signature: ' . $queryString);
        \Log::info('String to sign: ' . $stringToSign);
        
        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $secret);
        
        $url = "{$scheme}://{$host}:{$port}/apps/{$appId}/events?" . http_build_query($params);
        
        \Log::info('Direct broadcast URL: ' . $url);
        
        // Send the HTTP request
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'json' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 5,
        ]);
        
        \Log::info('Direct broadcast response status: ' . $response->getStatusCode());
        \Log::info('Direct broadcast response body: ' . $response->getBody());
        
        return response()->json(['status' => 'Direct broadcast sent', 'message' => $message]);
    } catch (\Exception $e) {
        \Log::error('Direct broadcast error: ' . $e->getMessage());
        \Log::error('Direct broadcast error trace: ' . $e->getTraceAsString());
        return response()->json(['status' => 'Error', 'error' => $e->getMessage()], 500);
    }
});
