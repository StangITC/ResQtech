import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService with ChangeNotifier {
  String? _baseUrl;
  String? _cookie;
  bool _isLoggedIn = false;
  String? _username;
  Timer? _pollingTimer;

  bool get isLoggedIn => _isLoggedIn;
  String? get username => _username;
  String? get baseUrl => _baseUrl;

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url');
    _cookie = prefs.getString('cookie');
    _username = prefs.getString('username');
    
    if (_cookie != null && _baseUrl != null) {
      _isLoggedIn = true;
      notifyListeners();
    }
  }

  Future<void> setBaseUrl(String url) async {
    _baseUrl = url;
    if (_baseUrl!.endsWith('/')) {
      _baseUrl = _baseUrl!.substring(0, _baseUrl!.length - 1);
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('base_url', _baseUrl!);
    notifyListeners();
  }

  Future<bool> login(String username, String password) async {
    if (_baseUrl == null) throw Exception('Base URL not set');

    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/api/mobile-login.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'username': username, 'password': password}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success') {
          // Extract Cookie
          String? rawCookie = response.headers['set-cookie'];
          if (rawCookie != null) {
            int index = rawCookie.indexOf(';');
            _cookie = (index == -1) ? rawCookie : rawCookie.substring(0, index);
          }

          _username = username;
          _isLoggedIn = true;

          // Save to prefs
          final prefs = await SharedPreferences.getInstance();
          if (_cookie != null) await prefs.setString('cookie', _cookie!);
          await prefs.setString('username', username);

          notifyListeners();
          return true;
        }
      }
      return false;
    } catch (e) {
      print('Login Error: $e');
      return false;
    }
  }

  Future<void> logout() async {
    _pollingTimer?.cancel();
    _isLoggedIn = false;
    _cookie = null;
    _username = null;
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('cookie');
    await prefs.remove('username');
    
    notifyListeners();
  }

  Future<Map<String, dynamic>> checkStatus() async {
    if (_baseUrl == null) return {};

    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/check-status.php'),
        headers: {
          'Content-Type': 'application/json',
          'Cookie': _cookie ?? '',
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else if (response.statusCode == 401) {
        logout();
      }
    } catch (e) {
      print('Check Status Error: $e');
    }
    return {};
  }

  Future<List<Map<String, dynamic>>> fetchHistory() async {
    if (_baseUrl == null) return [];

    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/api/get-history.php'),
        headers: {
          'Content-Type': 'application/json',
          'Cookie': _cookie ?? '',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          return List<Map<String, dynamic>>.from(data['data']);
        }
      } else if (response.statusCode == 401) {
        logout();
      }
    } catch (e) {
      print('Fetch History Error: $e');
    }
    return [];
  }

  void subscribeToStream(Function(Map<String, dynamic>) onEvent) {
    // Cancel existing timer
    _pollingTimer?.cancel();

    // Start polling every 3 seconds
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) async {
      if (!_isLoggedIn || _baseUrl == null) {
        timer.cancel();
        return;
      }

      final data = await checkStatus();
      if (data.isNotEmpty) {
        // Convert checkStatus response to Stream format
        onEvent({
          'type': 'heartbeat',
          'is_connected': data['is_connected'],
          'last_heartbeat': data['last_heartbeat'],
        });

        // Emulate Emergency Event if recent
        if (data['is_recent'] == true) {
          onEvent({
            'type': 'emergency',
            'is_recent': true,
            'last_event': data['last_event'],
          });
        }
      }
    });
  }

  void unsubscribe() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
  }
}
