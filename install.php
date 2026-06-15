<?php
/**
 * iPA Signer Bot Installer
 * Created to simplify the setup process
 */

session_start();

$config_file = 'config.php';
$certs_dir = 'certs/';
$uploads_dir = 'uploads/';
$signed_dir = 'signed/';

// التأكد من وجود المجلدات
if (!file_exists($certs_dir)) mkdir($certs_dir, 0777, true);
if (!file_exists($uploads_dir)) mkdir($uploads_dir, 0777, true);
if (!file_exists($signed_dir)) mkdir($signed_dir, 0777, true);

$message = "";
$status = "info";

// معالجة النموذج عند الإرسال
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup'])) {
    $bot_token = $_POST['bot_token'];
    $github_token = $_POST['github_token'];
    $github_owner = $_POST['github_owner'];
    $github_repo = $_POST['github_repo'];
    $server_url = $_POST['server_url'];
    $admin_id = $_POST['admin_id'];

    // رفع الشهادات
    if (isset($_FILES['p12_file']) && $_FILES['p12_file']['error'] == 0) {
        move_uploaded_file($_FILES['p12_file']['tmp_name'], $certs_dir . 'cert.p12');
    }
    if (isset($_FILES['provision_file']) && $_FILES['provision_file']['error'] == 0) {
        move_uploaded_file($_FILES['provision_file']['tmp_name'], $certs_dir . 'prov.mobileprovision');
    }

    // تحديث ملف config.php
    $config_content = "<?php
// إعدادات بوت تيليجرام
define('BOT_TOKEN', '$bot_token');
define('ADMIN_ID', '$admin_id');

// مسارات المجلدات
define('BASE_DIR', __DIR__);
define('UPLOAD_DIR', BASE_DIR . '/uploads/');
define('SIGNED_DIR', BASE_DIR . '/signed/');
define('CERTS_DIR', BASE_DIR . '/certs/');

// إعدادات zsign
define('ZSIGN_PATH', 'zsign');

// رابط السيرفر
define('SERVER_URL', '$server_url');

// إعدادات GitHub Releases
define('GITHUB_REPO_OWNER', '$github_owner');
define('GITHUB_REPO_NAME', '$github_repo');
define('GITHUB_TOKEN', '$github_token');
?>";

    if (file_put_contents($config_file, $config_content)) {
        $message = "✅ تم حفظ الإعدادات بنجاح! يمكنك الآن استخدام البوت.";
        $status = "success";
    } else {
        $message = "❌ فشل حفظ الإعدادات. تأكد من صلاحيات الكتابة لملف config.php";
        $status = "danger";
    }
}

// فحص المتطلبات
$zsign_installed = shell_exec('which zsign') ? true : false;
$config_writable = is_writable($config_file) || !file_exists($config_file);
$certs_writable = is_writable($certs_dir);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مُنصب بوت توقيع IPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .installer-container { max-width: 700px; margin: 50px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="installer-container">
        <h2 class="text-center mb-4">🚀 إعداد بوت توقيع IPA</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $status; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">فحص جاهزية السيرفر</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        أداة zsign
                        <span class="badge <?php echo $zsign_installed ? 'bg-success' : 'bg-danger'; ?> status-badge">
                            <?php echo $zsign_installed ? 'مثبتة' : 'غير موجودة'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        صلاحيات ملف الإعدادات
                        <span class="badge <?php echo $config_writable ? 'bg-success' : 'bg-danger'; ?> status-badge">
                            <?php echo $config_writable ? 'قابلة للكتابة' : 'غير قابلة للكتابة'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        مجلد الشهادات
                        <span class="badge <?php echo $certs_writable ? 'bg-success' : 'bg-danger'; ?> status-badge">
                            <?php echo $certs_writable ? 'جاهز' : 'تحتاج صلاحيات'; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">توكن بوت تيليجرام (Telegram Bot Token)</label>
                <input type="text" name="bot_token" class="form-control" placeholder="123456789:ABCDEF..." required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">معرف الأدمن (Admin ID)</label>
                    <input type="text" name="admin_id" class="form-control" placeholder="12345678">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">رابط السيرفر (HTTPS Server URL)</label>
                    <input type="url" name="server_url" class="form-control" placeholder="https://domain.com/bot/" required>
                </div>
            </div>

            <hr>
            <h5>إعدادات GitHub</h5>
            <div class="mb-3">
                <label class="form-label">GitHub Personal Access Token</label>
                <input type="password" name="github_token" class="form-control" placeholder="ghp_xxxxxxxxxxxx" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">مالك المستودع (Owner)</label>
                    <input type="text" name="github_owner" class="form-control" placeholder="username" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">اسم المستودع (Repo Name)</label>
                    <input type="text" name="github_repo" class="form-control" placeholder="ipa-signer-bot" required>
                </div>
            </div>

            <hr>
            <h5>رفع الشهادات</h5>
            <div class="mb-3">
                <label class="form-label">ملف الشهادة (cert.p12)</label>
                <input type="file" name="p12_file" class="form-control" accept=".p12">
            </div>
            <div class="mb-3">
                <label class="form-label">ملف البروفايل (prov.mobileprovision)</label>
                <input type="file" name="provision_file" class="form-control" accept=".mobileprovision">
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="setup" class="btn btn-success btn-lg">حفظ الإعدادات وتفعيل البوت</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
