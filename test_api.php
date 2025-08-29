<?php
/**
 * اسکریپت تست API های زیتو
 */

echo "🧪 شروع تست API های زیتو\n";
echo "============================\n\n";

// تست config API
echo "1️⃣ تست Config API:\n";
$config_response = file_get_contents('http://localhost:8000/api/config.php');
$config_data = json_decode($config_response, true);
echo "Response: " . $config_response . "\n\n";

// تست ثبت نام
echo "2️⃣ تست Register API:\n";
$register_data = [
    'name' => 'کاربر تست',
    'mobile' => '09123456789',
    'password' => '123456'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($register_data)
    ]
]);

$register_response = file_get_contents('http://localhost:8000/api/auth/register.php', false, $context);
echo "Response: " . $register_response . "\n\n";

// تست لیست uploads (خالی)
echo "3️⃣ تست Upload List API:\n";
$list_response = file_get_contents('http://localhost:8000/api/upload/list.php');
echo "Response: " . $list_response . "\n\n";

echo "✅ تست API ها کامل شد!\n";
?>
