import 'package:http/http.dart' as http;

import 'http_client_impl.dart';

http.Client createHttpClient() => createHttpClientImpl();

bool get isWebHttpClient => isWebHttpClientImpl;

