<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $file = $_FILES["ipaFile"]["tmp_name"];

    $certificate = $_FILES["certificate"]["tmp_name"];
    $password = $_POST["password"];
    $mobileprovision = $_FILES["mobileprovision"]["tmp_name"];

    $data = file_get_contents($file);

    $zip = new ZipArchive;
    if ($zip->open($file) === TRUE) {
        $zip->extractTo('my-temp-folder');
        $zip->close();
    }

    $infoPlistData = file_get_contents("my-temp-folder/Payload/*.app/Info.plist");
    $infoPlist = new SimpleXMLElement($infoPlistData);
    $appName = (string) $infoPlist->CFBundleExecutable;
    $appBundleId = (string) $infoPlist->CFBundleIdentifier;

    $mobileprovisionData = file_get_contents($mobileprovision);
  
    $fp = fopen("my-temp-folder/embedded.mobileprovision", "w");
    fwrite($fp, $mobileprovisionData);
    fclose($fp);
  
    $cmd = "codesign -f -s \"".$certificate."\" --keychain login.keychain --entitlements entitlements.plist --provisioning-profile my-temp-folder/embedded.mobileprovision my-temp-folder/Payload/".$appName.".app";
    exec($cmd);

    $zip = new ZipArchive;
    if ($zip->open("my-signed-file.ipa", ZipArchive::CREATE) === TRUE) {
        $zip->addFile("my-temp-folder/Payload/".$appName.".app", $appName.".app");
        $zip->close();
    }

    exec("rm -rf my-temp-folder");
}
?>
