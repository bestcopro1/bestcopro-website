<?php

require_once __DIR__ . "/../app/export/export_calculations.php";

function testAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "ECHEC: " . $message . PHP_EOL);
        exit(1);
    }
}

$periods = [
    ["montantDu" => 300, "montantPaye" => 300],
    ["montantDu" => 450, "montantPaye" => 200],
    ["montantDu" => 275, "montantPaye" => 0],
];
$calculation = bestcopro_export_calculate_period_cells($periods, [true, true, false], 3);
testAssert($calculation["cells"][0]["display"] === 300.0, "la premiere periode doit conserver son montant reel");
testAssert($calculation["cells"][1]["display"] === 200.0, "la deuxieme periode doit conserver son paiement reel");
testAssert($calculation["cells"][2]["display"] === null, "une periode impayee doit rester vide");
testAssert(abs($calculation["reste"] - 250.0) < 0.001, "seules les periodes echues alimentent le reste");

$annualAsMonthly = bestcopro_export_rebucket_period_rows(
    [["montantDu" => 3600, "montantPaye" => 750]],
    12
);
testAssert(count($annualAsMonthly) === 12, "une cotisation annuelle doit pouvoir etre detaillee sur douze mois");
testAssert($annualAsMonthly[0]["montantPaye"] === 300.0, "le paiement annuel doit commencer par la premiere periode");
testAssert($annualAsMonthly[2]["montantPaye"] === 150.0, "le reliquat doit rester sur sa periode reelle");
testAssert($annualAsMonthly[3]["montantPaye"] === 0.0, "les periodes suivantes ne doivent pas etre marquees payees");

$monthlyAsQuarterly = bestcopro_export_rebucket_period_rows(
    array_fill(0, 12, ["montantDu" => 300, "montantPaye" => 300]),
    4
);
testAssert($monthlyAsQuarterly[0]["montantDu"] === 900.0, "trois mensualites doivent former un trimestre");
testAssert($monthlyAsQuarterly[0]["montantPaye"] === 900.0, "les paiements du trimestre doivent etre additionnes");

$plan = bestcopro_export_build_reconciliation_plan(
    [
        ["id" => 10, "remaining" => 400],
        ["id" => 11, "remaining" => 500],
    ],
    [
        ["id_rel" => 101, "missing" => 300],
        ["id_rel" => 102, "missing" => 450],
    ]
);
testAssert(count($plan["allocations"]) === 3, "le plan doit repartir les anciens paiements sans en inventer");
testAssert($plan["allocations"][0]["id_paiement"] === 10, "le paiement le plus ancien doit etre utilise en premier");
testAssert($plan["allocations"][0]["id_rel"] === 101, "la periode la plus ancienne doit etre soldee en premier");
testAssert(abs($plan["unresolved"]) < 0.001, "les montants disponibles doivent etre totalement reconciliables");

$ranges = bestcopro_export_month_ranges("2026-07-01", 12);
testAssert(count($ranges) === 12, "douze plages mensuelles sont attendues");
for ($i = 0; $i < 11; $i++) {
    testAssert(
        $ranges[$i]["toExclusive"] === $ranges[$i + 1]["from"],
        "les plages de depenses ne doivent ni se chevaucher ni laisser de trou"
    );
}

$exportDir = realpath(__DIR__ . "/../app/export");
$exportFiles = glob($exportDir . DIRECTORY_SEPARATOR . "export_*.php");
foreach ($exportFiles as $file) {
    $name = basename($file);
    if (in_array($name, ["export_common.php", "export_calculations.php"], true)) {
        continue;
    }
    $source = file_get_contents($file);
    if ($name === "export_impaye.php") {
        testAssert(strpos($source, "export_cotisation.php") !== false, $name . " doit deleguer au moteur securise");
        continue;
    }
    testAssert(strpos($source, "export_common.php") !== false, $name . " doit charger la securite commune");
    testAssert(
        preg_match('/bestcopro_export_require_(?:copropriete|exercise|lot|payment)_access/', $source) === 1,
        $name . " doit verifier le perimetre de donnees"
    );
}

foreach (glob($exportDir . DIRECTORY_SEPARATOR . "*.php") as $file) {
    $source = file_get_contents($file);
    testAssert(
        preg_match('/\b(?:ALTER|CREATE|DROP|TRUNCATE)\s+TABLE\b/i', $source) !== 1,
        basename($file) . " ne doit jamais migrer la base pendant un export"
    );
}

$version = trim(file_get_contents(__DIR__ . "/../app/vendor/dompdf/VERSION"));
testAssert(version_compare($version, "3.1.6", ">="), "Dompdf 3.1.6 minimum est requis");

fwrite(STDOUT, "OK - regressions export" . PHP_EOL);
