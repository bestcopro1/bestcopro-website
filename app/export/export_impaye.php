<?php

// L'ancien export dupliquait un calcul incomplet et pouvait mélanger les exercices.
// La situation des impayés utilise désormais la même source de vérité que le relevé annuel.
if (!isset($_GET["date_situation"]) || $_GET["date_situation"] === "") {
    $_GET["date_situation"] = date("Y-m-d");
}
$_GET["export_prefix"] = "situation_impayes";

require __DIR__ . "/export_cotisation.php";
