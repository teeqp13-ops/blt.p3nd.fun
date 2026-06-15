<?php
// إعدادات بوت تيليجرام
define(\'BOT_TOKEN\', \'8555548440:AAGETCIm2PAdSnj2_yroEPI4SQDpoaKutw\');
define('ADMIN_ID', 'YOUR_TELEGRAM_ID');

// مسارات المجلدات
define('BASE_DIR', __DIR__);
define('UPLOAD_DIR', BASE_DIR . '/uploads/');
define('SIGNED_DIR', BASE_DIR . '/signed/');
define('CERTS_DIR', BASE_DIR . '/certs/');

// إعدادات zsign (يجب تثبيته على السيرفر)
define('ZSIGN_PATH', 'zsign'); // أو المسار الكامل للأداة

// رابط السيرفر (مهم لتحميل ملفات الـ plist)
define('SERVER_URL', 'https://your-domain.com/ipa-signer-bot/');
?>

// إعدادات GitHub Releases
define("GITHUB_REPO_OWNER", "teeqp13-ops");
define("GITHUB_REPO_NAME", "ipa-signer-bot");
define("GITHUB_TOKEN", "YOUR_GITHUB_TOKEN"); // يجب أن يكون له صلاحية repo
