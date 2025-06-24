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

// Messages de feedback
$message = '';
$error = '';

// Traitement de l'ajout d'une nouvelle catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $newCategory = trim($_POST['category_name']);
    
    if (empty($newCategory)) {
        $error = "Le nom de la catégorie ne peut pas être vide.";
    } elseif (in_array($newCategory, $categories)) {
        $error = "Cette catégorie existe déjà.";
    } else {
        $categoryDir = $portfolioMainDir . $newCategory . "/";
        
        // Créer le dossier
        if (mkdir($categoryDir, 0777, true)) {
            $message = "Catégorie '$newCategory' créée avec succès.";
            
            // Recharger les catégories
            $categories = getExistingCategories($portfolioMainDir);
        } else {
            $error = "Impossible de créer le dossier pour la catégorie.";
        }
    }
}

// Traitement de la suppression d'une catégorie
if (isset($_GET['delete_category'])) {
    $categoryToDelete = $_GET['delete_category'];
    
    if (in_array($categoryToDelete, $categories)) {
        $categoryDir = $portfolioMainDir . $categoryToDelete . "/";
        
        // Supprimer toutes les images de la catégorie
        if (is_dir($categoryDir)) {
            $images = glob($categoryDir . "*");
            foreach ($images as $image) {
                if (is_file($image)) {
                    unlink($image);
                }
            }
            
            // Supprimer le dossier
            if (rmdir($categoryDir)) {
                $message = "Catégorie '$categoryToDelete' supprimée avec succès.";
                
                // Recharger les catégories
                $categories = getExistingCategories($portfolioMainDir);
            } else {
                $error = "Impossible de supprimer le dossier de la catégorie.";
            }
        }
    }
}

