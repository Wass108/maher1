<?php
include "../connect/connect.php";
include "auth_check.php";

// Définir le header JSON dès le début
header('Content-Type: application/json');

// Vérifier la méthode et l'action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'delete_multiple') {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

// Vérifier les paramètres
if (!isset($_POST['category']) || !isset($_POST['images']) || !is_array($_POST['images'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$category = $_POST['category'];
$mediaToDelete = $_POST['images']; // Peut contenir des images et des vidéos

// Fonction pour récupérer dynamiquement toutes les catégories existantes
function getExistingCategories($portfolioMainDir) {
    $categories = [];
    
    if (is_dir($portfolioMainDir)) {
        $items = scandir($portfolioMainDir);
        
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($portfolioMainDir . $item)) {
                $categories[] = $item;
            }
        }
        sort($categories);
    }
    
    return $categories;
}

$portfolioMainDir = "../img/portfolio/";
$portfolioVideoDir = "../videos/portfolio/";
$categories = getExistingCategories($portfolioMainDir);

// Vérifier si la catégorie existe
if (!in_array($category, $categories)) {
    echo json_encode(['success' => false, 'message' => 'Catégorie invalide']);
    exit;
}

$categoryDir = $portfolioMainDir . $category . "/";
$categoryVideoDir = $portfolioVideoDir . $category . "/";

// Traitement de la suppression
$deletedCount = 0;
$deletedImages = 0;
$deletedVideos = 0;
$errorCount = 0;
$errors = [];

foreach ($mediaToDelete as $mediaName) {
    // Sécurité : vérifier que le nom de fichier ne contient pas de caractères dangereux
    if (strpos($mediaName, '../') !== false || strpos($mediaName, '/') !== false) {
        $errorCount++;
        $errors[] = "Nom de fichier invalide: " . $mediaName;
        continue;
    }
    
    // Déterminer le type de média par extension
    $extension = strtolower(pathinfo($mediaName, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'ogv'];
    
    $deleted = false;
    $mediaType = '';
    
    if (in_array($extension, $imageExtensions)) {
        // C'est probablement une image
        $imagePath = $categoryDir . $mediaName;
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                $deletedCount++;
                $deletedImages++;
                $deleted = true;
                $mediaType = 'image';
            } else {
                $errorCount++;
                $errors[] = "Impossible de supprimer l'image: " . $mediaName;
            }
        } else {
            $errorCount++;
            $errors[] = "Image non trouvée: " . $mediaName;
        }
    } elseif (in_array($extension, $videoExtensions)) {
        // C'est probablement une vidéo
        $videoPath = $categoryVideoDir . $mediaName;
        if (file_exists($videoPath)) {
            if (unlink($videoPath)) {
                $deletedCount++;
                $deletedVideos++;
                $deleted = true;
                $mediaType = 'vidéo';
            } else {
                $errorCount++;
                $errors[] = "Impossible de supprimer la vidéo: " . $mediaName;
            }
        } else {
            $errorCount++;
            $errors[] = "Vidéo non trouvée: " . $mediaName;
        }
    } else {
        // Extension inconnue, essayer les deux emplacements
        $imagePath = $categoryDir . $mediaName;
        $videoPath = $categoryVideoDir . $mediaName;
        
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                $deletedCount++;
                $deletedImages++;
                $deleted = true;
                $mediaType = 'image';
            } else {
                $errorCount++;
                $errors[] = "Impossible de supprimer le fichier: " . $mediaName;
            }
        } elseif (file_exists($videoPath)) {
            if (unlink($videoPath)) {
                $deletedCount++;
                $deletedVideos++;
                $deleted = true;
                $mediaType = 'vidéo';
            } else {
                $errorCount++;
                $errors[] = "Impossible de supprimer le fichier: " . $mediaName;
            }
        } else {
            $errorCount++;
            $errors[] = "Média non trouvé: " . $mediaName;
        }
    }
}

// Construire le message de succès détaillé
$messages = [];
if ($deletedImages > 0) {
    $messages[] = "$deletedImages image(s)";
}
if ($deletedVideos > 0) {
    $messages[] = "$deletedVideos vidéo(s)";
}

$successMessage = "";
if (!empty($messages)) {
    $successMessage = implode(' et ', $messages) . " supprimé(s) avec succès";
} else {
    $successMessage = "Aucun média supprimé";
}

if ($errorCount > 0) {
    $successMessage .= ", $errorCount erreur(s)";
}

// Construire la réponse
$response = [
    'success' => true,
    'deleted' => $deletedCount,
    'deleted_images' => $deletedImages,
    'deleted_videos' => $deletedVideos,
    'errors' => $errorCount,
    'message' => $successMessage,
    'error_details' => $errors
];

echo json_encode($response);
exit;
?>