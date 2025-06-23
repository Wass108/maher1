<?php
include "../connect/connect.php";
include "auth_check.php";

// Vérifier si la catégorie et l'image sont spécifiées
if (!isset($_GET['category']) || !isset($_GET['image'])) {
    $_SESSION['error'] = "Paramètres manquants pour la suppression.";
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$imageName = $_GET['image'];

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
    $_SESSION['error'] = "Catégorie invalide.";
    header("Location: portfolio.php");
    exit;
}

// Chemin de l'image à supprimer
$imagePath = $portfolioMainDir . $category . "/" . $imageName;

// Vérifier si l'image existe et la supprimer
if (file_exists($imagePath)) {
    if (unlink($imagePath)) {
        $_SESSION['message'] = "L'image '$imageName' a été supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Échec de la suppression de l'image '$imageName'.";
    }
} else {
    $_SESSION['error'] = "Image '$imageName' introuvable.";
}

// Déterminer la page de redirection basée sur le referer ou un paramètre
$redirectTo = 'portfolio.php';
if (isset($_GET['redirect'])) {
    if ($_GET['redirect'] === 'manage_category') {
        $redirectTo = 'manage_category.php?category=' . urlencode($category);
    } elseif ($_GET['redirect'] === 'portfolio') {
        $redirectTo = 'portfolio.php';
    }
} elseif (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (strpos($referer, 'manage_category.php') !== false) {
        $redirectTo = 'manage_category.php?category=' . urlencode($category);
    }
}

// Rediriger vers la page appropriée
header("Location: $redirectTo");
exit;
?>