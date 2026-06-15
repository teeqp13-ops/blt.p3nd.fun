<?php
/**
 * iPA Signer Bot Installer - Ultra Light Version
 * Designed to bypass server restrictions and fix 500 errors
 */

$config_file = 'config.php';

$message = "";
$status = "info";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup'])) {
    $bot_token = $_POST['bot_token'] ?? '';
    $github_token = $_POST['github_token'] ?? '';
    $github_owner = $_POST['github_owner'] ?? '';
    $github_repo = $_POST['github_repo'] ?? '';
    $server_url = $_POST['server_url'] ?? '';
    $admin_id = $_POST['admin_id'] ?? '';

    $config_content = "<?php
define('BOT_TOKEN', '$bot_token');
define('ADMIN_ID', '$admin_id');
define('BASE_DIR', __DIR__);
define('UPLOAD_DIR', BASE_DIR . '/uploads/');
define('SIGNED_DIR', BASE_DIR . '/signed/');
define('CERTS_DIR', BASE_DIR . '/certs/');
define('ZSIGN_PATH', BASE_DIR . '/zsign');
define('SERVER_URL', '$server_url');
define('GITHUB_REPO_OWNER', '$github_owner');
define('GITHUB_REPO_NAME', '$github_repo');
define('GITHUB_TOKEN', '$github_token');
?>";

    if (file_put_contents($config_file, $config_content)) {
        $message = "✅ تم الحفظ بنجاح!";
        $status = "success";
    } else {
        $message = "❌ فشل الحفظ. تأكد من الصلاحيات.";
        $status = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعداد البوت</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; text-align: center; }
        .box { background: white; max-width: 400px; margin: auto; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; width: 100%; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="box">
    <h2>🚀 إعداد البوت</h2>
    
    <?php if ($message): ?>
        <div class="alert <?php echo $status == 'success' ? 'success' : 'danger'; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="bot_token" placeholder="توكن البوت" required>
        <input type="text" name="admin_id" placeholder="ID الأدمن">
        <input type="url" name="server_url" placeholder="رابط السيرفر (https://...)" required>
        <input type="password" name="github_token" placeholder="GitHub Token" required>
        <input type="text" name="github_owner" placeholder="GitHub Username" required>
        <input type="text" name="github_repo" placeholder="Repo Name" required>
        <button type="submit" name="setup">حفظ الإعدادات</button>
    </form>
    <p style="font-size: 12px; color: #666; margin-top: 20px;">ملاحظة: تأكد من رفع الشهادات يدوياً لمجلد certs</p>
</div>

</body>
</html>
