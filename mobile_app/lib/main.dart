import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import 'services/api_service.dart';
import 'screens/login_screen.dart';
import 'screens/main_screen.dart';
import 'state/app_settings.dart';
import 'theme/app_theme.dart';
import 'l10n/l10n.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {}
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final apiService = ApiService();
  await apiService.init();
  final appSettings = AppSettings();
  await appSettings.init();
  await _initPushNotifications(apiService);

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => apiService),
        ChangeNotifierProvider(create: (_) => appSettings),
      ],
      child: const ResQtechApp(),
    ),
  );
}

Future<void> _initPushNotifications(ApiService apiService) async {
  if (kIsWeb) return;

  try {
    await Firebase.initializeApp();
  } catch (_) {
    return;
  }

  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

  try {
    await Permission.notification.request();
  } catch (_) {}

  try {
    final token = await FirebaseMessaging.instance.getToken();
    await apiService.setPushToken(token);
    FirebaseMessaging.instance.onTokenRefresh.listen(apiService.setPushToken);
  } catch (_) {}
}

class ResQtechApp extends StatelessWidget {
  const ResQtechApp({super.key});

  @override
  Widget build(BuildContext context) {
    final settings = context.watch<AppSettings>();
    return MaterialApp(
      title: 'ResQtech',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme(),
      darkTheme: AppTheme.darkTheme(),
      themeMode: settings.themeMode,
      locale: settings.locale,
      supportedLocales: L10n.supportedLocales,
      localizationsDelegates: const [
        L10nDelegate(),
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: Consumer<ApiService>(
        builder: (context, api, child) {
          return api.isLoggedIn ? const MainScreen() : const LoginScreen();
        },
      ),
    );
  }
}
