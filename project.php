<?php 
include "header.php";
include "connect/connect.php"; // Ce fichier instancie $pdo

// Récupération du projet via l'URL (slug), par défaut "dakar"
$projectSlug = isset($_GET['project']) ? $_GET['project'] : 'dakar';

// Récupération des détails du projet depuis la base
$query = "SELECT * FROM projects WHERE slug = :slug";
$stmt = $pdo->prepare($query);
$stmt->execute(['slug' => $projectSlug]);
$projectDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$projectDetails) {
    echo "<p>Projet non trouvé.</p>";
    include "footer.php";
    exit;
}

// Définir le dossier du projet en se basant sur le slug
$projectFolder = "img/projects/" . $projectSlug . "/";

// Récupérer toutes les images du dossier (formats jpg, jpeg, png, gif)
$imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
$images = array_map(function($path) {
    // Remplacer les espaces par %20 dans les chemins d'images pour l'URL
    return ['image_path' => str_replace(' ', '%20', $path)];
}, $imagesFromFolder);
?>

<link rel="stylesheet" href="css/single.css">

<!-- Hero section start -->
<section class="hero-section">
    <!-- Left social link bar -->
    <div class="left-bar">
        <div class="left-bar-content">
            <div class="social-links">
                <a href="#"><i class="fa fa-pinterest"></i></a>
                <a href="#"><i class="fa fa-linkedin"></i></a>
                <a href="#"><i class="fa fa-instagram"></i></a>
                <a href="#"><i class="fa fa-facebook"></i></a>
                <a href="#"><i class="fa fa-twitter"></i></a>
            </div>
        </div>
    </div>
    <!-- Hero slider area -->
    <div class="hero-slider">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $index => $img): ?>
                <div class="hero-slide-item set-bg" data-setbg="<?php echo $img['image_path']; ?>">
                    <div class="slide-inner">
                        <?php if ($index === 0): ?>
                            <div class="slide-content">
                                <h2><?php echo htmlspecialchars($projectDetails['title']); ?></h2><br>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucune image disponible.</p>
        <?php endif; ?>
    </div>
    <div class="slide-num-holder" id="snh-1"></div>
    <div class="hero-right-text">architecture</div>
</section>
<!-- Hero section end -->

<!-- Single section start -->
<div class="section sec-3">
    <div class="container">
        <div class="row mb-5 justify-content-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
                <?php if (!empty($images)): ?>
                    <img src="<?php echo $images[0]['image_path']; ?>" alt="Image">
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <div class="heading">Description</div>
                <p><?php echo htmlspecialchars($projectDetails['description']); ?></p>
                <?php if (!empty($projectDetails['website'])): ?>
                    <p><a href="<?php echo $projectDetails['website']; ?>" class="btn btn-primary"><?php echo htmlspecialchars($projectDetails['btnText']); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <?php if (!empty($projectDetails['year'])): ?>
                <div class="col-sm-3 border-left">
                    <span class="text-black-50 d-block">Année :</span> <?php echo htmlspecialchars($projectDetails['year']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($projectDetails['client'])): ?>
                <div class="col-sm-3 border-left">
                    <span class="text-black-50 d-block">Client :</span> <?php echo htmlspecialchars($projectDetails['client']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Single section end -->

<?php include "projects.php"; ?>
<?php include "footer.php"; ?>