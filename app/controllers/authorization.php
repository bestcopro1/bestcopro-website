<?php

function bestcopro_access_catalog()
{
    return [
        "dashboard" => ["label" => "Tableau de bord", "group" => "Général"],
        "copropriete" => ["label" => "Informations de la copropriété", "group" => "Copropriété"],
        "lots" => ["label" => "Situation des copropriétaires", "group" => "Copropriété"],
        "situation_immeuble" => ["label" => "Recouvrements et impayés", "group" => "Copropriété"],
        "suivi_cotisations_coproprietaires" => ["label" => "Suivi des cotisations", "group" => "Copropriété"],
        "creation_poste_budgetaire" => ["label" => "Création des postes budgétaires", "group" => "Copropriété"],
        "postes_comptables_budgetaires" => ["label" => "Postes comptables budgétaires", "group" => "Copropriété"],
        "contentieux" => ["label" => "Contentieux", "group" => "Copropriété"],
        "proprietaires" => ["label" => "Propriétaires", "group" => "Copropriété"],
        "paiements" => ["label" => "Paiements", "group" => "Gestion financière"],
        "depenses" => ["label" => "Dépenses", "group" => "Gestion financière"],
        "fournisseurs" => ["label" => "Fournisseurs", "group" => "Gestion financière"],
        "fonctionnement" => ["label" => "Budget de fonctionnement", "group" => "Gestion financière"],
        "suivi_budget" => ["label" => "Suivi du budget", "group" => "Gestion financière"],
        "investissement" => ["label" => "Budget d'investissement", "group" => "Gestion financière"],
        "assemblee" => ["label" => "Assemblée générale", "group" => "Suivi"],
        "reclamations" => ["label" => "Réclamations", "group" => "Suivi"],
        "actions" => ["label" => "Plans d'action", "group" => "Suivi"],
        "echeances" => ["label" => "Échéances", "group" => "Suivi"],
        "documents" => ["label" => "Documents", "group" => "Suivi"],
        "notifications" => ["label" => "Notifications", "group" => "Suivi"],
        "gerer_coproprietes" => ["label" => "Créer et modifier les copropriétés", "group" => "Administration"],
        "collaborateurs" => ["label" => "Gérer les collaborateurs", "group" => "Administration"],
    ];
}

function bestcopro_is_access_admin($roleId = null)
{
    if ($roleId === null) {
        $roleId = isset($_SESSION["id_usertype"]) ? $_SESSION["id_usertype"] : null;
    }

    return in_array((string) $roleId, ["1", "2"], true);
}

function bestcopro_legacy_permission_default($roleId, $permissionCode)
{
    $roleId = (string) $roleId;
    if (in_array($roleId, ["1", "2", "3"], true)) {
        return true;
    }

    if ($roleId === "4") {
        return !in_array(
            $permissionCode,
            ["assemblee", "actions", "gerer_coproprietes", "collaborateurs"],
            true
        );
    }

    return false;
}

function bestcopro_ensure_access_schema($connection, &$error = null)
{
    $request = "CREATE TABLE IF NOT EXISTS typesyndic_permission (
        id_typeSyndic INT NOT NULL,
        permission_code VARCHAR(80) NOT NULL,
        autorise TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id_typeSyndic, permission_code),
        INDEX idx_permission_code (permission_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$connection->query($request)) {
        $error = $connection->error;
        return false;
    }

    $roleIds = [];
    if ($result = $connection->query("SELECT id FROM typesyndic")) {
        while ($row = $result->fetch_assoc()) {
            $roleIds[] = (int) $row["id"];
        }
        $result->free();
    } else {
        $error = $connection->error;
        return false;
    }

    $stmt = $connection->prepare(
        "INSERT IGNORE INTO typesyndic_permission (id_typeSyndic, permission_code, autorise) VALUES (?, ?, ?)"
    );
    if (!$stmt) {
        $error = $connection->error;
        return false;
    }

    foreach ($roleIds as $roleId) {
        foreach (array_keys(bestcopro_access_catalog()) as $permissionCode) {
            $allowed = bestcopro_legacy_permission_default($roleId, $permissionCode) ? 1 : 0;
            $stmt->bind_param("isi", $roleId, $permissionCode, $allowed);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                return false;
            }
        }
    }
    $stmt->close();

    return true;
}

function bestcopro_role_has_permission($roleId, $permissionCode, $connection = null)
{
    static $roleCache = [];

    if ($permissionCode === "access_control") {
        return bestcopro_is_access_admin($roleId);
    }

    if (!array_key_exists($permissionCode, bestcopro_access_catalog())) {
        return false;
    }

    if (!$connection && isset($GLOBALS["connection"]) && $GLOBALS["connection"] instanceof mysqli) {
        $connection = $GLOBALS["connection"];
    }

    if (!$connection) {
        return bestcopro_legacy_permission_default($roleId, $permissionCode);
    }

    $roleKey = (string) $roleId;
    if (!array_key_exists($roleKey, $roleCache)) {
        $roleCache[$roleKey] = [];
        $stmt = $connection->prepare(
            "SELECT permission_code, autorise FROM typesyndic_permission WHERE id_typeSyndic = ?"
        );
        if (!$stmt) {
            $roleCache[$roleKey] = null;
        } else {
            $roleIdValue = (int) $roleId;
            $stmt->bind_param("i", $roleIdValue);
            if ($stmt->execute()) {
                $stmt->bind_result($storedCode, $allowed);
                while ($stmt->fetch()) {
                    $roleCache[$roleKey][$storedCode] = (int) $allowed === 1;
                }
            } else {
                $roleCache[$roleKey] = null;
            }
            $stmt->close();
        }
    }

    if (is_array($roleCache[$roleKey]) && array_key_exists($permissionCode, $roleCache[$roleKey])) {
        return $roleCache[$roleKey][$permissionCode];
    }

    return bestcopro_legacy_permission_default($roleId, $permissionCode);
}

function bestcopro_get_role_permissions($roleId, $connection)
{
    $permissions = [];
    foreach (array_keys(bestcopro_access_catalog()) as $permissionCode) {
        $permissions[$permissionCode] = bestcopro_role_has_permission($roleId, $permissionCode, $connection);
    }
    return $permissions;
}
