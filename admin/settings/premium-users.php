<?php
/**
 * زیتو (Xi2) - تنظیمات کاربران پریمیوم (در حال توسعه)
 * صفحه آماده برای ویژگی‌های آینده پریمیوم
 * طراحی شده طبق پرامپت شماره 3
 */

require_once __DIR__ . '/../includes/auth-check.php';

$page_title = 'کاربران پریمیوم';
$current_page = 'premium-users';

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - پنل مدیریت زیتو</title>
    <link href="../../assets/css/auth-responsive.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
        }
        
        .coming-soon {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            padding: 4rem 2rem;
            margin-top: 2rem;
        }
        
        .coming-soon-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
        }
        
        .coming-soon h1 {
            color: var(--primary-color);
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .coming-soon p {
            color: var(--gray-600);
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .features-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .feature-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            text-align: right;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .feature-description {
            opacity: 0.9;
        }
        
        .progress-bar {
            background: var(--gray-200);
            border-radius: 1rem;
            height: 8px;
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            height: 100%;
            width: 35%;
            transition: width 0.5s ease;
        }
        
        .timeline {
            background: var(--gray-50);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
            text-align: right;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .timeline-item:last-child {
            border-bottom: none;
        }
        
        .timeline-date {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .coming-soon h1 {
                font-size: 2rem;
            }
            
            .features-preview {
                grid-template-columns: 1fr;
            }
            
            .timeline-item {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="coming-soon">
            <div class="coming-soon-icon">🚧</div>
            <h1>کاربران پریمیوم</h1>
            <p>این بخش در حال توسعه است و به زودی راه‌اندازی خواهد شد</p>
            
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            
            <p><strong>35% تکمیل شده</strong></p>
        </div>
        
        <!-- پیش‌نمایش ویژگی‌ها */
        <div class="features-preview">
            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <div class="feature-title">آپلود حرفه‌ای</div>
                <div class="feature-description">
                    آپلود فایل‌های بزرگ تا 100MB با سرعت بالا
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <div class="feature-title">ابزارهای ویرایش</div>
                <div class="feature-description">
                    ویرایش آنلاین تصاویر با ابزارهای حرفه‌ای
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <div class="feature-title">آنالیتیکس پیشرفته</div>
                <div class="feature-description">
                    آمار دقیق بازدید و دانلود فایل‌ها
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <div class="feature-title">امنیت بالا</div>
                <div class="feature-description">
                    رمزگذاری فایل‌ها و کنترل دسترسی
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <div class="feature-title">CDN اختصاصی</div>
                <div class="feature-description">
                    سرعت بارگذاری فوق‌العاده در سراسر دنیا
                </div>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <div class="feature-title">API اختصاصی</div>
                <div class="feature-description">
                    API اختصاصی برای توسعه‌دهندگان
                </div>
            </div>
        </div>
        
        <!-- جدول زمانی توسعه -->
        <div class="timeline">
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                🗓️ جدول زمانی توسعه
            </h2>
            
            <div class="timeline-item">
                <div class="timeline-date">مرحله 1</div>
                <div class="timeline-content">
                    <div class="timeline-title">طراحی UI/UX پریمیوم</div>
                    <div class="text-gray">طراحی رابط کاربری اختصاصی برای کاربران پریمیوم</div>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-date">مرحله 2</div>
                <div class="timeline-content">
                    <div class="timeline-title">سیستم پرداخت</div>
                    <div class="text-gray">اتصال درگاه‌های پرداخت و مدیریت اشتراک</div>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-date">مرحله 3</div>
                <div class="timeline-content">
                    <div class="timeline-title">ابزارهای پیشرفته</div>
                    <div class="text-gray">توسعه ابزارهای ویرایش و مدیریت حرفه‌ای</div>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-date">مرحله 4</div>
                <div class="timeline-content">
                    <div class="timeline-title">تست و راه‌اندازی</div>
                    <div class="text-gray">تست کامل و راه‌اندازی نسخه نهایی پریمیوم</div>
                </div>
            </div>
        </div>
        
        <!-- اطلاعات تماس برای همکاری -->
        <div class="coming-soon" style="margin-top: 2rem; padding: 2rem;">
            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                🤝 علاقه‌مند به همکاری؟
            </h3>
            <p style="margin-bottom: 1rem;">
                اگر ایده یا پیشنهادی برای ویژگی‌های پریمیوم دارید، با ما در تماس باشید
            </p>
            <button class="btn btn-primary" onclick="alert('بخش تماس در حال توسعه است')">
                ارسال پیشنهاد
            </button>
        </div>
    </div>
    
    <script>
        // انیمیشن ساده برای progress bar
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.querySelector('.progress-fill').style.width = '35%';
            }, 500);
        });
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
