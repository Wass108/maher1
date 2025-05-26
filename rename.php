<?php
$baseDir = 'Site/';

// Vérifie que le dossier Site existe
if (!is_dir($baseDir)) {
    die("Le dossier $baseDir n'existe pas.");
}

// Récupère la liste des éléments dans le dossier Site
$items = scandir($baseDir);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }
    
    $folderPath = $baseDir . $item;
    
    // On ne traite que les dossiers
    if (is_dir($folderPath)) {
        echo "Traitement du dossier : $folderPath\n";
        
        // Récupère la liste des fichiers dans le dossier
        $files = array_diff(scandir($folderPath), array('.', '..'));
        sort($files); // Tri pour assurer un ordre
        
        $counter = 1;
        foreach ($files as $file) {
            $filePath = $folderPath . '/' . $file;
            if (is_file($filePath)) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $newName = sprintf("%02d.%s", $counter, $extension);
                $newPath = $folderPath . '/' . $newName;
                
                // Vérifie si le nouveau nom existe déjà
                if (file_exists($newPath)) {
                    echo "Le fichier $newPath existe déjà. Renommage annulé pour $file\n";
                } else {
                    if (rename($filePath, $newPath)) {
                        echo "Renommé $file en $newName dans $folderPath\n";
                    } else {
                        echo "Échec du renommage de $file dans $folderPath\n";
                    }
                }
                $counter++;
            }
        }
        echo "\n";
    }
}
?>