<?php
include "../connect/connect.php";
include "auth_check.php";

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
    $_SESSION['message'] = "Projet introuvable.";
    header("Location: projects.php");
    exit;
}

// Vérifier si la suppression est confirmée
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $projectDir = "../img/projects/" . $project['slug'] . "/";
    
    // Compter le nombre d'images supprimées
    $deletedCount = 0;
    
    // Supprimer toutes les images dans le dossier
    if (is_dir($projectDir)) {
        $images = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        
        foreach ($images as $image) {
            if (is_file($image)) {
                if (unlink($image)) {
                    $deletedCount++;
                }
            }
        }
    }
    
    if ($deletedCount > 0) {
        $_SESSION['message'] = "$deletedCount image(s) ont été supprimées avec succès.";
    } else {
        $_SESSION['message'] = "Aucune image n'a été trouvée ou supprimée.";
    }
    
    // Rediriger vers la page de gestion des images
    header("Location: manage_images.php?project=" . $projectId);
    exit;
} else {
    // Afficher la page de confirmation
    include "admin_header_buffered.php";
    
    // Récupérer le nombre d'images
    $projectDir = "../img/projects/" . $project['slug'] . "/";
    $imageCount = 0;
    
    if (is_dir($projectDir)) {
        $images = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        $imageCount = count($images);
    }
    
    ob_end_flush();
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Supprimer toutes les images</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="projects.php">Projets</a></li>
                    <li class="breadcrumb-item"><a href="manage_images.php?project=<?php echo $projectId; ?>">Gestion des images</a></li>
                    <li class="breadcrumb-item active">Supprimer toutes les images</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title">Confirmation de suppression</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h5><i class="fa fa-exclamation-triangle"></i> Attention !</h5>
                    <p>Vous êtes sur le point de supprimer <strong>toutes les images</strong> (<?php echo $imageCount; ?> image(s)) du projet "<strong><?php echo htmlspecialchars($project['title']); ?></strong>".</p>
                    <p>Cette action est <strong>irréversible</strong>. Êtes-vous sûr de vouloir continuer ?</p>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    <a href="delete_all_images.php?project=<?php echo $projectId; ?>&confirm=yes" class="btn btn-danger mx-2">
                        <i class="fa fa-trash"></i> Oui, supprimer toutes les images
                    </a>
                    <a href="manage_images.php?project=<?php echo $projectId; ?>" class="btn btn-secondary mx-2">
                        <i class="fa fa-times"></i> Non, annuler
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    include "admin_footer.php";
}
?>