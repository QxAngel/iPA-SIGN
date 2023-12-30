<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener una ruta temporal única para almacenar archivos firmados
    $tempFolder = "signed_ipas/" . uniqid();

    // Crear la carpeta temporal si no existe
    if (!file_exists($tempFolder)) {
        mkdir($tempFolder, 0777, true);
    }

    // Obtener las rutas de los archivos
    $file = $_FILES["ipaFile"]["tmp_name"];
    $certificate = $_FILES["certificate"]["tmp_name"];
    $password = $_POST["password"];
    $mobileprovision = $_FILES["mobileprovision"]["tmp_name"];

    // Cargar el contenido del archivo .ipa
    $data = file_get_contents($file);

    // Crear una instancia de ZipArchive para extraer el archivo .app
    $zip = new ZipArchive;
    if ($zip->open($file) === TRUE) {
        $zip->extractTo($tempFolder);
        $zip->close();
    }

    // Obtener el nombre de la aplicación y el identificador de paquete desde Info.plist
    $infoPlistData = file_get_contents("$tempFolder/Payload/*.app/Info.plist");
    $infoPlist = new SimpleXMLElement($infoPlistData);
    $appName = (string) $infoPlist->CFBundleExecutable;
    $appBundleId = (string) $infoPlist->CFBundleIdentifier;

    // Cargar el archivo de aprovisionamiento móvil
    $mobileprovisionData = file_get_contents($mobileprovision);

    // Escribir el archivo de aprovisionamiento móvil en la carpeta temporal
    $mobileprovisionPath = "$tempFolder/embedded.mobileprovision";
    file_put_contents($mobileprovisionPath, $mobileprovisionData);

    // Firmar la aplicación
    $cmd = "codesign -f -s \"$certificate\" --keychain login.keychain --entitlements entitlements.plist --provisioning-profile $mobileprovisionPath $tempFolder/Payload/$appName.app";
    exec($cmd);

    // Crear un archivo .ipa firmado en la carpeta temporal
    $signedIPAPath = "$tempFolder/signed-file.ipa";
    $zip = new ZipArchive;
    if ($zip->open($signedIPAPath, ZipArchive::CREATE) === TRUE) {
        $zip->addFile("$tempFolder/Payload/$appName.app", "$appName.app");
        $zip->close();
    }

    // Eliminar la carpeta temporal después de firmar
    exec("rm -rf $tempFolder");

    // Redirigir a la página de descarga
    header("Location: download.php");
    exit;
}
?>
