<?php
// Inclure les fichiers nécessaires
include "../connect/connect.php";
include "admin_header_buffered.php";

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

$portfolioMainDir = "../img/portfolio/";

// Récupérer dynamiquement toutes les catégories existantes
$categories = getExistingCategories($portfolioMainDir);

// Vérifier la catégorie
if (!isset($_GET['category']) || !in_array($_GET['category'], $categories)) {
    $_SESSION['error'] = "Catégorie invalide ou introuvable.";
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$categoryDir = $portfolioMainDir . $category . "/";

// Traitement du vidage de la catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_empty'])) {
    $deletedCount = 0;
    
    if (is_dir($categoryDir)) {
        // Supprimer toutes les images dans le dossier
        $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        
        foreach ($images as $image) {
            if (is_file($image)) {
                if (unlink($image)) {
                    $deletedCount++;
                }
            }
        }
        
        // Supprimer également tous les autres fichiers qui pourraient être présents
        $allFiles = glob($categoryDir . "*");
        foreach ($allFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // GARDER le dossier (ne pas utiliser rmdir())
    }
    
    if ($deletedCount > 0) {
        $_SESSION['message'] = "La catégorie '$category' a été vidée avec succès. $deletedCount image(s) supprimée(s).";
    } else {
        $_SESSION['message'] = "Aucune image trouvée dans la catégorie '$category'.";
    }
    
    // Déterminer la page de redirection basée sur le referer ou un paramètre
    $redirectTo = 'portfolio.php';
    if (isset($_GET['redirect']) && $_GET['redirect'] === 'manage_category') {
        $redirectTo = 'manage_category.php?category=' . urlencode($category);
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, 'manage_category.php') !== false) {
            $redirectTo = 'manage_category.php?category=' . urlencode($category);
        }
    }
    
    // Rediriger vers la page appropriée
    header("Location: $redirectTo");
    exit;
} else {
    // Afficher la page de confirmation
    
    // Récupérer le nombre d'images et les informations détaillées
    $imageCount = 0;
    $totalSize = 0;
    $sampleImages = [];
    
    if (is_dir($categoryDir)) {
        $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        $imageCount = count($images);
        
        // Calculer la taille totale et récupérer quelques images d'exemple
        foreach ($images as $index => $image) {
            $size = filesize($image);
            $totalSize += $size;
            
            // Récupérer les 4 premières images pour l'aperçu
            if ($index < 4) {
                $sampleImages[] = [
                    'name' => basename($image),
                    'url' => $image,
                    'size' => $size
                ];
            }
        }
    }
    
    ob_end_flush();
?>
<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Vider la catégorie</h1>
                <p class="text-gray-600 mt-1">Suppression du contenu en conservant le dossier</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <a href="portfolio.php" class="text-gray-500 hover:text-gray-700">Portfolio</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Vider catégorie</span>
            </nav>
        </div>
    </div>
</div>
<div class="px-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg border border-yellow-200 shadow-sm">
            <div class="p-6 border-b border-yellow-200 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-t-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-broom text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Confirmation de vidage</h3>
                        <p class="text-yellow-100 text-sm mt-1">Le dossier sera conservé</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="p-4 border-l-4 border-yellow-400 bg-yellow-50 rounded-md mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-lg font-semibold text-yellow-800 mb-2">Attention ! Vidage de catégorie</h4>
                            <p class="text-yellow-700 mb-3">
                                Vous êtes sur le point de <strong>VIDER</strong> la catégorie 
                                <span class="font-bold bg-yellow-100 px-2 py-1 rounded"><?php echo htmlspecialchars($category); ?></span>.
                            </p>
                            
                            <div class="bg-yellow-100 rounded-lg p-4 mt-4">
                                <h5 class="font-semibold text-yellow-800 mb-2">Cette action va :</h5>
                                <ul class="list-disc list-inside text-yellow-700 space-y-1">
                                    <li>Supprimer <strong><?php echo $imageCount; ?> image(s)</strong> définitivement</li>
                                    <li>Libérer <strong><?php echo round($totalSize / 1024 / 1024, 2); ?> MB</strong> d'espace disque</li>
                                    <li><strong class="text-green-700">CONSERVER</strong> le dossier de la catégorie (vide)</li>
                                    <li>Permettre d'ajouter de nouvelles images facilement</li>
                                </ul>
                                <div class="mt-3 p-2 bg-green-100 rounded border border-green-200">
                                    <p class="font-semibold text-green-800 text-center">
                                        ✅ Le dossier de catégorie sera préservé
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($imageCount > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-broom text-yellow-600"></i>
                                </div>
                                <h4 class="font-semibold text-yellow-800">Vider la catégorie</h4>
                            </div>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>✅ Supprime toutes les images</li>
                                <li>✅ Conserve le dossier</li>
                                <li>✅ Catégorie reste accessible</li>
                                <li>✅ Facile d'ajouter de nouveau</li>
                            </ul>
                        </div>
                        
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-trash text-red-600"></i>
                                </div>
                                <h4 class="font-semibold text-red-800">Supprimer complètement</h4>
                            </div>
                            <ul class="text-sm text-red-700 space-y-1">
                                <li>❌ Supprime toutes les images</li>
                                <li>❌ Supprime le dossier entier</li>
                                <li>❌ Catégorie disparaît totalement</li>
                                <li>❌ Doit recréer la catégorie</li>
                            </ul>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-images text-yellow-600 text-xl"></i>
                            </div>
                            <p class="text-sm text-gray-600">Images à supprimer</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $imageCount; ?></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-hdd text-orange-600 text-xl"></i>
                            </div>
                            <p class="text-sm text-gray-600">Espace libéré</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo round($totalSize / 1024 / 1024, 2); ?> MB</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-folder text-green-600 text-xl"></i>
                            </div>
                            <p class="text-sm text-gray-600">Dossier conservé</p>
                            <p class="text-lg font-bold text-green-700">✓ Préservé</p>
                        </div>
                    </div>
                    <?php if (!empty($sampleImages)): ?>
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-eye mr-2"></i>
                                Aperçu des images qui seront supprimées
                                <?php if ($imageCount > 4): ?>
                                    <span class="text-sm text-gray-600">(<?php echo min(4, $imageCount); ?> sur <?php echo $imageCount; ?> affichées)</span>
                                <?php endif; ?>
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php foreach ($sampleImages as $image): ?>
                                    <div class="relative group">
                                        <div class="aspect-w-1 aspect-h-1 bg-gray-100 rounded-lg overflow-hidden">
                                            <img src="<?php echo $image['url']; ?>" 
                                                 alt="Image à supprimer" 
                                                 class="w-full h-24 object-cover">
                                        </div>
                                        <div class="absolute inset-0 bg-yellow-500 bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center">
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <i class="fas fa-broom text-white text-xl"></i>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <p class="text-xs font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($image['name']); ?>">
                                                <?php echo htmlspecialchars($image['name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500"><?php echo round($image['size'] / 1024, 1); ?> KB</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($imageCount > 4): ?>
                                <div class="mt-4 text-center">
                                    <p class="text-sm text-gray-600">
                                        ... et <strong><?php echo $imageCount - 4; ?> autre(s) image(s)</strong> qui seront également supprimées
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-question-circle text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-blue-800 font-medium">
                                    Êtes-vous sûr de vouloir vider cette catégorie ?
                                </p>
                                <p class="text-blue-700 text-sm mt-1">
                                    Toutes les images seront supprimées mais le dossier sera conservé pour faciliter l'ajout de nouvelles images.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Confirmation de vidage</h4>
                        <form method="post" id="empty-form" class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="hidden" name="confirm_empty" value="1">
                                <button type="submit" 
                                        id="empty-button"
                                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200">
                                    <i class="fas fa-broom mr-2"></i>
                                    Oui, vider la catégorie (conserver le dossier)
                                </button>
                                <a href="portfolio.php" 
                                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler et retourner
                                </a>
                                <a href="manage_category.php?category=<?php echo urlencode($category); ?>" 
                                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-cog mr-2"></i>
                                    Gérer la catégorie
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-red-800">Vous préférez supprimer complètement ?</h4>
                                <p class="text-sm text-red-700">Supprimer la catégorie ET son dossier définitivement</p>
                            </div>
                            <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer complètement
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Catégorie déjà vide</h3>
                        <p class="text-gray-600 mb-6">Cette catégorie ne contient aucune image à supprimer.</p>
                        
                        <div class="flex justify-center space-x-3">
                            <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Ajouter des images
                            </a>
                            
                            <a href="portfolio.php" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Retourner au portfolio
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const emptyForm = document.getElementById('empty-form');
    const emptyButton = document.getElementById('empty-button');

    // Confirmation avant soumission
    if (emptyForm && emptyButton) {
        emptyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const confirmed = confirm(
                `CONFIRMATION DE VIDAGE\n\n` +
                `Vous allez vider la catégorie "${<?php echo json_encode($category); ?>}" :\n` +
                `- Supprimer toutes les images (<?php echo $imageCount; ?>)\n` +
                `- Conserver le dossier de catégorie\n` +
                `- Libérer <?php echo round($totalSize / 1024 / 1024, 2); ?> MB d'espace\n\n` +
                `Cette action est irréversible pour les images !\n\n` +
                `Cliquez sur OK pour vider la catégorie.`
            );
            
            if (confirmed) {
                // Désactiver le bouton pour éviter les double-clics
                emptyButton.disabled = true;
                emptyButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Vidage en cours...';
                
                // Soumettre le formulaire
                this.submit();
            }
        });
    }
});
</script>

<?php include "admin_footer.php"; ?>

<?php
}
?>