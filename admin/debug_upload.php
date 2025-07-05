<?php
// Script de débogage pour l'upload d'images
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

echo "<h2>Debug Upload - Projet: " . htmlspecialchars($project['title']) . "</h2>";
echo "<p>Slug: " . htmlspecialchars($project['slug']) . "</p>";

$projectDir = "../img/projects/" . $project['slug'] . "/";
echo "<h3>Dossier du projet: " . $projectDir . "</h3>";

// Traitement du formulaire de test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_images'])) {
    echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🔍 DÉBOGAGE DE L'UPLOAD EN COURS...</h3>";
    
    // Vérifier les données POST
    echo "<h4>1. Données POST reçues:</h4>";
    echo "<pre>";
    echo "POST variables:\n";
    print_r($_POST);
    echo "\nFILES variables:\n";
    print_r($_FILES);
    echo "</pre>";
    
    // Vérifier le dossier de destination
    echo "<h4>2. Vérification du dossier:</h4>";
    if (!is_dir($projectDir)) {
        echo "❌ Dossier n'existe pas. Création...\n";
        if (mkdir($projectDir, 0777, true)) {
            echo "✅ Dossier créé avec succès\n";
        } else {
            echo "❌ Échec de création du dossier\n";
        }
    } else {
        echo "✅ Dossier existe déjà\n";
    }
    
    echo "Permissions du dossier: " . (is_writable($projectDir) ? "✅ Écriture autorisée" : "❌ Écriture refusée") . "\n";
    
    // Traiter chaque fichier
    echo "<h4>3. Traitement des fichiers:</h4>";
    if (isset($_FILES['test_images']['tmp_name'])) {
        foreach ($_FILES['test_images']['tmp_name'] as $key => $tmp_name) {
            echo "\n--- Fichier $key ---\n";
            echo "Nom original: " . $_FILES['test_images']['name'][$key] . "\n";
            echo "Type MIME: " . $_FILES['test_images']['type'][$key] . "\n";
            echo "Taille: " . $_FILES['test_images']['size'][$key] . " bytes\n";
            echo "Erreur upload: " . $_FILES['test_images']['error'][$key] . "\n";
            echo "Fichier temporaire: " . $tmp_name . "\n";
            echo "Fichier temporaire existe: " . (file_exists($tmp_name) ? "✅ Oui" : "❌ Non") . "\n";
            
            if ($_FILES['test_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['test_images']['name'][$key];
                $fileType = $_FILES['test_images']['type'][$key];
                
                echo "Vérification type image: " . (strpos($fileType, 'image/') === 0 ? "✅ Valide" : "❌ Non valide") . "\n";
                
                if (strpos($fileType, 'image/') === 0) {
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = "test_upload." . $extension;
                    $destination = $projectDir . $newFileName;
                    
                    echo "Destination: " . $destination . "\n";
                    echo "Tentative de déplacement...\n";
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        echo "✅ Fichier déplacé avec succès!\n";
                        echo "Taille finale: " . filesize($destination) . " bytes\n";
                        
                        // Vérifier que le fichier est bien là
                        clearstatcache();
                        $files = glob($projectDir . "*.*");
                        echo "Fichiers dans le dossier après upload:\n";
                        foreach ($files as $file) {
                            echo "  - " . basename($file) . "\n";
                        }
                    } else {
                        echo "❌ Échec du déplacement du fichier\n";
                        echo "Raisons possibles:\n";
                        echo "  - Permissions insuffisantes\n";
                        echo "  - Dossier de destination inexistant\n";
                        echo "  - Fichier temporaire corrompu\n";
                    }
                }
            } else {
                echo "❌ Erreur lors de l'upload: ";
                switch ($_FILES['test_images']['error'][$key]) {
                    case UPLOAD_ERR_INI_SIZE:
                        echo "Fichier trop volumineux (limite PHP)\n";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        echo "Fichier trop volumineux (limite formulaire)\n";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        echo "Upload partiel\n";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        echo "Aucun fichier uploadé\n";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        echo "Dossier temporaire manquant\n";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        echo "Impossible d'écrire sur le disque\n";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        echo "Extension PHP a arrêté l'upload\n";
                        break;
                    default:
                        echo "Erreur inconnue (" . $_FILES['test_images']['error'][$key] . ")\n";
                }
            }
        }
    } else {
        echo "❌ Aucun fichier reçu dans \$_FILES['test_images']\n";
    }
    
    echo "</div>";
}

// Informations de configuration PHP
echo "<h3>Configuration PHP pour les uploads:</h3>";
echo "<pre>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Défaut système') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "s\n";
echo "</pre>";

?>

<h3>Formulaire de test d'upload:</h3>
<form method="post" enctype="multipart/form-data" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
    <p>Utilisez ce formulaire pour tester l'upload et voir exactement ce qui se passe :</p>
    <input type="file" name="test_images[]" accept="image/*" multiple required>
    <br><br>
    <button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px;">
        Tester l'upload
    </button>
</form>

<hr>
<a href="manage_images.php?project=<?php echo $projectId; ?>">← Retour à la gestion des images</a>