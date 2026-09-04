<?php

$autoload = isset($argv[1]) ? $argv[1] : __DIR__ . "/../app/vendor/dompdf/autoload.inc.php";
$outputDir = isset($argv[2]) ? $argv[2] : sys_get_temp_dir() . "/bestcopro-pdf-smoke";
if (!is_file($autoload)) {
    fwrite(STDERR, "Autoload Dompdf introuvable: " . $autoload . PHP_EOL);
    exit(1);
}
if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
    fwrite(STDERR, "Dossier de sortie impossible: " . $outputDir . PHP_EOL);
    exit(1);
}

require_once $autoload;

$svgLogo = "data:image/svg+xml;base64," . base64_encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="44"><rect width="220" height="44" fill="#164194"/><text x="12" y="29" fill="white" font-size="20">BEST COPRO</text></svg>'
);
$pngPixel = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=";

$documents = [
    "budget" => "portrait",
    "contentieux" => "landscape",
    "cotisation" => "landscape",
    "depense" => "landscape",
    "etat_factures" => "landscape",
    "factures_non_payees" => "landscape",
    "impaye" => "landscape",
    "proprietaire" => "portrait",
    "recu_paiement" => "portrait",
    "situation_encaissements" => "landscape",
    "situation_immeuble" => "landscape",
    "suivi_budget" => "landscape",
    "suivi_cotisations" => "landscape",
];

$rows = "";
for ($i = 1; $i <= 28; $i++) {
    $rows .= "<tr><td>TBC01A" . str_pad((string) $i, 2, "0", STR_PAD_LEFT) .
        "</td><td>300,00</td><td>" . ($i % 3 === 0 ? "0,00" : "300,00") . "</td></tr>";
}

foreach ($documents as $name => $orientation) {
    $runtimeDir = $outputDir . DIRECTORY_SEPARATOR . "runtime";
    if (!is_dir($runtimeDir) && !mkdir($runtimeDir, 0777, true)) {
        fwrite(STDERR, "Dossier temporaire Dompdf impossible: " . $runtimeDir . PHP_EOL);
        exit(1);
    }
    $options = new Dompdf\Options();
    $options->setTempDir($runtimeDir);
    $options->setFontDir($runtimeDir);
    $options->setFontCache($runtimeDir);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml(
        '<!doctype html><html><head><meta charset="UTF-8"><style>' .
        '@page{margin:12px}body{font-family:DejaVu Sans,sans-serif;font-size:10px}' .
        'h1{color:#164194}table{width:100%;border-collapse:collapse}th,td{border:1px solid #222;padding:3px}' .
        'th{background:#d9eaf7}</style></head><body>' .
        '<img src="' . $svgLogo . '" width="220" height="44" alt="BEST COPRO">' .
        '<img src="' . $pngPixel . '" width="1" height="1" alt="">' .
        '<h1>BEST COPRO — ' . htmlspecialchars($name, ENT_QUOTES, "UTF-8") . '</h1>' .
        '<p>Exercice du 01/07/2026 au 30/06/2027</p>' .
        '<table><thead><tr><th>Lot</th><th>Cotisation</th><th>Reste</th></tr></thead><tbody>' .
        $rows . '</tbody></table></body></html>'
    );
    $dompdf->setPaper("A4", $orientation);
    $dompdf->render();
    $pdf = $dompdf->output();
    if (strncmp($pdf, "%PDF-", 5) !== 0 || strlen($pdf) < 1000) {
        fwrite(STDERR, "PDF invalide: " . $name . PHP_EOL);
        exit(1);
    }
    file_put_contents($outputDir . DIRECTORY_SEPARATOR . $name . ".pdf", $pdf);
}

fwrite(STDOUT, "OK - " . count($documents) . " PDF generes dans " . $outputDir . PHP_EOL);
