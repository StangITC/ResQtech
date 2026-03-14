# 🚀 ResQTech — Tech Stack & System Architecture

> **ระบบแจ้งเตือนฉุกเฉินอัจฉริยะ ผ่าน ESP32 + LINE Messaging API**
> Repository: [StangITC/ResQtech](https://github.com/StangITC/ResQtech)

---

## 📌 ภาพรวมระบบ (System Overview)

ResQTech เป็นระบบ **IoT Emergency Notification** ที่ออกแบบมาเพื่อให้กดปุ่มฉุกเฉินบนอุปกรณ์ ESP32 แล้วแจ้งเตือนไปยังผู้ดูแลผ่านหลายช่องทาง (LINE, Push Notification, Web Dashboard) ได้ภายใน **ไม่กี่วินาที**

```mermaid
graph LR
    subgraph "🔧 Hardware Layer"
        ESP["🟢 ESP32<br/>Microcontroller"]
        BTN["🔴 Emergency<br/>Button"]
        LED["💡 Status LED"]
    end

    subgraph "☁️ Backend Server"
        API["📡 PHP API<br/>esp32-receiver.php"]
        LOG["📁 File-based<br/>Logging System"]
        AUTH["🔐 Auth &<br/>Security Layer"]
    end

    subgraph "📢 Notification Channels"
        LINE["💬 LINE<br/>Messaging API"]
        FCM["🔔 Firebase<br/>Cloud Messaging"]
    end

    subgraph "🖥️ Frontend Clients"
        WEB["🌐 Web Dashboard<br/>Neo-Brutalism UI"]
        APP["📱 Flutter<br/>Mobile App"]
    end

    BTN -->|"กดปุ่ม"| ESP
    ESP -->|"WiFi HTTP POST"| API
    API --> LOG
    API --> LINE
    API --> FCM
    LED ---|"แสดงสถานะ"| ESP
    LOG -->|"อ่าน Log"| WEB
    FCM -->|"Push"| APP
    WEB ---|"SSE Stream"| API
    APP ---|"REST API"| AUTH
```

---

## 🧱 Tech Stack (เทคโนโลยีทั้งหมดที่ใช้)

### แบ่งตาม Layer

```mermaid
block-beta
    columns 4
    
    block:hw:1
        columns 1
        A["🔧 Hardware"]
        B["ESP32"]
        C["C/C++"]
        D["Arduino IDE"]
    end
    
    block:be:1
        columns 1
        E["⚙️ Backend"]
        F["PHP 8.0+"]
        G["Apache"]
        H["File-based Storage"]
    end
    
    block:fe:1
        columns 1
        I["🎨 Frontend"]
        J["HTML5 / CSS3"]
        K["Vanilla JS"]
        L["Chart.js"]
    end
    
    block:mb:1
        columns 1
        M["📱 Mobile"]
        N["Flutter (Dart)"]
        O["Firebase FCM"]
        P["Google Sign-In"]
    end
```

### ตารางสรุป Tech Stack

| Layer | Technology | Version / Note | หน้าที่ |
|-------|-----------|---------------|--------|
| **Hardware** | ESP32 (Espressif) | DevKit v1 | ไมโครคอนโทรลเลอร์หลัก รองรับ WiFi ในตัว |
| **Hardware** | Arduino Framework | C/C++ | เขียน Firmware ควบคุมปุ่ม/LED/ส่งข้อมูล |
| **Backend** | PHP | 8.0+ | ภาษาหลักฝั่ง Server ประมวลผล Request |
| **Backend** | Apache | + mod_rewrite | Web Server ให้บริการ HTTP |
| **Backend** | Laragon | Local Dev | จำลอง Server สำหรับพัฒนาบน Windows |
| **Backend** | File-based Storage | `.log` / `.jsonl` | เก็บข้อมูลแทน Database เพื่อ Latency ต่ำ |
| **Frontend** | HTML5 + CSS3 | Vanilla | โครงสร้างและสไตล์หน้าเว็บ |
| **Frontend** | JavaScript | Vanilla (No Framework) | Logic ฝั่ง Client, Charts, SSE |
| **Frontend** | Chart.js | via CDN | วาดกราฟสถิติบน Dashboard |
| **Frontend** | PWA | Service Worker | ติดตั้งเว็บเป็น App ได้ |
| **Mobile** | Flutter | Dart | สร้าง App iOS/Android จาก Codebase เดียว |
| **Mobile** | Firebase FCM | v1 API | Push Notification แจ้งเตือนเด้งมือถือ |
| **Integration** | LINE Messaging API | Push Message | ส่งข้อความแจ้งเตือนเข้า LINE |
| **Integration** | Google OAuth 2.0 | OpenID Connect | Login ด้วย Google Account |
| **Design** | Neo-Brutalism | Custom CSS | ดีไซน์ระบบขอบหนา สีเจ็บ ใช้ดูง่าย |
| **Typography** | Inter, Space Grotesk, JetBrains Mono, Noto Sans Thai | Google Fonts | ฟอนต์ที่ใช้ทั้งภาษาไทยและอังกฤษ |

---

## 📂 โครงสร้างโปรเจกต์ (Project Structure)

```mermaid
graph TD
    ROOT["📁 ResQtechApp/"]

    ROOT --> API_DIR["📂 api/<br/><i>API Endpoints ทั้งหมด</i>"]
    ROOT --> ASSETS["📂 assets/<br/><i>CSS, JS</i>"]
    ROOT --> CONFIG["📂 config/<br/><i>config.php + .env loader</i>"]
    ROOT --> FIRMWARE["📂 firmware/<br/><i>ESP32 Arduino Code</i>"]
    ROOT --> INCLUDES["📂 includes/<br/><i>Core PHP Functions</i>"]
    ROOT --> MOBILE["📂 mobile_app/<br/><i>Flutter App</i>"]
    ROOT --> LOGS["📂 logs/<br/><i>Log Files (auto-created)</i>"]
    ROOT --> PAGES["📄 PHP Pages<br/><i>dashboard, login, etc.</i>"]

    API_DIR --> A1["esp32-receiver.php"]
    API_DIR --> A2["stream.php (SSE)"]
    API_DIR --> A3["check-status.php"]
    API_DIR --> A4["get-history.php"]
    API_DIR --> A5["mobile-login.php"]
    API_DIR --> A6["send-notification.php"]
    API_DIR --> A7["perf-report.php"]
    API_DIR --> A8["register-fcm-token.php"]

    INCLUDES --> I1["init.php (Bootstrap)"]
    INCLUDES --> I2["functions.php (Core)"]
    INCLUDES --> I3["auth.php"]
    INCLUDES --> I4["lang.php (i18n)"]
    INCLUDES --> I5["google-oauth.php"]
    INCLUDES --> I6["navigation.php"]
```

### ไฟล์สำคัญและหน้าที่

| ไฟล์ | หน้าที่ |
|------|--------|
| [.env](file:///c:/laragon/www/.env) | เก็บ Secrets ทั้งหมด (API Keys, Passwords) |
| [config/config.php](file:///c:/laragon/www/config/config.php) | โหลด `.env` → กำหนดค่าคงที่ (Constants) ให้ PHP ใช้ |
| [includes/init.php](file:///c:/laragon/www/includes/init.php) | Bootstrap: โหลด Config → Functions → Auth → Session → Lang |
| [includes/functions.php](file:///c:/laragon/www/includes/functions.php) | ฟังก์ชันแกนกลาง: Logging, LINE API, FCM, Rate Limit, Security |
| [includes/auth.php](file:///c:/laragon/www/includes/auth.php) | Session Management, Login/Logout, CSRF, Brute Force Protection |
| [includes/lang.php](file:///c:/laragon/www/includes/lang.php) | ระบบ Multi-language (TH/EN) |
| [api/esp32-receiver.php](file:///c:/laragon/www/api/esp32-receiver.php) | **API หลัก** รับข้อมูลจาก ESP32 (Heartbeat + Emergency) |
| [api/stream.php](file:///c:/laragon/www/api/stream.php) | SSE (Server-Sent Events) สำหรับ Real-time Dashboard |
| [firmware/esp32_resqtech.ino](file:///c:/laragon/www/firmware/esp32_resqtech.ino) | **Firmware ESP32** ส่ง Heartbeat + Emergency ผ่าน HTTP |
| [dashboard.php](file:///c:/laragon/www/dashboard.php) | หน้า Dashboard แสดงสถิติและกราฟ |
| [control-room.php](file:///c:/laragon/www/control-room.php) | หน้า War Room ศูนย์บัญชาการ |

---

## ⚡ การทำงานอย่างละเอียด (Detailed Workflow)

### 1️⃣ Boot Sequence — เมื่อเปิดเครื่อง ESP32

```mermaid
sequenceDiagram
    participant ESP as 🟢 ESP32
    participant WiFi as 📶 WiFi Router
    participant Server as ⚙️ PHP Server

    Note over ESP: เปิดเครื่อง / รีเซ็ต
    ESP->>ESP: Serial.begin(115200)
    ESP->>ESP: pinMode(BUTTON=GPIO0, INPUT_PULLUP)
    ESP->>ESP: pinMode(LED=GPIO2, OUTPUT)

    ESP->>WiFi: WiFi.begin(SSID, PASSWORD)
    Note over ESP,WiFi: LED กระพริบระหว่างรอ

    alt เชื่อมต่อสำเร็จ (< 15 วินาที)
        WiFi-->>ESP: Connected! ได้รับ IP
        Note over ESP: LED ติดค้าง ✅
        ESP->>ESP: เริ่ม loop() หลัก
    else เชื่อมต่อไม่ได้ (> 15 วิ)
        Note over ESP: ESP.restart() 🔄
    end
```

### 2️⃣ Heartbeat — ตรวจสอบสถานะต่อเนื่อง

```mermaid
sequenceDiagram
    participant ESP as 🟢 ESP32
    participant API as ⚙️ esp32-receiver.php
    participant Log as 📁 heartbeat.log
    participant Dash as 🖥️ Dashboard

    loop ทุก 5 วินาที
        ESP->>ESP: LED Toggle (กระพริบ)
        ESP->>API: POST { action: "heartbeat", key, device_id, location }

        API->>API: ✅ ตรวจ API Key (hash_equals)
        API->>API: ✅ ตรวจ Rate Limit (60 req/min)
        API->>Log: เขียน "[timestamp] Heartbeat from ESP32-001"
        API->>API: บันทึก perf_events.jsonl (RTT stats)
        API-->>ESP: { status: "success", server_total_ms: 5 }
    end

    Dash->>Log: อ่าน heartbeat.log
    Dash->>Dash: ถ้า Last heartbeat < 30 วิ → 🟢 ONLINE
    Dash->>Dash: ถ้า Last heartbeat > 30 วิ → 🔴 OFFLINE
```

> [!NOTE]
> ระบบใช้ **ไฟล์ Log** แทน Database เพื่อให้ Write Latency ต่ำที่สุด (ไม่ต้อง Connection overhead ของ MySQL) เหมาะสำหรับระบบฉุกเฉินที่ต้องการความเร็ว

### 3️⃣ Emergency Alert — เมื่อกดปุ่มขอความช่วยเหลือ

```mermaid
sequenceDiagram
    participant User as 👤 ผู้ใช้
    participant BTN as 🔴 ปุ่มฉุกเฉิน
    participant ESP as 🟢 ESP32
    participant API as ⚙️ esp32-receiver.php
    participant Log as 📁 emergency.log
    participant LINE as 💬 LINE API
    participant FCM as 🔔 Firebase FCM
    participant Phone as 📱 มือถือผู้ดูแล
    participant Dash as 🖥️ Web Dashboard

    User->>BTN: กดปุ่มฉุกเฉิน!
    BTN->>ESP: GPIO 0 = LOW

    ESP->>ESP: Debounce (10ms)
    ESP->>ESP: LED กระพริบ 3 ครั้ง ✨
    ESP->>API: POST { action: "emergency", key, device_id, location }

    rect rgba(255, 0, 0, 0.1)
        Note over API: 🔒 Security Checks
        API->>API: 1. ตรวจ API Key (timing-safe)
        API->>API: 2. ตรวจ Rate Limit
        API->>API: 3. Sanitize Input (XSS/Injection)
    end

    API->>Log: เขียน emergency.log

    par ส่ง LINE พร้อมกัน
        API->>LINE: POST Push Message<br/>"🚨 ฉุกเฉิน! สถานที่: ... อุปกรณ์: ..."
        LINE-->>Phone: ข้อความ LINE มาถึง 💬
    and ส่ง FCM พร้อมกัน
        API->>FCM: POST v1/messages:send
        FCM-->>Phone: Push Notification เด้ง 🔔
    end

    API-->>ESP: { status: "success", server_total_ms, line_api_ms }

    Dash->>Log: SSE Stream อ่าน Log ใหม่
    Dash->>Dash: 🚨 แสดงเหตุฉุกเฉินบนหน้าจอพร้อมเสียง
```

> [!IMPORTANT]
> เมื่อเกิด Emergency ระบบจะแจ้ง **3 ช่องทางพร้อมกัน**: LINE ข้อความ, FCM Push Notification, และ Web Dashboard (SSE Live Feed) เพื่อให้มั่นใจว่าผู้ดูแลจะได้รับแจ้งเสมอ

### 4️⃣ Performance Test — โหมดทดสอบความเร็ว

```mermaid
sequenceDiagram
    participant User as 👤 ผู้ทดสอบ
    participant ESP as 🟢 ESP32
    participant API as ⚙️ PHP Server

    User->>ESP: กดปุ่มค้าง ≥ 2.5 วินาที
    Note over ESP: เข้า Performance Test Mode

    loop 10 รอบ (PERF_TEST_ROUNDS)
        ESP->>API: POST { action: "emergency", seq: i }
        API-->>ESP: { server_total_ms, line_api_ms }
        ESP->>ESP: Serial.println("Round X: RTT=__ms")
        ESP->>ESP: delay(1000ms)
    end

    Note over ESP: "--- Test Completed ---"
```

> [!TIP]
> เปิดโหมดทดสอบได้โดยตั้ง `PERF_TEST_MODE = true` ใน Firmware แล้วกดปุ่มค้าง 2.5 วินาที ระบบจะยิง 10 รอบติดต่อกันและวัด Round-Trip Time (RTT) ของแต่ละรอบ

---

## 🔐 ระบบ Security (ความปลอดภัย)

```mermaid
graph TD
    REQ["📨 Incoming Request"] --> APIKEY["🔑 API Key Validation<br/>(hash_equals — timing-safe)"]
    APIKEY -->|❌ ผิด| REJECT1["⛔ 401 Unauthorized"]
    APIKEY -->|✅ ถูก| RATE["⏱️ Rate Limiting<br/>(60 req/min per IP)"]
    RATE -->|❌ เกิน| REJECT2["⛔ 429 Too Many Requests"]
    RATE -->|✅ ผ่าน| SANITIZE["🧹 Input Sanitization<br/>(htmlspecialchars + strip_tags)"]
    SANITIZE --> PROCESS["✅ Process Request"]

    subgraph "Web Login Security"
        LOGIN["🔒 Login Form"]
        BCRYPT["🔑 Bcrypt Password Hash"]
        CSRF["🛡️ CSRF Token"]
        BRUTE["🚫 Brute Force Protection<br/>(5 ครั้ง → ล็อก 15 นาที)"]
        SESSION["🍪 Session Security<br/>(regenerate + timeout + fingerprint)"]
        HEADERS["📋 Security Headers<br/>(CSP, X-Frame, XSS-Protection)"]
    end

    LOGIN --> BCRYPT
    LOGIN --> CSRF
    LOGIN --> BRUTE
    LOGIN --> SESSION
    SESSION --> HEADERS
```

| มาตรการ | รายละเอียด |
|---------|-----------|
| **API Key** | ใช้ `hash_equals()` (Timing-safe comparison) กัน Timing Attack |
| **Rate Limiting** | จำกัด 60 requests/นาที ต่อ IP (File-based counter) |
| **Password** | Bcrypt hash (PASSWORD_DEFAULT) ไม่เก็บ Plain text |
| **CSRF** | Token-based, หมดอายุใน 10 นาที |
| **Brute Force** | ล็อกหลังผิด 5 ครั้ง → รอ 15 นาที |
| **Session** | Regenerate ID, Timeout 30 นาที, Browser Fingerprint |
| **Headers** | X-Frame-Options: DENY, CSP, XSS-Protection, Nosniff |
| **Input** | `htmlspecialchars()` + `strip_tags()` ทุก Input |
| **Google OAuth** | Whitelist email เท่านั้นที่เข้าได้ |

---

## 🌐 หน้าเว็บ Dashboard ทั้งหมด

```mermaid
graph LR
    subgraph "🏠 Public"
        LOGIN["🔒 login.php<br/>เข้าสู่ระบบ"]
    end

    subgraph "📊 Dashboard Pages (ต้อง Login)"
        INDEX["🏠 index.php<br/>หน้าหลัก"]
        DASH["📊 dashboard.php<br/>สถิติภาพรวม"]
        CONTROL["🖥️ control-room.php<br/>War Room"]
        STATUS["📡 status-dashboard.php<br/>สถานะอุปกรณ์"]
        HISTORY["🧾 history-dashboard.php<br/>ประวัติเหตุการณ์"]
        LIVE["🔴 live-dashboard.php<br/>Live Feed (SSE)"]
        PERF["⏱️ perf-dashboard.php<br/>Latency Monitor"]
        DIAG["🧪 diagnostics-dashboard.php<br/>System Health"]
    end

    LOGIN -->|"Auth OK"| INDEX
    INDEX --> DASH
    INDEX --> CONTROL
    INDEX --> STATUS
    INDEX --> HISTORY
    INDEX --> LIVE
    INDEX --> PERF
    INDEX --> DIAG
```

| หน้า | URL | คำอธิบาย |
|------|-----|---------|
| 🔒 Login | `/login.php` | เข้าสู่ระบบ (Admin/Google OAuth) |
| 🏠 Home | `/index.php` | หน้าหลัก + Quick Actions |
| 📊 Dashboard | `/dashboard.php` | สถิติ, กราฟ, Uptime, จำนวน Events |
| 🖥️ Control Room | `/control-room.php` | ศูนย์บัญชาการรวมข้อมูลทั้งหมด |
| 📡 Device Status | `/status-dashboard.php` | สถานะ ONLINE/OFFLINE ของทุกอุปกรณ์ |
| 🧾 History | `/history-dashboard.php` | ประวัติเหตุการณ์ฉุกเฉินทั้งหมด |
| 🔴 Live Feed | `/live-dashboard.php` | Real-time SSE Stream ดูเหตุการณ์สด |
| ⏱️ Latency | `/perf-dashboard.php` | วัด Performance และ Response Time |
| 🧪 Diagnostics | `/diagnostics-dashboard.php` | ตรวจสุขภาพระบบ (DNS/TLS/FS/Config) |

---

## 📡 ESP32 Firmware — สรุปการตั้งค่า

ไฟล์: [firmware/esp32_resqtech.ino](file:///c:/laragon/www/firmware/esp32_resqtech.ino)

```mermaid
graph TD
    subgraph "⚙️ Configuration"
        WIFI["📶 WiFi<br/>SSID: SR<br/>PASS: 12345678"]
        SERVER["🌐 Server URL<br/>http://192.168.137.1/ResQtechApp/api/esp32-receiver.php"]
        APIKEY["🔑 API Key<br/>sornramno1APIwowwow"]
        DEVICE["📍 Device<br/>ID: ESP32-001<br/>Location: Main Entrance"]
        HW["🔌 Hardware<br/>BUTTON: GPIO 0 (BOOT)<br/>LED: GPIO 2"]
        TIMING["⏱️ Timing<br/>Heartbeat: ทุก 5 วิ<br/>Debounce: 10 ms"]
    end
```

### GPIO Pinout

| Pin | ชื่อ | ทิศทาง | หน้าที่ |
|-----|------|--------|--------|
| GPIO 0 | BUTTON_PIN | INPUT_PULLUP | ปุ่ม BOOT (กดเพื่อแจ้งฉุกเฉิน) |
| GPIO 2 | LED_PIN | OUTPUT | แสดงสถานะ WiFi / Heartbeat / Emergency |

### พฤติกรรม LED

| สถานะ | พฤติกรรม LED |
|-------|------------|
| กำลังเชื่อมต่อ WiFi | กระพริบถี่ (ทุก 0.5 วิ) |
| เชื่อมต่อ WiFi สำเร็จ | ติดค้าง |
| Heartbeat ส่งออก | Toggle สั้นๆ 1 ครั้ง |
| กดปุ่มฉุกเฉิน | กระพริบ 3 ครั้ง → ติดค้าง → ดับ |

---

## 🔄 Data Flow Summary (สรุปการไหลของข้อมูล)

```mermaid
flowchart TB
    subgraph "🔧 ESP32 Board"
        BUTTON["🔴 ปุ่มฉุกเฉิน"]
        MCU["⚡ ESP32 MCU"]
        WIFI_M["📶 WiFi Module"]
    end

    subgraph "🌐 Network"
        ROUTER["📡 WiFi Router<br/>(192.168.x.x)"]
    end

    subgraph "💻 Server (Laragon/Apache)"
        PHP_API["⚙️ PHP API Layer"]
        FILE_LOG["📁 File Storage<br/>• emergency.log<br/>• heartbeat.log<br/>• perf_events.jsonl"]
        SSE["📺 SSE Stream<br/>(stream.php)"]
        WEB_PAGES["🎨 Web Pages<br/>(PHP + HTML/CSS/JS)"]
    end

    subgraph "📢 External APIs"
        LINE_API["💬 LINE<br/>Messaging API"]
        FCM_API["🔔 Firebase<br/>FCM v1"]
        GOOGLE_AUTH["🔐 Google<br/>OAuth 2.0"]
    end

    subgraph "👥 End Users"
        ADMIN_WEB["🖥️ ผู้ดูแล<br/>(Web Browser)"]
        ADMIN_MOBILE["📱 ผู้ดูแล<br/>(Flutter App)"]
        LINE_USER["💬 ผู้รับแจ้ง<br/>(LINE Chat)"]
    end

    BUTTON -->|"Active Low"| MCU
    MCU -->|"HTTP POST JSON"| WIFI_M
    WIFI_M --> ROUTER
    ROUTER --> PHP_API

    PHP_API -->|"เขียน Log"| FILE_LOG
    PHP_API -->|"แจ้ง LINE"| LINE_API
    PHP_API -->|"แจ้ง FCM"| FCM_API

    FILE_LOG -->|"อ่านข้อมูล"| WEB_PAGES
    FILE_LOG -->|"Stream"| SSE

    SSE -->|"Real-time"| ADMIN_WEB
    WEB_PAGES --> ADMIN_WEB
    FCM_API -->|"Push"| ADMIN_MOBILE
    LINE_API -->|"ข้อความ"| LINE_USER
    GOOGLE_AUTH -->|"Login"| WEB_PAGES

    style BUTTON fill:#ff6b6b,stroke:#333,color:#fff
    style LINE_API fill:#06C755,stroke:#333,color:#fff
    style FCM_API fill:#FFCA28,stroke:#333
    style PHP_API fill:#777BB4,stroke:#333,color:#fff
```

---

## ⏱️ Performance Characteristics (ลักษณะด้าน Performance)

| Metric | ค่า | หมายเหตุ |
|--------|-----|---------|
| **Heartbeat Interval** | 5 วินาที | ESP32 ส่งทุก 5 วิ |
| **Online Threshold** | 30 วินาที | หาก heartbeat ล่ากว่า 30 วิ → OFFLINE |
| **Server Process (Heartbeat)** | ~5 ms | เขียน Log อย่างเดียว |
| **Server Process (Emergency)** | ~250 ms | รวม LINE API + FCM API |
| **Rate Limit** | 60 req/min/IP | ป้องกัน Spam |
| **Log Rotation** | 5 MB / file | หมุนไฟล์อัตโนมัติ เก็บ 5 backup |
| **Session Timeout** | 30 นาที | สำหรับ Web Login |
| **WiFi Reconnect Timeout** | 15 วินาที | ถ้าไม่ได้ → ESP32 restart ตัวเอง |

---

## 🗺️ API Endpoints

````carousel
### 📡 ESP32 Receiver API
```
POST /api/esp32-receiver.php
```
**รับได้ 2 actions:**
- `heartbeat` → บันทึกสถานะ, คืน `server_total_ms`
- `emergency` → แจ้ง LINE + FCM + บันทึก Log

**Security:** API Key + Rate Limit + Input Sanitization
<!-- slide -->
### 📺 SSE Real-time Stream
```
GET /api/stream.php
```
ส่งข้อมูล Event ใหม่แบบ Server-Sent Events
ใช้โดย Live Dashboard เพื่ออัปเดตหน้าจอแบบ Real-time

<!-- slide -->
### 📋 History API
```
GET /api/get-history.php
```
ดึงประวัติเหตุการณ์ฉุกเฉินจาก `emergency.log`
ใช้โดย History Dashboard + Flutter App

<!-- slide -->
### 📡 Device Status API
```
GET /api/check-status.php
```
ตรวจสอบสถานะ Online/Offline ของ ESP32
คำนวณจาก Last Heartbeat Timestamp

<!-- slide -->
### 📱 Mobile Login API
```
POST /api/mobile-login.php
```
สำหรับ Flutter App เข้าสู่ระบบ
คืน `session_id` ใช้เป็น Bearer Token

<!-- slide -->
### 🔔 FCM Token Registration
```
POST /api/register-fcm-token.php
```
ลงทะเบียน FCM Token ของมือถือ
เพื่อรับ Push Notification เมื่อเกิดเหตุ

<!-- slide -->
### ⏱️ Performance Report
```
GET /api/perf-report.php
```
ดึงข้อมูล Latency จาก `perf_events.jsonl`
ใช้โดย Latency Monitor Dashboard

<!-- slide -->
### 🧪 Connection Diagnostics
```
GET /api/connection-diagnostics.php
```
ตรวจสุขภาพระบบ: DNS, TLS, Filesystem, Config
ใช้โดย Diagnostics Dashboard
````

---

## 🏗️ Application Bootstrap Flow

ลำดับการโหลดเมื่อมีคนเปิดหน้าเว็บ:

```mermaid
sequenceDiagram
    participant User as 👤 ผู้ใช้
    participant Apache as 🌐 Apache
    participant Init as 📦 init.php
    participant Config as ⚙️ config.php
    participant Func as 🔧 functions.php
    participant Auth as 🔐 auth.php
    participant Lang as 🌍 lang.php
    participant Page as 📄 dashboard.php

    User->>Apache: GET /dashboard.php
    Apache->>Page: เรียก dashboard.php
    Page->>Init: require init.php

    Init->>Config: 1. โหลด config.php
    Config->>Config: อ่าน .env → define() Constants

    Init->>Func: 2. โหลด functions.php
    Init->>Auth: 3. โหลด auth.php

    Init->>Init: 4. initSession()
    Init->>Init: 5. setSecurityHeaders()
    Init->>Init: 6. Set Cache-Control: no-cache

    Init->>Lang: 7. โหลด lang.php (ใช้ $_SESSION)
    Init->>Init: 8. โหลด google-oauth.php
    Init->>Init: 9. โหลด navigation.php

    Init-->>Page: ✅ Bootstrap เสร็จ

    Page->>Page: requireLogin() → ตรวจ Session
    Page->>Page: getStatistics() → อ่าน Log Files
    Page->>User: ✅ แสดงหน้า Dashboard

```

---

> [!CAUTION]
> **ข้อมูลลับ (Secrets) ทั้งหมด** เก็บอยู่ในไฟล์ `.env` ซึ่งถูก `.gitignore` ไว้ไม่ให้ขึ้น Git ระวังอย่า commit ไฟล์นี้ขึ้น Public Repository!

---

## 📝 สรุป

ResQTech เป็นระบบ **Full-Stack IoT Emergency System** ที่ครอบคลุมตั้งแต่:

1. **🔧 ฮาร์ดแวร์** (ESP32 + Button + LED)
2. **⚙️ Backend** (PHP API + File-based Storage)
3. **🎨 Frontend** (Neo-Brutalism Web Dashboard + PWA)
4. **📱 Mobile** (Flutter App + FCM Push)
5. **📢 Notifications** (LINE + FCM + SSE)
6. **🔐 Security** (API Key, Rate Limit, CSRF, Bcrypt, Session, CSP)

ทั้งหมดออกแบบมาเพื่อให้ **Latency ต่ำที่สุด** และ **เชื่อถือได้สูงสุด** ในสถานการณ์ฉุกเฉิน 🚨
