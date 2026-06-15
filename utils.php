<?php
require_once 'config.php';

function sendMessage($chatId, $text, $replyMarkup = null) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    if ($replyMarkup) {
        $data['reply_markup'] = json_encode($replyMarkup);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function downloadFile($fileId, $destination) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getFile?file_id=" . $fileId;
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['ok']) {
        $filePath = $data['result']['file_path'];
        $fileUrl = "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $filePath;
        return copy($fileUrl, $destination);
    }
    return false;
}

function generatePlist($ipaUrl, $bundleId, $version, $appName) {
    $plist = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>items</key>
    <array>
        <dict>
            <key>assets</key>
            <array>
                <dict>
                    <key>kind</key>
                    <string>software-package</string>
                    <key>url</key>
                    <string>' . htmlspecialchars($ipaUrl) . '</string>
                </dict>
            </array>
            <key>metadata</key>
            <dict>
                <key>bundle-identifier</key>
                <string>' . htmlspecialchars($bundleId) . '</string>
                <key>bundle-version</key>
                <string>' . htmlspecialchars($version) . '</string>
                <key>kind</key>
                <string>software</string>
                <key>title</key>
                <string>' . htmlspecialchars($appName) . '</string>
            </dict>
        </dict>
    </array>
</dict>
</plist>';
    return $plist;
}
?>

function uploadToGitHubRelease($filePath, $releaseTag, $assetName) {
    $url = "https://uploads.github.com/repos/" . GITHUB_REPO_OWNER . "/" . GITHUB_REPO_NAME . "/releases/tags/" . $releaseTag . "/assets?name=" . urlencode($assetName);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GITHUB_TOKEN,
        "Content-Type: application/octet-stream"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'response' => $responseData
    ];
}

function createGitHubRelease($releaseTag, $releaseName, $body) {
    $url = "https://api.github.com/repos/" . GITHUB_REPO_OWNER . "/" . GITHUB_REPO_NAME . "/releases";
    
    $data = [
        "tag_name" => $releaseTag,
        "name" => $releaseName,
        "body" => $body,
        "draft" => false,
        "prerelease" => false
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GITHUB_TOKEN,
        "Content-Type: application/json",
        "User-Agent: PHP-Telegram-Bot"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'response' => $responseData
    ];
}
