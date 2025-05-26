<?php
include "connect/connect.php";

// Récupération des projets mis en avant pour le hero slider (featured = 1)
$query = "SELECT * FROM projects WHERE featured = 1 ORDER BY id ASC";
$stmt = $pdo->query($query);
$featuredProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - BYM</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Ajoutez ici vos autres CSS -->
</head>
<body>
    <?php include "header.php"; ?>
    
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
        
        <!-- Hero slider area dynamique -->
        <div class="hero-slider">
            <?php foreach ($featuredProjects as $project): ?>
                <?php
                    // Définir le dossier du projet en se basant sur le slug
                    $projectFolder = "img/projects/" . $project['slug'] . "/";
                    // Récupérer toutes les images du dossier (formats jpg, jpeg, png, gif)
                    $imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                    // Utiliser la première image trouvée, ou une image par défaut
                    $imagePath = !empty($imagesFromFolder) ? $imagesFromFolder[0] : 'img/default.jpg';
                ?>
                <div class="hero-slide-item set-bg" data-setbg="<?php echo $imagePath; ?>">
                    <div class="slide-inner">
                        <div class="slide-content">
                            <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                            <a href="project.php?project=<?php echo urlencode($project['slug']); ?>" class="site-btn sb-light">Voir Projet</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="slide-num-holder" id="snh-1"></div>
        <div class="hero-right-text">architecture</div>
    </section>
    <!-- Hero section end -->

    <!-- Intro section start -->
    <section class="intro-section pt100 pb50">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 intro-text mb-5 mb-lg-0">
                    <h2 class="sp-title">Nous sommes une agence <span>d'architecture </span>  </h2>
                    <p>  Spécialisée dans le mariage entre la modernité et l'efficacité grâce à notre expertise pointue en BIM (Building Information Modeling). <br>
                         Chez BYM, nous croyons fermement en l'importance de l'intégration des dernières technologies pour offrir à nos clients une expérience architecturale incomparable. Le BIM nous permet non seulement de visualiser chaque détail de nos projets en trois dimensions, mais également de garantir une gestion de projet transparente et une coordination précise du début à la fin. </p>
                    <a href="#" class="site-btn sb-dark">See Project</a>
                </div>
                <div class="col-lg-5 pt-4">
                    <img src="img/intro.jpg" alt="">
                </div>
            </div>
        </div>
    </section>
    <!-- Intro section end -->

    <!-- Service section start -->
    <?php include "services.php";?>
    <!-- Service section end -->

    <!-- CTA section start -->
    <section class="cta-section pt100 pb50">
        <div class="cta-image-box"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-7 pl-lg-0 offset-lg-5 cta-content">
                    <h2 class="sp-title">Savoir-faire / <br> Savoir <span> bâtir</span></h2>
                    <p>Notre agence dispose d'une expérience éprouvée dans les secteurs de l'habitat, de l'hôtellerie, des tours emblématiques, des projets bureautiques innovants et des centres commerciaux dynamiques, nous permettant ainsi de répondre efficacement à une diversité de besoins architecturaux avec excellence et savoir-faire. </p>
                    <div class="cta-icons">
                        <div class="cta-img-icon">
                            <img src="img/icon/light/1.png" alt="">
                        </div>
                        <div class="cta-img-icon">
                            <img src="img/icon/light/2.png" alt="">
                        </div>
                        <div class="cta-img-icon">
                            <img src="img/icon/color/3.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- CTA section end -->

    <!-- Milestones section Start -->
    <section class="milestones-section spad">
        <div class="container">
            <div class="row">
                <div class="col">
                    <div class="milestone">
                        <h2>7</h2>
                        <p>Ans <br>D'experience</p>
                    </div>
                </div>
                <div class="col">
                    <div class="milestone">
                        <h2>48</h2>
                        <p>Projects <br>Pris</p>
                    </div>
                </div>
                <div class="col">
                    <div class="milestone">
                        <h2>1k</h2>
                        <p>Instagram <br>Followers</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Milestones section end -->

    <!-- Projects section start -->
    <?php include "projects.php"; ?>
    <!-- Projects section end -->

    <!-- Clients section start -->
    <div class="client-section spad">
        <div class="container">
            <div id="client-carousel" class="client-slider">
                <div class="single-brand">
                    <a href="#">
                        <img src="img/clients/1.jpeg" alt="">
                    </a>
                </div>
                <div class="single-brand">
                    <a href="#">
                        <img src="img/clients/2.jpeg" alt="">
                    </a>
                </div>
                <div class="single-brand">
                    <a href="#">
                        <img src="img/clients/3.jpeg" alt="">
                    </a>
                </div>
                <div class="single-brand">
                    <a href="#">
                        <img src="img/clients/1.jpeg" alt="">
                    </a>
                </div>
                <div class="single-brand">
                    <a href="#">
                        <img src="img/clients/2.jpeg" alt="">
                    </a>
                </div>
                <div class="single-brand">
                    <a href="#">
                        <img src="img/clients/3.jpeg" alt="">
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Clients section end -->

    <?php include "footer.php"; ?>
</body>
</html>