// Récupérer les statistiques pour chaque catégorie
$categoryStats = [];
foreach ($categories as $category) {
    $categoryPath = $portfolioMainDir . $category . "/";
    $imageCount = 0;
    $totalSize = 0;
    
    if (is_dir($categoryPath)) {
        $images = glob($categoryPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        $imageCount = count($images);
        
        foreach ($images as $image) {
            $totalSize += filesize($image);
        }
    }
    
    $categoryStats[$category] = [
        'count' => $imageCount,
        'size' => $totalSize,
        'path' => $categoryPath
    ];
}

ob_end_flush();
?>
<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion des catégories</h1>
                <p class="text-gray-600 mt-1"><?php echo count($categories); ?> catégorie(s) détectée(s) automatiquement</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <a href="portfolio.php" class="text-gray-500 hover:text-gray-700">Portfolio</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Catégories</span>
            </nav>
        </div>
    </div>
</div>
<div class="px-6 space-y-6">
    <?php if (!empty($error)): ?>
        <div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>    
    <?php if (!empty($message)): ?>
        <div class="p-4 border-l-4 border-green-400 bg-green-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="p-4 border-l-4 border-blue-400 bg-blue-50 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Détection automatique des catégories</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Les catégories sont détectées automatiquement en lisant tous les dossiers dans <code class="bg-blue-100 px-1 rounded">img/portfolio/</code>.</p>
                    <p class="mt-1">Toute nouvelle catégorie créée apparaîtra immédiatement dans la liste.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-folder text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total catégories</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo count($categories); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-images text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total images</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo !empty($categories) ? array_sum(array_column($categoryStats, 'count')) : 0; ?></p>
                </div>
            </div>
        </div>        
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-hdd text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Espace utilisé</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo !empty($categories) ? round(array_sum(array_column($categoryStats, 'size')) / 1024 / 1024, 1) : 0; ?> MB</p>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Ajouter une nouvelle catégorie</h3>
            <p class="text-sm text-gray-600 mt-1">Créer un nouveau dossier de catégorie pour organiser vos images</p>
        </div>
        <div class="p-6">
            <form method="post" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-3">
                        <label for="category_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom de la catégorie <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="category_name" 
                               name="category_name" 
                               required 
                               placeholder="Ex: Architecture, Design, Photos..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                        <p class="text-xs text-gray-500 mt-1">Le nom doit être unique et sera utilisé pour créer un dossier.</p>
                    </div>                    
                    <div class="flex items-end">
                        <button type="submit" 
                                name="add_category" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter la catégorie
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Catégories existantes</h3>
                    <p class="text-sm text-gray-600 mt-1">Liste automatiquement détectée des dossiers de catégories</p>
                </div>
                <button type="button" 
                        onclick="location.reload();" 
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Actualiser
                </button>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($categories)): ?>
                <div class="hidden lg:block">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Catégorie
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Images
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Taille
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dossier
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($categories as $category): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                    <i class="fas fa-folder text-blue-600"></i>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($category); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $categoryStats[$category]['count']; ?> image(s)
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo round($categoryStats[$category]['size'] / 1024 / 1024, 2); ?> MB
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <code class="px-2 py-1 text-xs bg-gray-100 rounded text-gray-800">
                                                <?php echo htmlspecialchars($categoryStats[$category]['path']); ?>
                                            </code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                                                   class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200"
                                                   title="Ajouter des images">
                                                    <i class="fas fa-plus text-xs"></i>
                                                </a>                                                
                                                <a href="manage_category.php?category=<?php echo urlencode($category); ?>" 
                                                   class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-200"
                                                   title="Gérer la catégorie">
                                                    <i class="fas fa-cog text-xs"></i>
                                                </a>
                                                <?php if ($categoryStats[$category]['count'] == 0): ?>
                                                    <a href="?delete_category=<?php echo urlencode($category); ?>" 
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie vide?');" 
                                                       class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-red-600 hover:bg-red-700 transition-colors duration-200"
                                                       title="Supprimer la catégorie vide">
                                                        <i class="fas fa-trash text-xs"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                                                       class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200"
                                                       title="Supprimer la catégorie et ses images">
                                                        <i class="fas fa-exclamation-triangle text-xs"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="lg:hidden space-y-4">
                    <?php foreach ($categories as $category): ?>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-folder text-blue-600"></i>
                                    </div>
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category); ?></h4>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo $categoryStats[$category]['count']; ?> image(s)
                                </span>
                            </div>
                            <div class="mb-3">
                                <p class="text-xs text-gray-600 mb-1">Taille: <?php echo round($categoryStats[$category]['size'] / 1024 / 1024, 2); ?> MB</p>
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-800">
                                    <?php echo htmlspecialchars($categoryStats[$category]['path']); ?>
                                </code>
                            </div> 
                            <div class="flex items-center space-x-2">
                                <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                                   class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                                    <i class="fas fa-plus mr-1"></i>
                                    Ajouter
                                </a>
                                <a href="manage_category.php?category=<?php echo urlencode($category); ?>" 
                                   class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-200">
                                    <i class="fas fa-cog mr-1"></i>
                                    Gérer
                                </a>                                
                                <?php if ($categoryStats[$category]['count'] == 0): ?>
                                    <a href="?delete_category=<?php echo urlencode($category); ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie vide?');" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                                       class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>                
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-folder-plus text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune catégorie trouvée</h3>
                    <p class="text-gray-600 mb-6">Aucun dossier de catégorie n'a été détecté dans le répertoire portfolio.</p>
                    <p class="text-gray-600">Vous pouvez créer une nouvelle catégorie en utilisant le formulaire ci-dessus.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus sur le champ de saisie
    const categoryInput = document.getElementById('category_name');
    if (categoryInput) {
        categoryInput.focus();
    }
    
    // Validation en temps réel du nom de catégorie
    categoryInput.addEventListener('input', function() {
        const value = this.value.trim();
        const submitBtn = document.querySelector('button[name="add_category"]');
        
        // Vérifier si le nom est valide (pas vide, pas d'espaces multiples, etc.)
        const isValid = value.length > 0 && /^[a-zA-Z0-9\s\-_]+$/.test(value);
        
        if (isValid) {
            this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
            this.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else if (value.length > 0) {
            this.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    });
    
    // Animation des cartes au survol (version mobile)
    const cards = document.querySelectorAll('.lg\\:hidden .bg-gray-50');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow-md', 'transform', 'scale-105');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-md', 'transform', 'scale-105');
        });
    });
});
</script>

<?php include "admin_footer.php"; ?>