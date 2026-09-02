<?php
require_once __DIR__ . "/session.php";
bestcopro_start_session();

if (
    !isset($_SESSION["loggedin"], $_SESSION["id"], $_SESSION["id_usertype"]) ||
    $_SESSION["loggedin"] !== "ImIn"
) {
    header("Location: ./login.php");
    exit();
}

include_once __DIR__ . "/config/db.php";
include_once __DIR__ . "/controllers/functions.php";
$connection = $GLOBALS["connection"];

if (!bestcopro_is_access_admin()) {
    http_response_code(403);
    header("Location: ./index.php");
    exit();
}

if (empty($_SESSION["access_control_csrf"])) {
    $_SESSION["access_control_csrf"] = bin2hex(random_bytes(32));
}

function accessControlEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function accessControlRedirect($status, $roleId = null)
{
    $url = "access-control.php?status=" . rawurlencode($status);
    if ($roleId !== null) {
        $url .= "&role=" . (int) $roleId;
    }
    header("Location: " . $url, true, 303);
    exit();
}

function accessControlRoleExists($connection, $roleId)
{
    $stmt = $connection->prepare("SELECT id FROM typesyndic WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows === 1;
    $stmt->close();
    return $exists;
}

function accessControlHistory($connection, $action)
{
    $stmt = $connection->prepare(
        "INSERT INTO historique (date, action, id_collaborateur) VALUES (?, ?, ?)"
    );
    if (!$stmt) {
        return;
    }
    $date = date("Y-m-d H:i:s");
    $adminId = (string) $_SESSION["id"];
    $stmt->bind_param("sss", $date, $action, $adminId);
    $stmt->execute();
    $stmt->close();
}

$schemaError = null;
$schemaReady = bestcopro_ensure_access_schema($connection, $schemaError);
$formError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["access_control_csrf"], (string) $_POST["csrf_token"])
    ) {
        http_response_code(403);
        $formError = "La session du formulaire a expiré. Rechargez la page puis réessayez.";
    } elseif (!$schemaReady) {
        $formError = "La matrice des droits n'est pas disponible.";
    } else {
        $action = isset($_POST["action"]) ? (string) $_POST["action"] : "";

        if ($action === "create_role") {
            $roleName = trim((string) ($_POST["role_name"] ?? ""));
            if (mb_strlen($roleName) < 2 || mb_strlen($roleName) > 100) {
                $formError = "Le nom du rôle doit contenir entre 2 et 100 caractères.";
            } else {
                $stmt = $connection->prepare("INSERT INTO typesyndic (libelle) VALUES (?)");
                if ($stmt) {
                    $stmt->bind_param("s", $roleName);
                    if ($stmt->execute()) {
                        $newRoleId = (int) $connection->insert_id;
                        $stmt->close();
                        bestcopro_ensure_access_schema($connection, $schemaError);
                        accessControlHistory($connection, "a ajouté|role|" . $newRoleId);
                        accessControlRedirect("role_created", $newRoleId);
                    }
                    $formError = "Impossible de créer ce rôle. Vérifiez que son nom n'existe pas déjà.";
                    $stmt->close();
                } else {
                    $formError = "Impossible de préparer la création du rôle.";
                }
            }
        } elseif ($action === "save_role") {
            $roleId = (int) ($_POST["role_id"] ?? 0);
            $roleName = trim((string) ($_POST["role_name"] ?? ""));
            $selectedPermissions = isset($_POST["permissions"]) && is_array($_POST["permissions"])
                ? $_POST["permissions"]
                : [];

            if ((string) $_SESSION["id_usertype"] === "2" && $roleId === 1) {
                $formError = "Seul le super administrateur peut modifier le rôle Superadmin.";
            } elseif (!accessControlRoleExists($connection, $roleId)) {
                $formError = "Le rôle sélectionné n'existe pas.";
            } elseif (mb_strlen($roleName) < 2 || mb_strlen($roleName) > 100) {
                $formError = "Le nom du rôle doit contenir entre 2 et 100 caractères.";
            } else {
                $connection->begin_transaction();
                $saved = true;

                $stmt = $connection->prepare("UPDATE typesyndic SET libelle = ? WHERE id = ?");
                if (!$stmt) {
                    $saved = false;
                } else {
                    $stmt->bind_param("si", $roleName, $roleId);
                    $saved = $stmt->execute();
                    $stmt->close();
                }

                $permissionStmt = $connection->prepare(
                    "INSERT INTO typesyndic_permission (id_typeSyndic, permission_code, autorise)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE autorise = VALUES(autorise)"
                );
                if (!$permissionStmt) {
                    $saved = false;
                }

                if ($saved) {
                    foreach (array_keys(bestcopro_access_catalog()) as $permissionCode) {
                        $allowed = in_array($permissionCode, $selectedPermissions, true) ? 1 : 0;
                        $permissionStmt->bind_param("isi", $roleId, $permissionCode, $allowed);
                        if (!$permissionStmt->execute()) {
                            $saved = false;
                            break;
                        }
                    }
                }
                if ($permissionStmt) {
                    $permissionStmt->close();
                }

                if ($saved) {
                    $connection->commit();
                    accessControlHistory($connection, "a modifié|role_permissions|" . $roleId);
                    accessControlRedirect("role_saved", $roleId);
                }

                $connection->rollback();
                $formError = "Impossible d'enregistrer les droits de ce rôle.";
            }
        } elseif ($action === "assign_role") {
            $userId = (int) ($_POST["user_id"] ?? 0);
            $roleId = (int) ($_POST["role_id"] ?? 0);
            $currentAdminRole = (string) $_SESSION["id_usertype"];

            $stmt = $connection->prepare("SELECT id_typeSyndic FROM syndic WHERE id = ? LIMIT 1");
            $targetRoleId = null;
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->bind_result($targetRoleId);
                $stmt->fetch();
                $stmt->close();
            }

            if ($targetRoleId === null || !accessControlRoleExists($connection, $roleId)) {
                $formError = "Le collaborateur ou le rôle sélectionné n'existe pas.";
            } elseif ($currentAdminRole === "2" && ((string) $targetRoleId === "1" || (string) $roleId === "1")) {
                $formError = "Seul le super administrateur peut attribuer ou modifier le rôle Superadmin.";
            } elseif ((string) $targetRoleId === "1" && (string) $roleId !== "1") {
                $superadminCount = 0;
                if ($result = $connection->query("SELECT COUNT(*) AS total FROM syndic WHERE id_typeSyndic = 1")) {
                    $row = $result->fetch_assoc();
                    $superadminCount = (int) $row["total"];
                    $result->free();
                }
                if ($superadminCount <= 1) {
                    $formError = "Le dernier Superadmin ne peut pas perdre son rôle.";
                }
            } elseif ($userId === (int) $_SESSION["id"] && !bestcopro_is_access_admin($roleId)) {
                $formError = "Vous ne pouvez pas retirer votre propre accès administrateur.";
            }

            if ($formError === "") {
                $stmt = $connection->prepare("UPDATE syndic SET id_typeSyndic = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $roleId, $userId);
                    if ($stmt->execute()) {
                        $stmt->close();
                        if ($userId === (int) $_SESSION["id"]) {
                            $_SESSION["id_usertype"] = (string) $roleId;
                        }
                        accessControlHistory($connection, "a attribué|role_user|" . $userId . ":" . $roleId);
                        accessControlRedirect("user_saved", $roleId);
                    }
                    $formError = "Impossible de modifier le rôle de ce collaborateur.";
                    $stmt->close();
                } else {
                    $formError = "Impossible de préparer la modification du collaborateur.";
                }
            }
        }
    }
}

