# Laravel Cloud Deployment Guide

## تقديم
هذا الملف يشرح كيفية نشر تطبيق WebSockets على Laravel Cloud مع تشغيل WebSocket server بشكل دائم.

---

## المسار على Laravel Cloud
```
https://websockets-main-xxkgkx.laravel.cloud/
```

---

## الإعدادات المطلوبة

### 1. Procfile
ملف `Procfile` يحتوي على عمليتين:
- **web**: Apache web server (يخدم الطلبات الـ HTTP)
- **websockets**: WebSocket server على port 6001

```procfile
web: vendor/bin/heroku-php-apache2 public/
websockets: php artisan websockets:serve --host=0.0.0.0 --port=6001
```

### 2. متغيرات البيئة

**في Laravel Cloud Dashboard، أضف المتغيرات التالية:**

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_HOST=websockets-main-xxkgkx.laravel.cloud
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_ENCRYPTED=false
PUSHER_USE_TLS=false
```

### 3. توازي الـ Workers

في لوحة التحكم على Laravel Cloud:
1. اذهب إلى **Application** > **Workers**
2. أضف worker جديد مع الاسم `websockets`
3. قم بتعيين الأوامر التالي:
   ```bash
   php artisan websockets:serve --host=0.0.0.0 --port=6001
   ```

---

## خطوات النشر

### 1. دفع التغييرات إلى GitHub
```bash
git add .
git commit -m "Setup WebSocket server for Laravel Cloud"
git push origin main
```

### 2. المزامنة مع Laravel Cloud
- اذهب إلى Laravel Cloud Dashboard
- قم بربط المشروع بفرع GitHub الذي تريده
- فعّل النشر التلقائي

### 3. تشغيل الأوامر الأساسية
```bash
# بعد النشر الأول
php artisan migrate
php artisan websockets:serve --host=0.0.0.0 --port=6001
```

---

## اختبار الاتصال

### من المتصفح
```
https://websockets-main-xxkgkx.laravel.cloud/websockets-test
```

تحقق من:
- ✅ رسالة "Connected to server"
- ✅ رسالة "Subscribed to channel"
- ✅ القدرة على إرسال الرسائل

### من Command Line
```bash
# تحقق من حالة WebSocket
php artisan websockets:serve --host=0.0.0.0 --port=6001

# شاهد السجلات
tail -f storage/logs/laravel.log
```

---

## معالجة المشاكل

### المشكلة: الاتصال لا يعمل
**الحل:**
1. تحقق من أن `Procfile` موجود وصحيح
2. تحقق من متغيرات البيئة على Laravel Cloud
3. شاهد السجلات:
   ```bash
   laravel-cloud logs tail
   ```

### المشكلة: WebSocket server معطل
**الحل:**
```bash
# أعد تشغيل العملية
php artisan websockets:serve --host=0.0.0.0 --port=6001 --restart
```

### المشكلة: الاتصال آمن (HTTPS) مطلوب
**الحل:**
إذا كنت تستخدم HTTPS، غيّر الإعدادات:
```env
PUSHER_SCHEME=https
PUSHER_USE_TLS=true
PUSHER_ENCRYPTED=true
```

---

## الميزات المدعومة

✅ البث المباشر (Broadcasting)
✅ القنوات الخاصة (Private Channels)
✅ القنوات الحضور (Presence Channels)
✅ الأحداث المباشرة (Real-time Events)

---

## المراجع

- [Laravel Websockets Documentation](https://beyondcode.io/docs/laravel-websockets)
- [Laravel Cloud Documentation](https://laravel.cloud/docs)
- [Pusher Configuration](https://pusher.com/docs)

---

## الدعم

للمساعدة في حل المشاكل، اتصل بـ:
- Laravel Cloud Support
- BeyondCode Laravel Websockets Community