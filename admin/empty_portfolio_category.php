<?php
// Inclure les fichiers nécessaires
include "../connect/connect.php";
include "admin_header_buffered.php";

// Fonction pour récupérer dynamiquement toutes les catégories existantes
function getExistingCategories($portfolioMainDir) {
    $categories = [];
    
    if (is_dir($portfolioMainDir)) {
        $items = scandir($portfolioMainDir);
        
        foreach ($items as $item) {
            // Ignorer les dossiers système et les fichiers
            if ($item !== '.' && $item !== '..' && is_dir($portfolioMainDir . $item)) {
                $categories[] = $item;
            }
        }
        
        // Trier les catégories par ordre alphabétique
        sort($categories);
    }
    
    return $categories;
}

$portfolioMainDir = "../img/portfolio/";

// Récupérer dynamiquement toutes les catégories existantes
$categories = getExistingCategories($portfolioMainDir);

// Vérifier la catégorie
if (!isset($_GET['category']) || !in_array($_GET['category'], $categories)) {
    $_SESSION['error'] = "Catégorie invalide ou introuvable.";
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$categoryDir = $portfolioMainDir . $category . "/";

// Traitement du vidage de la catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_empty'])) {
    $deletedCount = 0;
    
    if (is_dir($categoryDir)) {
        // Supprimer toutes les images dans le dossier
        $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        
        foreach ($images as $image) {
            if (is_file($image)) {
                if (unlink($image)) {
                    $deletedCount++;
                }
            }
        }
        
        // Supprimer également tous les autres fichiers qui pourraient être présents
        $allFiles = glob($categoryDir . "*");
        foreach ($allFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // GARDER le dossier (ne pas utiliser rmdir())
    }
    
    if ($deletedCount > 0) {
        $_SESSION['message'] = "La catégorie '$category' a été vidée avec succès. $deletedCount image(s) supprimée(s).";
    } else {
        $_SESSION['message'] = "Aucune image trouvée dans la catégorie '$category'.";
    }
    
    // Déterminer la page de redirection basée sur le referer ou un paramètre
    $redirectTo = 'portfolio.php';
    if (isset($_GET['redirect']) && $_GET['redirect'] === 'manage_category') {
        $redirectTo = 'manage_category.php?category=' . urlencode($category);
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, 'manage_category.php') !== false) {
            $redirectTo = 'manage_category.php?category=' . urlencode($category);
        }
    }
    
    // Rediriger vers la page appropriée
    header("Location: $redirectTo");
    exit;
} else {
    // Afficher la page de confirmation
    
    // Récupérer le nombre d'images
    $imageCount = 0;
    if (is_dir($categoryDir)) {
        $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        $imageCount = count($images);
    }
    
    ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Vider la catégorie</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="portfolio.php">Portfolio</a></li>
                    <li class="breadcrumb-item active">Vider catégorie</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h3 class="card-title">
                    <i class="fa fa-warning"></i> Confirmation de vidage
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h4><i class="fa fa-exclamation-triangle"></i> Attention !</h4>
                    <p>Vous êtes sur le point de <strong>VIDER</strong> la catégorie <strong><?php echo htmlspecialchars($category); ?></strong>.</p>
                    <p>Cette action va :</p>
                    <ul>
                        <li>Supprimer <strong><?php echo $imageCount; ?> image(s)</strong></li>
                        <li><strong>GARDER</strong> le dossier de la catégorie (vide)</li>
                    </ul>
                    <p><strong>Cette action est irréversible !</strong></p>
                </div>
                
                <?php if ($imageCount > 0): ?>
                    <p>Êtes-vous sûr de vouloir vider cette catégorie ?</p>
                    
                    <form method="post" class="d-inline">
                        <input type="hidden" name="confirm_empty" value="1">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-trash"></i> Oui, vider la catégorie
                        </button>
                    </form>
                <?php else: ?>
                    <p>Aucune image trouvée dans cette catégorie.</p>
                    <a href="portfolio.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Retour
                    </a>
                <?php endif; ?>
                
                <a href="portfolio.php" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Annuler
                </a>
            </div>
        </div>
    </div>
</section>

<?php include "admin_footer.php"; ?>

<?php
}
?>