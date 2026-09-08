<?php

require_once __DIR__ . "/../app/controllers/document_storage.php";

function assertUploadErrorContains($upload, $expected)
{
    $actual = bestcoproDocumentUploadError($upload);
    if (strpos($actual, $expected) === false) {
        fwrite(STDERR, "Expected upload error containing '" . $expected . "', got '" . $actual . "'." . PHP_EOL);
        exit(1);
    }
}

assertUploadErrorContains(null, "choisir un fichier");
assertUploadErrorContains(["error" => UPLOAD_ERR_INI_SIZE], "taille maximale");
assertUploadErrorContains([
    "error" => UPLOAD_ERR_OK,
    "size" => 5 * 1024 * 1024 + 1,
    "name" => "document.pdf",
], "5 Mo");
assertUploadErrorContains([
    "error" => UPLOAD_ERR_OK,
    "size" => 10,
    "name" => "document.exe",
], "Format non autorisé");

$temporaryFile = tempnam(sys_get_temp_dir(), "bestcopro-upload-");
file_put_contents($temporaryFile, "%PDF-test");
$validUpload = [
    "error" => UPLOAD_ERR_OK,
    "size" => filesize($temporaryFile),
    "name" => "document.PDF",
    "tmp_name" => $temporaryFile,
];
if (bestcoproDocumentUploadError($validUpload) !== "") {
    fwrite(STDERR, "A valid PDF upload was rejected." . PHP_EOL);
    @unlink($temporaryFile);
    exit(1);
}
@unlink($temporaryFile);

fwrite(STDOUT, "OK - document upload validation" . PHP_EOL);
