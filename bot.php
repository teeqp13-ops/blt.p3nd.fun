<?php
require_once 'config.php';
require_once 'utils.php';
require_once 'signer.php';
require_once 'extractor.php'; // إضافة ملف extractor.php

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
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🌐 زيارة الموقع', 'url' => SERVER_URL]],
                [['text' => '👨‍💻 المطور', 'url' => 'https://github.com/teeqp13-ops']]
            ]
        ];
        sendMessage($chatId, "أهلاً بك في بوت توقيع تطبيقات IPA. 🚀\n\nقم بإرسال ملف الـ IPA الذي تود توقيعه.", $keyboard);
        exit;
    }

// التعامل مع الملفات المرفوعة
if ($document) {
    $fileName = $document['file_name'];
    $fileId = $document['file_id'];
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);

    if ($ext == 'ipa') {
        sendMessage($chatId, "جاري تحميل ملف IPA... ⏳");
        $localPath = UPLOAD_DIR . time() . "_" . $fileName;
        if (downloadFile($fileId, $localPath)) {
            sendMessage($chatId, "تم التحميل بنجاح. جاري البدء في عملية التوقيع... ✍️");
            
            // ملاحظة: هنا يجب أن يكون لديك ملفات الشهادة والبروفايل مسبقاً في مجلد certs
            // في هذا المثال سنفترض وجود ملفات افتراضية
            $p12 = CERTS_DIR . 'cert.p12';
            $pass = '123456'; // يجب استبدال هذا بكلمة مرور الشهادة الفعلية
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
                    // Upload IPA
                    $uploadIpaResult = uploadToGitHubRelease($out, $releaseTag, basename($out));

                    if ($uploadIpaResult['success']) {
                        $ipaUrl = $uploadIpaResult['response']['browser_download_url'];

                        // ==================================================================
                        // START: استخدام extractor.php لاستخراج معلومات التطبيق والأيقونة
                        // ==================================================================
                        $ipaInfo = extractIpaInfo($localPath);
                        $bundleId = $ipaInfo['bundleId'];
                        $version = $ipaInfo['version'];
                        $appName = $ipaInfo['appName'];
                        $iconUrl = '';

                        if (!empty($ipaInfo['iconPath'])) {
                            $iconName = 'icon_' . time() . '.png';
                            $uploadIconResult = uploadToGitHubRelease($ipaInfo['iconPath'], $releaseTag, $iconName);
                            if ($uploadIconResult['success']) {
                                $iconUrl = $uploadIconResult['response']['browser_download_url'];
                            }
                        }
                        // ==================================================================
                        // END: استخدام extractor.php لاستخراج معلومات التطبيق والأيقونة
                        // ==================================================================

                        // توليد plist content باستخدام الـ IPA URL الفعلي من GitHub ورابط الأيقونة (إن وجد)
                        $plistContent = generatePlist($ipaUrl, $bundleId, $version, $appName, $iconUrl);
                        $plistName = 'install_' . time() . '.plist';
                        $tempPlistPath = SIGNED_DIR . $plistName;
                        file_put_contents($tempPlistPath, $plistContent);

                        // Upload plist
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
                            sendMessage($chatId, "❌ فشل رفع ملف الـ plist إلى GitHub Releases.");
                        }
                    } else {
                        sendMessage($chatId, "❌ فشل رفع ملف IPA إلى GitHub Releases.");
                    }
                } else {
                    sendMessage($chatId, "❌ فشل إنشاء إصدار جديد على GitHub Releases.\n\nالخطأ:\n" . json_encode($createReleaseResult['response']));
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