$roles = [];
$rolesRequest = (string) $_SESSION["id_usertype"] === "2"
    ? "SELECT id, libelle FROM typesyndic WHERE id <> 1 ORDER BY id ASC"
    : "SELECT id, libelle FROM typesyndic ORDER BY id ASC";
if ($result = $connection->query($rolesRequest)) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    $result->free();
}

$users = [];
if ($result = $connection->query(
    "SELECT id, prenom, nom, email, id_typeSyndic, is_active FROM syndic ORDER BY nom ASC, prenom ASC"
)) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

$selectedRoleId = isset($_GET["role"]) ? (int) $_GET["role"] : 0;
if (!$selectedRoleId && count($roles) > 0) {
    $selectedRoleId = (int) $roles[0]["id"];
}

$selectedRole = null;
foreach ($roles as $role) {
    if ((int) $role["id"] === $selectedRoleId) {
        $selectedRole = $role;
        break;
    }
}
if (!$selectedRole && count($roles) > 0) {
    $selectedRole = $roles[0];
    $selectedRoleId = (int) $selectedRole["id"];
}

$rolePermissions = $selectedRole && $schemaReady
    ? bestcopro_get_role_permissions($selectedRoleId, $connection)
    : [];
$permissionGroups = [];
foreach (bestcopro_access_catalog() as $permissionCode => $permission) {
    $groupName = $permission["group"];
    $sectionCode = $permission["section"];
    if (!isset($permissionGroups[$groupName][$sectionCode])) {
        $permissionGroups[$groupName][$sectionCode] = [
            "label" => $permission["label"],
            "actions" => [],
        ];
    }
    $permissionGroups[$groupName][$sectionCode]["actions"][$permission["action"]] = $permissionCode;
}

