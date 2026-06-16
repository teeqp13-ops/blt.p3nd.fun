<?php

function extractIpaInfo($ipaPath) {
    $info = [
        'bundleId' => 'com.example.app',
        'version' => '1.0',
        'appName' => 'Signed App',
        'iconPath' => '',
        'iconUrl' => ''
    ];

    // 1. استخراج Info.plist
    $tempDir = sys_get_temp_dir() . '/' . uniqid('ipa_extract_');
    mkdir($tempDir);
    $command = 'unzip -j ' . escapeshellarg($ipaPath) . ' "Payload/*.app/Info.plist" -d ' . escapeshellarg($tempDir) . ' 2>&1';
    exec($command, $output, $returnVar);

    if ($returnVar === 0 && file_exists($tempDir . '/Info.plist')) {
        $plistContent = file_get_contents($tempDir . '/Info.plist');
        $plist = simplexml_load_string($plistContent, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($plist) {
            $dict = (array)$plist->dict;
            $keys = [];
            $values = [];
            foreach ($dict['key'] as $key) {
                $keys[] = (string)$key;
            }
            foreach ($dict['string'] as $string) {
                $values[] = (string)$string;
            }
            $infoArray = array_combine($keys, $values);

            $info['bundleId'] = $infoArray['CFBundleIdentifier'] ?? $info['bundleId'];
            $info['version'] = $infoArray['CFBundleShortVersionString'] ?? $info['version'];
            $info['appName'] = $infoArray['CFBundleDisplayName'] ?? $info['appName'];
        }
    }

    // 2. استخراج الأيقونة (نبحث عن أكبر أيقونة متوفرة)
    $iconName = '';
    $iconSizes = [
        'AppIcon1024x1024', 'AppIcon180x180', 'AppIcon167x167', 'AppIcon152x152', 'AppIcon120x120', 'AppIcon76x76', 'AppIcon60x60'
    ];

    foreach ($iconSizes as $size) {
        $iconPathInIpa = 'Payload/*.app/' . $size . '@*x.png';
        $command = 'unzip -l ' . escapeshellarg($ipaPath) . ' | grep -m 1 "' . $iconPathInIpa . '"';
        exec($command, $output, $returnVar);
        if ($returnVar === 0 && !empty($output)) {
            // استخراج اسم الملف الفعلي من ناتج grep
            preg_match('/Payload\/.*\.app\/(AppIcon.*\.png)/', $output[0], $matches);
            if (isset($matches[1])) {
                $iconName = $matches[1];
                break;
            }
        }
    }

    if (!empty($iconName)) {
        $extractedIconPath = $tempDir . '/' . $iconName;
        $command = 'unzip -j ' . escapeshellarg($ipaPath) . ' "Payload/*.app/' . $iconName . '" -d ' . escapeshellarg($tempDir) . ' 2>&1';
        exec($command, $output, $returnVar);
        if ($returnVar === 0 && file_exists($extractedIconPath)) {
            $info['iconPath'] = $extractedIconPath;
        }
    }

    // تنظيف الملفات المؤقتة (اختياري، يمكن تركها للتصحيح)
    // rmdir($tempDir); // لا تحذف المجلد إذا كان يحتوي على ملفات

    return $info;
}

?>
