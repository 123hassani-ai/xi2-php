# 🧪 Testing Guide - زیتو (Xi2)

راهنمای کامل تست کردن و اطمینان از کیفیت پلتفرم زیتو

## 🎯 استراتژی تست

### انواع تست‌ها
1. **Unit Tests**: تست واحدهای کوچک کد
2. **Integration Tests**: تست ارتباط بین بخش‌ها
3. **E2E Tests**: تست مسیر کامل کاربر
4. **Performance Tests**: تست کارایی و سرعت
5. **Security Tests**: تست امنیت و نفوذ

### Coverage هدف
- **Unit Tests**: 85%+ coverage
- **Integration Tests**: کلیه API endpoints
- **E2E Tests**: مسیرهای اصلی کاربر
- **Performance**: Response time < 500ms
- **Security**: OWASP Top 10 checklist

## 🔧 تست‌های Manual

### 1. تست احراز هویت

#### ثبت‌نام کاربر جدید
```
✅ Test Case: ثبت‌نام موفق
1. باز کردن صفحه اصلی
2. کلیک روی "ثبت‌نام"
3. وارد کردن نام، موبایل، رمز عبور
4. کلیک "ثبت‌نام"
5. انتظار: کد OTP ارسال شود

✅ Expected Result:
- پیام موفقیت نمایش داده شود
- کد 6 رقمی در دیتابیس ثبت شود
- وضعیت کاربر "pending" باشد
```

#### تست ورود
```
✅ Test Case: ورود موفق
1. کلیک "ورود"
2. وارد کردن شماره و رمز صحیح
3. کلیک "ورود"

✅ Expected Result:
- توکن session دریافت شود
- هدایت به داشبورد
- اطلاعات کاربر نمایش یابد
```

### 2. تست آپلود تصویر

#### آپلود با Drag & Drop
```
✅ Test Case: آپلود موفق
1. وارد شدن به حساب کاربری
2. Drag کردن تصویر به منطقه آپلود
3. انتظار تا تکمیل آپلود

✅ Expected Result:
- نوار پیشرفت نمایش یابد
- تصویر در لیست ظاهر شود
- thumbnail ساخته شود
- فایل در storage ذخیره شود
```

#### تست محدودیت‌ها
```
❌ Test Case: فایل بزرگ
1. انتخاب فایلی بیش از 10MB
2. تلاش برای آپلود

❌ Expected Result:
- پیام خطا نمایش یابد: "حجم فایل بیش از حد مجاز"
- آپلود متوقف شود
```

### 3. تست مدیریت تصاویر

#### حذف تصویر
```
✅ Test Case: حذف موفق
1. باز کردن لیست تصاویر
2. کلیک روی آیکون حذف
3. تایید حذف

✅ Expected Result:
- تصویر از لیست حذف شود
- فایل از storage پاک شود
- رکورد از دیتابیس حذف شود
```

## 🏗️ تست‌های Automated

### PHPUnit Tests

#### نصب PHPUnit
```bash
composer require --dev phpunit/phpunit
```

#### تست API Authentication
```php
<?php
// tests/AuthTest.php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase 
{
    public function testUserRegistration()
    {
        $data = [
            'name' => 'Test User',
            'mobile' => '09123456789',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register.php', $data);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('userId', $response['data']);
        $this->assertEquals('pending', $response['data']['status']);
    }

    public function testUserLogin()
    {
        // First register a user
        $this->createTestUser();
        
        $data = [
            'mobile' => '09123456789', 
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/login.php', $data);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('token', $response['data']);
        $this->assertNotEmpty($response['data']['token']);
    }

    public function testInvalidLogin()
    {
        $data = [
            'mobile' => '09123456789',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/auth/login.php', $data);
        
        $this->assertFalse($response['success']);
        $this->assertContains('اطلاعات ورود', $response['message']);
    }

    private function createTestUser()
    {
        // Helper method to create test user
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO users (full_name, mobile, password_hash, status) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test User', '09123456789', sha256('password123'), 'active']);
    }
}
```

