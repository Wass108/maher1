<?php
$baseDir = 'Site/';

// Vérifie que le dossier Site existe
if (!is_dir($baseDir)) {
    die("Le dossier $baseDir n'existe pas.");
}

$supportedExtensions = ['jpg', 'jpeg', 'png'];

$items = scandir($baseDir);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }
    
    $folderPath = $baseDir . $item;
    
    // On ne traite que les dossiers
    if (is_dir($folderPath)) {
        echo "Traitement du dossier : $folderPath\n";
        
        $files = array_diff(scandir($folderPath), array('.', '..'));
        foreach ($files as $file) {
            $filePath = $folderPath . '/' . $file;
            if (is_file($filePath)) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, $supportedExtensions)) {
                    
                    // Compression pour les fichiers JPEG
                    if ($extension === 'jpg' || $extension === 'jpeg') {
                        $image = imagecreatefromjpeg($filePath);
                        if ($image !== false) {
                            // Réenregistre l'image avec une qualité de 75 (qualité réduite)
                            if (imagejpeg($image, $filePath, 75)) {
                                echo "Image compressée : $filePath\n";
                            } else {
                                echo "Échec compression : $filePath\n";
                            }
                            imagedestroy($image);
                        } else {
                            echo "Impossible de lire l'image : $filePath\n";
                        }
                    
                    // Compression pour les fichiers PNG
                    } elseif ($extension === 'png') {
                        $image = imagecreatefrompng($filePath);
                        if ($image !== false) {
                            // Pour PNG, le paramètre représente le niveau de compression de 0 (aucune comp.) à 9 (max)
                            if (imagepng($image, $filePath, 6)) {
                                echo "Image compressée : $filePath\n";
                            } else {
                                echo "Échec compression : $filePath\n";
                            }
                            imagedestroy($image);
                        } else {
                            echo "Impossible de lire l'image : $filePath\n";
                        }
                    }
                }
            }
        }
        echo "\n";
    }
}
?>