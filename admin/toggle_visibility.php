<?php
include "../connect/connect.php";
include "auth_check.php";

// Vérifier si l'ID du projet est spécifié
if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['id'];

// Récupérer l'état actuel du projet
$query = "SELECT hidden, title FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

if ($project) {
    // Basculer l'état (hidden)
    $newState = $project['hidden'] ? 0 : 1;
    
    // Mettre à jour la base de données
    $query = "UPDATE projects SET hidden = :hidden WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        'hidden' => $newState,
        'id' => $projectId
    ]);
    
    if ($result) {
        $action = $newState ? "masqué" : "rendu visible";
        $_SESSION['message'] = "Le projet \"" . htmlspecialchars($project['title']) . "\" a été " . $action . " avec succès.";
    } else {
        $_SESSION['message'] = "Une erreur est survenue lors de la mise à jour du projet.";
    }
}

// Rediriger vers la page des projets
header("Location: projects.php");
exit;
?>