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

Route::post('/send-message', function (\Illuminate\Http\Request $request) {
    $message = $request->input('message');
    broadcast(new \App\Events\TestEvent($message));
    return response()->json(['status' => 'Message sent']);
});
