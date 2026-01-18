import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppSettings extends ChangeNotifier {
  static const _kThemeMode = 'theme_mode';
  static const _kLocale = 'locale';

  ThemeMode _themeMode = ThemeMode.system;
  Locale _locale = const Locale('th');

  ThemeMode get themeMode => _themeMode;
  Locale get locale => _locale;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final theme = prefs.getString(_kThemeMode);
    final locale = prefs.getString(_kLocale);

    _themeMode = _parseThemeMode(theme) ?? ThemeMode.system;
    _locale = _parseLocale(locale) ?? const Locale('th');
    notifyListeners();
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    _themeMode = mode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kThemeMode, _themeMode.name);
    notifyListeners();
  }

  Future<void> setLocale(Locale locale) async {
    _locale = locale;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kLocale, _locale.languageCode);
    notifyListeners();
  }

  ThemeMode? _parseThemeMode(String? value) {
    if (value == null || value.isEmpty) return null;
    for (final m in ThemeMode.values) {
      if (m.name == value) return m;
    }
    return null;
  }

  Locale? _parseLocale(String? value) {
    if (value == null || value.isEmpty) return null;
    if (value == 'th') return const Locale('th');
    if (value == 'en') return const Locale('en');
    return null;
  }
}

