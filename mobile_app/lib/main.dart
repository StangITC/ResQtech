import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'services/api_service.dart';
import 'screens/login_screen.dart';
import 'screens/main_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final apiService = ApiService();
  await apiService.init();

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => apiService),
      ],
      child: const ResQtechApp(),
    ),
  );
}

class ResQtechApp extends StatelessWidget {
  const ResQtechApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'ResQtech',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1E88E5)),
        useMaterial3: true,
        fontFamily: 'Poppins', // If you add font assets, otherwise it defaults
      ),
      home: Consumer<ApiService>(
        builder: (context, api, child) {
          return api.isLoggedIn ? const MainScreen() : const LoginScreen();
        },
      ),
    );
  }
}
