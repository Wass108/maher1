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
$imagesToDelete = $_POST['images'];

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
$categories = getExistingCategories($portfolioMainDir);

// Vérifier si la catégorie existe
if (!in_array($category, $categories)) {
    echo json_encode(['success' => false, 'message' => 'Catégorie invalide']);
    exit;
}

$categoryDir = $portfolioMainDir . $category . "/";

// Traitement de la suppression
$deletedCount = 0;
$errorCount = 0;
$errors = [];

foreach ($imagesToDelete as $imageName) {
    // Sécurité : vérifier que le nom de fichier ne contient pas de caractères dangereux
    if (strpos($imageName, '../') !== false || strpos($imageName, '/') !== false) {
        $errorCount++;
        $errors[] = "Nom de fichier invalide: " . $imageName;
        continue;
    }
    
    $imagePath = $categoryDir . $imageName;
    
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            $deletedCount++;
        } else {
            $errorCount++;
            $errors[] = "Impossible de supprimer: " . $imageName;
        }
    } else {
        $errorCount++;
        $errors[] = "Image non trouvée: " . $imageName;
    }
}

// Construire la réponse
$response = [
    'success' => true,
    'deleted' => $deletedCount,
    'errors' => $errorCount,
    'message' => "$deletedCount image(s) supprimée(s) avec succès" . 
                ($errorCount > 0 ? ", $errorCount erreur(s)" : ""),
    'error_details' => $errors
];

echo json_encode($response);
exit;
?>