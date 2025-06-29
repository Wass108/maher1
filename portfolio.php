<?php include "header.php"; ?> 

<?php
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

// Définir les dossiers principaux du portfolio
$portfolioImgDir = "img/portfolio/";
$portfolioVideoDir = "videos/portfolio/";

// Récupérer dynamiquement toutes les catégories existantes (depuis les images)
$categories = getExistingCategories($portfolioImgDir);

// Tableaux pour stocker les images et vidéos par catégorie
$portfolioItems = [];

// Récupérer toutes les images et vidéos de chaque catégorie
foreach ($categories as $category) {
    // Récupérer les images
    $categoryImgPath = $portfolioImgDir . $category . "/";
    if (is_dir($categoryImgPath)) {
        $images = glob($categoryImgPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        foreach ($images as $image) {
            $portfolioItems[] = [
                'path' => str_replace(' ', '%20', $image),
                'type' => 'image',
                'category' => strtolower(str_replace(' ', '', $category)),
                'categoryName' => $category
            ];
        }
    }
    
    // Récupérer les vidéos
    $categoryVideoPath = $portfolioVideoDir . $category . "/";
    if (is_dir($categoryVideoPath)) {
        $videos = glob($categoryVideoPath . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
        foreach ($videos as $video) {
            $portfolioItems[] = [
                'path' => str_replace(' ', '%20', $video),
                'type' => 'video',
                'category' => strtolower(str_replace(' ', '', $category)),
                'categoryName' => $category
            ];
        }
    }
}
?>

	<!-- Page header section start -->
	<section class="page-header-section set-bg" data-setbg="img/portfolio-bg.jpg">
		<div class="container">
			<h1 class="header-title">Portfolio<span>.</span></h1>
		</div>
	</section>
	<!-- Page header section end -->


	<!-- Page section start -->
	<div class="page-section spad">
		<div class="container">
			<!-- portfolio filter menu -->
			<ul class="portfolio-filter">
				<li class="filter" data-filter="*">Toutes</li>
				<?php foreach ($categories as $category): 
					$filterName = strtolower(str_replace(' ', '', $category));
				?>
					<li class="filter" data-filter=".<?php echo $filterName; ?>"><?php echo htmlspecialchars($category); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	
		<!-- portfolio items -->
		<div class="portfolio-warp spad">
			<div id="portfolio">
				<div class="grid-sizer"></div>
                
                <?php if (!empty($portfolioItems)): ?>
                    <?php foreach ($portfolioItems as $index => $item): 
                        // Déterminer la classe de filtrage selon la catégorie
                        $filterClass = $item['category']; // Utiliser le nom nettoyé de la catégorie comme classe
                        
                        // Ajouter classes supplémentaires pour certaines images (tous les 5 items)
                        $specialClass = "";
                        if ($index % 5 == 0) $specialClass = "grid-wide";
                        else if ($index % 7 == 0) $specialClass = "grid-long";
                    ?>
                    
                    <?php if ($item['type'] === 'image'): ?>
                        <!-- Affichage pour les images -->
                        <div class="grid-item set-bg <?php echo $specialClass . ' ' . $filterClass; ?>" data-setbg="<?php echo $item['path']; ?>">
                            <a class="img-popup" href="<?php echo $item['path']; ?>"></a>
                        </div>
                    <?php else: ?>
                        <!-- Affichage pour les vidéos -->
                        <div class="grid-item <?php echo $specialClass . ' ' . $filterClass; ?>" style="position: relative; background: #000; display: flex; align-items: center; justify-content: center; min-height: 250px;">
                            <div class="video-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; cursor: pointer;" onclick="openVideoModal('<?php echo $item['path']; ?>')">
                                <div style="text-align: center; color: white;">
                                    <i class="fa fa-play-circle" style="font-size: 48px; margin-bottom: 10px;"></i>
                                    <p style="margin: 0; font-size: 14px;">Cliquez pour voir la vidéo</p>
                                </div>
                            </div>
                            <!-- Thumbnail de la vidéo (optionnel) -->
                            <video style="width: 100%; height: 100%; object-fit: cover;" preload="metadata">
                                <source src="<?php echo $item['path']; ?>" type="video/<?php echo pathinfo($item['path'], PATHINFO_EXTENSION); ?>">
                            </video>
                        </div>
                    <?php endif; ?>
                    
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Aucun contenu de portfolio disponible pour le moment.</p>
                    </div>
                <?php endif; ?>
			</div>
		</div>
		<div class="container">
			<div class="pagination">
				<a href="#" class="active">01</a>
			</div>
		</div>
	</div>
	<!-- Page section end -->

	<!-- Modal pour les vidéos -->
	<div id="videoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center;">
		<div style="position: relative; max-width: 90%; max-height: 90%;">
			<button onclick="closeVideoModal()" style="position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">&times;</button>
			<video id="modalVideo" controls style="width: 100%; height: auto; max-height: 80vh;">
				<source src="" type="video/mp4">
				Votre navigateur ne supporte pas la lecture de vidéos.
			</video>
		</div>
	</div>

	<script>
		function openVideoModal(videoPath) {
			const modal = document.getElementById('videoModal');
			const video = document.getElementById('modalVideo');
			const source = video.querySelector('source');
			
			source.src = videoPath;
			const extension = videoPath.split('.').pop().toLowerCase();
			source.type = 'video/' + extension;
			
			video.load();
			modal.style.display = 'flex';
			
			// Empêcher le scroll du body
			document.body.style.overflow = 'hidden';
		}

		function closeVideoModal() {
			const modal = document.getElementById('videoModal');
			const video = document.getElementById('modalVideo');
			
			video.pause();
			video.currentTime = 0;
			modal.style.display = 'none';
			
			// Réactiver le scroll du body
			document.body.style.overflow = 'auto';
		}

		// Fermer la modal en cliquant à l'extérieur
		document.getElementById('videoModal').addEventListener('click', function(e) {
			if (e.target === this) {
				closeVideoModal();
			}
		});

		// Fermer la modal avec la touche Escape
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				closeVideoModal();
			}
		});
	</script>

<?php include "footer.php"; ?>