#### تست File Upload
```php
<?php
// tests/UploadTest.php
class UploadTest extends TestCase 
{
    public function testImageUpload()
    {
        $token = $this->getAuthToken();
        
        // Create test image file
        $testFile = $this->createTestImage();
        
        $response = $this->uploadFile('/api/upload/upload.php', $testFile, $token);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('uploadId', $response['data']);
        $this->assertArrayHasKey('filePath', $response['data']);
        $this->assertArrayHasKey('thumbnailPath', $response['data']);
        
        // Check if files actually exist
        $this->assertFileExists($response['data']['filePath']);
        $this->assertFileExists($response['data']['thumbnailPath']);
    }

    public function testInvalidFileType()
    {
        $token = $this->getAuthToken();
        
        // Create test text file
        $testFile = $this->createTestFile('.txt');
        
        $response = $this->uploadFile('/api/upload/upload.php', $testFile, $token);
        
        $this->assertFalse($response['success']);
        $this->assertContains('نوع فایل', $response['message']);
    }

    public function testUnauthorizedUpload()
    {
        $testFile = $this->createTestImage();
        
        $response = $this->uploadFile('/api/upload/upload.php', $testFile);
        
        $this->assertFalse($response['success']);
        $this->assertEquals(401, $response['httpCode']);
    }

    private function createTestImage()
    {
        // Create a simple test image
        $image = imagecreate(100, 100);
        $filename = tempnam(sys_get_temp_dir(), 'test_image') . '.jpg';
        imagejpeg($image, $filename);
        imagedestroy($image);
        return $filename;
    }
}
```

### اجرای تست‌ها
```bash
# اجرای همه تست‌ها
./vendor/bin/phpunit

# اجرای تست‌های خاص
./vendor/bin/phpunit tests/AuthTest.php

# اجرای با coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## 🌐 تست‌های Frontend

### JavaScript Unit Tests (Jest)

#### نصب Jest
```bash
npm install --save-dev jest
```

#### تست کلاس‌های JavaScript
```javascript
// tests/Xi2Auth.test.js
const Xi2Auth = require('../src/assets/js/auth.js');

describe('Xi2Auth', () => {
    test('should validate mobile number format', () => {
        const auth = new Xi2Auth();
        
        expect(auth.validateMobile('09123456789')).toBe(true);
        expect(auth.validateMobile('912345678')).toBe(false);
        expect(auth.validateMobile('abc')).toBe(false);
    });

    test('should validate password strength', () => {
        const auth = new Xi2Auth();
        
        expect(auth.validatePassword('123456')).toBe(true);
        expect(auth.validatePassword('123')).toBe(false);
        expect(auth.validatePassword('')).toBe(false);
    });

    test('should format OTP input', () => {
        const auth = new Xi2Auth();
        
        expect(auth.formatOTP('123456')).toBe('123456');
        expect(auth.formatOTP('123abc')).toBe('123');
    });
});
```

#### تست Upload Functionality
```javascript
// tests/Xi2Upload.test.js
describe('Xi2Upload', () => {
    test('should validate file type', () => {
        const upload = new Xi2Upload();
        
        const imageFile = new File([''], 'test.jpg', { type: 'image/jpeg' });
        const textFile = new File([''], 'test.txt', { type: 'text/plain' });
        
        expect(upload.validateFileType(imageFile)).toBe(true);
        expect(upload.validateFileType(textFile)).toBe(false);
    });

    test('should validate file size', () => {
        const upload = new Xi2Upload();
        
        const smallFile = new File(['x'.repeat(1000)], 'small.jpg');
        const largeFile = new File(['x'.repeat(11000000)], 'large.jpg');
        
        expect(upload.validateFileSize(smallFile)).toBe(true);
        expect(upload.validateFileSize(largeFile)).toBe(false);
    });
});
```

## 🚀 تست‌های Performance

### Load Testing با Artillery

#### نصب Artillery
```bash
npm install -g artillery
```

#### تست Load API
```yaml
# load-test.yml
config:
  target: 'http://localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 10
  variables:
    mobile: "091234567{{ $randomInt(10, 99) }}"
    password: "password123"

