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

// Définir le dossier principal du portfolio
$portfolioMainDir = "img/portfolio/";

// Récupérer dynamiquement toutes les catégories existantes
$categories = getExistingCategories($portfolioMainDir);

// Tableau pour stocker les images par catégorie
$portfolioImages = [];

// Récupérer toutes les images de chaque catégorie
foreach ($categories as $category) {
    $categoryPath = $portfolioMainDir . $category . "/";
    if (is_dir($categoryPath)) {
        $images = glob($categoryPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        foreach ($images as $image) {
            $portfolioImages[] = [
                'path' => str_replace(' ', '%20', $image), // Encoder les espaces pour l'URL
                'category' => strtolower(str_replace(' ', '', $category)), // Nettoyer le nom pour CSS
                'categoryName' => $category // Garder le nom original pour l'affichage
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
                
                <?php if (!empty($portfolioImages)): ?>
                    <?php foreach ($portfolioImages as $index => $image): 
                        // Déterminer la classe de filtrage selon la catégorie
                        $filterClass = $image['category']; // Utiliser le nom nettoyé de la catégorie comme classe
                        
                        // Ajouter classes supplémentaires pour certaines images (tous les 5 items)
                        $specialClass = "";
                        if ($index % 5 == 0) $specialClass = "grid-wide";
                        else if ($index % 7 == 0) $specialClass = "grid-long";
                    ?>
                    <div class="grid-item set-bg <?php echo $specialClass . ' ' . $filterClass; ?>" data-setbg="<?php echo $image['path']; ?>">
                        <a class="img-popup" href="<?php echo $image['path']; ?>"></a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Aucune image de portfolio disponible pour le moment.</p>
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


<?php include "footer.php"; ?>
