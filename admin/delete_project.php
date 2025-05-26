<?php
include "../connect/connect.php";
include "auth_check.php";

// Vérifier si l'ID du projet est spécifié
if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['id'];

// Récupérer les informations du projet avant de le supprimer
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

// Vérifier si le projet existe
if (!$project) {
    $_SESSION['message'] = "Projet introuvable.";
    header("Location: projects.php");
    exit;
}

// Récupérer le slug pour pouvoir supprimer le dossier des images
$projectSlug = $project['title'];

// Si confirmé ou si accès direct (dans ce cas la confirmation est faite via JavaScript)
// Supprimer le projet de la base de données
$query = "DELETE FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$result = $stmt->execute(['id' => $projectId]);

if ($result) {
    // Supprimer le dossier des images du projet
    $projectDir = "../img/projects/" . $project['slug'] . "/";
    
    if (is_dir($projectDir)) {
        // Supprimer tous les fichiers dans le dossier
        $files = glob($projectDir . "*.*");
        foreach($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Supprimer le dossier
        rmdir($projectDir);
    }
    
    $_SESSION['message'] = "Le projet \"" . htmlspecialchars($project['title']) . "\" a été supprimé avec succès.";
} else {
    $_SESSION['message'] = "Une erreur est survenue lors de la suppression du projet.";
}

// Rediriger vers la liste des projets
header("Location: projects.php");
exit;
?>