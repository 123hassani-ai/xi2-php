# 📡 API Reference - زیتو (Xi2)

مرجع کامل API های زیتو برای توسعه‌دهندگان

## 🔗 Base URL
```
Development: http://localhost:8000/api
Production: https://xi2.app/api
```

## 🔐 Authentication

همه API های احراز هویت نیاز به authentication ندارند، اما API های مربوط به مدیریت تصاویر نیاز به Bearer Token دارند.

### Header Format
```
Authorization: Bearer {session_token}
Content-Type: application/json
```

## 📝 Auth APIs

### POST /auth/register.php
ثبت‌نام کاربر جدید در سیستم

**Request Body:**
```json
{
    "name": "علی احمدی",
    "mobile": "09123456789",
    "password": "123456"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "ثبت‌نام موفقیت‌آمیز بود. کد تایید به شماره شما ارسال شد",
    "timestamp": "2025-08-29 13:38:33",
    "data": {
        "userId": "1",
        "mobile": "09123456789",
        "needsVerification": true,
        "otpExpires": "2025-08-29 13:43:33"
    }
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "این شماره موبایل قبلاً ثبت شده است",
    "timestamp": "2025-08-29 13:38:33",
    "data": []
}
```

### POST /auth/login.php
ورود کاربر به سیستم

**Request Body:**
```json
{
    "mobile": "09123456789",
    "password": "123456"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "ورود موفقیت‌آمیز",
    "timestamp": "2025-08-29 13:38:39",
    "data": {
        "user": {
            "id": 1,
            "fullName": "علی احمدی",
            "mobile": "09123456789",
            "status": "active"
        },
        "token": "aff8788bc412d5c44715ec8a31073b0e0501c934aee1d88cfd3da8b9eb034d13",
        "stats": {
            "totalUploads": 0,
            "totalSize": 0,
            "totalViews": 0
        }
    }
}
```

### POST /auth/verify-otp.php
تایید کد OTP برای فعال‌سازی حساب

**Request Body:**
```json
{
    "mobile": "09123456789",
    "otpCode": "123456"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "حساب کاربری با موفقیت فعال شد",
    "data": {
        "user": {
            "id": 1,
            "fullName": "علی احمدی",
            "mobile": "09123456789",
            "status": "active"
        },
        "token": "session_token_here"
    }
}
```

### POST /auth/logout.php
خروج کاربر از سیستم

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "خروج موفقیت‌آمیز"
}
```

## 📸 Upload APIs

### POST /upload/upload.php
آپلود تصویر جدید

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (FormData):**
- `image`: فایل تصویر (Required)
- `title`: عنوان تصویر (Optional)
- `description`: توضیحات (Optional)
- `isPublic`: عمومی بودن (Optional, default: true)

**Response (Success):**
```json
{
    "success": true,
    "message": "آپلود با موفقیت انجام شد",
    "data": {
        "uploadId": 123,
        "filename": "image_123_20250829.jpg",
        "originalName": "my-photo.jpg",
        "filePath": "/storage/uploads/2025/08/29/image_123_20250829.jpg",
        "thumbnailPath": "/storage/uploads/2025/08/29/thumb_image_123_20250829.jpg",
        "fileSize": 2048576,
        "mimeType": "image/jpeg",
        "shareUrl": "https://xi2.app/view/123",
        "downloadUrl": "https://xi2.app/download/123"
    }
}
```

### GET /upload/list.php
دریافت لیست تصاویر کاربر

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page`: شماره صفحه (default: 1)
- `limit`: تعداد آیتم در صفحه (default: 20, max: 100)
- `search`: جستجو در عنوان
- `orderBy`: مرتب‌سازی (created_at, title, views, file_size)
- `orderDir`: جهت مرتب‌سازی (ASC, DESC)

**Example:**
```
GET /upload/list.php?page=1&limit=20&search=تصویر&orderBy=created_at&orderDir=DESC
```

