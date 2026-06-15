<?php
if (isset($GLOBALS["connection"]) && $GLOBALS["connection"] instanceof mysqli) {
    mysqli_set_charset($GLOBALS["connection"], "utf8mb4");
    mysqli_query($GLOBALS["connection"], "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}

if (!headers_sent()) {
    header("Content-Type: text/html; charset=utf-8");
}

/**
 * getView
 *
 * @param mixed $page
 * @return mixed
 */
function getView($page = "dashboard")
{
    if (
        file_exists("views/" . $page . ".php") &&
        hadAccess($page, $_SESSION["id_usertype"])
    ) {
        include_once "views/" . $page . ".php";
    } else {
        include_once "views/dashboard.php";
    }
}
/**
 * hadAccess
 *
 * @param mixed $page
 * @param mixed $id_usertype
 * @return mixed
 */
function hadAccess($page, $id_usertype)
{
    if ($id_usertype == "1") {
        return true;
    } elseif ($id_usertype == "2") {
        return true;
    } elseif ($id_usertype == "3") {
        return true;
    } elseif (
        $id_usertype == "4" &&
        $page != "assemblee" &&
        $page != "actions"
    ) {
        return true;
    } else {
        return false;
    }
}
// get action
/**
 * getAction
 *
 * @param mixed $id
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getAction($id = null, $id_copropriete = null, $connection)
{
    if ($id != null) {
        $request =
            "SELECT id, nom, origine, dateDebut, dateFin, dateFinReelle, id_statutAction, efficacite, pilote, intervenants, avancement, observation, id_copropriete FROM action WHERE id = ?";
    } else {
        $request =
            "SELECT id, nom, origine, dateDebut, dateFin, dateFinReelle, id_statutAction, efficacite, pilote, intervenants, avancement, observation, id_copropriete FROM action WHERE id_copropriete = ? ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } else {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $nom,
                $origine,
                $dateDebut,
                $dateFin,
                $dateFinReelle,
                $id_statutAction,
                $efficacite,
                $pilote,
                $intervenants,
                $avancement,
                $observation,
                $id_copropriete,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "nom" => $nom,
                    "origine" => $origine,
                    "dateDebut" => $dateDebut,
                    "dateFin" => $dateFin,
                    "dateFinReelle" => $dateFinReelle,
                    "id_statutAction" => $id_statutAction,
                    "efficacite" => $efficacite,
                    "pilote" => $pilote,
                    "intervenants" => $intervenants,
                    "avancement" => $avancement,
                    "observation" => $observation,
                    "id_copropriete" => $id_copropriete,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get assemblee
/**
 * getAssemblee
 *
 * @param mixed $id
 * @param mixed $id_exercice
 * @param mixed $connection
 * @return mixed
 */
function getAssemblee($id = null, $id_exercice = null, $connection)
{
    if ($id != null) {
        $request =
            "SELECT id, date1, date2, date3, objet, ordreDuJour, titre1, titre2, id_exercice FROM assemblee WHERE id = ?";
    } else {
        $request =
            "SELECT id, date1, date2, date3, objet, ordreDuJour, titre1, titre2, id_exercice FROM assemblee WHERE id_exercice = ?";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } else {
            $stmt->bind_param("s", $id_exercice);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $date1,
                $date2,
                $date3,
                $objet,
                $ordreDuJour,
                $titre1,
                $titre2,
                $id_exercice,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "date1" => $date1,
                    "date2" => $date2,
                    "date3" => $date3,
                    "objet" => $objet,
                    "ordreDuJour" => $ordreDuJour,
                    "titre1" => $titre1,
                    "titre2" => $titre2,
                    "id_exercice" => $id_exercice,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get copropriete
/**
 * getContentieux
 *
 * @param mixed $id_lot
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getContentieux($id_lot = null, $id_copropriete = null, $connection)
{
    if ($id_lot != null) {
        $request =
            "SELECT id, id_lot, date, id_etat, remarque FROM contentieux WHERE id_lot = ?";
    } elseif ($id_copropriete != null) {
        $request =
            "SELECT id, id_lot, date, id_etat, remarque FROM contentieux WHERE id IN (SELECT max(id) FROM contentieux WHERE id_lot IN (SELECT id FROM lot WHERE id_copropriete = ?)  GROUP BY id_lot)";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id_lot != null) {
            $stmt->bind_param("s", $id_lot);
        } elseif ($id_copropriete != null) {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $id_lot, $date, $id_etat, $remarque);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "id_lot" => $id_lot,
                    "date" => $date,
                    "id_etat" => $id_etat,
                    "remarque" => $remarque,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
} // get copropriete
/**
 * getCopropriete
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getCopropriete($id = null, $connection)
{
    if ($id != null) {
        $request =
            "SELECT id, nom, adresse, ville, codePostale, nbrLot, dateExercice, id_syndic, prefixe, rib FROM copropriete WHERE id = ? AND display = 1";
    } else {
        $request =
            "SELECT id, nom, adresse, ville, codePostale, nbrLot, dateExercice, id_syndic, prefixe, rib FROM copropriete WHERE display = 1";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $nom,
                $adresse,
                $ville,
                $codePostale,
                $nbrLot,
                $dateExercice,
                $id_syndic,
                $prefixe,
                $rib,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "nom" => $nom,
                    "adresse" => $adresse,
                    "ville" => $ville,
                    "codePostale" => $codePostale,
                    "nbrLot" => $nbrLot,
                    "dateExercice" => $dateExercice,
                    "id_syndic" => $id_syndic,
                    "prefixe" => $prefixe,
                    "rib" => $rib,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get depense
function ensureDepensePaiementFields($connection)
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $columns = [];
    if ($result = $connection->query("SHOW COLUMNS FROM depense")) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row["Field"];
        }
    }

    $alters = [];
    if (!in_array("situationPaiement", $columns)) {
        $alters[] = "ADD COLUMN situationPaiement VARCHAR(20) NOT NULL DEFAULT 'paye'";
    }
    if (!in_array("datePaiement", $columns)) {
        $alters[] = "ADD COLUMN datePaiement DATE NULL";
    }
    if (!in_array("montantPaye", $columns)) {
        $alters[] = "ADD COLUMN montantPaye DECIMAL(12,2) NULL";
    }

    if (!empty($alters)) {
        $connection->query("ALTER TABLE depense " . implode(", ", $alters));
        $connection->query(
            "UPDATE depense SET situationPaiement = 'paye' WHERE situationPaiement IS NULL OR situationPaiement = ''",
        );
        $connection->query(
            "UPDATE depense SET datePaiement = date WHERE situationPaiement = 'paye' AND datePaiement IS NULL",
        );
        $connection->query(
            "UPDATE depense SET montantPaye = montant WHERE situationPaiement = 'paye' AND montantPaye IS NULL",
        );
    }
}

/**
 * getDepense
 *
 * @param mixed $id
 * @param mixed $id_exercice
 * @param mixed $connection
 * @return mixed
 */
function getDepense($id = null, $id_exercice = null, $connection)
{
    ensureDepensePaiementFields($connection);
    if ($id != null) {
        $request =
            "SELECT id, id_poste, date, montant, id_fournisseur, id_modePaiement, commentaire, id_exercice, id_syndic, situationPaiement, datePaiement, montantPaye FROM depense WHERE id = ?";
    } else {
        $request =
            "SELECT id, id_poste, date, montant, id_fournisseur, id_modePaiement, commentaire, id_exercice, id_syndic, situationPaiement, datePaiement, montantPaye FROM depense WHERE id_exercice = ? ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } else {
            $stmt->bind_param("s", $id_exercice);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $id_poste,
                $date,
                $montant,
                $id_fournisseur,
                $id_modePaiement,
                $commentaire,
                $id_exercice,
                $id_syndic,
                $situationPaiement,
                $datePaiement,
                $montantPaye,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "id_poste" => $id_poste,
                    "date" => $date,
                    "montant" => $montant,
                    "id_fournisseur" => $id_fournisseur,
                    "id_modePaiement" => $id_modePaiement,
                    "commentaire" => $commentaire,
                    "id_exercice" => $id_exercice,
                    "id_syndic" => $id_syndic,
                    "situationPaiement" => $situationPaiement,
                    "datePaiement" => $datePaiement,
                    "montantPaye" => $montantPaye,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get document
/**
 * getDocument
 *
 * @param mixed $id
 * @param mixed $id_copropriete
 * @param mixed $aPartager
 * @param mixed $connection
 * @return mixed
 */
function getDocument(
    $id = null,
    $id_copropriete,
    $aPartager = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, titre, date, id_typedocument, id_copropriete, public FROM document WHERE id_copropriete = ? AND id = ?";
    } elseif ($aPartager != null) {
        $request =
            "SELECT id, titre, date, id_typedocument, id_copropriete, public FROM document WHERE id_copropriete = ? AND public = ? ORDER BY id DESC";
    } else {
        $request =
            "SELECT id, titre, date, id_typedocument, id_copropriete, public FROM document WHERE id_copropriete = ? ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("ss", $id_copropriete, $id);
        } elseif ($aPartager != null) {
            $stmt->bind_param("ss", $id_copropriete, $aPartager);
        } else {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $titre,
                $date,
                $id_typedocument,
                $id_copropriete,
                $public,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "titre" => $titre,
                    "date" => $date,
                    "id_typedocument" => $id_typedocument,
                    "id_copropriete" => $id_copropriete,
                    "public" => $public,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get echeance
/**
 * getEcheance
 *
 * @param mixed $id
 * @param mixed $date
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getEcheance(
    $id = null,
    $date = null,
    $id_copropriete = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, description, date, jourAlerte, id_copropriete FROM echeance WHERE id = ?";
    } elseif ($date != null) {
        $request =
            "SELECT id, description, date, jourAlerte, id_copropriete FROM echeance WHERE id NOT IN (SELECT idPage FROM notificationsyndic WHERE nomPage = 'echeances') AND DATE_SUB(date, INTERVAL jourAlerte DAY) <= CURDATE() ORDER BY id DESC";
    } elseif ($id_copropriete != null) {
        $request =
            "SELECT id, description, date, jourAlerte, id_copropriete FROM echeance WHERE id_copropriete = ? ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_copropriete != null) {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $description,
                $date,
                $jourAlerte,
                $id_copropriete,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "description" => $description,
                    "date" => $date,
                    "jourAlerte" => $jourAlerte,
                    "id_copropriete" => $id_copropriete,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get etat
/**
 * getEtat
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getEtat($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM etat WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM etat";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get exercice
/**
 * getExercice
 *
 * @param mixed $id
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getExercice($id = null, $id_copropriete = null, $connection)
{
    if ($id != null) {
        $request =
            "SELECT id, dateDebut, dateFin, id_periodePaiement, id_repartitionFonct, id_repartitionInvest, montantFonct, montantInvest, id_copropriete FROM exercice WHERE id = ?";
    } else {
        $request =
            "SELECT id, dateDebut, dateFin, id_periodePaiement, id_repartitionFonct, id_repartitionInvest, montantFonct, montantInvest, id_copropriete FROM exercice WHERE id_copropriete = ? ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } else {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $dateDebut,
                $dateFin,
                $id_periodePaiement,
                $id_repartitionFonct,
                $id_repartitionInvest,
                $montantFonct,
                $montantInvest,
                $id_copropriete,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "dateDebut" => $dateDebut,
                    "dateFin" => $dateFin,
                    "id_periodePaiement" => $id_periodePaiement,
                    "id_repartitionFonct" => $id_repartitionFonct,
                    "id_repartitionInvest" => $id_repartitionInvest,
                    "montantFonct" => $montantFonct,
                    "montantInvest" => $montantInvest,
                    "id_copropriete" => $id_copropriete,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get fournisseur
/**
 * getFournisseur
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getFournisseur($id = null, $connection)
{
    if ($id != null) {
        $request =
            "SELECT id, raisonSocial, ice, email, telephone, adresse, codePostale, ville FROM fournisseur WHERE id = ?";
    } else {
        $request =
            "SELECT id, raisonSocial, ice, email, telephone, adresse, codePostale, ville FROM fournisseur";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $raisonSocial,
                $ice,
                $email,
                $telephone,
                $adresse,
                $codePostale,
                $ville,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "raisonSocial" => $raisonSocial,
                    "ice" => $ice,
                    "email" => $email,
                    "telephone" => $telephone,
                    "adresse" => $adresse,
                    "codePostale" => $codePostale,
                    "ville" => $ville,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get lot
/**
 * getLot
 *
 * @param mixed $id
 * @param mixed $id_proprietaire
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getLot(
    $id = null,
    $id_proprietaire = null,
    $id_copropriete = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, code, password, token, id_typeLot, numeroImm, etage, numero, foncier, tantieme, dateAcquisition, dateRemiseCle, id_proprietaire, id_typeProprietaire, impaye0, impaye1, impaye2, impaye3, impaye4, impaye5, id_copropriete FROM lot WHERE id = ?";
    } elseif ($id_proprietaire != null) {
        $request =
            "SELECT id, code, password, token, id_typeLot, numeroImm, etage, numero, foncier, tantieme, dateAcquisition, dateRemiseCle, id_proprietaire, id_typeProprietaire, impaye0, impaye1, impaye2, impaye3, impaye4, impaye5, id_copropriete FROM lot WHERE id_proprietaire = ?";
    } else {
        $request =
            "SELECT id, code, password, token, id_typeLot, numeroImm, etage, numero, foncier, tantieme, dateAcquisition, dateRemiseCle, id_proprietaire, id_typeProprietaire, impaye0, impaye1, impaye2, impaye3, impaye4, impaye5, id_copropriete FROM lot WHERE id_copropriete = ?";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_proprietaire != null) {
            $stmt->bind_param("s", $id_proprietaire);
        } else {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $code,
                $password,
                $token,
                $id_typeLot,
                $numeroImm,
                $etage,
                $numero,
                $foncier,
                $tantieme,
                $dateAcquisition,
                $dateRemiseCle,
                $id_proprietaire,
                $id_typeProprietaire,
                $impaye0,
                $impaye1,
                $impaye2,
                $impaye3,
                $impaye4,
                $impaye5,
                $id_copropriete,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "code" => $code,
                    "password" => $password,
                    "token" => $token,
                    "id_typeLot" => $id_typeLot,
                    "numeroImm" => $numeroImm,
                    "etage" => $etage,
                    "numero" => $numero,
                    "foncier" => $foncier,
                    "tantieme" => $tantieme,
                    "dateAcquisition" => $dateAcquisition,
                    "dateRemiseCle" => $dateRemiseCle,
                    "id_proprietaire" => $id_proprietaire,
                    "id_typeProprietaire" => $id_typeProprietaire,
                    "impaye0" => $impaye0,
                    "impaye1" => $impaye1,
                    "impaye2" => $impaye2,
                    "impaye3" => $impaye3,
                    "impaye4" => $impaye4,
                    "impaye5" => $impaye5,
                    "id_copropriete" => $id_copropriete,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get message
/**
 * getMessages
 *
 * @param mixed $id_reclamation
 * @param mixed $connection
 * @return mixed
 */
function getMessages($id_reclamation, $connection)
{
    $request =
        "SELECT id, date, id_syndic, id_proprietaire, commentaire FROM message WHERE id_reclamation = ? ORDER BY id ASC";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_reclamation);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $date,
                $id_syndic,
                $id_proprietaire,
                $commentaire,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "date" => $date,
                    "id_syndic" => $id_syndic,
                    "id_proprietaire" => $id_proprietaire,
                    "commentaire" => $commentaire,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
//  get modepaiement
/**
 * getModepaiement
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getModepaiement($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM modepaiement WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM modepaiement";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
//  get notificationsyndic
/**
 * getNotificationsyndic
 *
 * @param mixed $limit
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getNotificationsyndic($limit = null, $id_copropriete, $connection)
{
    if ($limit == "seen") {
        $request =
            "SELECT id, description, date, nomPage, idPage, id_copropriete, seen FROM notificationsyndic WHERE id_copropriete = ? AND seen = 0 ORDER BY id DESC LIMIT 1";
    } elseif ($limit != null) {
        $request =
            "SELECT id, description, date, nomPage, idPage, id_copropriete, seen FROM notificationsyndic WHERE id_copropriete = ? ORDER BY id DESC LIMIT ?";
    } else {
        $request =
            "SELECT id, description, date, nomPage, idPage, id_copropriete, seen FROM notificationsyndic WHERE id_copropriete = ? ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($limit == "seen") {
            $stmt->bind_param("s", $id_copropriete);
        } elseif ($limit != null) {
            $stmt->bind_param("ss", $id_copropriete, $limit);
        } else {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $description,
                $date,
                $nomPage,
                $idPage,
                $id_copropriete,
                $seen,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "description" => $description,
                    "date" => $date,
                    "nomPage" => $nomPage,
                    "idPage" => $idPage,
                    "id_copropriete" => $id_copropriete,
                    "seen" => $seen,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get paiement
/**
 * getPaiement
 *
 * @param mixed $id
 * @param mixed $id_copropriete
 * @param mixed $id_lot
 * @param mixed $connection
 * @return mixed
 */
function getPaiement(
    $id = null,
    $id_copropriete = null,
    $id_lot = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, id_lot, date, montant, id_modePaiement, commentaire, id_syndic FROM paiement WHERE id = ?";
    } elseif ($id_lot != null) {
        $request =
            "SELECT id, id_lot, date, montant, id_modePaiement, commentaire, id_syndic FROM paiement WHERE id_lot = ? ORDER BY id DESC";
    } elseif ($id_copropriete != null) {
        $request =
            "SELECT id, id_lot, date, montant, id_modePaiement, commentaire, id_syndic FROM paiement WHERE id_lot IN (SELECT id FROM lot WHERE id_copropriete = ?) ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_lot != null) {
            $stmt->bind_param("s", $id_lot);
        } elseif ($id_copropriete != null) {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $id_lot,
                $date,
                $montant,
                $id_modePaiement,
                $commentaire,
                $id_syndic,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "id_lot" => $id_lot,
                    "date" => $date,
                    "montant" => $montant,
                    "id_modePaiement" => $id_modePaiement,
                    "commentaire" => $commentaire,
                    "id_syndic" => $id_syndic,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get periodepaiement
/**
 * getPeriodepaiement
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getPeriodepaiement($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM periodepaiement WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM periodepaiement";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get poste
/**
 * getPoste
 *
 * @param mixed $id
 * @param mixed $id_rubrique
 * @param mixed $libelle_rubrique
 * @param mixed $connection
 * @return mixed
 */
function getPoste(
    $id = null,
    $id_rubrique = null,
    $libelle_rubrique = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, libelle, montant, id_rubrique FROM poste WHERE id = ?";
    } elseif ($id_rubrique != null) {
        $request =
            "SELECT id, libelle, montant, id_rubrique FROM poste WHERE id_rubrique = ? ORDER BY id ASC";
    } else {
        $request =
            "SELECT id, libelle, montant, id_rubrique FROM poste WHERE id_rubrique IN (SELECT id FROM rubrique WHERE libelle LIKE ?) GROUP BY (libelle) ORDER BY id ASC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_rubrique != null) {
            $stmt->bind_param("s", $id_rubrique);
        } else {
            $stmt->bind_param("s", $libelle_rubrique);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle, $montant, $id_rubrique);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                    "montant" => $montant,
                    "id_rubrique" => $id_rubrique,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get proprietaire
/**
 * getProprietaire
 *
 * @param mixed $id
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getProprietaire($id = null, $id_copropriete = null, $connection)
{
    if ($id != null) {
        $request =
            "SELECT id, civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale FROM proprietaire WHERE id = ?";
    } elseif ($id_copropriete != null) {
        $request = "SELECT id, civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale FROM proprietaire WHERE id IN 
					(SELECT id_proprietaire FROM lot WHERE id_copropriete = ?)";
    } else {
        $request =
            "SELECT id, civilite, prenom, nom, email, telephone, mobile, adresse, ville, codePostale FROM proprietaire";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_copropriete != null) {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $civilite,
                $prenom,
                $nom,
                $email,
                $telephone,
                $mobile,
                $adresse,
                $ville,
                $codePostale,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "civilite" => $civilite,
                    "prenom" => $prenom,
                    "nom" => $nom,
                    "email" => $email,
                    "telephone" => $telephone,
                    "mobile" => $mobile,
                    "adresse" => $adresse,
                    "ville" => $ville,
                    "codePostale" => $codePostale,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get reclamation
/**
 * getReclamation
 *
 * @param mixed $id
 * @param mixed $id_lot
 * @param mixed $id_copropriete
 * @param mixed $connection
 * @return mixed
 */
function getReclamation(
    $id = null,
    $id_lot = null,
    $id_copropriete = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, date, objet, id_lot, id_statutReclamation, dateFermeture FROM reclamation WHERE id = ?";
    } elseif ($id_lot != null) {
        $request =
            "SELECT id, date, objet, id_lot, id_statutReclamation, dateFermeture FROM reclamation WHERE id_lot = ?";
    } elseif ($id_copropriete != null) {
        $request =
            "SELECT id, date, objet, id_lot, id_statutReclamation, dateFermeture FROM reclamation WHERE id_lot IN (SELECT id FROM lot WHERE id_copropriete = ?) ORDER BY id DESC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_lot != null) {
            $stmt->bind_param("s", $id_lot);
        } elseif ($id_copropriete != null) {
            $stmt->bind_param("s", $id_copropriete);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $date,
                $objet,
                $id_lot,
                $id_statutReclamation,
                $dateFermeture,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "date" => $date,
                    "objet" => $objet,
                    "id_lot" => $id_lot,
                    "id_statutReclamation" => $id_statutReclamation,
                    "dateFermeture" => $dateFermeture,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get rel_copropriete_syndic
/**
 * getRel_copropriete_syndic
 *
 * @param mixed $id_syndic
 * @param mixed $connection
 * @return mixed
 */
function getRel_copropriete_syndic($id_syndic, $connection)
{
    $request =
        "SELECT id_copropriete FROM rel_copropriete_syndic WHERE id_syndic= ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_syndic);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_copropriete);
            while ($stmt->fetch()) {
                $result[] = $id_copropriete;
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get rel_lot_exercice
/**
 * getRel_lot_exercice
 *
 * @param mixed $id_lot
 * @param mixed $id_exercice
 * @param mixed $connection
 * @return mixed
 */
function getRel_lot_exercice($id_lot = null, $id_exercice = null, $connection)
{
    if ($id_lot != null && $id_exercice != null) {
        $request =
            "SELECT id_rel, id_lot, id_exercice, partFonct, partInv, dateFinPeriode, cotisation FROM rel_lot_exercice WHERE id_lot= ? AND id_exercice= ?";
    } elseif ($id_lot != null) {
        $request =
            "SELECT id_rel, id_lot, id_exercice, partFonct, partInv, dateFinPeriode, cotisation FROM rel_lot_exercice WHERE id_lot= ?";
    } elseif ($id_exercice != null) {
        $request =
            "SELECT id_rel, id_lot, id_exercice, partFonct, partInv, dateFinPeriode, cotisation FROM rel_lot_exercice WHERE id_exercice= ?";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id_lot != null && $id_exercice != null) {
            $stmt->bind_param("ss", $id_lot, $id_exercice);
        } elseif ($id_lot != null) {
            $stmt->bind_param("s", $id_lot);
        } elseif ($id_exercice != null) {
            $stmt->bind_param("s", $id_exercice);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id_rel,
                $id_lot,
                $id_exercice,
                $partFonct,
                $partInv,
                $dateFinPeriode,
                $cotisation,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id_rel" => $id_rel,
                    "id_lot" => $id_lot,
                    "id_exercice" => $id_exercice,
                    "partFonct" => $partFonct,
                    "partInv" => $partInv,
                    "dateFinPeriode" => $dateFinPeriode,
                    "cotisation" => $cotisation,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get rel_rel_paiement
/**
 * getRel_rel_paiement
 *
 * @param mixed $id_paiement
 * @param mixed $connection
 * @return mixed
 */
function getRel_rel_paiement($id_paiement, $connection)
{
    $request =
        "SELECT id_rel, id_paiement, montant FROM rel_rel_paiement WHERE id_paiement= ? ORDER BY id_rel ASC";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_paiement);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_rel, $id_paiement, $montant);
            while ($stmt->fetch()) {
                $result[] = [
                    "id_rel" => $id_rel,
                    "id_paiement" => $id_paiement,
                    "montant" => $montant,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get rel_lot_proprietaire
/**
 * getRepartitionfonct
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getRepartitionfonct($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM repartitionfonct WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM repartitionfonct";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get repartitioninvest
/**
 * getRepartitioninvest
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getRepartitioninvest($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM repartitioninvest WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM repartitioninvest";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get rubrique
/**
 * getRubrique
 *
 * @param mixed $id
 * @param mixed $id_exercice
 * @param mixed $id_typeRubrique
 * @param mixed $connection
 * @return mixed
 */
function getRubrique(
    $id = null,
    $id_exercice = null,
    $id_typeRubrique = null,
    $connection,
) {
    if ($id != null) {
        $request =
            "SELECT id, libelle, id_exercice, id_typeRubrique FROM rubrique WHERE id = ?";
    } elseif ($id_exercice != null && $id_typeRubrique != null) {
        $request =
            "SELECT id, libelle, id_exercice, id_typeRubrique FROM rubrique WHERE id_exercice = ? AND id_typeRubrique = ? ORDER BY id ASC";
    } elseif ($id_exercice != null) {
        $request =
            "SELECT id, libelle, id_exercice, id_typeRubrique FROM rubrique WHERE id_exercice = ? ORDER BY id ASC";
    } elseif ($id_typeRubrique != null) {
        $request =
            "SELECT id, libelle, id_exercice, id_typeRubrique FROM rubrique WHERE id_typeRubrique = ?  GROUP BY(libelle) ORDER BY id ASC";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($id_exercice != null && $id_typeRubrique != null) {
            $stmt->bind_param("ss", $id_exercice, $id_typeRubrique);
        } elseif ($id_exercice != null) {
            $stmt->bind_param("s", $id_exercice);
        } elseif ($id_typeRubrique != null) {
            $stmt->bind_param("s", $id_typeRubrique);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle, $id_exercice, $id_typeRubrique);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                    "id_exercice" => $id_exercice,
                    "id_typeRubrique" => $id_typeRubrique,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get statutaction
/**
 * getStatutaction
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getStatutaction($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM statutaction WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM statutaction";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get statutreclamation
/**
 * getStatutreclamation
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getStatutreclamation($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM statutreclamation WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM statutreclamation";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get syndic
/**
 * getSyndic
 *
 * @param mixed $id
 * @param mixed $currentId_typeSyndic
 * @param mixed $connection
 * @return mixed
 */
function getSyndic($id = null, $currentId_typeSyndic = null, $connection)
{
    if ($id != null && $currentId_typeSyndic != null) {
        $request =
            "SELECT id, civilite, prenom, nom, email, password, token, telephone, mobile, id_typeSyndic, is_active FROM syndic WHERE id = ? AND id_typeSyndic >= ?";
    } elseif ($id != null) {
        $request =
            "SELECT id, civilite, prenom, nom, email, password, token, telephone, mobile, id_typeSyndic, is_active FROM syndic WHERE id = ?";
    } elseif ($currentId_typeSyndic != null) {
        $request =
            "SELECT id, civilite, prenom, nom, email, password, token, telephone, mobile, id_typeSyndic, is_active FROM syndic WHERE id != 1 AND id_typeSyndic >= ?";
    } else {
        $request =
            "SELECT id, civilite, prenom, nom, email, password, token, telephone, mobile, id_typeSyndic, is_active FROM syndic WHERE id != 1";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null && $currentId_typeSyndic != null) {
            $stmt->bind_param("ss", $id, $currentId_typeSyndic);
        } elseif ($id != null) {
            $stmt->bind_param("s", $id);
        } elseif ($currentId_typeSyndic != null) {
            $stmt->bind_param("s", $currentId_typeSyndic);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id,
                $civilite,
                $prenom,
                $nom,
                $email,
                $password,
                $token,
                $telephone,
                $mobile,
                $id_typeSyndic,
                $is_active,
            );
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "civilite" => $civilite,
                    "prenom" => $prenom,
                    "nom" => $nom,
                    "email" => $email,
                    "password" => $password,
                    "token" => $token,
                    "telephone" => $telephone,
                    "mobile" => $mobile,
                    "id_typeSyndic" => $id_typeSyndic,
                    "is_active" => $is_active,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get typedocument
/**
 * getTypedocument
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getTypedocument($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM typedocument WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM typedocument";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get typelot
/**
 * getTypelot
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getTypelot($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM typelot WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM typelot";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get typeproprietaire
/**
 * getTypeproprietaire
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getTypeproprietaire($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM typeproprietaire WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM typeproprietaire";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get typerubrique
/**
 * getTyperubrique
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getTyperubrique($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM typerubrique WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM typerubrique";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get typesyndic
/**
 * getTypesyndic
 *
 * @param mixed $id
 * @param mixed $connection
 * @return mixed
 */
function getTypesyndic($id = null, $connection)
{
    if ($id != null) {
        $request = "SELECT id, libelle FROM typesyndic WHERE id = ?";
    } else {
        $request = "SELECT id, libelle FROM typesyndic";
    }
    if ($stmt = $connection->prepare($request)) {
        if ($id != null) {
            $stmt->bind_param("s", $id);
        }
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $libelle);
            while ($stmt->fetch()) {
                $result[] = [
                    "id" => $id,
                    "libelle" => $libelle,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get exercicename
/**
 * getNameexercice
 *
 * @param mixed $date
 * @return mixed
 */
function getNameexercice($date)
{
    $month = date("m", strtotime($date));
    if ($month == "01") {
        return "Exercice " . date("Y", strtotime($date));
    } else {
        $nextYear = floatval(date("Y", strtotime($date)));
        return "Exercice " .
            date("Y", strtotime($date)) .
            "-" .
            ($nextYear + 1);
    }
}

function getExercisePeriodLabel($dateDebut, $offsetYears = 0)
{
    $start = strtotime(
        date("Y-m-d", strtotime($dateDebut)) . " " . intval($offsetYears) . " year"
    );
    if (date("m", $start) == "01") {
        return date("Y", $start);
    }

    $end = strtotime(date("Y-m-d", $start) . " + 11 month");
    return date("m/Y", $start) . " - " . date("m/Y", $end);
}
// get periodeInfo
/**
 * periodeInfo
 *
 * @param mixed $id_rel
 * @param mixed $connection
 * @return mixed
 */
function periodeInfo($id_rel, $connection)
{
    $request =
        "SELECT id_rel, id_lot, id_exercice, partFonct, partInv, dateFinPeriode, cotisation FROM rel_lot_exercice WHERE id_rel = ?";
    if ($stmt = $connection->prepare($request)) {
        $stmt->bind_param("s", $id_rel);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result(
                $id_rel,
                $id_lot,
                $id_exercice,
                $partFonct,
                $partInv,
                $dateFinPeriode,
                $cotisation,
            );
            while ($stmt->fetch()) {
                if ($id_exercice == "0") {
                    $nomPeriode = "Impayé promoteur";
                } elseif (intval($id_exercice) < 0) {
                    $nomPeriode =
                        "Impayé : " .
                        getExercisePeriodLabel($dateFinPeriode);
                } else {
                    $exercice = getExercice($id_exercice, null, $connection);
                    if ($exercice[0]["id_periodePaiement"] == "1") {
                        $nomPeriode = date(
                            "m/Y",
                            strtotime(
                                date("Y-m-d", strtotime($dateFinPeriode)) .
                                    " - 1 month",
                            ),
                        );
                    } elseif ($exercice[0]["id_periodePaiement"] == "4") {
                        $nomPeriode = getExercisePeriodLabel(
                            $exercice[0]["dateDebut"]
                        );
                    }
                }
                $result[] = [
                    "id_rel" => $id_rel,
                    "id_lot" => $id_lot,
                    "id_exercice" => $id_exercice,
                    "partFonct" => $partFonct,
                    "partInv" => $partInv,
                    "dateFinPeriode" => $dateFinPeriode,
                    "nomPeriode" => $nomPeriode,
                    "cotisation" => $cotisation,
                ];
            }
            return $result;
        } else {
            return [];
        }
    }
}
// get getURL
/**
 * getURL
 * @return mixed
 */
function getURL()
{
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") {
        $url = "https://";
    } else {
        $url = "http://";
    }
    $url .= $_SERVER["HTTP_HOST"];
    $url .= dirname($_SERVER["PHP_SELF"]);
    return $url;
}
// get Password
/**
 * getPassword
 *
 * @param mixed $length
 * @return mixed
 */
function getPassword($length = 8)
{
    //$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $password = substr(str_shuffle($chars), 0, $length);
    return $password;
}
?>
