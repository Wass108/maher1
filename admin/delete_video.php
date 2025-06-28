<?php
// Inclure les fichiers nécessaires
include "../connect/connect.php";
include "auth_check.php";

// Vérifier les paramètres
if (!isset($_GET['project']) || !isset($_GET['video'])) {
    $_SESSION['error'] = "Paramètres manquants.";
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['project'];
$videoName = $_GET['video'];

// Récupérer les informations du projet
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

// Vérifier si le projet existe
if (!$project) {
    $_SESSION['error'] = "Projet non trouvé.";
    header("Location: projects.php");
    exit;
}

// Construire le chemin de la vidéo
$projectDir = "../videos/projects/" . $project['slug'] . "/";
$videoPath = $projectDir . $videoName;

// Vérifier si la vidéo existe
if (!file_exists($videoPath)) {
    $_SESSION['error'] = "Vidéo non trouvée.";
    header("Location: manage_videos.php?project=" . $projectId);
    exit;
}

// Supprimer la vidéo
if (unlink($videoPath)) {
    $_SESSION['message'] = "Vidéo supprimée avec succès.";
} else {
    $_SESSION['error'] = "Impossible de supprimer la vidéo.";
}

// Rediriger vers la page de gestion des vidéos
header("Location: manage_videos.php?project=" . $projectId);
exit;
?>