scenarios:
  - name: "User Registration"
    weight: 30
    flow:
      - post:
          url: "/api/auth/register.php"
          json:
            name: "Test User {{ $randomInt(1, 1000) }}"
            mobile: "{{ mobile }}"
            password: "{{ password }}"

  - name: "User Login"
    weight: 70
    flow:
      - post:
          url: "/api/auth/login.php"
          json:
            mobile: "09123456789"
            password: "password123"
          capture:
            - json: "$.data.token"
              as: "token"
      - get:
          url: "/api/upload/list.php"
          headers:
            Authorization: "Bearer {{ token }}"
```

#### اجرای Load Test
```bash
artillery run load-test.yml
```

### Performance Monitoring
```php
<?php
// src/utils/PerformanceMonitor.php
class PerformanceMonitor 
{
    private static $startTime;
    
    public static function start()
    {
        self::$startTime = microtime(true);
    }
    
    public static function end($operation = 'unknown')
    {
        $endTime = microtime(true);
        $duration = ($endTime - self::$startTime) * 1000; // milliseconds
        
        error_log("Performance: {$operation} took {$duration}ms");
        
        // Alert if too slow
        if ($duration > 500) {
            error_log("SLOW QUERY ALERT: {$operation} - {$duration}ms");
        }
        
        return $duration;
    }
}

// Usage in API endpoints
PerformanceMonitor::start();
// ... your code ...
PerformanceMonitor::end('user_login');
```

## 🔒 تست‌های Security

### OWASP Security Checklist

#### SQL Injection
```php
// ✅ SAFE - Using prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE mobile = ?");
$stmt->execute([$mobile]);

// ❌ VULNERABLE - Direct string concatenation
$query = "SELECT * FROM users WHERE mobile = '$mobile'";
```

#### XSS Protection
```php
// ✅ SAFE - Input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$safeName = sanitizeInput($_POST['name']);
```

#### File Upload Security
```php
// تست امنیت آپلود فایل
function testFileUploadSecurity() {
    // تست فایل PHP
    $phpFile = "<?php echo 'hacked'; ?>";
    $result = uploadFile($phpFile, 'hack.php');
    assert($result['success'] === false);
    
    // تست فایل JavaScript
    $jsFile = "<script>alert('xss')</script>";
    $result = uploadFile($jsFile, 'hack.js');
    assert($result['success'] === false);
    
    // تست فایل تصویر معتبر
    $imageFile = createValidImage();
    $result = uploadFile($imageFile, 'valid.jpg');
    assert($result['success'] === true);
}
```

### Penetration Testing
```bash
# نصب OWASP ZAP برای تست نفوذ
wget https://github.com/zaproxy/zaproxy/releases/download/v2.12.0/ZAP_2_12_0_unix.sh
chmod +x ZAP_2_12_0_unix.sh
./ZAP_2_12_0_unix.sh

# اجرای تست خودکار
zap-baseline.py -t http://localhost:8000
```

## 📊 تست Database

### تست Schema
```sql
-- تست وجود جداول
SHOW TABLES;

-- تست فیلدهای اجباری
INSERT INTO users (full_name, mobile, password_hash) 
VALUES ('', '', ''); -- باید خطا دهد

-- تست Foreign Keys
INSERT INTO uploads (user_id, filename, file_path, file_size, mime_type) 
VALUES (999999, 'test.jpg', '/path', 1000, 'image/jpeg'); -- باید خطا دهد

-- تست Indexes
EXPLAIN SELECT * FROM users WHERE mobile = '09123456789';
-- باید از index استفاده کند
```

### Performance Tests
```sql
-- تست کارایی جستجو
SELECT COUNT(*) FROM uploads WHERE MATCH(title, description) AGAINST('تست');

-- تست کارایی JOIN ها
EXPLAIN SELECT u.full_name, up.title 
FROM users u 
JOIN uploads up ON u.id = up.user_id 
WHERE u.status = 'active';
```

## 📱 تست PWA

### Service Worker Test
```javascript
// tests/service-worker.test.js
describe('Service Worker', () => {
    test('should cache essential resources', async () => {
        // Simulate service worker installation
        self.dispatchEvent(new Event('install'));
        
        const cache = await caches.open('xi2-v1');
        const cachedRequests = await cache.keys();
        
        expect(cachedRequests.length).toBeGreaterThan(0);
        expect(cachedRequests.some(req => req.url.includes('index.html'))).toBe(true);
    });
});
```

### Manifest Validation
```bash
# استفاده از Lighthouse برای تست PWA
npm install -g lighthouse

