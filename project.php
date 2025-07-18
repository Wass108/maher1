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
$videoFolder = "videos/projects/" . $projectSlug . "/";

// Récupérer toutes les images du dossier (formats jpg, jpeg, png, gif)
$imagesFromFolder = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

// Récupérer toutes les vidéos du dossier
$videosFromFolder = glob($videoFolder . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);

// Trier les images par nom de fichier pour maintenir l'ordre numérique (même logique que dans l'admin)
usort($imagesFromFolder, function($a, $b) {
    return strnatcmp(basename($a), basename($b));
});

// Trier les vidéos par nom de fichier
usort($videosFromFolder, function($a, $b) {
    return strnatcmp(basename($a), basename($b));
});

$images = array_map(function($path) {
    // Remplacer les espaces par %20 dans les chemins d'images pour l'URL
    return ['image_path' => str_replace(' ', '%20', $path)];
}, $imagesFromFolder);

$videos = array_map(function($path) {
    // Remplacer les espaces par %20 dans les chemins de vidéos pour l'URL
    return ['video_path' => str_replace(' ', '%20', $path)];
}, $videosFromFolder);

// Compter le nombre d'images pour adapter le slider
$imageCount = count($images);
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
    <?php if ($imageCount === 1): ?>
        <!-- Affichage statique pour une seule image -->
        <div class="hero-single-image">
            <div class="hero-slide-item set-bg" data-setbg="<?php echo $images[0]['image_path']; ?>">
                <div class="slide-inner">
                    <div class="slide-content">
                        <h2><?php echo htmlspecialchars($projectDetails['title']); ?></h2><br>
                    </div>
                </div>
            </div>
        </div>
        <div class="slide-num-holder">
            <span>01</span> / 01
        </div>
    <?php elseif ($imageCount > 1): ?>
        <!-- Slider Owl Carousel pour plusieurs images -->
        <div class="hero-slider">
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
        </div>
        <div class="slide-num-holder" id="snh-1"></div>
    <?php else: ?>
        <!-- Aucune image disponible -->
        <div class="hero-no-image">
            <div class="slide-inner">
                <div class="slide-content">
                    <h2><?php echo htmlspecialchars($projectDetails['title']); ?></h2>
                    <p>Aucune image disponible pour ce projet.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
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
        
        <!-- Section Vidéos -->
        <?php if (!empty($videos)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="heading mb-4">Vidéos du projet</div>
                <div class="project-videos-gallery">
                    <?php foreach ($videos as $index => $video): ?>
                        <div class="video-item" data-aos="fade-up" data-aos-delay="<?php echo ($index * 100); ?>">
                            <div class="video-card">
                                <div class="video-thumbnail-container">
                                    <video class="video-thumbnail" preload="metadata" muted>
                                        <source src="<?php echo $video['video_path']; ?>" type="video/<?php echo pathinfo($video['video_path'], PATHINFO_EXTENSION); ?>">
                                    </video>
                                    <div class="video-overlay">
                                        <div class="play-button" onclick="openVideoModal('<?php echo $video['video_path']; ?>', '<?php echo basename($video['video_path']); ?>')">
                                            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="30" cy="30" r="30" fill="rgba(255,255,255,0.9)"/>
                                                <path d="M24 18L42 30L24 42V18Z" fill="#000"/>
                                            </svg>
                                        </div>
                                        <div class="video-duration"></div>
                                    </div>
                                </div>
                                <div class="video-info">
                                    <h6 class="video-title">Vidéo <?php echo ($index + 1); ?></h6>
                                    <p class="video-filename"><?php echo basename($video['video_path']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Modal Vidéo -->
        <div id="videoModal" class="video-modal">
            <div class="video-modal-content">
                <div class="video-modal-header">
                    <h4 id="modalVideoTitle">Vidéo</h4>
                    <button class="video-modal-close" onclick="closeVideoModal()">&times;</button>
                </div>
                <div class="video-modal-body">
                    <video id="modalVideo" controls>
                        <source src="" type="video/mp4">
                        Votre navigateur ne supporte pas la lecture de vidéos.
                    </video>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
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

<script>
    function openVideoModal(videoPath, videoTitle) {
        var modal = document.getElementById("videoModal");
        var modalVideo = document.getElementById("modalVideo");
        var modalVideoTitle = document.getElementById("modalVideoTitle");
        
        modalVideo.src = videoPath;
        modalVideoTitle.innerHTML = videoTitle;
        
        modal.style.display = "block";
    }

    function closeVideoModal() {
        var modal = document.getElementById("videoModal");
        var modalVideo = document.getElementById("modalVideo");
        
        modalVideo.pause();
        modalVideo.src = "";
        
        modal.style.display = "none";
    }

    // Initialisation du slider seulement s'il y a plus d'une image
    $(document).ready(function() {
        var imageCount = <?php echo $imageCount; ?>;
        
        if (imageCount > 1) {
            // Configuration du slider pour plusieurs images
            var hero_s = $(".hero-slider");
            hero_s.owlCarousel({
                loop: true,
                margin: 0,
                nav: true,
                items: 1,
                dots: false,
                animateOut: 'fadeOutRight',
                animateIn: 'fadeInLeft',
                navText: ['<i class="fa fa-long-arrow-left"></i> PREV', 'NEXT<i class="fa fa-long-arrow-right"></i>'],
                smartSpeed: 1200,
                autoHeight: false,
                autoplay: true,
                mouseDrag: false,
                onInitialized: function() {
                    var a = this.items().length;
                    if(a < 10){
                        $("#snh-1").html("<span>01" + " / </span>0" + a);
                    } else{
                        $("#snh-1").html("<span>01" + " / </span>" + a);
                    }
                }
            }).on("changed.owl.carousel", function(a) {
                var b = --a.item.index, a = a.item.count;
                if(a < 10){
                    $("#snh-1").html("<span>0" + ( 1 > b ? b + a : b > a ? b - a : b) + " / </span>0" + a);
                } else{
                    $("#snh-1").html("<span> "+ (1 > b ? b + a : b > a ? b - a : b) + " / </span>" + a);
                }
            });
        }
    });
</script>