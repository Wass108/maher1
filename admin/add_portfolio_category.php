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
            if ($item !== '.' && $item !== '..' && is_dir($portfolioMainDir . $item)) {
                $categories[] = $item;
            }
        }
        sort($categories);
    }
    
    return $categories;
}

$portfolioMainDir = "../img/portfolio/";

// Récupérer la catégorie depuis l'URL ou la redirection
$selectedCategory = $_GET['category'] ?? '';
$categories = getExistingCategories($portfolioMainDir);

// Si aucune catégorie n'est spécifiée ou si elle n'existe pas, rediriger vers portfolio.php
if (empty($selectedCategory) || !in_array($selectedCategory, $categories)) {
    $_SESSION['error'] = "Catégorie non spécifiée ou invalide.";
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$categoryDir = $portfolioMainDir . $category . "/";

// Messages de feedback
$message = '';
$error = '';

// Traitement du téléchargement d'images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
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
    
    // Rediriger vers la page portfolio principale
    header("Location: portfolio.php");
    exit;
}

// Récupérer les images existantes de cette catégorie
$existingImages = [];
if (is_dir($categoryDir)) {
    $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
    foreach ($images as $image) {
        $existingImages[] = [
            'path' => $image,
            'name' => basename($image),
            'size' => filesize($image),
            'url' => $image
        ];
    }
}

ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Ajouter des images - <?php echo htmlspecialchars($category); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="portfolio.php">Portfolio</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($category); ?></li>
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
        
        <!-- Formulaire d'ajout d'images -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title">
                    <i class="fa fa-upload"></i> Télécharger des images dans la catégorie "<?php echo htmlspecialchars($category); ?>"
                </h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label for="images">Sélectionner des images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                        <small class="form-text text-muted">
                            Vous pouvez sélectionner plusieurs images à la fois. Formats acceptés : JPG, JPEG, PNG, GIF
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-upload"></i> Télécharger les images
                        </button>
                        <a href="portfolio.php" class="btn btn-secondary btn-lg">
                            <i class="fa fa-arrow-left"></i> Retour au portfolio
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Aperçu des images existantes dans cette catégorie -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-images"></i> Images existantes dans "<?php echo htmlspecialchars($category); ?>"
                    <span class="badge bg-info"><?php echo count($existingImages); ?> image(s)</span>
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($existingImages)): ?>
                    <div class="row">
                        <?php foreach ($existingImages as $image): ?>
                            <div class="col-md-2 col-sm-4 col-6 mb-3">
                                <div class="card">
                                    <img src="<?php echo $image['url']; ?>" class="card-img-top" alt="Image" style="height: 120px; object-fit: cover;">
                                    <div class="card-body p-2 text-center">
                                        <h6 class="card-title small"><?php echo substr($image['name'], 0, 12) . (strlen($image['name']) > 12 ? '...' : ''); ?></h6>
                                        <p class="card-text small text-muted"><?php echo round($image['size'] / 1024, 1); ?> KB</p>
                                        <a href="<?php echo $image['url']; ?>" target="_blank" class="btn btn-info btn-sm" title="Voir en grand">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <i class="fa fa-image fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucune image dans cette catégorie pour le moment.</p>
                        <p class="text-muted">Utilisez le formulaire ci-dessus pour ajouter vos premières images.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informations sur la catégorie -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5>Catégorie</h5>
                        <h3><?php echo htmlspecialchars($category); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>Images</h5>
                        <h3><?php echo count($existingImages); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5>Espace utilisé</h5>
                        <h3><?php echo round(array_sum(array_column($existingImages, 'size')) / 1024 / 1024, 1); ?> MB</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include "admin_footer.php"; ?>