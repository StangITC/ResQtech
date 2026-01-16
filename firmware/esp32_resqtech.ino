#include <WiFi.h>
#include <HTTPClient.h>

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

// ==========================================
// 2. VARIABLES
// ==========================================
unsigned long lastHeartbeatTime = 0;
unsigned long lastButtonPress = 0;
bool lastButtonState = HIGH;

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
      sendRequest("emergency");
      lastButtonPress = millis();
    }
  }
  lastButtonState = reading;

  // 3. Handle Heartbeat
  if ((millis() - lastHeartbeatTime) > HEARTBEAT_INTERVAL) {
    sendRequest("heartbeat");
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

void sendRequest(String action) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");

    // Construct JSON Payload manually to avoid dependency
    String jsonPayload = "{";
    jsonPayload += "\"key\":\"" + String(API_KEY) + "\",";
    jsonPayload += "\"action\":\"" + action + "\",";
    jsonPayload += "\"device_id\":\"" + String(DEVICE_ID) + "\",";
    jsonPayload += "\"location\":\"" + String(LOCATION) + "\"";
    jsonPayload += "}";

    Serial.print("Sending " + action + "... ");
    
    // Blink LED fast to indicate sending
    digitalWrite(LED_PIN, LOW); 
    int httpResponseCode = http.POST(jsonPayload);
    digitalWrite(LED_PIN, HIGH);

    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Success (" + String(httpResponseCode) + ")");
      Serial.println("Response: " + response);
    } else {
      Serial.print("Error code: ");
      Serial.println(httpResponseCode);
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected");
  }
}
