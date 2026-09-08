<?php

if (!function_exists("bestcoproCurrentApplicationPath")) {
    function bestcoproCurrentApplicationPath()
    {
        $scriptName = isset($_SERVER["SCRIPT_NAME"])
            ? str_replace("\\", "/", (string) $_SERVER["SCRIPT_NAME"])
            : "";
        $basePath = rtrim(str_replace("\\", "/", dirname($scriptName)), "/");
        if (substr($basePath, -5) === "/json") {
            $basePath = substr($basePath, 0, -5);
        } elseif (substr($basePath, -6) === "/views") {
            $basePath = substr($basePath, 0, -6);
        }

        return $basePath === "." ? "" : $basePath;
    }
}

if (!function_exists("bestcoproDocumentStorageLocations")) {
    function bestcoproDocumentStorageLocations()
    {
        $currentRoot = dirname(__DIR__);
        $currentBasePath = bestcoproCurrentApplicationPath();
        $locations = [
            [
                "directory" => $currentRoot . DIRECTORY_SEPARATOR . "justificatifs" . DIRECTORY_SEPARATOR . "documents",
                "public_path" => $currentBasePath,
            ],
        ];

        // app et staging partagent actuellement la meme base. Une piece jointe
        // peut donc avoir ete deposee dans l'autre dossier de deploiement.
        $parent = dirname($currentRoot);
        foreach (["app", "staging"] as $applicationDirectory) {
            $root = $parent . DIRECTORY_SEPARATOR . $applicationDirectory;
            if (strcasecmp($root, $currentRoot) === 0 || !is_dir($root)) {
                continue;
            }
            $locations[] = [
                "directory" => $root . DIRECTORY_SEPARATOR . "justificatifs" . DIRECTORY_SEPARATOR . "documents",
                "public_path" => "/" . $applicationDirectory,
            ];
        }

        return $locations;
    }
}

if (!function_exists("bestcoproDocumentsDirectory")) {
    function bestcoproDocumentsDirectory()
    {
        $locations = bestcoproDocumentStorageLocations();
        return $locations[0]["directory"];
    }
}

if (!function_exists("bestcoproDocumentUploadError")) {
    function bestcoproDocumentUploadError($upload)
    {
        if (!is_array($upload) || !array_key_exists("error", $upload)) {
            return "Veuillez choisir un fichier.";
        }

        $error = (int) $upload["error"];
        if ($error === UPLOAD_ERR_NO_FILE) {
            return "Veuillez choisir un fichier.";
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return "Le fichier dépasse la taille maximale autorisée par le serveur.";
        }
        if ($error === UPLOAD_ERR_PARTIAL) {
            return "Le fichier n'a été que partiellement téléversé. Veuillez réessayer.";
        }
        if ($error !== UPLOAD_ERR_OK) {
            return "Le téléversement du fichier a échoué (code " . $error . ").";
        }

        $size = isset($upload["size"]) ? (int) $upload["size"] : 0;
        if ($size <= 0) {
            return "Le fichier sélectionné est vide.";
        }
        if ($size > 5 * 1024 * 1024) {
            return "La taille du fichier doit être inférieure ou égale à 5 Mo.";
        }

        $extension = strtolower(pathinfo((string) ($upload["name"] ?? ""), PATHINFO_EXTENSION));
        if (!in_array($extension, ["jpg", "jpeg", "png", "pdf"], true)) {
            return "Format non autorisé. Utilisez un fichier PDF, JPG, JPEG ou PNG.";
        }

        if (empty($upload["tmp_name"]) || !is_file($upload["tmp_name"])) {
            return "Le fichier téléversé est introuvable. Veuillez réessayer.";
        }

        return "";
    }
}

if (!function_exists("bestcoproStoreDocumentUpload")) {
    function bestcoproStoreDocumentUpload($upload, $documentId, &$errorMessage = "")
    {
        $errorMessage = bestcoproDocumentUploadError($upload);
        if ($errorMessage !== "") {
            return false;
        }

        $directory = bestcoproDocumentsDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log("BestCopro document upload: unable to create storage directory " . $directory);
            $errorMessage = "Le dossier des documents n'existe pas et n'a pas pu être créé.";
            return false;
        }
        if (!is_writable($directory)) {
            error_log("BestCopro document upload: directory is not writable: " . $directory);
            $errorMessage = "Le dossier des documents n'est pas accessible en écriture.";
            return false;
        }

        $extension = strtolower(pathinfo((string) $upload["name"], PATHINFO_EXTENSION));
        $location = $directory . DIRECTORY_SEPARATOR . (int) $documentId . "." . $extension;
        if (!@move_uploaded_file($upload["tmp_name"], $location)) {
            error_log("BestCopro document upload: move_uploaded_file failed for " . $location);
            $errorMessage = "Impossible d'enregistrer le fichier dans le dossier des documents.";
            return false;
        }

        return $location;
    }
}

if (!function_exists("bestcoproDocumentFiles")) {
    function bestcoproDocumentFiles($documentId)
    {
        $documentId = filter_var($documentId, FILTER_VALIDATE_INT);
        if ($documentId === false || $documentId <= 0) {
            return [];
        }

        foreach (bestcoproDocumentStorageLocations() as $location) {
            $files = glob($location["directory"] . DIRECTORY_SEPARATOR . $documentId . ".*");
            if (!is_array($files)) {
                continue;
            }
            $files = array_values(array_filter($files, "is_file"));
            if (count($files) > 0) {
                sort($files, SORT_NATURAL | SORT_FLAG_CASE);
                return $files;
            }
        }

        return [];
    }
}

if (!function_exists("bestcoproDocumentPublicUrl")) {
    function bestcoproDocumentPublicUrl($file)
    {
        $file = str_replace("\\", "/", (string) $file);
        foreach (bestcoproDocumentStorageLocations() as $location) {
            $directory = rtrim(str_replace("\\", "/", $location["directory"]), "/");
            if (strpos($file, $directory . "/") === 0) {
                return rtrim($location["public_path"], "/") .
                    "/justificatifs/documents/" .
                    rawurlencode(basename($file));
            }
        }

        return rtrim(bestcoproCurrentApplicationPath(), "/") .
            "/justificatifs/documents/" .
            rawurlencode(basename($file));
    }
}
