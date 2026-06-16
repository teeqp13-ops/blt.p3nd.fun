<?php
// تفعيل تسجيل الأخطاء
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

require_once 'config.php';
require_once 'utils.php';
require_once 'signer.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$message = $update['message'] ?? null;
if (!$message) {
    exit;
}

$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$document = $message['document'] ?? null;

// الترحيب
if ($text == '/start') {
    sendMessage($chatId, "أهلاً بك في بوت توقيع تطبيقات IPA. 🚀\n\nقم بإرسال ملف الـ IPA الذي تود توقيعه.");
    exit;
}

// التعامل مع الملفات المرفوعة
if ($document) {
    $fileName = $document['file_name'];
    $fileId = $document['file_id'];
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);

    if (strtolower($ext) == 'ipa') {
        sendMessage($chatId, "جاري تحميل ملف IPA... ⏳");
        $localPath = UPLOAD_DIR . time() . "_" . $fileName;
        
        if (downloadFile($fileId, $localPath)) {
            sendMessage($chatId, "تم التحميل بنجاح. جاري البدء في عملية التوقيع... ✍️");
            
            $p12 = CERTS_DIR . 'cert.p12';
            $pass = '123456'; // يمكنك جعل هذا متغيراً في config
            $mp = CERTS_DIR . 'prov.mobileprovision';
            $out = SIGNED_DIR . 'signed_' . time() . '_' . $fileName;

            if (!file_exists($p12) || !file_exists($mp)) {
                sendMessage($chatId, "خطأ: لم يتم العثور على ملفات الشهادة (cert.p12 أو prov.mobileprovision) في السيرفر.");
                exit;
            }

            $result = signIpa($localPath, $p12, $pass, $mp, $out);

            if ($result['success']) {
                $releaseTag = 'v' . time();
                $releaseName = 'Signed IPA - ' . basename($out);
                $releaseBody = 'Signed IPA file and plist for ' . basename($out);

                $createReleaseResult = createGitHubRelease($releaseTag, $releaseName, $releaseBody);

                if ($createReleaseResult['success']) {
                    $uploadIpaResult = uploadToGitHubRelease($out, $releaseTag, basename($out));

                    if ($uploadIpaResult['success']) {
                        $ipaUrl = $uploadIpaResult['response']['browser_download_url'];
                        $plistContent = generatePlist($ipaUrl, 'com.p3nd.app', '1.0', 'Signed App');
                        $plistName = 'install_' . time() . '.plist';
                        $tempPlistPath = SIGNED_DIR . $plistName;
                        file_put_contents($tempPlistPath, $plistContent);

                        $uploadPlistResult = uploadToGitHubRelease($tempPlistPath, $releaseTag, $plistName);

                        if ($uploadPlistResult['success']) {
                            $plistUrl = $uploadPlistResult['response']['browser_download_url'];
                            $installUrl = "itms-services://?action=download-manifest&url=" . $plistUrl;

                            $keyboard = [
                                'inline_keyboard' => [
                                    [['text' => 'تثبيت التطبيق 📲', 'url' => $installUrl]],
                                    [['text' => 'تحميل ملف IPA 📥', 'url' => $ipaUrl]]
                                ]
                            ];
                            sendMessage($chatId, "✅ تم توقيع التطبيق ورفعه إلى GitHub Releases بنجاح!", $keyboard);
                        } else {
                            sendMessage($chatId, "❌ فشل رفع ملف الـ plist.");
                        }
                    } else {
                        sendMessage($chatId, "❌ فشل رفع ملف IPA.");
                    }
                } else {
                    sendMessage($chatId, "❌ فشل إنشاء إصدار GitHub.");
                }
            } else {
                sendMessage($chatId, "❌ فشلت عملية التوقيع.\n\nالخطأ:\n" . $result['output']);
            }
        } else {
            sendMessage($chatId, "❌ فشل تحميل الملف من تيليجرام.");
        }
    } else {
        sendMessage($chatId, "الرجاء إرسال ملف بصيغة .ipa فقط.");
    }
}
?>
