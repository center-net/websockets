# WebSocket Broadcasting System - Architecture

## Overview

This Laravel application implements a real-time messaging system using WebSockets. It broadcasts messages to connected clients (primarily Flutter mobile application) using the Pusher protocol.

## Directory Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── BroadcastController.php    # WebSocket broadcasting logic
│       └── HealthController.php        # Health checks & diagnostics
└── Events/
    └── TestEvent.php                   # Broadcast event definition

routes/
├── web.php                             # Web routes (cleaned up)
├── channels.php                        # Channel authorization
└── api.php                             # API routes (if any)

config/
├── broadcasting.php                    # Pusher configuration
└── cache.php                           # Redis cache configuration
```

## Components

### 1. BroadcastController
Handles all WebSocket broadcasting operations:

- **sendMessage()** - Send messages via Laravel's broadcast system
  - Route: `POST /send-message`
  - Logs all operations for debugging
  
- **testDirectBroadcast()** - Direct HTTP API to WebSocket server
  - Route: `POST /test-direct-broadcast`
  - Uses HMAC-SHA256 authentication
  - Demonstrates low-level Pusher protocol
  
- **checkPusherSettings()** - Verify Pusher configuration
  - Route: `GET /check-pusher-settings`
  - Returns current configuration and settings

### 2. HealthController
Handles diagnostic endpoints:

- **redisTest()** - Test Redis connection
  - Route: `GET /redis-test`
  - Verifies Redis connectivity

### 3. TestEvent
Broadcast event class:

- Implements `ShouldBroadcast` interface
- Broadcasts on `test-channel`
- Event name: `test-event`
- Contains message payload

### 4. Channel Authorization
File: `routes/channels.php`

Defines who can access which channels. Currently allows all users to subscribe to public channels.

## Routes

### Public Pages
```
GET  /                          Welcome page
GET  /websockets-test           WebSocket test interface
```

### Health & Diagnostics
```
GET  /redis-test                Test Redis connection
```

### WebSocket Broadcasting
```
POST /send-message              Send message via broadcast system
POST /test-direct-broadcast     Send message via direct HTTP API
GET  /check-pusher-settings     Check Pusher configuration
```

## Configuration

### Pusher Settings
File: `config/broadcasting.php`

```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'host' => 'localhost',
        'port' => 6001,
        'scheme' => 'http',
    ],
]
```

### Redis Settings
File: `config/cache.php`

Used for caching and session management.

## Usage Examples

### Send Message via Laravel Broadcast
```bash
curl -X POST http://localhost:8000/send-message \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello from Laravel!"}'
```

### Send Message via Direct API
```bash
curl -X POST http://localhost:8000/test-direct-broadcast \
  -H "Content-Type: application/json" \
  -d '{"message": "Direct broadcast test"}'
```

### Check Pusher Settings
```bash
curl http://localhost:8000/check-pusher-settings
```

### Test Redis Connection
```bash
curl http://localhost:8000/redis-test
```

## Client Integration (Flutter)

The Flutter mobile application subscribes to the `test-channel` and listens for `test-event` events.

Key points:
- Uses Pusher Dart client
- Connects to local WebSocket server (configured in app)
- Handles real-time message delivery
- Implements reconnection logic

## Logging

All broadcasting operations log to:
- File: `storage/logs/laravel.log`
- Includes: Request/response details, timestamps, error traces

Monitor logs during development:
```bash
tail -f storage/logs/laravel.log
```

## Security Considerations

1. **Channel Authorization**: Implement proper authorization in `routes/channels.php`
2. **Rate Limiting**: Consider adding rate limiting to broadcasting endpoints
3. **Authentication**: Add middleware for protected routes if needed
4. **HTTPS**: Use HTTPS in production with `scheme: 'https'` in config

## Development

### Running Tests
```bash
php artisan test
```

### Running WebSocket Server
```bash
php artisan websockets:serve
```

### Code Quality
```bash
./vendor/bin/pint
```

## Troubleshooting

### WebSocket Connection Issues
1. Check Pusher server is running: `php artisan websockets:serve`
2. Verify Redis is running
3. Check logs: `storage/logs/laravel.log`
4. Test Redis connection: `GET /redis-test`
5. Verify Pusher settings: `GET /check-pusher-settings`

### Message Not Delivered
1. Check channel authorization in `routes/channels.php`
2. Verify client is subscribed to correct channel name
3. Check event name matches on client side
4. Review broadcasting logs for errors

### Redis Connection Issues
1. Ensure Redis server is running on localhost:6379
2. Check Redis configuration in `config/cache.php`
3. Test with: `GET /redis-test`

## Code Quality Improvements

- ✅ Controllers separated from routes
- ✅ Clear documentation and comments
- ✅ Proper error handling with try-catch blocks
- ✅ Comprehensive logging for debugging
- ✅ Type hints for parameters and return values
- ✅ Grouped routes by functionality

## Next Steps

1. Implement proper authentication for sensitive endpoints
2. Add rate limiting for broadcasting routes
3. Create API documentation
4. Add unit and integration tests
5. Implement message queue for high-volume scenarios