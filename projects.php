<?php
include "connect/connect.php";

// Récupération de tous les projets non masqués dans la base
$query = "SELECT * FROM projects WHERE hidden = 0 ORDER BY id ASC";
$stmt = $pdo->query($query);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Projects</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Ajoutez ici vos autres CSS -->
</head>
<body>
    <!-- Projects section start -->
    <div class="projects-section pb50">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="section-title">
                        <h1>Projects</h1>
                    </div>
                </div>
                <div class="col-lg-9">
                    <ul class="projects-filter-nav">
                        <li class="btn-filter" data-filter="*">All</li>
                        <li class="btn-filter" data-filter=".loge">Logements</li>
                        <li class="btn-filter" data-filter=".hotels">Hotels</li>
                        <li class="btn-filter" data-filter=".indus">Industries</li>
                        <li class="btn-filter" data-filter=".bim">BIM</li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="projects-carousel" class="projects-slider">
            <?php foreach ($projects as $project): ?>
                <?php
                    // Définir le dossier du projet en se basant sur le slug
                    $projectFolder = "img/projects/" . $project['slug'] . "/";
                    // Récupérer toutes les images du dossier (formats jpg, jpeg, png, gif)
                    $imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                    
                    // Traiter le chemin d'image s'il existe
                    $imagePath = 'img/default.jpg';
                    if (!empty($imagesFromFolder)) {
                        // Séparer le chemin de base et le nom du fichier
                        $pathInfo = pathinfo($imagesFromFolder[0]);
                        $directory = $pathInfo['dirname'] . '/'; // dossier avec slash final
                        $filename = $pathInfo['basename']; // nom du fichier avec extension
                        
                        // Encoder seulement le nom de fichier
                        $imagePath = $directory . urlencode($filename);
                    }
                    
                    // Récupérer la catégorie et la normaliser (ex: loge, hotels, etc.)
                    $category = !empty($project['category']) ? strtolower(trim($project['category'])) : '';
                ?>
                <div class="single-project set-bg <?php echo htmlspecialchars($project['slug']) . ' ' . htmlspecialchars($category); ?>" data-setbg="<?php echo $imagePath; ?>">
                    <div class="project-content">
                        <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                        <p><?php echo htmlspecialchars($project['year']); ?></p>
                        <a href="project.php?project=<?php echo urlencode($project['slug']); ?>" class="seemore">See Project</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Projects section end -->
</body>
</html>