# اجرای تست PWA
lighthouse http://localhost:8000 --only-categories=pwa --output=json --output-path=./pwa-audit.json
```

## 🎯 تست‌های End-to-End

### Cypress E2E Tests

#### نصب Cypress
```bash
npm install --save-dev cypress
```

#### تست User Journey کامل
```javascript
// cypress/integration/user-journey.spec.js
describe('Complete User Journey', () => {
    it('should register, upload image, and manage uploads', () => {
        // ثبت‌نام
        cy.visit('/');
        cy.get('[data-testid=register-btn]').click();
        cy.get('[data-testid=name-input]').type('Test User');
        cy.get('[data-testid=mobile-input]').type('09123456789');
        cy.get('[data-testid=password-input]').type('password123');
        cy.get('[data-testid=register-submit]').click();
        
        // تایید OTP (mock)
        cy.get('[data-testid=otp-input]').type('123456');
        cy.get('[data-testid=verify-submit]').click();
        
        // ورود
        cy.get('[data-testid=login-btn]').click();
        cy.get('[data-testid=mobile-input]').type('09123456789');
        cy.get('[data-testid=password-input]').type('password123');
        cy.get('[data-testid=login-submit]').click();
        
        // آپلود تصویر
        const fileName = 'test-image.jpg';
        cy.fixture(fileName).then(fileContent => {
            cy.get('[data-testid=file-input]').attachFile({
                fileContent: fileContent.toString(),
                fileName: fileName,
                mimeType: 'image/jpeg'
            });
        });
        
        // تایید آپلود موفق
        cy.contains('آپلود با موفقیت انجام شد');
        cy.get('[data-testid=uploaded-image]').should('be.visible');
        
        // حذف تصویر
        cy.get('[data-testid=delete-btn]').click();
        cy.get('[data-testid=confirm-delete]').click();
        cy.contains('تصویر با موفقیت حذف شد');
    });
});
```

## 🎯 CI/CD Testing

### GitHub Actions Workflow
```yaml
# .github/workflows/test.yml
name: Xi2 Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: xi2_test
        ports:
          - 3306:3306

    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, pdo_mysql, gd
    
    - name: Install Composer dependencies
      run: composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader
    
    - name: Setup test database
      run: |
        php src/database/install.php
    
    - name: Run PHPUnit tests
      run: ./vendor/bin/phpunit --coverage-clover coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v1
      with:
        file: ./coverage.xml

  e2e-tests:
    runs-on: ubuntu-latest
    needs: unit-tests
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup Node.js
      uses: actions/setup-node@v2
      with:
        node-version: '16'
    
    - name: Install dependencies
      run: npm install
    
    - name: Start server
      run: |
        php -S localhost:8000 -t public &
        sleep 5
    
    - name: Run Cypress tests
      uses: cypress-io/github-action@v2
      with:
        build: npm run build
        start: npm run start
        wait-on: 'http://localhost:8000'
```

## 📈 Continuous Testing

### Test Automation Schedule
```bash
# تست‌های روزانه
0 2 * * * /path/to/run-daily-tests.sh

# تست‌های هفتگی (امنیت)
0 1 * * 0 /path/to/run-security-tests.sh

# تست‌های ماهانه (کارایی)
0 0 1 * * /path/to/run-performance-tests.sh
```

### Monitoring & Alerts
```php
// تست‌های زنده سیستم
function healthCheck() {
    $checks = [
        'database' => testDatabaseConnection(),
        'storage' => testStorageAccess(),
        'api' => testAPIEndpoints(),
        'performance' => testResponseTime()
    ];
    
    foreach ($checks as $test => $result) {
        if (!$result) {
            sendAlert("Health check failed: $test");
        }
    }
    
    return $checks;
}
```

---

**برای سوالات درباره تست‌ها، به [مستندات فنی](technical-docs.md) مراجعه کنید.**
