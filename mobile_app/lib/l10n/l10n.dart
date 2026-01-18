import 'package:flutter/widgets.dart';

@immutable
class L10n {
  final Locale locale;
  const L10n(this.locale);

  static const supportedLocales = <Locale>[Locale('th'), Locale('en')];

  static L10n of(BuildContext context) {
    final l10n = Localizations.of<L10n>(context, L10n);
    assert(l10n != null, 'L10n not found. Did you add L10nDelegate?');
    return l10n!;
  }

  static const _th = <String, String>{
    'app_name': 'ResQtech',
    'nav_dashboard': 'แดชบอร์ด',
    'nav_history': 'ประวัติ',
    'nav_settings': 'ตั้งค่า',
    'login_title': 'เข้าสู่ระบบ',
    'login_subtitle': 'Emergency Notification System',
    'server_url': 'Server URL',
    'username': 'ชื่อผู้ใช้',
    'password': 'รหัสผ่าน',
    'sign_in': 'เข้าสู่ระบบ',
    'login_failed': 'เข้าสู่ระบบไม่สำเร็จ กรุณาตรวจสอบข้อมูล',
    'invalid_url': 'กรุณากรอก URL ให้ถูกต้อง',
    'required_field': 'จำเป็นต้องกรอก',
    'system_online': 'ระบบออนไลน์',
    'system_offline': 'ระบบออฟไลน์',
    'last_heartbeat': 'Heartbeat ล่าสุด',
    'last_event': 'เหตุการณ์ล่าสุด',
    'emergency': 'ฉุกเฉิน!',
    'incident_at': 'พบเหตุการณ์เวลา',
    'pull_to_refresh': 'ดึงเพื่อรีเฟรช',
    'activity_log': 'บันทึกกิจกรรม',
    'no_recent_activity': 'ไม่มีรายการล่าสุด',
    'refresh': 'รีเฟรช',
    'filter': 'กรอง',
    'device': 'อุปกรณ์',
    'location': 'สถานที่',
    'status': 'สถานะ',
    'online': 'ออนไลน์',
    'offline': 'ออฟไลน์',
    'connected': 'เชื่อมต่อ',
    'disconnected': 'ไม่เชื่อมต่อ',
    'settings': 'ตั้งค่า',
    'base_url': 'Base URL',
    'save': 'บันทึก',
    'test_connection': 'ทดสอบการเชื่อมต่อ',
    'theme': 'ธีม',
    'theme_system': 'ตามระบบ',
    'theme_light': 'สว่าง',
    'theme_dark': 'มืด',
    'language': 'ภาษา',
    'about': 'เกี่ยวกับแอป',
    'logout': 'ออกจากระบบ',
    'administrator': 'ผู้ดูแลระบบ',
    'loading': 'กำลังโหลด...',
    'fetch_failed': 'ดึงข้อมูลไม่สำเร็จ',
    'no_data': 'ไม่มีข้อมูล',
    'retry': 'ลองใหม่',
    'all': 'ทั้งหมด',
    'alert': 'แจ้งเตือน',
    'normal': 'ปกติ',
  };

  static const _en = <String, String>{
    'app_name': 'ResQtech',
    'nav_dashboard': 'Dashboard',
    'nav_history': 'History',
    'nav_settings': 'Settings',
    'login_title': 'Sign in',
    'login_subtitle': 'Emergency Notification System',
    'server_url': 'Server URL',
    'username': 'Username',
    'password': 'Password',
    'sign_in': 'Sign In',
    'login_failed': 'Login failed. Please check your credentials.',
    'invalid_url': 'Please enter a valid URL',
    'required_field': 'Required',
    'system_online': 'System Online',
    'system_offline': 'System Offline',
    'last_heartbeat': 'Last Heartbeat',
    'last_event': 'Last Event',
    'emergency': 'EMERGENCY!',
    'incident_at': 'Incident at',
    'pull_to_refresh': 'Pull to refresh',
    'activity_log': 'Activity Log',
    'no_recent_activity': 'No recent activity',
    'refresh': 'Refresh',
    'filter': 'Filter',
    'device': 'Device',
    'location': 'Location',
    'status': 'Status',
    'online': 'ONLINE',
    'offline': 'OFFLINE',
    'connected': 'CONNECTED',
    'disconnected': 'DISCONNECTED',
    'settings': 'Settings',
    'base_url': 'Base URL',
    'save': 'Save',
    'test_connection': 'Test connection',
    'theme': 'Theme',
    'theme_system': 'System',
    'theme_light': 'Light',
    'theme_dark': 'Dark',
    'language': 'Language',
    'about': 'About',
    'logout': 'Log out',
    'administrator': 'Administrator',
    'loading': 'Loading...',
    'fetch_failed': 'Fetch failed',
    'no_data': 'No data',
    'retry': 'Retry',
    'all': 'All',
    'alert': 'Alert',
    'normal': 'Normal',
  };

  String t(String key) {
    final lang = locale.languageCode == 'en' ? _en : _th;
    return lang[key] ?? key;
  }
}

class L10nDelegate extends LocalizationsDelegate<L10n> {
  const L10nDelegate();

  @override
  bool isSupported(Locale locale) =>
      locale.languageCode == 'th' || locale.languageCode == 'en';

  @override
  Future<L10n> load(Locale locale) async => L10n(locale);

  @override
  bool shouldReload(covariant LocalizationsDelegate<L10n> old) => false;
}

