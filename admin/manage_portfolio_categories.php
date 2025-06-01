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

// Messages de feedback
$message = '';
$error = '';

// Traitement de l'ajout d'une nouvelle catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $newCategory = trim($_POST['category_name']);
    
    if (empty($newCategory)) {
        $error = "Le nom de la catégorie ne peut pas être vide.";
    } elseif (in_array($newCategory, $categories)) {
        $error = "Cette catégorie existe déjà.";
    } else {
        $categoryDir = $portfolioMainDir . $newCategory . "/";
        
        // Créer le dossier
        if (mkdir($categoryDir, 0777, true)) {
            $message = "Catégorie '$newCategory' créée avec succès.";
            
            // Recharger les catégories
            $categories = getExistingCategories($portfolioMainDir);
        } else {
            $error = "Impossible de créer le dossier pour la catégorie.";
        }
    }
}

// Traitement de la suppression d'une catégorie
if (isset($_GET['delete_category'])) {
    $categoryToDelete = $_GET['delete_category'];
    
    if (in_array($categoryToDelete, $categories)) {
        $categoryDir = $portfolioMainDir . $categoryToDelete . "/";
        
        // Supprimer toutes les images de la catégorie
        if (is_dir($categoryDir)) {
            $images = glob($categoryDir . "*");
            foreach ($images as $image) {
                if (is_file($image)) {
                    unlink($image);
                }
            }
            
            // Supprimer le dossier
            if (rmdir($categoryDir)) {
                $message = "Catégorie '$categoryToDelete' supprimée avec succès.";
                
                // Recharger les catégories
                $categories = getExistingCategories($portfolioMainDir);
            } else {
                $error = "Impossible de supprimer le dossier de la catégorie.";
            }
        }
    }
}

// Récupérer les statistiques pour chaque catégorie
$categoryStats = [];
foreach ($categories as $category) {
    $categoryPath = $portfolioMainDir . $category . "/";
    $imageCount = 0;
    $totalSize = 0;
    
    if (is_dir($categoryPath)) {
        $images = glob($categoryPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        $imageCount = count($images);
        
        foreach ($images as $image) {
            $totalSize += filesize($image);
        }
    }
    
    $categoryStats[$category] = [
        'count' => $imageCount,
        'size' => $totalSize,
        'path' => $categoryPath
    ];
}

ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestion des catégories du Portfolio</h1>
                <p class="text-muted">Catégories détectées automatiquement : <?php echo count($categories); ?></p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="portfolio.php">Portfolio</a></li>
                    <li class="breadcrumb-item active">Catégories</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Information sur la détection automatique -->
        <div class="alert alert-info">
            <h5><i class="fa fa-info-circle"></i> Détection automatique des catégories</h5>
            <p>Les catégories sont détectées automatiquement en lisant tous les dossiers dans <code>img/portfolio/</code>.</p>
            <p>Toute nouvelle catégorie créée apparaîtra immédiatement dans la liste.</p>
        </div>
        
        <!-- Formulaire d'ajout de catégorie -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Ajouter une nouvelle catégorie</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="category_name">Nom de la catégorie</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" required placeholder="Ex: Architecture">
                                <small class="form-text text-muted">Le nom doit être unique et sera utilisé pour créer un dossier.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="add_category" class="btn btn-success form-control">
                                    <i class="fa fa-plus"></i> Ajouter la catégorie
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Liste des catégories existantes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Catégories existantes (détectées automatiquement)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="location.reload();">
                        <i class="fa fa-refresh"></i> Actualiser
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($categories)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Nombre d'images</th>
                                    <th>Taille totale</th>
                                    <th>Dossier</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $categoryStats[$category]['count']; ?> images</span>
                                        </td>
                                        <td>
                                            <?php echo round($categoryStats[$category]['size'] / 1024 / 1024, 2); ?> MB
                                        </td>
                                        <td>
                                            <code><?php echo $categoryStats[$category]['path']; ?></code>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" class="btn btn-info btn-sm" title="Ajouter des images à cette catégorie">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="manage_category.php?category=<?php echo urlencode($category); ?>" class="btn btn-primary btn-sm" title="Gérer cette catégorie (renommer, modifier)">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <?php if ($categoryStats[$category]['count'] == 0): ?>
                                                    <a href="?delete_category=<?php echo urlencode($category); ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie vide?');" 
                                                       title="Supprimer la catégorie">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                                                       class="btn btn-warning btn-sm" 
                                                       title="Supprimer la catégorie et toutes ses images">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <h5><i class="fa fa-exclamation-triangle"></i> Aucune catégorie trouvée</h5>
                        <p>Aucun dossier de catégorie n'a été détecté dans le répertoire portfolio.</p>
                        <p>Vous pouvez créer une nouvelle catégorie en utilisant le formulaire ci-dessus.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistiques globales -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Total catégories</h5>
                                <h2><?php echo count($categories); ?></h2>
                            </div>
                            <i class="fa fa-folder fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Total images</h5>
                                <h2><?php echo !empty($categories) ? array_sum(array_column($categoryStats, 'count')) : 0; ?></h2>
                            </div>
                            <i class="fa fa-image fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Espace utilisé</h5>
                                <h2><?php echo !empty($categories) ? round(array_sum(array_column($categoryStats, 'size')) / 1024 / 1024, 1) : 0; ?> MB</h2>
                            </div>
                            <i class="fa fa-hdd-o fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "admin_footer.php"; ?>