$statusMessages = [
    "role_created" => "Le rôle a été créé.",
    "role_saved" => "Le rôle et ses droits ont été enregistrés.",
    "user_saved" => "Le rôle du collaborateur a été mis à jour.",
];
$status = isset($_GET["status"]) ? (string) $_GET["status"] : "";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BEST COPRO - Gestion des accès</title>
    <link rel="shortcut icon" type="image/png" href="images/favicon.png">
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div id="preloader">
        <div class="lds-ripple"><div></div><div></div></div>
    </div>

    <div id="main-wrapper">
        <?php include __DIR__ . "/header.php"; ?>

        <div class="content-body" style="padding-top: 5rem; margin-left: 0;">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                    <div>
                        <h2 class="text-primary font-w600 mb-1">Gestion des accès</h2>
                        <span>Gérez les rôles, leurs rubriques et les collaborateurs associés.</span>
                    </div>
                    <a href="./index.php" class="btn btn-rounded btn-outline-secondary mt-2">Retour aux copropriétés</a>
                </div>

                <?php if ($status !== "" && isset($statusMessages[$status])): ?>
                    <div class="alert alert-success" role="alert"><?= accessControlEscape($statusMessages[$status]) ?></div>
                <?php endif; ?>
                <?php if ($formError !== ""): ?>
                    <div class="alert alert-danger" role="alert"><?= accessControlEscape($formError) ?></div>
                <?php endif; ?>
                <?php if (!$schemaReady): ?>
                    <div class="alert alert-danger" role="alert">
                        Impossible d'initialiser la gestion des accès. <?= accessControlEscape($schemaError) ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header border-0 pb-0">
                                <div>
                                    <h4 class="fs-20 mb-1">Rôles et droits</h4>
                                    <span>Définissez, pour chaque rubrique, les droits de consultation, modification et suppression.</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="get" class="row align-items-end mb-4">
                                    <div class="col-md-8">
                                        <label class="text-label" for="role">Rôle à configurer</label>
                                        <select name="role" id="role" class="form-control default-select wide">
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= (int) $role["id"] ?>" <?= (int) $role["id"] === $selectedRoleId ? "selected" : "" ?>>
                                                    <?= accessControlEscape($role["libelle"]) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mt-3 mt-md-0">
                                        <button type="submit" class="btn btn-rounded btn-outline-primary w-100">Afficher</button>
                                    </div>
                                </form>

                                <?php if ($selectedRole): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= accessControlEscape($_SESSION["access_control_csrf"]) ?>">
                                        <input type="hidden" name="action" value="save_role">
                                        <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">

                                        <div class="mb-4">
                                            <label class="text-label" for="role_name">Nom du rôle</label>
                                            <input type="text" id="role_name" name="role_name" class="form-control input-rounded" maxlength="100" required value="<?= accessControlEscape($selectedRole["libelle"]) ?>">
                                        </div>

                                        <?php foreach ($permissionGroups as $groupName => $groupPermissions): ?>
                                            <div class="border rounded p-3 mb-3">
                                                <h5 class="text-primary mb-3"><?= accessControlEscape($groupName) ?></h5>
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Rubrique</th>
                                                                <th class="text-center">Consulter</th>
                                                                <th class="text-center">Modifier</th>
                                                                <th class="text-center">Supprimer</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($groupPermissions as $sectionCode => $section): ?>
                                                                <tr>
                                                                    <td class="font-w600"><?= accessControlEscape($section["label"]) ?></td>
                                                                    <?php foreach (["view" => "Consulter", "edit" => "Modifier", "delete" => "Supprimer"] as $actionCode => $actionLabel): ?>
                                                                        <?php $permissionCode = $section["actions"][$actionCode]; ?>
                                                                        <td class="text-center">
                                                                            <div class="form-check d-inline-flex justify-content-center m-0">
                                                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= accessControlEscape($permissionCode) ?>" id="permission_<?= accessControlEscape($permissionCode) ?>" aria-label="<?= accessControlEscape($actionLabel . " : " . $section["label"]) ?>" <?= !empty($rolePermissions[$permissionCode]) ? "checked" : "" ?>>
                                                                            </div>
                                                                        </td>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-rounded btn-primary">Enregistrer le rôle et ses droits</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header border-0 pb-0">
                                <h4 class="fs-20 mb-1">Créer un rôle</h4>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= accessControlEscape($_SESSION["access_control_csrf"]) ?>">
                                    <input type="hidden" name="action" value="create_role">
                                    <label class="text-label" for="new_role_name">Nom du nouveau rôle</label>
                                    <input type="text" id="new_role_name" name="role_name" class="form-control input-rounded mb-3" maxlength="100" required placeholder="Ex. Comptable">
                                    <button type="submit" class="btn btn-rounded btn-primary w-100">Créer le rôle</button>
                                </form>
                                <p class="fs-12 mt-3 mb-0">Un nouveau rôle est créé sans aucun droit. Configurez ensuite les rubriques autorisées.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <div>
                            <h4 class="fs-20 mb-1">Rôles des collaborateurs</h4>
                            <span>Chaque collaborateur possède un rôle principal. La modification s'applique à sa prochaine connexion.</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-responsive-md">
                                <thead>
                                    <tr>
                                        <th>Collaborateur</th>
                                        <th>E-mail</th>
                                        <th>Statut</th>
                                        <th>Rôle</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php $protectedSuperadmin = (string) $_SESSION["id_usertype"] === "2" && (string) $user["id_typeSyndic"] === "1"; ?>
                                        <tr>
                                            <td><?= accessControlEscape(trim($user["prenom"] . " " . $user["nom"])) ?></td>
                                            <td><?= accessControlEscape($user["email"]) ?></td>
                                            <td>
                                                <span class="badge badge-rounded <?= (string) $user["is_active"] === "1" ? "badge-success" : "badge-danger" ?>">
                                                    <?= (string) $user["is_active"] === "1" ? "Actif" : "Inactif" ?>
                                                </span>
                                            </td>
                                            <td colspan="2">
                                                <?php if ($protectedSuperadmin): ?>
                                                    <div class="text-end"><span class="badge badge-rounded badge-primary">Superadmin protégé</span></div>
                                                <?php else: ?>
                                                    <form method="post" class="d-flex justify-content-end align-items-center gap-2">
                                                        <input type="hidden" name="csrf_token" value="<?= accessControlEscape($_SESSION["access_control_csrf"]) ?>">
                                                        <input type="hidden" name="action" value="assign_role">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user["id"] ?>">
                                                        <select name="role_id" class="form-control default-select">
                                                            <?php foreach ($roles as $role): ?>
                                                                <?php if ((string) $_SESSION["id_usertype"] === "2" && (string) $role["id"] === "1") { continue; } ?>
                                                                <option value="<?= (int) $role["id"] ?>" <?= (int) $role["id"] === (int) $user["id_typeSyndic"] ? "selected" : "" ?>>
                                                                    <?= accessControlEscape($role["libelle"]) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-rounded btn-primary">Enregistrer</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/global/global.min.js"></script>
    <script src="vendor/jquery-nice-select/js/jquery.nice-select.min.js"></script>
    <script src="js/custom.min.js"></script>
</body>
</html>
