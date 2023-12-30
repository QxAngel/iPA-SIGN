<?php
// Verificar si el archivo .ipa firmado existe
$signedIPAPath = "signed_ipas/latest/signed-file.ipa";
if (file_exists($signedIPAPath)) {
    // Enviar encabezados para forzar la descarga del archivo
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="signed-file.ipa"');
    header('Content-Length: ' . filesize($signedIPAPath));
    
    // Leer y enviar el archivo al cliente
    readfile($signedIPAPath);
    exit;
} else {
    echo "El archivo firmado no está disponible.";
}
?>
