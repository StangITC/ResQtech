import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'http_client.dart';

class ApiService with ChangeNotifier {
  String? _baseUrl;
  String? _cookie;
  String? _sessionId;
  String? _pushToken;
  bool _isLoggedIn = false;
  String? _username;
  Timer? _pollingTimer;
  String? _lastEmergencyEvent;
  String? _lastError;
  late final http.Client _client;

  bool get isLoggedIn => _isLoggedIn;
  String? get username => _username;
  String? get baseUrl => _baseUrl;
  String? get lastError => _lastError;

  Future<void> init() async {
    _client = createHttpClient();
    final prefs = await SharedPreferences.getInstance();
    _baseUrl = prefs.getString('base_url');
    _cookie = prefs.getString('cookie');
    _sessionId = prefs.getString('session_id');
    _username = prefs.getString('username');
    _pushToken = prefs.getString('push_token');

    const envBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: '');
    if ((_baseUrl == null || _baseUrl!.isEmpty) && envBaseUrl.isNotEmpty) {
      _baseUrl = _normalizeBaseUrl(envBaseUrl);
    } else if (_baseUrl != null && _baseUrl!.isNotEmpty) {
      _baseUrl = _normalizeBaseUrl(_baseUrl!);
    }
    
    if (_baseUrl != null && ((_cookie != null && _cookie!.isNotEmpty) || (_sessionId != null && _sessionId!.isNotEmpty))) {
      _isLoggedIn = true;
      notifyListeners();
      await _registerPushTokenIfPossible();
    }
  }

  Future<void> setBaseUrl(String url) async {
    _baseUrl = _normalizeBaseUrl(url);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('base_url', _baseUrl!);
    notifyListeners();
  }

  Future<bool> login(String username, String password) async {
    if (_baseUrl == null) throw Exception('Base URL not set');

    try {
      _lastError = null;
      Future<http.Response> doRequest(String baseUrl) {
        return _client
            .post(
              Uri.parse('$baseUrl/api/mobile-login.php'),
              headers: {'Content-Type': 'application/json'},
              body: jsonEncode({'username': username, 'password': password}),
            )
            .timeout(const Duration(seconds: 12));
      }

      http.Response response;
      try {
        response = await doRequest(_baseUrl!);
      } on http.ClientException catch (_) {
        final uri = Uri.tryParse(_baseUrl!);
        if (uri != null && uri.scheme == 'https') {
          final httpBaseUrl = uri.replace(scheme: 'http').toString();
          response = await doRequest(httpBaseUrl);
          await setBaseUrl(httpBaseUrl);
        } else {
          rethrow;
        }
      } on TimeoutException catch (_) {
        final uri = Uri.tryParse(_baseUrl!);
        if (uri != null && uri.scheme == 'https') {
          final httpBaseUrl = uri.replace(scheme: 'http').toString();
          response = await doRequest(httpBaseUrl);
          await setBaseUrl(httpBaseUrl);
        } else {
          rethrow;
        }
      }

      Map<String, dynamic>? data;
      try {
        data = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {}

      if (response.statusCode == 200 && data != null) {
        if (data['status'] == 'success') {
          final rawCookie = response.headers['set-cookie'];
          final sid = data['session_id'];
          if (sid != null) _sessionId = sid.toString();
          if (_sessionId == null || _sessionId!.isEmpty) return false;

          if (!isWebHttpClient) {
            _cookie = _extractSessionCookie(rawCookie);
          }

          _username = username;
          _isLoggedIn = true;

          // Save to prefs
          final prefs = await SharedPreferences.getInstance();
          if (!isWebHttpClient && _cookie != null && _cookie!.isNotEmpty) {
            await prefs.setString('cookie', _cookie!);
          }
          if (_sessionId != null && _sessionId!.isNotEmpty) await prefs.setString('session_id', _sessionId!);
          await prefs.setString('username', username);

          notifyListeners();
          await _registerPushTokenIfPossible();
          return true;
        }
      }
      if (data != null && data['message'] != null) {
        _lastError = data['message'].toString();
      } else {
        _lastError = 'HTTP ${response.statusCode}';
      }
      return false;
    } on http.ClientException catch (e) {
      debugPrint('Login Error: $e');
      _lastError = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ (Failed to fetch). ถ้าใช้ https บนเครื่องในวง LAN ให้ลองเปลี่ยนเป็น http';
      return false;
    } on TimeoutException catch (e) {
      debugPrint('Login Error: $e');
      _lastError = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ทันเวลา (timeout). ตรวจสอบ Server URL/เครือข่าย';
      return false;
    } catch (e) {
      debugPrint('Login Error: $e');
      _lastError = e.toString();
      return false;
    }
  }

  Future<void> logout() async {
    _pollingTimer?.cancel();
    _isLoggedIn = false;
    _cookie = null;
    _sessionId = null;
    _username = null;
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('cookie');
    await prefs.remove('session_id');
    await prefs.remove('username');
    
    notifyListeners();
  }

  Future<void> setPushToken(String? token) async {
    final v = token?.trim();
    if (v == null || v.isEmpty) return;
    _pushToken = v;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('push_token', v);
    await _registerPushTokenIfPossible();
  }

  Future<void> _registerPushTokenIfPossible() async {
    if (!_isLoggedIn) return;
    if (_baseUrl == null || _baseUrl!.isEmpty) return;
    if (_pushToken == null || _pushToken!.isEmpty) return;

    try {
      await _client
          .post(
            Uri.parse('$_baseUrl/api/register-fcm-token.php'),
            headers: _buildHeaders(),
            body: jsonEncode({
              'token': _pushToken,
              'platform': defaultTargetPlatform.name,
            }),
          )
          .timeout(const Duration(seconds: 10));
    } catch (_) {}
  }

  Future<Map<String, dynamic>> checkStatus() async {
    if (_baseUrl == null) return {};

    try {
      final response = await _client.get(
        Uri.parse('$_baseUrl/api/check-status.php'),
        headers: _buildHeaders(),
      ).timeout(const Duration(seconds: 12));

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else if (response.statusCode == 401) {
        await logout();
      }
    } catch (e) {
      debugPrint('Check Status Error: $e');
    }
    return {};
  }

  Future<List<Map<String, dynamic>>> fetchHistory() async {
    if (_baseUrl == null) return [];

    try {
      final response = await _client.get(
        Uri.parse('$_baseUrl/api/get-history.php'),
        headers: _buildHeaders(),
      ).timeout(const Duration(seconds: 12));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'success' && data['data'] != null) {
          return List<Map<String, dynamic>>.from(data['data']);
        }
      } else if (response.statusCode == 401) {
        await logout();
      }
    } catch (e) {
      debugPrint('Fetch History Error: $e');
    }
    return [];
  }

  Future<bool> testConnection() async {
    if (_baseUrl == null) return false;
    try {
      final response = await _client
          .get(Uri.parse('$_baseUrl/api/check-status.php'), headers: _buildHeaders())
          .timeout(const Duration(seconds: 10));
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('Test Connection Error: $e');
      return false;
    }
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
          'devices_list': data['devices_list'],
        });

        // Emulate Emergency Event if recent
        final isRecent = data['is_recent'] == true;
        final lastEvent = data['last_event'];
        if (isRecent && lastEvent != null) {
          final key = lastEvent.toString();
          if (_lastEmergencyEvent != key) {
            _lastEmergencyEvent = key;
            onEvent({
              'type': 'emergency',
              'is_recent': true,
              'last_event': lastEvent,
              'emergency_device': data['emergency_device'],
            });
          }
        }
      }
    });
  }

  void unsubscribe() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
  }

  Map<String, String> _buildHeaders() {
    final headers = <String, String>{'Content-Type': 'application/json'};
    if (_sessionId != null && _sessionId!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $_sessionId';
      if (!isWebHttpClient && _cookie != null && _cookie!.isNotEmpty) {
        headers['Cookie'] = _cookie!;
      }
      return headers;
    }

    if (!isWebHttpClient && _cookie != null && _cookie!.isNotEmpty) {
      headers['Cookie'] = _cookie!;
    }
    return headers;
  }

  String? _extractSessionCookie(String? rawCookie) {
    if (rawCookie == null || rawCookie.isEmpty) return null;
    final cookies = rawCookie.split(',');
    for (final c in cookies) {
      final trimmed = c.trimLeft();
      if (trimmed.startsWith('PHPSESSID=')) {
        final idx = trimmed.indexOf(';');
        return idx == -1 ? trimmed : trimmed.substring(0, idx);
      }
    }
    final first = rawCookie.trimLeft();
    final idx = first.indexOf(';');
    return idx == -1 ? first : first.substring(0, idx);
  }

  String _normalizeBaseUrl(String url) {
    var v = url.trim();
    while (v.endsWith('/')) {
      v = v.substring(0, v.length - 1);
    }
    return v;
  }
}
