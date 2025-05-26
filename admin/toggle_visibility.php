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
$query = "SELECT hidden, featured, title FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

if ($project) {
    // Basculer l'état (hidden)
    $newState = $project['hidden'] ? 0 : 1;
    
    // Si on masque le projet et qu'il est en une, on le retire également de la une
    $setFeatured = '';
    $params = [
        'hidden' => $newState,
        'id' => $projectId
    ];
    
    if ($newState == 1 && $project['featured'] == 1) {
        $setFeatured = ', featured = 0';
        $infoFeatured = "Le projet a également été retiré de la une car un projet masqué ne peut pas être en une.";
    } else {
        $infoFeatured = "";
    }
    
    // Mettre à jour la base de données
    $query = "UPDATE projects SET hidden = :hidden" . $setFeatured . " WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        $action = $newState ? "masqué" : "rendu visible";
        $_SESSION['message'] = "Le projet \"" . htmlspecialchars($project['title']) . "\" a été " . $action . " avec succès. " . $infoFeatured;
    } else {
        $_SESSION['message'] = "Une erreur est survenue lors de la mise à jour du projet.";
    }
}

// Rediriger vers la page des projets
header("Location: projects.php");
exit;
?>