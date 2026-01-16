#include <WiFi.h>
#include <HTTPClient.h>
#include <math.h>

// ==========================================
// 1. CONFIGURATION
// ==========================================

// WiFi Settings
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";

// API Settings
const char* SERVER_URL = "http://192.168.1.100/ResQtech/api/esp32-receiver.php"; // แก้ IP เป็นของ Server
const char* API_KEY    = "YOUR_ESP32_API_KEY"; // ต้องตรงกับค่าใน .env (ESP32_API_KEY)

// Device Settings
const char* DEVICE_ID  = "ESP32-001";
const char* LOCATION   = "Main Entrance";

// Pin Definitions
const int BUTTON_PIN   = 0;  // ปุ่ม Boot หรือต่อแยก (Active Low)
const int LED_PIN      = 2;  // Built-in LED

// Timing (Milliseconds)
const unsigned long HEARTBEAT_INTERVAL = 10000; // ส่ง Heartbeat ทุก 10 วินาที
const unsigned long DEBOUNCE_DELAY     = 200;   // ป้องกันการกดซ้ำ

const bool PERF_TEST_MODE = true;
const int PERF_TEST_ROUNDS = 10;
const unsigned long PERF_TEST_DELAY_MS = 1200;

// ==========================================
// 2. VARIABLES
// ==========================================
unsigned long lastHeartbeatTime = 0;
unsigned long lastButtonPress = 0;
bool lastButtonState = HIGH;
bool perfRunning = false;

void setup() {
  Serial.begin(115200);
  
  pinMode(BUTTON_PIN, INPUT_PULLUP);
  pinMode(LED_PIN, OUTPUT);
  
  // Connect to WiFi
  connectWiFi();
}

void loop() {
  // 1. Check WiFi Connection
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  // 2. Handle Button Press (Emergency)
  int reading = digitalRead(BUTTON_PIN);
  if (reading == LOW && lastButtonState == HIGH) { // Button Pressed
    if ((millis() - lastButtonPress) > DEBOUNCE_DELAY) {
      Serial.println("\n[EMERGENCY] Button Pressed!");
      if (PERF_TEST_MODE) {
        runEmergencyPerfTest(PERF_TEST_ROUNDS);
      } else {
        sendRequest("emergency", 0);
      }
      lastButtonPress = millis();
    }
  }
  lastButtonState = reading;

  // 3. Handle Heartbeat
  if (!perfRunning && (millis() - lastHeartbeatTime) > HEARTBEAT_INTERVAL) {
    sendRequest("heartbeat", 0);
    lastHeartbeatTime = millis();
  }
}

// ==========================================
// 3. FUNCTIONS
// ==========================================

void connectWiFi() {
  Serial.print("Connecting to WiFi");
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    digitalWrite(LED_PIN, !digitalRead(LED_PIN)); // Blink LED while connecting
    attempts++;
    if(attempts > 20) {
      Serial.println("\nWifi Connect Failed! Retrying...");
      return;
    }
  }
  
  digitalWrite(LED_PIN, HIGH); // LED ON when connected
  Serial.println("\nWiFi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

long extractIntField(const String& json, const char* key) {
  String pattern = String("\"") + key + "\":";
  int idx = json.indexOf(pattern);
  if (idx < 0) return -1;
  idx += pattern.length();
  while (idx < (int)json.length() && (json[idx] == ' ' || json[idx] == '\"')) idx++;

  bool neg = false;
  if (idx < (int)json.length() && json[idx] == '-') {
    neg = true;
    idx++;
  }

  long value = 0;
  bool hasDigit = false;
  while (idx < (int)json.length()) {
    char c = json[idx];
    if (c < '0' || c > '9') break;
    hasDigit = true;
    value = value * 10 + (c - '0');
    idx++;
  }
  if (!hasDigit) return -1;
  return neg ? -value : value;
}

void sortUL(unsigned long* arr, int n) {
  for (int i = 1; i < n; i++) {
    unsigned long key = arr[i];
    int j = i - 1;
    while (j >= 0 && arr[j] > key) {
      arr[j + 1] = arr[j];
      j--;
    }
    arr[j + 1] = key;
  }
}

void sortL(long* arr, int n) {
  for (int i = 1; i < n; i++) {
    long key = arr[i];
    int j = i - 1;
    while (j >= 0 && arr[j] > key) {
      arr[j + 1] = arr[j];
      j--;
    }
    arr[j + 1] = key;
  }
}

float avgUL(const unsigned long* arr, int n) {
  if (n <= 0) return 0.0f;
  unsigned long sum = 0;
  for (int i = 0; i < n; i++) sum += arr[i];
  return (float)sum / (float)n;
}

float avgL(const long* arr, int n) {
  if (n <= 0) return 0.0f;
  long sum = 0;
  for (int i = 0; i < n; i++) sum += arr[i];
  return (float)sum / (float)n;
}

unsigned long pUL(unsigned long* arr, int n, float p) {
  if (n <= 0) return 0;
  sortUL(arr, n);
  int rank = (int)ceil(p * n);
  int idx = rank - 1;
  if (idx < 0) idx = 0;
  if (idx >= n) idx = n - 1;
  return arr[idx];
}

long pL(long* arr, int n, float p) {
  if (n <= 0) return 0;
  sortL(arr, n);
  int rank = (int)ceil(p * n);
  int idx = rank - 1;
  if (idx < 0) idx = 0;
  if (idx >= n) idx = n - 1;
  return arr[idx];
}

String sendRequest(String action, int seq) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");

    String jsonPayload = "{";
    jsonPayload += "\"key\":\"" + String(API_KEY) + "\",";
    jsonPayload += "\"action\":\"" + action + "\",";
    jsonPayload += "\"seq\":" + String(seq) + ",";
    jsonPayload += "\"device_id\":\"" + String(DEVICE_ID) + "\",";
    jsonPayload += "\"location\":\"" + String(LOCATION) + "\",";
    jsonPayload += "\"client_uptime_ms\":" + String(millis());
    jsonPayload += "}";

    Serial.print("Sending " + action + " seq=" + String(seq) + "... ");
    digitalWrite(LED_PIN, LOW);
    unsigned long t0 = millis();
    int httpResponseCode = http.POST(jsonPayload);
    unsigned long t1 = millis();
    digitalWrite(LED_PIN, HIGH);

    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Success (" + String(httpResponseCode) + ") RTT_ms=" + String(t1 - t0));
      http.end();
      return response;
    } else {
      Serial.print("Error code: ");
      Serial.println(httpResponseCode);
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected");
  }
  return "";
}

