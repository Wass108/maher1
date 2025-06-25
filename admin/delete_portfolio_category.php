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

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $deletedCount = 0;
    $folderDeleted = false;
    
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
        
        // Supprimer le dossier lui-même
        if (rmdir($categoryDir)) {
            $folderDeleted = true;
        }
    }
    
    if ($deletedCount > 0 && $folderDeleted) {
        $_SESSION['message'] = "La catégorie '$category' a été supprimée avec succès. $deletedCount image(s) supprimée(s) et le dossier a été supprimé.";
    } elseif ($deletedCount > 0) {
        $_SESSION['message'] = "$deletedCount image(s) supprimée(s) de la catégorie $category, mais le dossier n'a pas pu être supprimé.";
    } elseif ($folderDeleted) {
        $_SESSION['message'] = "Le dossier de la catégorie '$category' a été supprimé (aucune image trouvée).";
    } else {
        $_SESSION['message'] = "Aucune image trouvée dans la catégorie $category et le dossier n'existait pas.";
    }
    
    // Déterminer la page de redirection basée sur le referer ou un paramètre
    $redirectTo = 'portfolio.php';
    if (isset($_GET['redirect']) && $_GET['redirect'] === 'manage_categories') {
        $redirectTo = 'manage_portfolio_categories.php';
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, 'manage_category.php') !== false) {
            // Si on vient de manage_category.php, retourner au portfolio puisque la catégorie n'existe plus
            $redirectTo = 'portfolio.php';
        } elseif (strpos($referer, 'manage_portfolio_categories.php') !== false) {
            $redirectTo = 'manage_portfolio_categories.php';
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
            
            // Récupérer les 3 premières images pour l'aperçu
            if ($index < 3) {
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
                <h1 class="text-2xl font-bold text-gray-900">Supprimer la catégorie</h1>
                <p class="text-gray-600 mt-1">Confirmation de suppression définitive</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <a href="portfolio.php" class="text-gray-500 hover:text-gray-700">Portfolio</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Supprimer catégorie</span>
            </nav>
        </div>
    </div>
</div>
<div class="px-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg border border-red-200 shadow-sm">
            <div class="p-6 border-b border-red-200 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-t-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Confirmation de suppression</h3>
                        <p class="text-red-100 text-sm mt-1">Cette action est irréversible</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-lg font-semibold text-red-800 mb-2">Attention ! Action dangereuse</h4>
                            <p class="text-red-700 mb-3">
                                Vous êtes sur le point de supprimer <strong>COMPLÈTEMENT</strong> la catégorie 
                                <span class="font-bold bg-red-100 px-2 py-1 rounded"><?php echo htmlspecialchars($category); ?></span>.
                            </p>
                            
                            <div class="bg-red-100 rounded-lg p-4 mt-4">
                                <h5 class="font-semibold text-red-800 mb-2">Cette action va :</h5>
                                <ul class="list-disc list-inside text-red-700 space-y-1">
                                    <li>Supprimer <strong><?php echo $imageCount; ?> image(s)</strong> définitivement</li>
                                    <li>Libérer <strong><?php echo round($totalSize / 1024 / 1024, 2); ?> MB</strong> d'espace disque</li>
                                    <li>Supprimer le <strong>dossier de la catégorie</strong> entièrement</li>
                                    <li>Supprimer <strong>tout le contenu</strong> lié à cette catégorie</li>
                                </ul>
                                <p class="font-bold text-red-800 mt-3 text-center">
                                    ⚠️ Cette action est IRRÉVERSIBLE ! ⚠️
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($imageCount > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-images text-red-600 text-xl"></i>
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
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-folder text-purple-600 text-xl"></i>
                            </div>
                            <p class="text-sm text-gray-600">Catégorie</p>
                            <p class="text-lg font-bold text-gray-900 truncate"><?php echo htmlspecialchars($category); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($sampleImages)): ?>
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-eye mr-2"></i>
                                Aperçu des images qui seront supprimées
                                <?php if ($imageCount > 3): ?>
                                    <span class="text-sm text-gray-600">(<?php echo min(3, $imageCount); ?> sur <?php echo $imageCount; ?> affichées)</span>
                                <?php endif; ?>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <?php foreach ($sampleImages as $image): ?>
                                    <div class="relative group">
                                        <div class="aspect-w-16 aspect-h-9 bg-gray-100 rounded-lg overflow-hidden">
                                            <img src="<?php echo $image['url']; ?>" 
                                                 alt="Image à supprimer" 
                                                 class="w-full h-32 object-cover">
                                        </div>
                                        <div class="absolute inset-0 bg-red-500 bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center">
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <i class="fas fa-trash text-white text-2xl"></i>
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
                            <?php if ($imageCount > 3): ?>
                                <div class="mt-4 text-center">
                                    <p class="text-sm text-gray-600">
                                        ... et <strong><?php echo $imageCount - 3; ?> autre(s) image(s)</strong> qui seront également supprimées
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-question-circle text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-yellow-800 font-medium">
                                    Êtes-vous absolument sûr de vouloir procéder à cette suppression ?
                                </p>
                                <p class="text-yellow-700 text-sm mt-1">
                                    Cette action ne peut pas être annulée. Toutes les données seront perdues définitivement.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Confirmation de sécurité</h4>
                        <p class="text-gray-700 mb-4">
                            Pour confirmer la suppression, veuillez taper le nom de la catégorie 
                            <code class="bg-gray-200 px-2 py-1 rounded font-mono"><?php echo htmlspecialchars($category); ?></code> 
                            dans le champ ci-dessous :
                        </p>
                        <form method="post" id="delete-form" class="space-y-4">
                            <div>
                                <input type="text" 
                                       id="category-confirmation" 
                                       placeholder="Tapez le nom de la catégorie pour confirmer"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       autocomplete="off">
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="hidden" name="confirm_delete" value="1">
                                <button type="submit" 
                                        id="delete-button"
                                        disabled
                                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                    <i class="fas fa-trash mr-2"></i>
                                    Oui, supprimer définitivement la catégorie
                                </button>
                                <a href="portfolio.php" 
                                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler et retourner
                                </a>
                                <a href="manage_category.php?category=<?php echo urlencode($category); ?>" 
                                   class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                    <i class="fas fa-cog mr-2"></i>
                                    Gérer plutôt la catégorie
                                </a>
                            </div>
                        </form>
                    </div>
                <?php elseif (is_dir($categoryDir)): ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Catégorie vide</h3>
                        <p class="text-gray-600 mb-6">Cette catégorie ne contient aucune image, seul le dossier sera supprimé.</p>
                        
                        <form method="post" class="inline-flex space-x-3">
                            <input type="hidden" name="confirm_delete" value="1">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer le dossier vide
                            </button>
                            <a href="portfolio.php" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Annuler
                            </a>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-question-circle text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Catégorie inexistante</h3>
                        <p class="text-gray-600 mb-6">Aucune image trouvée et le dossier n'existe pas pour cette catégorie.</p>
                        
                        <a href="portfolio.php" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retourner au portfolio
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryName = "<?php echo htmlspecialchars($category); ?>";
    const confirmationInput = document.getElementById('category-confirmation');
    const deleteButton = document.getElementById('delete-button');
    const deleteForm = document.getElementById('delete-form');

    // Vérification en temps réel de la saisie
    if (confirmationInput && deleteButton) {
        confirmationInput.addEventListener('input', function() {
            const inputValue = this.value.trim();
            const isMatch = inputValue === categoryName;
            
            if (isMatch) {
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                deleteButton.classList.add('hover:bg-red-700');
                this.classList.remove('border-gray-300', 'focus:border-red-500');
                this.classList.add('border-green-300', 'focus:border-green-500', 'focus:ring-green-500');
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
                deleteButton.classList.remove('hover:bg-red-700');
                this.classList.remove('border-green-300', 'focus:border-green-500', 'focus:ring-green-500');
                this.classList.add('border-gray-300', 'focus:border-red-500');
            }
        });

        // Confirmation supplémentaire avant soumission
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const confirmed = confirm(
                `DERNIÈRE CONFIRMATION !\n\n` +
                `Vous allez supprimer DÉFINITIVEMENT :\n` +
                `- La catégorie "${categoryName}"\n` +
                `- Toutes ses images (<?php echo $imageCount; ?>)\n` +
                `- Son dossier complet\n\n` +
                `Cette action est IRRÉVERSIBLE !\n\n` +
                `Cliquez sur OK pour procéder à la suppression.`
            );
            
            if (confirmed) {
                // Désactiver le bouton pour éviter les double-clics
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Suppression en cours...';
                
                // Soumettre le formulaire
                this.submit();
            }
        });

        // Focus automatique sur le champ de confirmation
        confirmationInput.focus();
    }
});
</script>

<?php include "admin_footer.php"; ?>

<?php
}
?>