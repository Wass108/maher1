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

// Traitement du téléchargement d'images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $category = $_POST['category'] ?? '';
    
    if (!in_array($category, $categories)) {
        $_SESSION['error'] = "Catégorie invalide.";
    } else {
        $categoryDir = $portfolioMainDir . $category . "/";
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($categoryDir)) {
            mkdir($categoryDir, 0777, true);
        }
        
        $uploadCount = 0;
        $errorCount = 0;
        
        // Traiter chaque fichier
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['images']['name'][$key];
                $fileType = $_FILES['images']['type'][$key];
                
                // Vérifier si c'est une image
                if (strpos($fileType, 'image/') === 0) {
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    
                    // Générer un nom unique basé sur le timestamp
                    $newFileName = time() . '_' . $key . '.' . $extension;
                    $destination = $categoryDir . $newFileName;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $uploadCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
        }
        
        // Messages de feedback stockés en session
        if ($uploadCount > 0) {
            $_SESSION['message'] = "$uploadCount image(s) téléchargée(s) avec succès dans la catégorie $category.";
        }
        if ($errorCount > 0) {
            $_SESSION['error'] = "$errorCount image(s) n'ont pas pu être téléchargées.";
        }
    }
    
    // Rediriger pour éviter la resoumission du formulaire
    header("Location: portfolio.php");
    exit;
}

// Traitement de la suppression d'une image
if (isset($_GET['delete']) && isset($_GET['category'])) {
    $imageToDelete = $_GET['delete'];
    $category = $_GET['category'];
    
    if (in_array($category, $categories)) {
        $imagePath = $portfolioMainDir . $category . "/" . $imageToDelete;
        
        if (file_exists($imagePath) && unlink($imagePath)) {
            $_SESSION['message'] = "Image supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Impossible de supprimer l'image.";
        }
    }
    
    // Rediriger pour éviter la resoumission
    header("Location: portfolio.php");
    exit;
}

// Récupérer toutes les images par catégorie
$portfolioImages = [];
foreach ($categories as $category) {
    $categoryPath = $portfolioMainDir . $category . "/";
    $portfolioImages[$category] = [];
    
    if (is_dir($categoryPath)) {
        $images = glob($categoryPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        foreach ($images as $image) {
            $portfolioImages[$category][] = [
                'path' => $image,
                'name' => basename($image),
                'size' => filesize($image),
                'url' => $image // Garder le chemin relatif complet depuis admin/
            ];
        }
    }
}

ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestion du Portfolio</h1>
                <p class="text-muted">Catégories trouvées : <?php echo count($categories); ?></p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item active">Portfolio</li>
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
        
        <!-- Affichage des messages de session -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <!-- Information sur les catégories trouvées -->
        <?php if (empty($categories)): ?>
            <div class="alert alert-warning">
                <h5><i class="fa fa-exclamation-triangle"></i> Aucune catégorie trouvée</h5>
                <p>Aucun dossier de catégorie n'a été trouvé dans le répertoire portfolio.</p>
                <p>Vous pouvez créer une nouvelle catégorie en utilisant le bouton "Gérer les catégories" ci-dessous.</p>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire d'ajout d'images -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Ajouter des images au portfolio</h3>
                <div class="card-tools">
                    <a href="manage_portfolio_categories.php" class="btn btn-primary btn-sm">
                        <i class="fa fa-cog"></i> Gérer les catégories
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($categories)): ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="category">Catégorie</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Sélectionner une catégorie</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="images">Sélectionner des images</label>
                                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                                    <small class="form-text text-muted">Vous pouvez sélectionner plusieurs images à la fois.</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Télécharger
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-center text-muted">Créez d'abord une catégorie pour pouvoir ajouter des images.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Affichage des images par catégorie -->
        <?php foreach ($categories as $category): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo htmlspecialchars($category); ?> 
                        <span class="badge bg-info"><?php echo count($portfolioImages[$category]); ?> image(s)</span>
                    </h3>
                    <div class="card-tools">
                        <a href="manage_category.php?category=<?php echo urlencode($category); ?>" class="btn btn-primary btn-sm">
                            <i class="fa fa-cog"></i> Gérer la catégorie
                        </a>
                        <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Ajouter des images
                        </a>
                        <button type="button" class="btn btn-warning btn-sm" onclick="emptyCategory('<?php echo htmlspecialchars($category); ?>')">
                            <i class="fa fa-trash"></i> Vider la catégorie
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteAllCategory('<?php echo htmlspecialchars($category); ?>')">
                            <i class="fa fa-times"></i> Supprimer la catégorie
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($portfolioImages[$category])): ?>
                        <div class="row">
                            <?php foreach ($portfolioImages[$category] as $image): ?>
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="card">
                                        <img src="<?php echo htmlspecialchars($image['url']); ?>" class="card-img-top" alt="Image" style="height: 150px; object-fit: cover;">
                                        <div class="card-body p-2 text-center">
                                            <h6 class="card-title small"><?php echo htmlspecialchars(substr($image['name'], 0, 15) . (strlen($image['name']) > 15 ? '...' : '')); ?></h6>
                                            <p class="card-text small text-muted"><?php echo round($image['size'] / 1024, 1); ?> KB</p>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo htmlspecialchars($image['url']); ?>" target="_blank" class="btn btn-info btn-sm" title="Voir">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="?delete=<?php echo urlencode($image['name']); ?>&category=<?php echo urlencode($category); ?>" 
                                                   class="btn btn-danger btn-sm" 
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
                        <p class="text-center text-muted">Aucune image dans cette catégorie.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
function deleteAllCategory(category) {
    if (confirm('Êtes-vous sûr de vouloir supprimer TOUTES les images de la catégorie "' + category + '" ? Cette action est irréversible.')) {
        window.location.href = 'delete_portfolio_category.php?category=' + encodeURIComponent(category);
    }
}

function emptyCategory(category) {
    if (confirm('Êtes-vous sûr de vouloir vider la catégorie "' + category + '" ? Toutes les images seront supprimées, mais la catégorie restera.')) {
        window.location.href = 'empty_portfolio_category.php?category=' + encodeURIComponent(category);
    }
}
</script>

<?php include "admin_footer.php"; ?>