void runEmergencyPerfTest(int rounds) {
  perfRunning = true;
  lastHeartbeatTime = millis();

  if (rounds <= 0) {
    perfRunning = false;
    return;
  }

  unsigned long rttMs[PERF_TEST_ROUNDS];
  long serverMs[PERF_TEST_ROUNDS];
  long lineMs[PERF_TEST_ROUNDS];
  int ok = 0;

  int cappedRounds = rounds;
  if (cappedRounds > PERF_TEST_ROUNDS) cappedRounds = PERF_TEST_ROUNDS;

  for (int i = 0; i < cappedRounds; i++) {
    if (WiFi.status() != WL_CONNECTED) {
      connectWiFi();
    }

    unsigned long t0 = millis();
    String resp = sendRequest("emergency", i + 1);
    unsigned long t1 = millis();

    if (resp.length() > 0) {
      rttMs[ok] = t1 - t0;
      serverMs[ok] = extractIntField(resp, "server_total_ms");
      lineMs[ok] = extractIntField(resp, "line_api_ms");
      Serial.println("server_total_ms=" + String(serverMs[ok]) + " line_api_ms=" + String(lineMs[ok]));
      ok++;
    }

    delay(PERF_TEST_DELAY_MS);
  }

  Serial.println("\n[PERF] Summary");
  Serial.println("ok=" + String(ok) + " rounds=" + String(cappedRounds));

  if (ok > 0) {
    unsigned long rttCopy[PERF_TEST_ROUNDS];
    long serverCopy[PERF_TEST_ROUNDS];
    long lineCopy[PERF_TEST_ROUNDS];
    for (int i = 0; i < ok; i++) {
      rttCopy[i] = rttMs[i];
      serverCopy[i] = serverMs[i];
      lineCopy[i] = lineMs[i];
    }

    sortUL(rttCopy, ok);
    sortL(serverCopy, ok);
    sortL(lineCopy, ok);

    Serial.println("RTT_ms min=" + String(rttCopy[0]) + " p50=" + String(pUL(rttCopy, ok, 0.50f)) + " p95=" + String(pUL(rttCopy, ok, 0.95f)) + " max=" + String(rttCopy[ok - 1]) + " avg=" + String(avgUL(rttMs, ok), 1));
    Serial.println("server_total_ms min=" + String(serverCopy[0]) + " p50=" + String(pL(serverCopy, ok, 0.50f)) + " p95=" + String(pL(serverCopy, ok, 0.95f)) + " max=" + String(serverCopy[ok - 1]) + " avg=" + String(avgL(serverMs, ok), 1));
    Serial.println("line_api_ms min=" + String(lineCopy[0]) + " p50=" + String(pL(lineCopy, ok, 0.50f)) + " p95=" + String(pL(lineCopy, ok, 0.95f)) + " max=" + String(lineCopy[ok - 1]) + " avg=" + String(avgL(lineMs, ok), 1));
  }

  perfRunning = false;
}
