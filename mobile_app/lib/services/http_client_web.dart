import 'package:http/browser_client.dart';
import 'package:http/http.dart' as http;

http.Client createHttpClientImpl() {
  final c = BrowserClient()..withCredentials = true;
  return c;
}

bool get isWebHttpClientImpl => true;

