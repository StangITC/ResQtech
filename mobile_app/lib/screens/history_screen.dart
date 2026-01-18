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
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(l10n.t('activity_log'), style: AppTheme.heading2),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
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
          padding: const EdgeInsets.all(20),
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
                    child: Center(child: Text('${l10n.t('fetch_failed')}: ${snapshot.error}')),
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
                            color: Theme.of(context).colorScheme.onSurfaceVariant.withValues(alpha: 0.5),
                          ),
                          const SizedBox(height: 16),
                          Text(l10n.t('no_recent_activity'), style: AppTheme.bodyText),
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

                    return Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.03),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          )
                        ],
                        border: isAlert ? Border.all(color: Colors.red.shade200) : null,
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: isAlert ? Colors.red.shade50 : Colors.blue.shade50,
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              isAlert ? Icons.warning_amber_rounded : Icons.info_outline,
                              color: isAlert ? Colors.red : Colors.blue,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  (item['event'] ?? '').toString().isEmpty
                                      ? l10n.t('no_data')
                                      : item['event'].toString(),
                                  style: AppTheme.heading2.copyWith(fontSize: 16),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  (item['time'] ?? '-').toString(),
                                  style: AppTheme.bodyText.copyWith(fontSize: 14),
                                ),
                                Text(
                                  '${(item['device'] ?? '-')} (${(item['location'] ?? '-')})',
                                  style: AppTheme.bodyText.copyWith(
                                    fontSize: 12,
                                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
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
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.filter_alt_outlined, color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 10),
              Text(l10n.t('filter'), style: const TextStyle(fontWeight: FontWeight.w700)),
            ],
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _deviceController,
            decoration: InputDecoration(
              prefixIcon: const Icon(Icons.sensors),
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
          const SizedBox(height: 10),
          DropdownButtonFormField<String>(
            initialValue: _statusFilter,
            items: [
              DropdownMenuItem(value: 'all', child: Text('${l10n.t('status')}: ${l10n.t('all')}')),
              DropdownMenuItem(value: 'alert', child: Text('${l10n.t('status')}: ${l10n.t('alert')}')),
              DropdownMenuItem(value: 'ok', child: Text('${l10n.t('status')}: ${l10n.t('normal')}')),
            ],
            onChanged: (v) => setState(() => _statusFilter = v ?? 'all'),
          ),
        ],
      ),
    );
  }
}
