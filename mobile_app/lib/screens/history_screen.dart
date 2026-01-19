import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../l10n/l10n.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  late Future<List<Map<String, dynamic>>> _historyFuture;
  final _deviceController = TextEditingController();
  final _locationController = TextEditingController();
  String _statusFilter = 'all';
  bool _filtersExpanded = false;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  void _refresh() {
    setState(() {
      _historyFuture = Provider.of<ApiService>(context, listen: false).fetchHistory();
    });
  }

  @override
  void dispose() {
    _deviceController.dispose();
    _locationController.dispose();
    super.dispose();
  }

  List<Map<String, dynamic>> _applyFilter(List<Map<String, dynamic>> rows) {
    final d = _deviceController.text.trim().toLowerCase();
    final l = _locationController.text.trim().toLowerCase();
    return rows.where((r) {
      final device = (r['device'] ?? '').toString().toLowerCase();
      final loc = (r['location'] ?? '').toString().toLowerCase();
      final status = (r['status'] ?? '').toString().toUpperCase();
      final okD = d.isEmpty || device.contains(d);
      final okL = l.isEmpty || loc.contains(l);
      final okS = _statusFilter == 'all'
          ? true
          : (_statusFilter == 'alert' ? status == 'ALERT' : status != 'ALERT');
      return okD && okL && okS;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.t('activity_log')),
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: AppTheme.primaryColor),
            onPressed: _refresh,
          )
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => _refresh(),
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          children: [
            _buildFilters(context),
            const SizedBox(height: 16),
            FutureBuilder<List<Map<String, dynamic>>>(
              future: _historyFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Padding(
                    padding: EdgeInsets.only(top: 40),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }

                if (snapshot.hasError) {
                  return Padding(
                    padding: const EdgeInsets.only(top: 40),
                    child: Center(
                      child: Text(
                        '${l10n.t('fetch_failed')}: ${snapshot.error}',
                        style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  );
                }

                final history = _applyFilter(snapshot.data ?? []);

                if (history.isEmpty) {
                  return Padding(
                    padding: const EdgeInsets.only(top: 40),
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.history_toggle_off,
                            size: 64,
                            color: scheme.onSurfaceVariant.withValues(alpha: 0.5),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            l10n.t('no_recent_activity'),
                            style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                          ),
                        ],
                      ),
                    ),
                  );
                }

                return ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: history.length,
                  itemBuilder: (context, index) {
                    final item = history[index];
                    final isAlert = (item['status'] ?? '').toString().toUpperCase() == 'ALERT';
                    final event = (item['event'] ?? '').toString();
                    final time = (item['time'] ?? '-').toString();
                    final device = (item['device'] ?? '-').toString();
                    final location = (item['location'] ?? '-').toString();

                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Card(
                        child: ListTile(
                          leading: Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: isAlert
                                  ? scheme.errorContainer.withValues(alpha: 0.9)
                                  : scheme.primaryContainer.withValues(alpha: 0.9),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              isAlert ? Icons.warning_amber_rounded : Icons.info_outline_rounded,
                              color: isAlert ? scheme.onErrorContainer : scheme.onPrimaryContainer,
                            ),
                          ),
                          title: Text(
                            event.isEmpty ? l10n.t('no_data') : event,
                            style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(time),
                                Text(
                                  '$device • $location',
                                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                          isThreeLine: true,
                          trailing: Chip(
                            label: Text(isAlert ? l10n.t('alert') : l10n.t('normal')),
                            backgroundColor: isAlert ? scheme.errorContainer : scheme.secondaryContainer,
                            labelStyle: theme.textTheme.labelMedium?.copyWith(
                              color: isAlert ? scheme.onErrorContainer : scheme.onSecondaryContainer,
                            ),
                            side: BorderSide(color: (isAlert ? scheme.error : scheme.secondary).withValues(alpha: 0.25)),
                          ),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilters(BuildContext context) {
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final hasFilter = _deviceController.text.trim().isNotEmpty ||
        _locationController.text.trim().isNotEmpty ||
        _statusFilter != 'all';

    return Card(
      child: Theme(
        data: theme.copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          initiallyExpanded: _filtersExpanded || hasFilter,
          onExpansionChanged: (v) => setState(() => _filtersExpanded = v),
          leading: Icon(Icons.filter_alt_outlined, color: scheme.primary),
          title: Text(l10n.t('filter'), style: theme.textTheme.titleMedium),
          childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          children: [
            TextField(
              controller: _deviceController,
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.sensors_rounded),
                labelText: l10n.t('device'),
              ),
              onChanged: (_) => setState(() {}),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _locationController,
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.location_on_outlined),
                labelText: l10n.t('location'),
              ),
              onChanged: (_) => setState(() {}),
            ),
            const SizedBox(height: 12),
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                l10n.t('status'),
                style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ),
            const SizedBox(height: 8),
            SegmentedButton<String>(
              showSelectedIcon: false,
              segments: [
                ButtonSegment(value: 'all', label: Text(l10n.t('all'))),
                ButtonSegment(value: 'alert', label: Text(l10n.t('alert'))),
                ButtonSegment(value: 'ok', label: Text(l10n.t('normal'))),
              ],
              selected: {_statusFilter},
              onSelectionChanged: (v) => setState(() => _statusFilter = v.first),
            ),
            if (hasFilter) ...[
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: () {
                    _deviceController.clear();
                    _locationController.clear();
                    setState(() => _statusFilter = 'all');
                  },
                  icon: const Icon(Icons.clear_rounded),
                  label: Text(l10n.t('dismiss')),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
