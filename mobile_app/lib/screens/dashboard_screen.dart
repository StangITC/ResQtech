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
  ApiService? _apiService; // Store reference to avoid context issues
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
    _apiService = Provider.of<ApiService>(context,
        listen: false); // Store reference when context is valid
    _connectStream();
    _refresh();
  }

  Future<void> _refresh() async {
    final api = _apiService;
    if (api == null || !mounted || _isDisposing) return;
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
    final api = _apiService;
    if (api == null) return;
    api.subscribeToStream((data) {
      if (mounted && !_isDisposing) {
        if (data['type'] == 'heartbeat') {
          setState(() {
            _isConnected = data['is_connected'] ?? false;
            _lastHeartbeat = data['last_heartbeat'] ?? '-';
            final list = data['devices_list'];
            _devices =
                list is List ? List<Map<String, dynamic>>.from(list) : _devices;
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
      _devices =
          list is List ? List<Map<String, dynamic>>.from(list) : _devices;
    });
  }

  @override
  void deactivate() {
    // Unsubscribe when widget is removed from tree (e.g. switching tabs)
    _apiService?.unsubscribe(); // Use stored reference to avoid context issues
    super.deactivate();
  }

  @override
  void dispose() {
    _isDisposing = true;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final baseBg = theme.scaffoldBackgroundColor;
    final bg = _isEmergency
        ? Color.alphaBlend(
            scheme.errorContainer.withValues(alpha: 0.30), baseBg)
        : baseBg;
    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        title: Text(l10n.t('nav_dashboard')),
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
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
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
                Expanded(
                    child: _buildInfoCard(l10n.t('last_heartbeat'),
                        _lastHeartbeat, Icons.timer_outlined)),
                const SizedBox(width: 16),
                Expanded(
                    child: _buildInfoCard(l10n.t('last_event'), _lastEventTime,
                        Icons.history_rounded)),
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
    final onlineCount = _devices.where((d) => d['is_online'] == true).length;
    final total = _devices.length;
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      padding: const EdgeInsets.all(0),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      _isConnected
                          ? l10n.t('system_online')
                          : l10n.t('system_offline'),
                      style: theme.textTheme.titleMedium?.copyWith(
                        color:
                            _isConnected ? AppTheme.successColor : scheme.error,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  if (_isEmergency)
                    Chip(
                      label: Text(l10n.t('emergency')),
                      backgroundColor: scheme.errorContainer,
                      labelStyle: theme.textTheme.labelMedium
                          ?.copyWith(color: scheme.onErrorContainer),
                      side: BorderSide(
                          color: scheme.error.withValues(alpha: 0.35)),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              CircularPercentIndicator(
                radius: 72.0,
                lineWidth: 12.0,
                animation: true,
                percent: _isConnected ? 1.0 : 0.0,
                center: Icon(
                  _isConnected ? Icons.wifi_rounded : Icons.wifi_off_rounded,
                  size: 46.0,
                  color: _isConnected ? AppTheme.successColor : scheme.error,
                ),
                footer: Padding(
                  padding: const EdgeInsets.only(top: 14.0),
                  child: Text(
                    '$onlineCount / $total ${l10n.t('device')}',
                    style: theme.textTheme.bodyMedium
                        ?.copyWith(color: scheme.onSurfaceVariant),
                  ),
                ),
                circularStrokeCap: CircularStrokeCap.round,
                progressColor:
                    _isConnected ? AppTheme.successColor : scheme.error,
                backgroundColor: scheme.surfaceContainerHighest,
              ),
            ],
          ),
        ),
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
            child: const Icon(Icons.warning_amber_rounded,
                color: Colors.white, size: 32),
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
            icon: const Icon(Icons.close_rounded, color: Colors.white),
            tooltip: l10n.t('dismiss'),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoCard(String title, String value, IconData icon) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: scheme.primary),
            const SizedBox(height: 10),
            Text(title,
                style: theme.textTheme.bodyMedium
                    ?.copyWith(color: scheme.onSurfaceVariant)),
            const SizedBox(height: 6),
            Text(
              value,
              style: theme.textTheme.titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDevicesSection() {
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.sensors_rounded, color: scheme.primary),
                const SizedBox(width: 10),
                Text(l10n.t('device'), style: theme.textTheme.titleMedium),
                const Spacer(),
                if (_isLoading)
                  const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2)),
              ],
            ),
            const SizedBox(height: 10),
            if (_devices.isEmpty && !_isLoading)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 10),
                child: Text(l10n.t('no_data'),
                    style: theme.textTheme.bodyMedium
                        ?.copyWith(color: scheme.onSurfaceVariant)),
              )
            else
              ..._devices.take(6).map((d) {
                final online = d['is_online'] == true;
                final id = (d['id'] ?? '-').toString();
                final loc = (d['location'] ?? '-').toString();
                final secs = d['seconds_ago'];
                final secsText = secs is num ? secs.toString() : '-';

                return Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Card(
                    color: scheme.surfaceContainerLowest,
                    child: ListTile(
                      leading: Container(
                        width: 12,
                        height: 12,
                        decoration: BoxDecoration(
                          color: online ? AppTheme.successColor : scheme.error,
                          shape: BoxShape.circle,
                        ),
                      ),
                      title: Text(id,
                          style: theme.textTheme.titleSmall
                              ?.copyWith(fontWeight: FontWeight.w700)),
                      subtitle: Text(loc,
                          maxLines: 1, overflow: TextOverflow.ellipsis),
                      trailing: Text('${secsText}s',
                          style: theme.textTheme.labelMedium
                              ?.copyWith(color: scheme.onSurfaceVariant)),
                      dense: true,
                      visualDensity: VisualDensity.compact,
                    ),
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorCard(String message) {
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Card(
      color: scheme.errorContainer
          .withValues(alpha: theme.brightness == Brightness.dark ? 0.35 : 0.65),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            Icon(Icons.error_outline_rounded, color: scheme.error),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
            TextButton(
              onPressed: _refresh,
              child: Text(l10n.t('retry')),
            ),
          ],
        ),
      ),
    );
  }
}
