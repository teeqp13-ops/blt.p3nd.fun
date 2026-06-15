<?php
require_once 'config.php';

function signIpa($inputIpa, $p12File, $p12Password, $mobileProvision, $outputIpa) {
    // بناء أمر zsign
    // zsign -k privkey.p12 -p password -m dev.mobileprovision -o output.ipa input.ipa
    $command = sprintf(
        '%s -k %s -p %s -m %s -o %s %s 2>&1',
        escapeshellarg(ZSIGN_PATH),
        escapeshellarg($p12File),
        escapeshellarg($p12Password),
        escapeshellarg($mobileProvision),
        escapeshellarg($outputIpa),
        escapeshellarg($inputIpa)
    );
    
    exec($command, $output, $returnVar);
    
    return [
        'success' => ($returnVar === 0),
        'output' => implode("\n", $output)
    ];
}
?>
