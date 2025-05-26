<?php
$sourceDir = 'img/projects/nuage/';

if (!is_dir($sourceDir)) {
    die("Le dossier $sourceDir n'existe pas.");
}

$files = array_diff(scandir($sourceDir), array('.', '..'));

foreach ($files as $file) {
    $filePath = $sourceDir . $file;
    if (is_file($filePath)) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        // Si le fichier est déjà en jpg, on le saute
        if ($extension === 'jpg' || $extension === 'jpeg') {
            echo "Le fichier $file est déjà en jpg.\n";
            continue;
        }
        
        // Création de la ressource image en fonction de l'extension
        switch ($extension) {
            case 'png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'gif':
                $image = imagecreatefromgif($filePath);
                break;
            case 'bmp':
                if (function_exists('imagecreatefrombmp')) {
                    $image = imagecreatefrombmp($filePath);
                } else {
                    echo "Fonction imagecreatefrombmp non disponible pour $file.\n";
                    continue 2;
                }
                break;
            default:
                echo "Extension non supportée pour $file.\n";
                continue 2;
        }
        
        if ($image !== false) {
            // Création du nom du nouveau fichier jpg
            $newName = pathinfo($file, PATHINFO_FILENAME) . '.jpg';
            $newPath = $sourceDir . $newName;
            
            // Conversion et sauvegarde avec une qualité de 90
            if (imagejpeg($image, $newPath, 90)) {
                echo "Conversion réussie : $file -> $newName\n";
                // Pour supprimer le fichier d'origine, décommentez la ligne suivante :
                // unlink($filePath);
            } else {
                echo "Échec de la conversion pour $file.\n";
            }
            imagedestroy($image);
        } else {
            echo "Impossible de créer l'image pour $file.\n";
        }
    }
}
?>