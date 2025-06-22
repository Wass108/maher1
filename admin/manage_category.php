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

// Vérifier si la catégorie est spécifiée et valide
if (!isset($_GET['category']) || !in_array($_GET['category'], $categories)) {
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$categoryDir = $portfolioMainDir . $category . "/";

// Messages de feedback
$message = '';
$error = '';

// Traitement du renommage de la catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_category'])) {
    $newCategoryName = trim($_POST['new_category_name']);
    
    if (empty($newCategoryName)) {
        $_SESSION['error'] = "Le nouveau nom de catégorie ne peut pas être vide.";
    } elseif (in_array($newCategoryName, $categories) && $newCategoryName !== $category) {
        $_SESSION['error'] = "Une catégorie avec ce nom existe déjà.";
    } else {
        $newCategoryDir = $portfolioMainDir . $newCategoryName . "/";
        
        // Renommer le dossier
        if (rename($categoryDir, $newCategoryDir)) {
            $_SESSION['message'] = "Catégorie renommée de '$category' vers '$newCategoryName' avec succès.";
            // Rediriger vers la nouvelle catégorie
            header("Location: manage_category.php?category=" . urlencode($newCategoryName));
            exit;
        } else {
            $_SESSION['error'] = "Impossible de renommer la catégorie.";
        }
    }
    
    // Rediriger pour éviter la resoumission
    header("Location: manage_category.php?category=" . urlencode($category));
    exit;
}

// Traitement de la suppression d'une image
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $imageToDelete = $_GET['delete'];
    $imagePath = $categoryDir . $imageToDelete;
    
    if (file_exists($imagePath) && unlink($imagePath)) {
        $_SESSION['message'] = "Image '$imageToDelete' supprimée avec succès.";
    } else {
        $_SESSION['error'] = "Impossible de supprimer l'image '$imageToDelete'.";
    }
    
    // Rediriger pour éviter la resoumission
    header("Location: manage_category.php?category=" . urlencode($category));
    exit;
}

// Récupérer les images de cette catégorie
$categoryImages = [];
if (is_dir($categoryDir)) {
    $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
    foreach ($images as $image) {
        $categoryImages[] = [
            'path' => $image,
            'name' => basename($image),
            'size' => filesize($image),
            'url' => $image,
            'modified' => filemtime($image)
        ];
    }
    
    // Trier par date de modification (plus récent en premier)
    usort($categoryImages, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Calculer les statistiques
$totalImages = count($categoryImages);
$totalSize = array_sum(array_column($categoryImages, 'size'));

ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gérer la catégorie : <?php echo htmlspecialchars($category); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="portfolio.php">Portfolio</a></li>
                    <li class="breadcrumb-item"><a href="manage_portfolio_categories.php">Catégories</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($category); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Affichage des messages de session -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <!-- Actions rapides -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="btn-group" role="group">
                    <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" class="btn btn-success">
                        <i class="fa fa-plus"></i> Ajouter des images
                    </a>
                    <a href="portfolio.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Retour au portfolio
                    </a>
                    <a href="manage_portfolio_categories.php" class="btn btn-info">
                        <i class="fa fa-list"></i> Toutes les catégories
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Informations et actions sur la catégorie -->
        <div class="row mb-4">
            <!-- Statistiques -->
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5>Images</h5>
                                <h2><?php echo $totalImages; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5>Taille</h5>
                                <h2><?php echo round($totalSize / 1024 / 1024, 1); ?> MB</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5>Dossier</h5>
                                <p class="mb-0"><small><?php echo $category; ?>/</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Renommer la catégorie -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Renommer la catégorie</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group mb-3">
                                <input type="text" class="form-control" name="new_category_name" 
                                       value="<?php echo htmlspecialchars($category); ?>" required>
                            </div>
                            <button type="submit" name="rename_category" class="btn btn-warning btn-sm">
                                <i class="fa fa-edit"></i> Renommer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des images -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-images"></i> Images de la catégorie "<?php echo htmlspecialchars($category); ?>"
                </h3>
                <div class="card-tools">
                    <?php if ($totalImages > 0): ?>
                        <a href="empty_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                           class="btn btn-warning btn-sm"
                           onclick="return confirm('Êtes-vous sûr de vouloir vider cette catégorie ? Toutes les images seront supprimées mais la catégorie restera.');">
                            <i class="fa fa-trash"></i> Vider la catégorie
                        </a>
                        <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer complètement cette catégorie ? Le dossier et toutes les images seront supprimés.');">
                            <i class="fa fa-times"></i> Supprimer la catégorie
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($categoryImages)): ?>
                    <div class="row">
                        <?php foreach ($categoryImages as $image): ?>
                            <div class="col-md-3 col-sm-6 col-12 mb-3">
                                <div class="card">
                                    <img src="<?php echo $image['url']; ?>" class="card-img-top" alt="Image" style="height: 200px; object-fit: cover;">
                                    <div class="card-body p-2">
                                        <h6 class="card-title small text-truncate" title="<?php echo htmlspecialchars($image['name']); ?>">
                                            <?php echo htmlspecialchars($image['name']); ?>
                                        </h6>
                                        <p class="card-text small text-muted mb-2">
                                            <?php echo round($image['size'] / 1024, 1); ?> KB<br>
                                            <small><?php echo date('d/m/Y H:i', $image['modified']); ?></small>
                                        </p>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <a href="<?php echo $image['url']; ?>" target="_blank" class="btn btn-info" title="Voir en grand">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="?delete=<?php echo urlencode($image['name']); ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');" 
                                               title="Supprimer">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa fa-image fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucune image dans cette catégorie</h4>
                        <p class="text-muted">Commencez par ajouter des images à cette catégorie.</p>
                        <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Ajouter des images
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include "admin_footer.php"; ?>