#include <WiFi.h>
#include <HTTPClient.h>
#include <math.h>

// ==========================================
// 1. CONFIGURATION (ตั้งค่าระบบ)
// ==========================================

// WiFi Settings (แก้ไขชื่อและรหัสผ่าน WiFi ที่นี่)
const char* WIFI_SSID = "YOUR_WIFI_SSID";      // ใส่ชื่อ WiFi
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";  // ใส่รหัสผ่าน WiFi

// API Settings (ตั้งค่าการเชื่อมต่อ Server)
// ตรวจสอบ IP Address ของเครื่องคอมพิวเตอร์ที่รัน ResQTech (ใช้คำสั่ง ipconfig ใน cmd)
const char* SERVER_URL = "http://192.168.1.111/ResQtech/api/esp32-receiver.php"; 
const char* API_KEY    = "YOUR_ESP32_API_KEY"; // ต้องตรงกับค่า ESP32_API_KEY ในไฟล์ .env

// Device Information (ข้อมูลประจำอุปกรณ์)
const char* DEVICE_ID  = "ESP32-001";
const char* LOCATION   = "Main Entrance";

// Hardware Settings
const int BUTTON_PIN   = 0;  // GPIO 0 คือปุ่ม BOOT บนบอร์ด ESP32 ส่วนใหญ่ (Active Low)
const int LED_PIN      = 2;  // GPIO 2 คือไฟ LED บนบอร์ด (Active High ในบางรุ่น)

// Timing Configuration
const unsigned long HEARTBEAT_INTERVAL = 10000; // ส่ง Heartbeat ทุก 10 วินาที
const unsigned long DEBOUNCE_DELAY     = 300;   // ป้องกันการกดปุ่มซ้ำ (ms)

// Debug / Performance Test Mode
const bool PERF_TEST_MODE = false; // true เฉพาะตอนต้องการโหมดทดสอบ
const int PERF_TEST_ROUNDS = 10;
const unsigned long PERF_TEST_DELAY_MS = 1000;
const unsigned long PERF_TEST_HOLD_MS = 2500; // กดค้างเพื่อเข้าโหมดทดสอบ (ms)

// ==========================================
// 2. GLOBAL VARIABLES
// ==========================================
unsigned long lastHeartbeatTime = 0;
unsigned long lastButtonPress = 0;
bool lastButtonState = HIGH;
bool perfRunning = false;
unsigned long buttonPressStart = 0;
bool buttonIsDown = false;
HTTPClient http; // Reuse HTTP Client

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  Serial.println("\n\n--- ResQTech ESP32 Firmware Starting ---");
  Serial.println("Device ID: " + String(DEVICE_ID));
  Serial.println("Server: " + String(SERVER_URL));
  
  // Hardware Init
  pinMode(BUTTON_PIN, INPUT_PULLUP); // ใช้ Internal Pull-up Resistor
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW); // เริ่มต้นปิดไฟ
  
  // Connect WiFi
  connectWiFi();
}

void loop() {
  // 1. Auto Reconnect WiFi
  if (WiFi.status() != WL_CONNECTED) {
    digitalWrite(LED_PIN, LOW); // ปิดไฟถ้าเน็ตหลุด
    connectWiFi();
  } else {
    // ไฟติดค้างเมื่อเชื่อมต่อ WiFi ได้
    // หรือจะเลือกให้กระพริบตาม Heartbeat ก็ได้ (ในที่นี้เลือกให้ติดไว้เพื่อบอกสถานะ Online)
    // digitalWrite(LED_PIN, HIGH); 
  }

  // 2. Handle Emergency Button (Priority High)
  handleButton();

  // 3. Handle Heartbeat (Routine)
  if (!perfRunning && (millis() - lastHeartbeatTime) > HEARTBEAT_INTERVAL) {
    if (WiFi.status() == WL_CONNECTED) {
      Serial.println("[HEARTBEAT] Sending status...");
      digitalWrite(LED_PIN, !digitalRead(LED_PIN)); // กระพริบไฟสั้นๆ
      sendRequest("heartbeat", 0);
      digitalWrite(LED_PIN, !digitalRead(LED_PIN)); // คืนค่าไฟ
      lastHeartbeatTime = millis();
    }
  }
}

// ==========================================
// 3. CORE FUNCTIONS
// ==========================================

