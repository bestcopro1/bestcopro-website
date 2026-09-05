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
