<?php
// Fichier de débogage pour diagnostiquer le problème d'upload d'images
include "../connect/connect.php";

if (!isset($_GET['project'])) {
    die("ID du projet requis");
}

$projectId = $_GET['project'];

// Récupérer les informations du projet
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

if (!$project) {
    die("Projet non trouvé");
}

echo "<h2>Debug - Projet: " . htmlspecialchars($project['title']) . "</h2>";
echo "<p>Slug: " . htmlspecialchars($project['slug']) . "</p>";

$projectDir = "../img/projects/" . $project['slug'] . "/";
echo "<h3>Dossier du projet: " . $projectDir . "</h3>";
echo "<p>Chemin absolu: " . realpath($projectDir) . "</p>";

// Vérifier si le dossier existe
if (is_dir($projectDir)) {
    echo "<p style='color: green;'>✓ Le dossier existe</p>";
    
    // Lister tous les fichiers avec plus de détails
    $allFiles = scandir($projectDir);
    echo "<h4>Tous les fichiers dans le dossier (" . count($allFiles) . " éléments):</h4>";
    echo "<pre>";
    foreach ($allFiles as $file) {
        if ($file != '.' && $file != '..') {
            $fullPath = $projectDir . $file;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $size = filesize($fullPath);
            $type = is_dir($fullPath) ? 'DIR' : 'FILE';
            echo "- {$file} (Type: {$type}, Extension: {$extension}, Taille: {$size} bytes)\n";
        }
    }
    echo "</pre>";
    
    // Test avec différents patterns glob()
    echo "<h4>Tests avec différents patterns glob():</h4>";
    
    $patterns = [
        "*.{jpg,jpeg,png,gif}",
        "*.jpg",
        "*.jpeg", 
        "*.png",
        "*.gif",
        "*.JPG",
        "*.JPEG",
        "*.PNG",
        "*.GIF",
        "*.*"
    ];
    
    foreach ($patterns as $pattern) {
        clearstatcache();
        $matches = glob($projectDir . $pattern, GLOB_BRACE);
        echo "<strong>Pattern '{$pattern}':</strong> " . count($matches) . " fichiers trouvés<br>";
        if (!empty($matches)) {
            foreach ($matches as $match) {
                echo "&nbsp;&nbsp;- " . basename($match) . "<br>";
            }
        }
    }
    
    // Test avec readdir()
    echo "<h4>Test avec readdir():</h4>";
    $handle = opendir($projectDir);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $foundImages = [];
    
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $imageExtensions)) {
                $foundImages[] = $file;
            }
        }
    }
    closedir($handle);
    
    echo "<p>Images trouvées avec readdir(): " . count($foundImages) . "</p>";
    foreach ($foundImages as $img) {
        echo "- " . $img . "<br>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Le dossier n'existe pas</p>";
}

// Test de création de fichier image
echo "<h3>Test de création d'une image test:</h3>";
$testImagePath = $projectDir . "test_debug.jpg";

// Créer une image de test simple
$image = imagecreate(100, 100);
$bg = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 0, 0);
imagestring($image, 5, 10, 40, 'TEST', $text_color);

if (imagejpeg($image, $testImagePath)) {
    echo "<p style='color: green;'>✓ Image test créée avec succès</p>";
    
    // Maintenant tester si glob() la trouve
    clearstatcache();
    $testGlob = glob($projectDir . "*.jpg");
    echo "<p>Images .jpg trouvées après création: " . count($testGlob) . "</p>";
    
    // Supprimer l'image test
    if (unlink($testImagePath)) {
        echo "<p style='color: green;'>✓ Image test supprimée</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Impossible de créer l'image test</p>";
}

imagedestroy($image);

// Test de création de dossier
echo "<h3>Test de création de dossier:</h3>";
$testDir = $projectDir . "test_debug/";
if (mkdir($testDir, 0777, true)) {
    echo "<p style='color: green;'>✓ Création de dossier test réussie</p>";
    rmdir($testDir);
    echo "<p style='color: green;'>✓ Suppression du dossier test réussie</p>";
} else {
    echo "<p style='color: red;'>✗ Échec de création du dossier test</p>";
}

// Vérifier les permissions
echo "<h3>Permissions:</h3>";
if (is_writable(dirname($projectDir))) {
    echo "<p style='color: green;'>✓ Le dossier parent est accessible en écriture</p>";
} else {
    echo "<p style='color: red;'>✗ Le dossier parent n'est pas accessible en écriture</p>";
}

if (is_writable($projectDir)) {
    echo "<p style='color: green;'>✓ Le dossier du projet est accessible en écriture</p>";
} else {
    echo "<p style='color: red;'>✗ Le dossier du projet n'est pas accessible en écriture</p>";
}

// Informations système
echo "<h3>Informations système:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>OS: " . php_uname() . "</p>";
echo "<p>Extension GD: " . (extension_loaded('gd') ? '✓ Activée' : '✗ Désactivée') . "</p>";

echo "<hr>";
echo "<a href='manage_images.php?project=" . $projectId . "'>← Retour à la gestion des images</a>";
?>