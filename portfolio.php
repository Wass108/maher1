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
        
        // Trier les images par ordre numérique naturel pour respecter l'ordre de l'administration
        usort($images, function($a, $b) {
            return strnatcmp(basename($a), basename($b));
        });
        
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
        
        // Trier les vidéos par ordre numérique naturel aussi
        usort($videos, function($a, $b) {
            return strnatcmp(basename($a), basename($b));
        });
        
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
                        <!-- Affichage pour les vidéos avec design cohérent au site -->
                        <div class="grid-item video-item <?php echo $specialClass . ' ' . $filterClass; ?>" style="position: relative; background: #f5f5f5; min-height: 250px; overflow: hidden;">
                            <!-- Thumbnail de la vidéo -->
                            <video style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0;" preload="metadata" muted>
                                <source src="<?php echo $item['path']; ?>#t=1" type="video/<?php echo pathinfo($item['path'], PATHINFO_EXTENSION); ?>">
                            </video>
                            
                            <!-- Overlay avec le style du site -->
                            <div class="video-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(34, 34, 34, 0.7); display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.4s ease;" onclick="openVideoModal('<?php echo $item['path']; ?>')">
                                <div style="text-align: center; color: white;">
                                    <i class="fa fa-play-circle" style="font-size: 48px; color: #baff00; margin-bottom: 15px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"></i>
                                    <p style="margin: 0; font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">Voir la vidéo</p>
                                </div>
                            </div>
                            
                            <!-- Badge vidéo dans le style du site -->
                            <div class="video-badge" style="position: absolute; top: 15px; left: 15px; background: #baff00; color: #222; padding: 4px 10px; font-size: 12px; font-weight: 700; text-transform: uppercase; z-index: 2;">
                                <i class="fa fa-video-camera" style="margin-right: 5px;"></i>
                                Vidéo
                            </div>
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
			
			// Décoder l'URL si elle contient des caractères encodés
			const decodedPath = decodeURIComponent(videoPath);
			
			source.src = decodedPath;
			const extension = decodedPath.split('.').pop().toLowerCase();
			
			// Déterminer le bon type MIME selon l'extension
			let mimeType = 'video/mp4'; // Par défaut
			switch(extension) {
				case 'mp4':
					mimeType = 'video/mp4';
					break;
				case 'webm':
					mimeType = 'video/webm';
					break;
				case 'mov':
					mimeType = 'video/quicktime';
					break;
				case 'avi':
					mimeType = 'video/x-msvideo';
					break;
				case 'wmv':
					mimeType = 'video/x-ms-wmv';
					break;
				case 'flv':
					mimeType = 'video/x-flv';
					break;
				case 'mkv':
					mimeType = 'video/x-matroska';
					break;
				default:
					mimeType = 'video/' + extension;
			}
			
			source.type = mimeType;
			
			// Recharger la vidéo et afficher la modal
			video.load();
			modal.style.display = 'flex';
			
			// Empêcher le scroll du body
			document.body.style.overflow = 'hidden';
			
			// Optionnel : démarrer la lecture automatiquement
			video.addEventListener('loadeddata', function() {
				video.play().catch(e => {
					console.log('Autoplay bloqué par le navigateur:', e);
				});
			}, { once: true });
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

		// Vérifier si les vidéos peuvent être lues au chargement de la page
		document.addEventListener('DOMContentLoaded', function() {
			const videoElements = document.querySelectorAll('video');
			videoElements.forEach(function(video) {
				video.addEventListener('error', function(e) {
					console.error('Erreur de chargement vidéo:', e);
					// Optionnel : remplacer par une image d'erreur
					const parent = video.closest('.grid-item');
					if (parent) {
						parent.style.background = '#333';
						const overlay = parent.querySelector('.video-overlay div');
						if (overlay) {
							overlay.innerHTML = '<i class="fa fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 10px;"></i><p style="margin: 0; font-size: 14px;">Vidéo non disponible</p>';
						}
					}
				});
			});
		});
	</script>

	<style>
		/* Styles pour les vidéos dans le style du site */
		.video-item:hover .video-overlay {
			opacity: 1 !important;
		}

		.video-item:hover .video-badge {
			background: #222222 !important;
			color: #baff00 !important;
		}

		.video-item .video-overlay i:hover {
			transform: scale(1.1);
			transition: transform 0.4s ease;
		}

		/* Style pour la modal cohérent avec le site */
		#videoModal {
			backdrop-filter: none;
		}

		#videoModal video {
			border-radius: 0;
			box-shadow: none;
		}

		#videoModal button {
			background: #baff00 !important;
			color: #222 !important;
			border: none !important;
			font-weight: 700;
			width: 40px;
			height: 40px;
			border-radius: 0;
		}

		#videoModal button:hover {
			background: #222 !important;
			color: #baff00 !important;
		}
	</style>

<?php include "footer.php"; ?>
