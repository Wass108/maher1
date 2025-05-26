<?php
include "../connect/connect.php";
include "auth_check.php";

// Vérifier si l'ID du projet et le nom de l'image sont spécifiés
if (!isset($_GET['project']) || !isset($_GET['image'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['project'];
$imageName = $_GET['image'];

// Récupérer les informations du projet
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

// Chemin de l'image à supprimer
$imagePath = "../img/projects/" . $project['slug'] . "/" . $imageName;

// Vérifier si l'image existe
if (file_exists($imagePath)) {
    // Supprimer l'image
    if (unlink($imagePath)) {
        $_SESSION['message'] = "L'image a été supprimée avec succès.";
    } else {
        $_SESSION['message'] = "Échec de la suppression de l'image.";
    }
} else {
    $_SESSION['message'] = "Image introuvable.";
}

// Rediriger vers la page de gestion des images
header("Location: manage_images.php?project=" . $projectId);
exit;
?>