void handleButton() {
  int reading = digitalRead(BUTTON_PIN);

  // กดลง (Active Low: กดแล้วเป็น LOW)
  if (reading == LOW && lastButtonState == HIGH) {
    if ((millis() - lastButtonPress) > DEBOUNCE_DELAY) {
      buttonPressStart = millis();
      buttonIsDown = true;
      lastButtonPress = millis();
    }
  }

  // ปล่อยปุ่ม (กลับเป็น HIGH)
  if (reading == HIGH && lastButtonState == LOW && buttonIsDown) {
    unsigned long holdMs = millis() - buttonPressStart;
    buttonIsDown = false;

    Serial.println("\n\n[EMERGENCY] Button Released");
    Serial.println("Hold ms: " + String(holdMs));

    for (int i = 0; i < 3; i++) {
      digitalWrite(LED_PIN, HIGH); delay(60);
      digitalWrite(LED_PIN, LOW); delay(60);
    }
    digitalWrite(LED_PIN, HIGH);

    if (PERF_TEST_MODE && holdMs >= PERF_TEST_HOLD_MS) {
      Serial.println("[MODE] Running Performance Test...");
      runEmergencyPerfTest(PERF_TEST_ROUNDS);
    } else {
      Serial.println("[MODE] Sending Single Emergency Alert...");
      String resp = sendRequest("emergency", 1);
      Serial.println("Server Response: " + resp);
    }

    digitalWrite(LED_PIN, LOW);
  }

  lastButtonState = reading;
}

void connectWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(WIFI_SSID);
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    digitalWrite(LED_PIN, !digitalRead(LED_PIN)); // กระพริบไฟระว่างรอ
    attempts++;
    
    if(attempts > 30) { // 15 วินาที
      Serial.println("\n[ERROR] WiFi Connect Failed. Restarting in 3 seconds...");
      delay(3000);
      ESP.restart(); // รีเซ็ตตัวเองถ้านานเกินไป
    }
  }
  
  digitalWrite(LED_PIN, HIGH); // ติดค้าง
  Serial.println("\n[SUCCESS] WiFi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.print("RSSI: ");
  Serial.println(WiFi.RSSI());
}

String sendRequest(String action, int seq) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");
    
    // Construct JSON Payload
    String jsonPayload = "{";
    jsonPayload += "\"key\":\"" + String(API_KEY) + "\",";
    jsonPayload += "\"action\":\"" + action + "\",";
    jsonPayload += "\"seq\":" + String(seq) + ",";
    jsonPayload += "\"device_id\":\"" + String(DEVICE_ID) + "\",";
    jsonPayload += "\"location\":\"" + String(LOCATION) + "\",";
    jsonPayload += "\"uptime_ms\":" + String(millis());
    jsonPayload += "}";

    unsigned long t0 = millis();
    int httpResponseCode = http.POST(jsonPayload);
    unsigned long t1 = millis();

    String response = "Error";
    if (httpResponseCode > 0) {
      response = http.getString();
      Serial.println("Response (" + String(httpResponseCode) + "): " + response + " | RTT: " + String(t1 - t0) + "ms");
    } else {
      Serial.print("Error on sending POST: ");
      Serial.println(httpResponseCode);
      Serial.println("Check Server URL or WiFi Connection");
    }
    
    http.end();
    return response;
  } else {
    Serial.println("Error: WiFi Disconnected");
    return "WiFi Disconnected";
  }
}

// ==========================================
// 4. UTILS & PERFORMANCE TESTING
// ==========================================

long extractIntField(const String& json, const char* key) {
  String pattern = String("\"") + key + "\":";
  int idx = json.indexOf(pattern);
  if (idx < 0) return -1;
  idx += pattern.length();
  
  // Skip whitespace
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

void runEmergencyPerfTest(int rounds) {
  perfRunning = true;
  Serial.println("--- Starting Performance Test ---");
  
  for (int i = 0; i < rounds; i++) {
    unsigned long t0 = millis();
    String resp = sendRequest("emergency", i + 1);
    unsigned long t1 = millis();
    
    // ดึงค่า Latency จาก Server Response มาแสดง
    long serverTotalMs = extractIntField(resp, "server_total_ms");
    
    if(serverTotalMs >= 0) {
      Serial.println("Round " + String(i+1) + ": RTT=" + String(t1-t0) + "ms | ServerProcess=" + String(serverTotalMs) + "ms");
    }
    
    delay(PERF_TEST_DELAY_MS);
  }
  
  Serial.println("--- Test Completed ---");
  perfRunning = false;
}
