import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../l10n/l10n.dart';
import '../state/app_settings.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _baseUrlController = TextEditingController();
  bool _testing = false;
  
  String _sanitizeUrlInput(String value) {
    return value.trim().replaceAll(RegExp(r'\s'), '%20');
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final api = context.read<ApiService>();
      if (api.baseUrl != null) _baseUrlController.text = api.baseUrl!;
    });
  }

  @override
  void dispose() {
    _baseUrlController.dispose();
    super.dispose();
  }

  Future<void> _saveBaseUrl() async {
    final l10n = L10n.of(context);
    final api = context.read<ApiService>();
    final v = _sanitizeUrlInput(_baseUrlController.text);
    final uri = Uri.tryParse(v);
    if (uri == null || uri.host.isEmpty || (uri.scheme != 'http' && uri.scheme != 'https')) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.t('invalid_url'))));
      return;
    }
    await api.setBaseUrl(v);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.t('save'))));
  }

  Future<void> _testConnection() async {
    final l10n = L10n.of(context);
    final api = context.read<ApiService>();
    setState(() => _testing = true);
    final ok = await api.testConnection();
    if (!mounted) return;
    setState(() => _testing = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(ok ? l10n.t('connected') : l10n.t('fetch_failed')),
        backgroundColor: ok ? AppTheme.successColor : AppTheme.errorColor,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final api = context.watch<ApiService>();
    final settings = context.watch<AppSettings>();
    final l10n = L10n.of(context);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.t('settings')),
        automaticallyImplyLeading: false,
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 26,
                    backgroundColor: scheme.primary,
                    child: const Icon(Icons.person_rounded, size: 26, color: Colors.white),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(api.username ?? 'Admin', style: theme.textTheme.titleMedium),
                        const SizedBox(height: 2),
                        Text(
                          l10n.t('administrator'),
                          style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _buildSection(
            context,
            title: l10n.t('base_url'),
            child: Column(
              children: [
                TextField(
                  controller: _baseUrlController,
                  keyboardType: TextInputType.url,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.link_rounded),
                    labelText: l10n.t('server_url'),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _testing ? null : _testConnection,
                        icon: _testing
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                            : const Icon(Icons.wifi_tethering_rounded),
                        label: Text(l10n.t('test_connection')),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: _saveBaseUrl,
                        icon: const Icon(Icons.save_rounded),
                        label: Text(l10n.t('save')),
                      ),
                    ),
                  ],
                )
              ],
            ),
          ),
          const SizedBox(height: 16),
          _buildSection(
            context,
            title: l10n.t('language'),
            child: DropdownButtonFormField<String>(
              initialValue: settings.locale.languageCode,
              items: const [
                DropdownMenuItem(value: 'th', child: Text('ไทย')),
                DropdownMenuItem(value: 'en', child: Text('English')),
              ],
              onChanged: (v) {
                if (v == null) return;
                settings.setLocale(Locale(v));
              },
            ),
          ),
          const SizedBox(height: 16),
          _buildSection(
            context,
            title: l10n.t('theme'),
            child: DropdownButtonFormField<String>(
              initialValue: settings.themeMode.name,
              items: [
                DropdownMenuItem(value: ThemeMode.system.name, child: Text(l10n.t('theme_system'))),
                DropdownMenuItem(value: ThemeMode.light.name, child: Text(l10n.t('theme_light'))),
                DropdownMenuItem(value: ThemeMode.dark.name, child: Text(l10n.t('theme_dark'))),
              ],
              onChanged: (v) {
                if (v == null) return;
                settings.setThemeMode(ThemeMode.values.firstWhere((m) => m.name == v));
              },
            ),
          ),
          const SizedBox(height: 16),
          _buildSection(
            context,
            title: l10n.t('about'),
            child: ListTile(
              leading: Icon(Icons.info_outline_rounded, color: scheme.primary),
              title: Text(l10n.t('about')),
              subtitle: const Text('v1.0.0+1'),
              trailing: const Icon(Icons.chevron_right_rounded),
              onTap: () => _showAboutDialog(context),
            ),
          ),
          const SizedBox(height: 16),
          FilledButton.tonalIcon(
            onPressed: () => api.logout(),
            icon: const Icon(Icons.logout_rounded),
            label: Text(l10n.t('logout')),
            style: FilledButton.styleFrom(
              foregroundColor: scheme.error,
              backgroundColor: scheme.errorContainer,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection(BuildContext context, {required String title, required Widget child}) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: theme.textTheme.titleMedium),
            const SizedBox(height: 10),
            IconTheme(
              data: IconThemeData(color: scheme.onSurfaceVariant),
              child: child,
            ),
          ],
        ),
      ),
    );
  }

  void _showAboutDialog(BuildContext context) {
    final l10n = L10n.of(context);
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.t('about')),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('ResQtech Mobile App'),
            const SizedBox(height: 8),
            const Text('Version: 1.0.0+1'),
            const SizedBox(height: 16),
            Text(
              'An emergency notification system mobile client developed with Flutter.',
              style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
}
