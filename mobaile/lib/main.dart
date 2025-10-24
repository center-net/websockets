import 'package:flutter/material.dart';
import 'package:web_socket_channel/web_socket_channel.dart';
import 'package:web_socket_channel/status.dart' as status;
import 'dart:convert';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WebSocket Notifications',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepPurple),
      ),
      home: const NotificationPage(),
    );
  }
}

class NotificationPage extends StatefulWidget {
  const NotificationPage({super.key});

  @override
  State<NotificationPage> createState() => _NotificationPageState();
}

class _NotificationPageState extends State<NotificationPage> {
  List<String> messages = [];
  WebSocketChannel? channel;
  String connectionStatus = 'Connecting...';

  @override
  void initState() {
    super.initState();
    _connectWebSocket();
  }

  void _connectWebSocket() {
    try {
      // Connect to the local WebSocket server
      // 10.0.2.2 is the special IP for localhost from Android emulator
      channel = WebSocketChannel.connect(
        Uri.parse('ws://10.0.2.2:6001/app/local?protocol=7'),
      );

      // Listen to WebSocket messages
      channel!.stream.listen(
        (message) {
          _handleWebSocketMessage(message);
        },
        onError: (error) {
          print('WebSocket error: $error');
          setState(() {
            connectionStatus = 'Error: $error';
          });
        },
        onDone: () {
          print('WebSocket connection closed');
          setState(() {
            connectionStatus = 'Disconnected - Reconnecting...';
          });
          // Attempt to reconnect after 3 seconds
          Future.delayed(const Duration(seconds: 3), _connectWebSocket);
        },
      );

      // Send subscription message after connecting
      Future.delayed(const Duration(milliseconds: 500), () {
        _subscribeToChannel('test-channel');
      });

      setState(() {
        connectionStatus = 'Connected';
      });
    } catch (e) {
      print('Error connecting to WebSocket: $e');
      setState(() {
        connectionStatus = 'Connection failed: $e';
      });
      // Retry after 3 seconds
      Future.delayed(const Duration(seconds: 3), _connectWebSocket);
    }
  }

  void _subscribeToChannel(String channelName) {
    if (channel == null) return;

    final subscribeMessage = {
      'event': 'pusher:subscribe',
      'data': {'channel': channelName},
    };

    print('Subscribing to $channelName');
    channel!.sink.add(jsonEncode(subscribeMessage));
  }

  void _handleWebSocketMessage(dynamic message) {
    print('WebSocket message: $message');

    try {
      final data = jsonDecode(message);

      // Handle server ping (heartbeat) - respond with pong to keep connection alive
      if (data['event'] == 'pusher:ping') {
        print('Received ping from server, sending pong...');
        final pongMessage = {'event': 'pusher:pong'};
        channel!.sink.add(jsonEncode(pongMessage));
        return;
      }

      // Handle subscription confirmation
      if (data['event'] == 'pusher_internal:subscription_succeeded') {
        print('Subscription succeeded for channel: ${data['channel']}');
        setState(() {
          connectionStatus = 'Subscribed to test-channel ✓';
        });
        return;
      }

      // Handle custom events
      if (data['event'] == 'test-event') {
        print('Received test-event: ${data['data']}');
        try {
          final eventData = jsonDecode(data['data']);
          print('Event data: $eventData');
          setState(() {
            messages.add(eventData['message'] ?? 'Unknown message');
          });
        } catch (e) {
          // If data is not JSON, treat it as string
          setState(() {
            messages.add(data['data'].toString());
          });
        }
        return;
      }
    } catch (e) {
      print('Error parsing WebSocket message: $e');
    }
  }

  @override
  void dispose() {
    channel?.sink.close(status.goingAway);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isConnected = connectionStatus.contains('Subscribed');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Real-time Notifications'),
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isConnected ? Colors.green[100] : Colors.orange[100],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: isConnected ? Colors.green : Colors.orange,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    isConnected ? Icons.check_circle : Icons.info,
                    color: isConnected ? Colors.green : Colors.orange,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      connectionStatus,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: isConnected
                            ? Colors.green[900]
                            : Colors.orange[900],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Received Messages:',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: messages.isEmpty
                  ? const Center(child: Text('No messages received yet'))
                  : ListView.builder(
                      itemCount: messages.length,
                      itemBuilder: (context, index) {
                        return Card(
                          margin: const EdgeInsets.symmetric(vertical: 4),
                          child: ListTile(
                            leading: const Icon(Icons.notifications),
                            title: Text(messages[index]),
                            subtitle: Text('Message ${index + 1}'),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
