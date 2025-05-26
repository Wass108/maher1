<?php include "header.php"; ?> 

<?php
// Définir le dossier principal du portfolio - correction du chemin
$portfolioMainDir = "img/portfolio/";

// Définir les catégories (dossiers) disponibles
$categories = array('Photographie', 'Design', 'Chantier');

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
                'category' => strtolower($category)
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
				<li class="filter" data-filter="*">All</li>
				<li class="filter" data-filter=".photographie">Photographie</li>
				<li class="filter" data-filter=".design">Design</li>
				<li class="filter" data-filter=".chantier">Chantier</li>
			</ul>
		</div>
	
		<!-- portfolio items -->
		<div class="portfolio-warp spad">
			<div id="portfolio">
				<div class="grid-sizer"></div>
                
                <?php foreach ($portfolioImages as $index => $image): 
                    // Déterminer la classe de filtrage selon la catégorie
                    $filterClass = $image['category']; // Utiliser directement le nom de la catégorie comme classe
                    
                    // Ajouter classes supplémentaires pour certaines images (tous les 5 items)
                    $specialClass = "";
                    if ($index % 5 == 0) $specialClass = "grid-wide";
                    else if ($index % 7 == 0) $specialClass = "grid-long";
                ?>
                <div class="grid-item set-bg <?php echo $specialClass . ' ' . $filterClass; ?>" data-setbg="<?php echo $image['path']; ?>">
                    <a class="img-popup" href="<?php echo $image['path']; ?>"></a>
                </div>
                <?php endforeach; ?>
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
