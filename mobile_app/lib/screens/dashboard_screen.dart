import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:percent_indicator/circular_percent_indicator.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../l10n/l10n.dart';

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
  List<Map<String, dynamic>> _devices = const [];
  bool _isLoading = true;
  String? _error;
  bool _isDisposing = false;

  @override
  void initState() {
    super.initState();
    _connectStream();
    _refresh();
  }

  Future<void> _refresh() async {
    final api = Provider.of<ApiService>(context, listen: false);
    if (!mounted || _isDisposing) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final data = await api.checkStatus();
      if (!mounted || _isDisposing) return;
      if (data.isNotEmpty) {
        _updateState(data);
      } else {
        setState(() => _error = L10n.of(context).t('fetch_failed'));
      }
    } catch (_) {
      if (!mounted || _isDisposing) return;
      setState(() => _error = L10n.of(context).t('fetch_failed'));
    } finally {
      if (mounted && !_isDisposing) setState(() => _isLoading = false);
    }
  }

  void _connectStream() {
    final api = Provider.of<ApiService>(context, listen: false);
    api.subscribeToStream((data) {
      if (mounted && !_isDisposing) {
        if (data['type'] == 'heartbeat') {
           setState(() {
             _isConnected = data['is_connected'] ?? false;
             _lastHeartbeat = data['last_heartbeat'] ?? '-';
             final list = data['devices_list'];
             _devices = list is List ? List<Map<String, dynamic>>.from(list) : _devices;
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
      final list = data['devices_list'];
      _devices = list is List ? List<Map<String, dynamic>>.from(list) : _devices;
    });
  }

  @override
  void dispose() {
    _isDisposing = true;
    // Unsubscribe from API stream before dispose
    final api = Provider.of<ApiService>(context, listen: false);
    api.unsubscribe();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = L10n.of(context);
    final baseBg = Theme.of(context).scaffoldBackgroundColor;
    return Scaffold(
      backgroundColor: _isEmergency ? Colors.red.shade50 : baseBg,
      appBar: AppBar(
        title: Text(l10n.t('nav_dashboard'), style: AppTheme.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            tooltip: l10n.t('refresh'),
            onPressed: _refresh,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _buildStatusCard(),
            const SizedBox(height: 16),
            if (_isEmergency) _buildEmergencyAlert(),
            if (_error != null) ...[
              const SizedBox(height: 16),
              _buildErrorCard(_error!),
            ],
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(child: _buildInfoCard(l10n.t('last_heartbeat'), _lastHeartbeat, Icons.timer)),
                const SizedBox(width: 16),
                Expanded(child: _buildInfoCard(l10n.t('last_event'), _lastEventTime, Icons.history)),
              ],
            ),
            const SizedBox(height: 16),
            _buildDevicesSection(),
          ].animate(interval: 80.ms).fadeIn().slideY(begin: 0.04, end: 0),
        ),
      ),
    );
  }

  Widget _buildStatusCard() {
    final l10n = L10n.of(context);
    final onlineCount =
        _devices.where((d) => d['is_online'] == true).length;
    final total = _devices.length;
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: theme.cardColor,
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
              child: Column(
                children: [
                  Text(
                    _isConnected ? l10n.t('system_online') : l10n.t('system_offline'),
                    style: AppTheme.heading2.copyWith(
                      color: _isConnected ? AppTheme.successColor : AppTheme.errorColor,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    '$onlineCount / $total ${l10n.t('device')}',
                    style: AppTheme.bodyText.copyWith(fontSize: 13),
                  ),
                ],
              ),
            ),
            circularStrokeCap: CircularStrokeCap.round,
            progressColor: _isConnected ? AppTheme.successColor : AppTheme.errorColor,
            backgroundColor: theme.colorScheme.surfaceContainerHighest,
          ),
        ],
      ),
    );
  }

  Widget _buildEmergencyAlert() {
    final l10n = L10n.of(context);
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
                Text(
                  l10n.t('emergency'),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  '${l10n.t('incident_at')} $_lastEventTime',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.9)),
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: () => setState(() => _isEmergency = false),
            icon: const Icon(Icons.close, color: Colors.white),
            tooltip: 'Dismiss',
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCard(String title, String value, IconData icon) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: theme.cardColor,
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

  Widget _buildDevicesSection() {
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: theme.cardColor,
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
          Row(
            children: [
              Icon(Icons.sensors, color: theme.colorScheme.primary),
              const SizedBox(width: 10),
              Text(l10n.t('device'), style: AppTheme.heading2.copyWith(fontSize: 16)),
              const Spacer(),
              if (_isLoading) const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
            ],
          ),
          const SizedBox(height: 12),
          if (_devices.isEmpty && !_isLoading)
            Text(l10n.t('no_data'), style: AppTheme.bodyText)
          else
            ..._devices.take(6).map((d) {
              final online = d['is_online'] == true;
              final id = (d['id'] ?? '-').toString();
              final loc = (d['location'] ?? '-').toString();
              final secs = d['seconds_ago'];
              final secsText = secs is num ? secs.toString() : '-';
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  children: [
                    Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                        color: online ? AppTheme.successColor : AppTheme.errorColor,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(id, style: const TextStyle(fontWeight: FontWeight.w700)),
                          Text(loc, style: AppTheme.bodyText.copyWith(fontSize: 12)),
                        ],
                      ),
                    ),
                    Text('${secsText}s', style: AppTheme.bodyText.copyWith(fontSize: 12)),
                  ],
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _buildErrorCard(String message) {
    final l10n = L10n.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.red.shade100),
      ),
      child: Row(
        children: [
          Icon(Icons.error_outline, color: Colors.red.shade400),
          const SizedBox(width: 12),
          Expanded(child: Text(message)),
          TextButton(
            onPressed: _refresh,
            child: Text(l10n.t('retry')),
          ),
        ],
      ),
    );
  }
}
