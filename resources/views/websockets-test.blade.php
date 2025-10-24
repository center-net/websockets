<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSockets Test</title>
    <script src="https://js.pusherapp.com/7.2/pusher.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>WebSockets Test</h1>
    <div id="messages"></div>
    <input type="text" id="messageInput" placeholder="Enter message">
    <button onclick="sendMessage()">Send Message</button>

    <script>
        // Initialize Pusher
        const pusher = new Pusher('local', {
            wsHost: '127.0.0.1',
            wsPort: 6001,
            wssPort: 6001,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
            forceTLS: false,
        });

        // Debug: Connection events
        pusher.connection.bind('connecting', function() {
            console.log('🔌 Connecting to Pusher...');
        });

        pusher.connection.bind('connected', function() {
            console.log('✅ Connected to Pusher!');
        });

        pusher.connection.bind('failed', function() {
            console.log('❌ Failed to connect to Pusher');
        });

        pusher.connection.bind('disconnected', function() {
            console.log('🔌 Disconnected from Pusher');
        });

        // Subscribe to a channel
        const channel = pusher.subscribe('test-channel');

        channel.bind('pusher:subscription_succeeded', function() {
            console.log('✅ Subscribed to test-channel');
        });

        channel.bind('pusher:subscription_error', function(status) {
            console.log('❌ Subscription error:', status);
        });

        // Listen for events
        channel.bind('test-event', function(data) {
            console.log('📨 Message received:', data);
            const messagesDiv = document.getElementById('messages');
            messagesDiv.innerHTML += '<p>' + data.message + '</p>';
        });

        function sendMessage() {
            const message = document.getElementById('messageInput').value;
            console.log('📤 Sending message:', message);
            // Send message via AJAX to trigger event
            fetch('/send-message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: message })
            }).then(r => r.json()).then(data => {
                console.log('Server response:', data);
            }).catch(e => {
                console.error('Error sending message:', e);
            });
        }

        console.log('🚀 WebSocket test page loaded');
    </script>
</body>
</html>
