# ResQTech Emergency Notification System

ระบบแจ้งเตือนฉุกเฉินผ่าน LINE สำหรับ ESP32

## 📁 โครงสร้างโปรเจค

```
resqtech/
├── api/                    # API endpoints
│   ├── check-status.php    # ตรวจสอบสถานะ ESP32
│   ├── dashboard.php       # API สำหรับ Dashboard
│   ├── esp32-receiver.php  # รับสัญญาณจาก ESP32
│   └── send-notification.php
├── assets/
│   ├── css/
│   │   ├── style.css       # Main stylesheet
│   │   └── dashboard.css   # Dashboard styles
│   └── js/
│       ├── app.js          # Main application JS
│       ├── dashboard.js    # Dashboard JS
│       └── theme.js        # Theme management
├── config/
│   └── config.php          # Configuration (loads .env)
├── includes/
│   ├── auth.php            # Authentication functions
│   ├── functions.php       # Core functions
│   ├── google-oauth.php    # Google OAuth
│   ├── init.php            # Application initialization
│   └── lang.php            # Language system
├── logs/                   # Log files (auto-created)
├── .htaccess               # Apache configuration
├── .env.example            # Example environment variables
├── dashboard.php           # Dashboard page
├── google-callback.php     # Google OAuth callback
├── index.php               # Main page
├── login.php               # Login page
└── logout.php              # Logout handler
```

## 🔧 การติดตั้ง

1. อัพโหลดไฟล์ทั้งหมดไปยัง web server
2. สร้างไฟล์ `.env` จาก `.env.example` แล้วตั้งค่า:
   - `ADMIN_PASSWORD_HASH` (สร้างด้วย `password_hash()`)
   - LINE credentials (`LINE_CHANNEL_ACCESS_TOKEN`, `LINE_USER_ID`)
   - `ESP32_API_KEY`
   - Google OAuth (ถ้าต้องการ)

3. ตรวจสอบว่า Apache mod_rewrite เปิดใช้งาน
4. ตรวจสอบ permissions ของ `logs/` directory

## 🔐 ความปลอดภัย

- ✅ CSRF Protection
- ✅ Session Security (regeneration, timeout)
- ✅ Brute Force Protection
- ✅ Security Headers
- ✅ Input Sanitization
- ✅ Rate Limiting
- ✅ Password Hashing

## 📱 ESP32 API

### Heartbeat
```
GET /api/esp32-receiver.php?key=YOUR_API_KEY&action=heartbeat
```

### Emergency Alert
```
GET /api/esp32-receiver.php?key=YOUR_API_KEY&action=emergency
```

## 🔑 สร้าง Password Hash

```php
<?php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

## 📝 License

MIT License