**Response:**
```json
{
    "success": true,
    "message": "لیست آپلودها با موفقیت دریافت شد",
    "data": {
        "uploads": [
            {
                "id": 123,
                "originalName": "my-photo.jpg",
                "title": "تصویر من",
                "description": "توضیحات تصویر",
                "filePath": "/storage/uploads/2025/08/29/image_123.jpg",
                "thumbnailPath": "/storage/uploads/2025/08/29/thumb_image_123.jpg",
                "fileSize": 2048576,
                "fileSizeFormatted": "2.0 MB",
                "mimeType": "image/jpeg",
                "views": 15,
                "downloads": 3,
                "isPublic": true,
                "shareUrl": "https://xi2.app/view/123",
                "createdAt": "2025-08-29 13:45:00",
                "updatedAt": "2025-08-29 13:45:00"
            }
        ],
        "pagination": {
            "currentPage": 1,
            "totalPages": 5,
            "totalCount": 87,
            "limit": 20,
            "hasNextPage": true,
            "hasPrevPage": false
        },
        "stats": {
            "totalUploads": 87,
            "totalSize": 156789123,
            "totalSizeFormatted": "149.5 MB",
            "totalViews": 1250,
            "totalDownloads": 340
        },
        "filters": {
            "search": "تصویر",
            "orderBy": "created_at",
            "orderDir": "DESC"
        }
    }
}
```

### DELETE /upload/delete.php
حذف تصویر

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "uploadId": 123
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "تصویر با موفقیت حذف شد",
    "data": {
        "deletedFiles": [
            "/storage/uploads/2025/08/29/image_123.jpg",
            "/storage/uploads/2025/08/29/thumb_image_123.jpg"
        ]
    }
}
```

## 🔧 Utility APIs

### GET /config.php
دریافت تنظیمات عمومی

**Response:**
```json
{
    "success": true,
    "data": {
        "version": "2.0.0",
        "appName": "زیتو (Xi2)",
        "maxFileSize": 10485760,
        "allowedTypes": ["image/jpeg", "image/png", "image/webp"],
        "features": {
            "userRegistration": true,
            "publicUploads": true,
            "socialShare": true
        }
    }
}
```

## 🚫 Error Codes

### HTTP Status Codes
- `200`: موفقیت
- `201`: ایجاد موفقیت‌آمیز
- `400`: درخواست نامعتبر
- `401`: عدم احراز هویت
- `403`: عدم دسترسی
- `404`: یافت نشد
- `422`: خطای اعتبارسنجی
- `500`: خطای سرور

### Application Error Codes
```json
{
    "success": false,
    "message": "پیام خطا به فارسی",
    "errorCode": "VALIDATION_ERROR",
    "timestamp": "2025-08-29 13:38:33",
    "data": {
        "field": "mobile",
        "rule": "required"
    }
}
```

### Common Errors
- `VALIDATION_ERROR`: خطای اعتبارسنجی ورودی
- `AUTH_REQUIRED`: نیاز به احراز هویت
- `INVALID_TOKEN`: توکن نامعتبر
- `USER_NOT_FOUND`: کاربر یافت نشد
- `FILE_TOO_LARGE`: فایل بزرگ‌تر از حد مجاز
- `INVALID_FILE_TYPE`: نوع فایل مجاز نیست
- `UPLOAD_FAILED`: خطا در آپلود
- `DATABASE_ERROR`: خطای پایگاه داده

## 📊 Rate Limiting

### محدودیت‌ها
- **Authentication APIs**: 5 درخواست در دقیقه
- **Upload APIs**: 10 آپلود در دقیقه
- **General APIs**: 60 درخواست در دقیقه

### Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1629876543
```

## 💡 Usage Examples

### JavaScript (Fetch API)

```javascript
// ثبت‌نام
async function register(userData) {
    const response = await fetch('/api/auth/register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    });
    return response.json();
}

// آپلود تصویر
async function uploadImage(file, token) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('title', 'عنوان تصویر');

    const response = await fetch('/api/upload/upload.php', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        body: formData
    });
    return response.json();
}
```

### PHP (cURL)

```php
// ورود
$data = [
    'mobile' => '09123456789',
    'password' => '123456'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);
```

### Python (Requests)

```python
import requests

# ثبت‌نام
def register(name, mobile, password):
    data = {
        'name': name,
        'mobile': mobile,
        'password': password
    }
    response = requests.post(
        'http://localhost:8000/api/auth/register.php',
        json=data
    )
    return response.json()

# لیست آپلودها
def get_uploads(token, page=1):
    headers = {'Authorization': f'Bearer {token}'}
    params = {'page': page, 'limit': 20}
    response = requests.get(
        'http://localhost:8000/api/upload/list.php',
        headers=headers,
        params=params
    )
    return response.json()
```

---

**برای سوالات بیشتر، به [مستندات کامل](../README-COMPLETE.md) مراجعه کنید.**
