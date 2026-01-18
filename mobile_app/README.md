# resqtech_mobile

ResQtech Mobile App (Flutter) สำหรับดูสถานะระบบ/ดูประวัติ และจัดการการตั้งค่าพื้นฐานของระบบแจ้งเตือนฉุกเฉิน

## Getting Started

### 1) ติดตั้ง Dependencies

```bash
flutter pub get
```

### 2) รันแอป

```bash
flutter run
```

## Usage

### Login

- กรอก Server URL เช่น `http://<server-ip>/ResQtech` (แนะนำให้ใช้ http/https ที่เข้าถึงจากมือถือได้จริง)
- กรอก Username/Password (ตามระบบเว็บ)

### Tabs

- Dashboard: แสดงสถานะการเชื่อมต่อ + รายการอุปกรณ์ล่าสุด (พร้อม Pull-to-refresh)
- History: ดูประวัติเหตุการณ์ พร้อมค้นหา/กรองตาม Device/Location/Status
- Settings: ตั้งค่า Base URL, ทดสอบการเชื่อมต่อ, เลือกภาษา (TH/EN), เลือกธีม (System/Light/Dark), Logout

## Notes

- การติดตามแบบ Live ในมือถือใช้ polling จาก `api/check-status.php` (เพื่อทำงานได้แม้ไม่มี SSE บนมือถือ/เครือข่ายบางแบบ)
- ภาษาและธีมจะถูกจำค่าด้วย SharedPreferences
