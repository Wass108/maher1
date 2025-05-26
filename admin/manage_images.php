<?php
// Inclure les fichiers nécessaires (en utilisant la version buffered du header)
include "../connect/connect.php";
include "admin_header_buffered.php";  // On utilise la version avec buffer

// Vérifier si l'ID du projet est spécifié
if (!isset($_GET['project'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['project'];

// Récupérer les informations du projet
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

// Vérifier si le projet existe
if (!$project) {
    header("Location: projects.php");
    exit;
}

$message = '';
$error = '';

// Traitement du téléchargement d'une image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $projectDir = "../img/projects/" . $project['slug'] . "/";
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($projectDir)) {
        mkdir($projectDir, 0777, true);
    }
    
    // Récupérer les images existantes pour déterminer le prochain numéro
    $existingImages = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    $nextNumber = count($existingImages) + 1;
    
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
                $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                $destination = $projectDir . $newFileName;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploadCount++;
                    $nextNumber++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }
    }
    
    if ($uploadCount > 0) {
        $message = "$uploadCount image(s) téléchargée(s) avec succès.";
    }
    if ($errorCount > 0) {
        $error = "$errorCount image(s) n'ont pas pu être téléchargées.";
    }
}

// Récupérer les images du projet
$projectDir = "../img/projects/" . $project['slug'] . "/";
$images = [];
if (is_dir($projectDir)) {
    $images = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
}

// À ce stade, toutes les redirections potentielles ont été traitées
// On peut donc vider le buffer pour afficher la page
ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestion des images - <?php echo htmlspecialchars($project['title']); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="projects.php">Projets</a></li>
                    <li class="breadcrumb-item active">Gestion des images</li>
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
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ajouter des images</h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label for="images">Sélectionner des images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                        <small class="form-text text-muted">Vous pouvez sélectionner plusieurs images à la fois.</small>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Télécharger</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Images actuelles</h3>
                <div class="card-tools">
                    <a href="edit_project.php?id=<?php echo $projectId; ?>" class="btn btn-info">
                        <i class="fa fa-pencil"></i> Modifier le projet
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($images)): ?>
                    <p>Note: L'image affichée en premier sera utilisée comme image principale du projet dans les listes.</p>
                    <div class="row">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card">
                                    <img src="<?php echo $image; ?>" class="card-img-top" alt="Image">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo basename($image); ?></h5>
                                        <p class="card-text">
                                            <?php if ($index === 0): ?>
                                                <span class="badge bg-success">Image principale</span>
                                            <?php endif; ?>
                                        </p>
                                        <a href="delete_image.php?project=<?php echo $projectId; ?>&image=<?php echo urlencode(basename($image)); ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');">
                                            <i class="fa fa-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">Aucune image n'est disponible pour ce projet.</p>
                    <p class="text-center">Utilisez le formulaire ci-dessus pour ajouter des images.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include "admin_footer.php"; ?>