<?php
include "../connect/connect.php";
include "auth_check.php";

// Vérifier si la catégorie et le média sont spécifiés
if (!isset($_GET['category']) || !isset($_GET['image'])) {
    $_SESSION['error'] = "Paramètres manquants pour la suppression.";
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$mediaName = $_GET['image']; // Peut être une image ou une vidéo
$mediaType = isset($_GET['type']) ? $_GET['type'] : 'auto'; // 'image', 'video', ou 'auto'

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
    $_SESSION['error'] = "Catégorie invalide.";
    header("Location: portfolio.php");
    exit;
}

// Déterminer le type de média et les chemins possibles
$imagePath = $portfolioMainDir . $category . "/" . $mediaName;
$videoPath = $portfolioVideoDir . $category . "/" . $mediaName;

$deleted = false;
$deletedType = '';

// Si le type est spécifié, essayer de supprimer directement
if ($mediaType === 'image') {
    if (file_exists($imagePath)) {
        if (unlink($imagePath)) {
            $deleted = true;
            $deletedType = 'image';
        }
    }
} elseif ($mediaType === 'video') {
    if (file_exists($videoPath)) {
        if (unlink($videoPath)) {
            $deleted = true;
            $deletedType = 'vidéo';
        }
    }
} else {
    // Mode automatique : détecter le type de fichier par extension
    $extension = strtolower(pathinfo($mediaName, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'ogv'];
    
    if (in_array($extension, $imageExtensions)) {
        // C'est probablement une image
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                $deleted = true;
                $deletedType = 'image';
            }
        }
    } elseif (in_array($extension, $videoExtensions)) {
        // C'est probablement une vidéo
        if (file_exists($videoPath)) {
            if (unlink($videoPath)) {
                $deleted = true;
                $deletedType = 'vidéo';
            }
        }
    } else {
        // Extension inconnue, essayer les deux emplacements
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                $deleted = true;
                $deletedType = 'image';
            }
        } elseif (file_exists($videoPath)) {
            if (unlink($videoPath)) {
                $deleted = true;
                $deletedType = 'vidéo';
            }
        }
    }
}

// Définir le message de retour
if ($deleted) {
    $_SESSION['message'] = "Le média '$mediaName' ($deletedType) a été supprimé avec succès.";
} else {
    $_SESSION['error'] = "Média '$mediaName' introuvable dans cette catégorie.";
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