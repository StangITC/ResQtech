import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:percent_indicator/circular_percent_indicator.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  bool _isConnected = false;
  bool _isEmergency = false;
  String _lastEventTime = '-';
  String _lastHeartbeat = '-';

  @override
  void initState() {
    super.initState();
    _connectStream();
    _initialCheck();
  }

  Future<void> _initialCheck() async {
    final api = Provider.of<ApiService>(context, listen: false);
    final data = await api.checkStatus();
    if (data.isNotEmpty) {
      _updateState(data);
    }
  }

  void _connectStream() {
    final api = Provider.of<ApiService>(context, listen: false);
    api.subscribeToStream((data) {
      if (mounted) {
        if (data['type'] == 'heartbeat') {
           setState(() {
             _isConnected = data['is_connected'] ?? false;
             _lastHeartbeat = data['last_heartbeat'] ?? '-';
           });
        } else if (data['type'] == 'emergency') {
           setState(() {
             _isEmergency = data['is_recent'] ?? false;
             _lastEventTime = data['last_event'] ?? '-';
           });
        }
      }
    });
  }
  
  void _updateState(Map<String, dynamic> data) {
    if (!mounted) return;
    setState(() {
      _isConnected = data['is_connected'] ?? false;
      _isEmergency = data['is_recent'] ?? false;
      _lastEventTime = data['last_event'] ?? _lastEventTime;
      _lastHeartbeat = data['last_heartbeat'] ?? _lastHeartbeat;
    });
  }

  @override
  void dispose() {
    // Unsubscribe from API stream before dispose
    final api = Provider.of<ApiService>(context, listen: false);
    api.unsubscribe();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _isEmergency ? Colors.red.shade50 : AppTheme.backgroundColor,
      appBar: AppBar(
        title: Text('Dashboard', style: AppTheme.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        automaticallyImplyLeading: false,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Status Card
            _buildStatusCard(),
            const SizedBox(height: 20),
            
            // Emergency Alert (Conditional)
            if (_isEmergency) _buildEmergencyAlert(),
            
            const SizedBox(height: 20),

            // Grid Stats
            Row(
              children: [
                Expanded(child: _buildInfoCard('Last Heartbeat', _lastHeartbeat, Icons.timer)),
                const SizedBox(width: 16),
                Expanded(child: _buildInfoCard('Last Event', _lastEventTime, Icons.history)),
              ],
            ),
          ].animate(interval: 100.ms).fadeIn().slideY(begin: 0.1, end: 0),
        ),
      ),
    );
  }

  Widget _buildStatusCard() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 20,
            offset: const Offset(0, 10),
          )
        ],
      ),
      child: Column(
        children: [
          CircularPercentIndicator(
            radius: 80.0,
            lineWidth: 12.0,
            animation: true,
            percent: _isConnected ? 1.0 : 0.0,
            center: Icon(
              _isConnected ? Icons.wifi : Icons.wifi_off,
              size: 50.0,
              color: _isConnected ? AppTheme.successColor : AppTheme.errorColor,
            ),
            footer: Padding(
              padding: const EdgeInsets.only(top: 16.0),
              child: Text(
                _isConnected ? "System Online" : "System Offline",
                style: AppTheme.heading2.copyWith(
                  color: _isConnected ? AppTheme.successColor : AppTheme.errorColor,
                ),
              ),
            ),
            circularStrokeCap: CircularStrokeCap.round,
            progressColor: _isConnected ? AppTheme.successColor : AppTheme.errorColor,
            backgroundColor: Colors.grey.shade100,
          ),
        ],
      ),
    );
  }

  Widget _buildEmergencyAlert() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.red.shade400, Colors.red.shade700],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.red.withValues(alpha: 0.4),
            blurRadius: 20,
            offset: const Offset(0, 10),
          )
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 32),
          )
          .animate(onPlay: (controller) => controller.repeat(reverse: true))
          .scale(begin: const Offset(1, 1), end: const Offset(1.2, 1.2)),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'EMERGENCY!',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  'Incident detected at $_lastEventTime',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.9)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCard(String title, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 15,
            offset: const Offset(0, 5),
          )
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: AppTheme.primaryColor),
          const SizedBox(height: 12),
          Text(title, style: AppTheme.bodyText.copyWith(fontSize: 14)),
          const SizedBox(height: 4),
          Text(
            value,
            style: AppTheme.heading2.copyWith(fontSize: 16),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
