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
if (!isset($_POST['project']) || !isset($_POST['images']) || !is_array($_POST['images'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$projectId = $_POST['project'];
$imagesToDelete = $_POST['images'];

// Récupérer les informations du projet depuis la base de données
try {
    $query = "SELECT slug FROM projects WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projet non trouvé dans la base de données']);
        exit;
    }
    
    $projectSlug = $project['slug'];
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
}

$projectsMainDir = "../img/projects/";
$projectDir = $projectsMainDir . $projectSlug . "/";

// Vérifier si le dossier du projet existe
if (!is_dir($projectDir)) {
    echo json_encode(['success' => false, 'message' => 'Dossier du projet non trouvé: ' . $projectSlug]);
    exit;
}

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
    
    $imagePath = $projectDir . $imageName;
    
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