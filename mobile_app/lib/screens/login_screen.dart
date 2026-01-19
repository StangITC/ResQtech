import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../l10n/l10n.dart';
import '../state/app_settings.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlController = TextEditingController();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _isTestingServer = false;
  bool _isPasswordVisible = false;
  
  String _sanitizeUrlInput(String value) {
    return value.trim().replaceAll(RegExp(r'\s'), '%20');
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final apiService = Provider.of<ApiService>(context, listen: false);
      if (apiService.baseUrl != null) {
        _urlController.text = apiService.baseUrl!;
      }
    });
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final apiService = Provider.of<ApiService>(context, listen: false);
    
    await apiService.setBaseUrl(_sanitizeUrlInput(_urlController.text));

    final success = await apiService.login(
      _usernameController.text.trim(),
      _passwordController.text.trim(),
    );

    setState(() => _isLoading = false);

    if (!success && mounted) {
      final l10n = L10n.of(context);
      final msg = apiService.lastError?.trim().isNotEmpty == true
          ? apiService.lastError!.trim()
          : l10n.t('login_failed');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(msg),
          backgroundColor: AppTheme.errorColor,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    }
  }

  Future<void> _testServer() async {
    if (_isTestingServer) return;

    final urlError = _validateUrl(_urlController.text);
    if (urlError != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(urlError)));
      return;
    }

    setState(() => _isTestingServer = true);
    final apiService = Provider.of<ApiService>(context, listen: false);
    await apiService.setBaseUrl(_sanitizeUrlInput(_urlController.text));

    final ok = await apiService.testConnection();
    if (!mounted) return;
    setState(() => _isTestingServer = false);

    final l10n = L10n.of(context);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(ok ? l10n.t('connected') : l10n.t('disconnected')),
        backgroundColor: ok ? AppTheme.successColor : AppTheme.errorColor,
      ),
    );
  }

  @override
  void dispose() {
    _urlController.dispose();
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  String? _validateUrl(String? value) {
    final v = _sanitizeUrlInput(value ?? '');
    if (v.isEmpty) return L10n.of(context).t('required_field');
    final uri = Uri.tryParse(v);
    if (uri == null || uri.host.isEmpty || (uri.scheme != 'http' && uri.scheme != 'https')) {
      return L10n.of(context).t('invalid_url');
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final l10n = L10n.of(context);
    final settings = context.watch<AppSettings>();
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      body: Stack(
        children: [
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    scheme.primaryContainer.withValues(alpha: theme.brightness == Brightness.dark ? 0.25 : 0.55),
                    theme.scaffoldBackgroundColor,
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
            ),
          ),
          Positioned(
            top: -120,
            right: -120,
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                color: scheme.primary.withValues(alpha: theme.brightness == Brightness.dark ? 0.08 : 0.12),
                shape: BoxShape.circle,
              ),
            ),
          ),
          Positioned(
            bottom: -140,
            left: -120,
            child: Container(
              width: 300,
              height: 300,
              decoration: BoxDecoration(
                color: scheme.tertiary.withValues(alpha: theme.brightness == Brightness.dark ? 0.06 : 0.10),
                shape: BoxShape.circle,
              ),
            ),
          ),
          SafeArea(
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 520),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  child: Column(
                    children: [
                      Align(
                        alignment: Alignment.centerRight,
                        child: Wrap(
                          spacing: 8,
                          children: [
                            IconButton(
                              tooltip: l10n.t('language'),
                              onPressed: () {
                                final next = settings.locale.languageCode == 'th'
                                    ? const Locale('en')
                                    : const Locale('th');
                                settings.setLocale(next);
                              },
                              icon: const Icon(Icons.language),
                            ),
                            IconButton(
                              tooltip: l10n.t('theme'),
                              onPressed: () {
                                final next = settings.themeMode == ThemeMode.dark
                                    ? ThemeMode.light
                                    : ThemeMode.dark;
                                settings.setThemeMode(next);
                              },
                              icon: Icon(
                                settings.themeMode == ThemeMode.dark
                                    ? Icons.dark_mode
                                    : Icons.light_mode,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      Icon(Icons.shield_moon_rounded, size: 80, color: scheme.primary)
                          .animate()
                          .scale(duration: 500.ms, curve: Curves.easeOutBack),
                      const SizedBox(height: 14),
                      Text(
                        l10n.t('app_name'),
                        style: theme.textTheme.headlineSmall,
                        textAlign: TextAlign.center,
                      ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.25, end: 0),
                      const SizedBox(height: 6),
                      Text(
                        l10n.t('login_subtitle'),
                        style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                        textAlign: TextAlign.center,
                      ).animate().fadeIn(delay: 280.ms),
                      const SizedBox(height: 24),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(20),
                          child: AutofillGroup(
                            child: Form(
                              key: _formKey,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  TextFormField(
                                    controller: _urlController,
                                    decoration: AppTheme.inputDecoration(l10n.t('server_url'), Icons.link).copyWith(
                                      helperText: l10n.t('server_url_hint'),
                                    ),
                                    keyboardType: TextInputType.url,
                                    textInputAction: TextInputAction.next,
                                    validator: _validateUrl,
                                  ),
                                  const SizedBox(height: 12),
                                  Row(
                                    children: [
                                      Expanded(
                                        child: OutlinedButton.icon(
                                          onPressed: _isLoading || _isTestingServer ? null : _testServer,
                                          icon: _isTestingServer
                                              ? const SizedBox(
                                                  width: 18,
                                                  height: 18,
                                                  child: CircularProgressIndicator(strokeWidth: 2),
                                                )
                                              : const Icon(Icons.wifi_tethering),
                                          label: Text(l10n.t('test_connection')),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 16),
                                  TextFormField(
                                    controller: _usernameController,
                                    decoration: AppTheme.inputDecoration(l10n.t('username'), Icons.person_outline),
                                    textInputAction: TextInputAction.next,
                                    autofillHints: const [AutofillHints.username],
                                    validator: (value) =>
                                        (value ?? '').trim().isEmpty ? l10n.t('required_field') : null,
                                  ),
                                  const SizedBox(height: 12),
                                  TextFormField(
                                    controller: _passwordController,
                                    obscureText: !_isPasswordVisible,
                                    decoration: AppTheme.inputDecoration(l10n.t('password'), Icons.lock_outline).copyWith(
                                      suffixIcon: IconButton(
                                        icon: Icon(
                                          _isPasswordVisible ? Icons.visibility : Icons.visibility_off,
                                          color: scheme.onSurfaceVariant,
                                        ),
                                        onPressed: () =>
                                            setState(() => _isPasswordVisible = !_isPasswordVisible),
                                      ),
                                    ),
                                    textInputAction: TextInputAction.done,
                                    autofillHints: const [AutofillHints.password],
                                    onFieldSubmitted: (_) => _isLoading ? null : _login(),
                                    validator: (value) => (value ?? '').isEmpty ? l10n.t('required_field') : null,
                                  ),
                                  const SizedBox(height: 18),
                                  FilledButton(
                                    onPressed: _isLoading ? null : _login,
                                    child: _isLoading
                                        ? const SizedBox(
                                            width: 22,
                                            height: 22,
                                            child: CircularProgressIndicator(
                                              color: Colors.white,
                                              strokeWidth: 2,
                                            ),
                                          )
                                        : Text(l10n.t('sign_in')),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ).animate().fadeIn(delay: 380.ms).slideY(begin: 0.12, end: 0),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
