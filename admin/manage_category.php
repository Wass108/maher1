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
$portfolioVideoDir = "../videos/portfolio/";

// Récupérer dynamiquement toutes les catégories existantes
$categories = getExistingCategories($portfolioMainDir);

// Vérifier si la catégorie est spécifiée et valide
if (!isset($_GET['category']) || !in_array($_GET['category'], $categories)) {
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$categoryDir = $portfolioMainDir . $category . "/";
$categoryVideoDir = $portfolioVideoDir . $category . "/";

// Messages de feedback
$message = '';
$error = '';

// Traitement de la réorganisation des images par AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder']) && $_POST['reorder'] === 'true') {
    // IMPORTANT: Stopper complètement le buffer de sortie pour les requêtes AJAX
    if (ob_get_level()) {
        ob_end_clean(); // Nettoyer le buffer au lieu de le vider
    }
    
    header('Content-Type: application/json');
    
    if (isset($_POST['imageOrder']) && is_array($_POST['imageOrder'])) {
        $tempDir = $categoryDir . "temp_reorder/";
        
        try {
            // Nettoyer et créer un dossier temporaire unique
            if (is_dir($tempDir)) {
                // Supprimer le contenu du dossier temporaire s'il existe
                $files = glob($tempDir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($tempDir);
            }
            
            if (!mkdir($tempDir, 0777, true)) {
                throw new Exception("Impossible de créer le dossier temporaire");
            }
            
            // Récupérer les images existantes
            $existingImages = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
            $existingImageNames = array_map('basename', $existingImages);
            
            // Vérifier que toutes les images demandées existent
            foreach ($_POST['imageOrder'] as $imageName) {
                if (!in_array($imageName, $existingImageNames)) {
                    throw new Exception("Image non trouvée: " . $imageName);
                }
            }
            
            // Copier les images dans l'ordre spécifié avec une numérotation séquentielle
            $counter = 1;
            $imageMapping = [];
            foreach ($_POST['imageOrder'] as $imageName) {
                $originalPath = $categoryDir . $imageName;
                $extension = pathinfo($imageName, PATHINFO_EXTENSION);
                $newName = sprintf("%02d.%s", $counter, $extension);
                $tempPath = $tempDir . $newName;
                
                if (!copy($originalPath, $tempPath)) {
                    throw new Exception("Impossible de copier l'image: " . $imageName);
                }
                
                $imageMapping[$imageName] = $newName;
                $counter++;
            }
            
            // Supprimer les anciennes images
            foreach ($existingImages as $imagePath) {
                if (!unlink($imagePath)) {
                    throw new Exception("Impossible de supprimer l'ancienne image: " . basename($imagePath));
                }
            }
            
            // Déplacer les nouvelles images réorganisées
            foreach ($imageMapping as $oldName => $newName) {
                $tempPath = $tempDir . $newName;
                $finalPath = $categoryDir . $newName;
                
                if (!rename($tempPath, $finalPath)) {
                    throw new Exception("Impossible de déplacer l'image réorganisée: " . $newName);
                }
            }
            
            // Nettoyer le dossier temporaire
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'L\'ordre des images a été sauvegardé avec succès.'
            ]);
            
        } catch (Exception $e) {
            // En cas d'erreur, essayer de restaurer depuis le dossier temporaire
            if (is_dir($tempDir)) {
                $tempFiles = glob($tempDir . '*');
                foreach ($tempFiles as $tempFile) {
                    $fileName = basename($tempFile);
                    $finalPath = $categoryDir . $fileName;
                    if (file_exists($tempFile)) {
                        copy($tempFile, $finalPath);
                        unlink($tempFile);
                    }
                }
                rmdir($tempDir);
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la réorganisation: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Données de réorganisation invalides.'
        ]);
    }
    
    exit;
}

// Traitement du renommage de la catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_category'])) {
    $newCategoryName = trim($_POST['new_category_name']);
    
    if (empty($newCategoryName)) {
        $_SESSION['error'] = "Le nouveau nom de catégorie ne peut pas être vide.";
    } elseif (in_array($newCategoryName, $categories) && $newCategoryName !== $category) {
        $_SESSION['error'] = "Une catégorie avec ce nom existe déjà.";
    } else {
        $newCategoryDir = $portfolioMainDir . $newCategoryName . "/";
        
        // Renommer le dossier
        if (rename($categoryDir, $newCategoryDir)) {
            $_SESSION['message'] = "Catégorie renommée de '$category' vers '$newCategoryName' avec succès.";
            // Rediriger vers la nouvelle catégorie
            header("Location: manage_category.php?category=" . urlencode($newCategoryName));
            exit;
        } else {
            $_SESSION['error'] = "Impossible de renommer la catégorie.";
        }
    }
    
    // Rediriger pour éviter la resoumission
    header("Location: manage_category.php?category=" . urlencode($category));
    exit;
}

// Récupérer les images et vidéos de cette catégorie
$categoryImages = [];
$categoryVideos = [];

if (is_dir($categoryDir)) {
    $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
    // Trier les images par nom de fichier pour maintenir l'ordre numérique
    usort($images, function($a, $b) {
        return strnatcmp(basename($a), basename($b));
    });
    
    foreach ($images as $image) {
        $categoryImages[] = [
            'path' => $image,
            'name' => basename($image),
            'size' => filesize($image),
            'url' => $image,
            'type' => 'image',
            'modified' => filemtime($image)
        ];
    }
}

if (is_dir($categoryVideoDir)) {
    $videos = glob($categoryVideoDir . "*.{mp4,avi,mov,wmv,flv,webm,mkv,3gp,ogv}", GLOB_BRACE);
    // Trier les vidéos par nom de fichier
    usort($videos, function($a, $b) {
        return strnatcmp(basename($a), basename($b));
    });
    
    foreach ($videos as $video) {
        $categoryVideos[] = [
            'path' => $video,
            'name' => basename($video),
            'size' => filesize($video),
            'url' => $video,
            'type' => 'video',
            'modified' => filemtime($video)
        ];
    }
}

// Combiner images et vidéos mais garder les images triées en premier
$categoryMedia = array_merge($categoryImages, $categoryVideos);

// Calculer les statistiques
$totalImages = count($categoryImages);
$totalVideos = count($categoryVideos);
$totalMedia = count($categoryMedia);
$totalSize = array_sum(array_column($categoryMedia, 'size'));

ob_end_flush();
?>
<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gérer la catégorie</h1>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($category); ?></p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <a href="portfolio.php" class="text-gray-500 hover:text-gray-700">Portfolio</a>
                <span class="text-gray-400">/</span>
                <a href="manage_portfolio_categories.php" class="text-gray-500 hover:text-gray-700">Catégories</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($category); ?></span>
            </nav>
        </div>
    </div>
</div>
<div class="px-6 space-y-6">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 border-l-4 border-green-400 bg-green-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="flex flex-wrap items-center gap-3">
        <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>&redirect=manage_category" 
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i>
            Ajouter des images
        </a>
        <a href="portfolio.php" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour au portfolio
        </a>
        <a href="manage_portfolio_categories.php" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
            <i class="fas fa-list mr-2"></i>
            Toutes les catégories
        </a>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-images text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Images</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $totalImages; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-hdd text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Taille</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo round($totalSize / 1024 / 1024, 1); ?> MB</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-folder text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Dossier</p>
                            <p class="text-sm font-bold text-gray-900 truncate"><?php echo $category; ?>/</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Renommer la catégorie</h3>
                <p class="text-sm text-gray-600 mt-1">Modifier le nom de cette catégorie</p>
            </div>
            <div class="p-6">
                <form method="post" class="space-y-4">
                    <div>
                        <label for="new_category_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nouveau nom
                        </label>
                        <input type="text" 
                               id="new_category_name"
                               name="new_category_name" 
                               value="<?php echo htmlspecialchars($category); ?>" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    </div>
                    <button type="submit" 
                            name="rename_category" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        Renommer
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-photo-video mr-3"></i>
                        Médias de "<?php echo htmlspecialchars($category); ?>"
                        <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo $totalImages; ?> image(s)
                        </span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <?php echo $totalVideos; ?> vidéo(s)
                        </span>
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Les images sont triées par ordre numérique • <?php echo $totalMedia; ?> média(s) au total</p>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Filtres de type de média -->
                    <div id="media-filters" class="flex items-center space-x-1 mr-4">
                        <button type="button" onclick="filterMedia('all')" id="filter-all"
                                class="media-filter-btn active inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-th mr-1"></i>
                            Tout
                        </button>
                        <button type="button" onclick="filterMedia('images')" id="filter-images"
                                class="media-filter-btn inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-images mr-1"></i>
                            Photos
                        </button>
                        <button type="button" onclick="filterMedia('videos')" id="filter-videos"
                                class="media-filter-btn inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-video mr-1"></i>
                            Vidéos
                        </button>
                    </div>

                    <?php if ($totalMedia > 0): ?>
                        <div id="bulk-actions" class="hidden space-x-2">
                            <button type="button" onclick="deleteSelectedImages()" disabled
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer (<span id="selected-count">0</span>)
                            </button>
                            <button type="button" onclick="clearSelection()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Annuler
                            </button>
                        </div>
                        
                        <!-- Contrôles de réorganisation -->
                        <div id="reorder-actions" class="hidden space-x-2">
                            <button id="saveOrder" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>
                                Enregistrer l'ordre
                            </button>
                            <button id="cancelReorder" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Annuler
                            </button>
                        </div>
                        
                        <div id="category-actions" class="flex items-center space-x-2">
                            <button type="button" onclick="toggleSelectionMode()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-check-square mr-2"></i>
                                Sélection multiple
                            </button>
                            
                            <?php if ($totalImages > 1): ?>
                                <button id="enableReorder" type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                    <i class="fas fa-arrows-alt mr-2"></i>
                                    Réorganiser
                                </button>
                            <?php endif; ?>
                            
                            <a href="empty_portfolio_category.php?category=<?php echo urlencode($category); ?>&redirect=manage_category" 
                               onclick="return confirm('Êtes-vous sûr de vouloir vider cette catégorie ? Tous les médias seront supprimés mais la catégorie restera.');"
                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200">
                                <i class="fas fa-broom mr-2"></i>
                                Vider
                            </a>
                            <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer complètement cette catégorie ? Le dossier et tous les médias seront supprimés.');"
                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer catégorie
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        <div class="p-6">
            <?php if (!empty($categoryMedia)): ?>
                <div id="ajax-messages" class="mb-6"></div>
                
                <div id="selection-controls" class="hidden mb-6">
                    <div class="flex items-center space-x-3">
                        <button type="button" onclick="selectAll()"
                                class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-check-square mr-1"></i>
                            Tout sélectionner
                        </button>
                        <button type="button" onclick="selectNone()"
                                class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-square mr-1"></i>
                            Tout désélectionner
                        </button>
                        <button type="button" onclick="invertSelection()"
                                class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-exchange-alt mr-1"></i>
                            Inverser
                        </button>
                    </div>
                </div>
                
                <div id="reorderInstructions" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Mode de réorganisation:</strong> Seules les images peuvent être réorganisées. Glissez et déposez les images pour modifier leur ordre. Les vidéos restent en place. N'oubliez pas de cliquer sur "Enregistrer l'ordre" lorsque vous avez terminé.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Conteneur principal pour tous les médias -->
                <div id="mediaGallery" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($categoryMedia as $media): ?>
                        <div class="media-item image-container relative group <?php echo $media['type'] === 'image' ? 'media-type-image' : 'media-type-video'; ?>" 
                             data-image="<?php echo htmlspecialchars($media['name']); ?>" 
                             data-type="<?php echo $media['type']; ?>">
                            
                            <!-- Checkbox pour sélection multiple -->
                            <div class="image-checkbox absolute top-3 right-3 z-10 hidden">
                                <input type="checkbox" class="image-select h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                       value="<?php echo htmlspecialchars($media['name']); ?>" 
                                       data-type="<?php echo $media['type']; ?>" 
                                       onchange="updateSelectionCount()">
                            </div>
                            
                            <!-- Poignée de drag pour réorganisation (seulement pour les images) -->
                            <?php if ($media['type'] === 'image'): ?>
                                <div class="drag-handle hidden cursor-move bg-blue-50 border border-blue-200 text-center py-2 rounded-t-lg">
                                    <i class="fas fa-grip-vertical text-blue-600"></i>
                                    <span class="text-xs text-blue-700 ml-2">Déplacer</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="image-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-200">
                                <div class="aspect-w-1 aspect-h-1 bg-gray-100">
                                    <?php if ($media['type'] === 'image'): ?>
                                        <img src="<?php echo $media['url']; ?>" 
                                             alt="Image" 
                                             class="w-full h-32 object-cover">
                                    <?php else: ?>
                                        <div class="relative">
                                            <video class="w-full h-32 object-cover" muted preload="metadata">
                                                <source src="<?php echo $media['url']; ?>" type="video/<?php echo pathinfo($media['url'], PATHINFO_EXTENSION); ?>">
                                                Votre navigateur ne prend pas en charge la lecture de vidéos.
                                            </video>
                                            <!-- Overlay de lecture pour la modal -->
                                            <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-200 cursor-pointer" onclick="openVideoModal('<?php echo $media['url']; ?>')">
                                                <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-play text-white text-sm"></i>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2 image-actions">
                                        <?php if ($media['type'] === 'image'): ?>
                                            <a href="<?php echo $media['url']; ?>" 
                                               target="_blank" 
                                               class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200"
                                               title="Voir en grand">
                                                <i class="fas fa-eye text-sm"></i>
                                            </a>
                                        <?php else: ?>
                                            <button onclick="openVideoModal('<?php echo $media['url']; ?>')" 
                                                   class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-purple-600 hover:bg-purple-700 transition-colors duration-200"
                                                   title="Lire la vidéo">
                                                <i class="fas fa-play text-sm"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="delete_portfolio_image.php?category=<?php echo urlencode($category); ?>&image=<?php echo urlencode($media['name']); ?>&redirect=manage_category" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce média?');" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-red-600 hover:bg-red-700 transition-colors duration-200"
                                           title="Supprimer">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <h6 class="text-xs font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($media['name']); ?>">
                                        <?php echo htmlspecialchars($media['name']); ?>
                                    </h6>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <p><?php echo round($media['size'] / 1024, 1); ?> KB</p>
                                        <p><?php echo date('d/m/Y H:i', $media['modified']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-images text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune image dans cette catégorie</h3>
                    <p class="text-gray-600 mb-6">Commencez par ajouter des images à cette catégorie.</p>
                    <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter des images
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include "admin_footer.php"; ?>

<!-- Ajouter les librairies nécessaires pour le drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<script>
// Variables globales pour éviter les conflits
let selectionMode = false;
let reorderMode = false;
let sortableInstance = null;
let originalOrder = []; // Sauvegarde de l'ordre original

function toggleSelectionMode() {
    // Vérifier qu'on n'est pas en mode réorganisation
    if (reorderMode) {
        alert('Veuillez d\'abord quitter le mode de réorganisation avant d\'activer la sélection multiple');
        return;
    }
    
    selectionMode = !selectionMode;
    
    if (selectionMode) {
        // Activer le mode sélection
        $('.image-checkbox').removeClass('hidden');
        $('.image-card').addClass('selection-mode');
        $('#selection-controls').removeClass('hidden');
        $('#bulk-actions').removeClass('hidden');
        $('#category-actions').addClass('hidden');
        
        // Désactiver le bouton de réorganisation pendant la sélection
        $('#enableReorder').prop('disabled', true);
        
        console.log('Mode sélection activé'); // Debug
    } else {
        exitSelectionMode();
    }
}

function exitSelectionMode() {
    console.log('Exit selection mode'); // Debug
    selectionMode = false;
    
    // Désactiver le mode sélection
    $('.image-checkbox').addClass('hidden');
    $('.image-card').removeClass('selection-mode selected');
    $('.image-select').prop('checked', false);
    $('#selection-controls').addClass('hidden');
    $('#bulk-actions').addClass('hidden');
    $('#category-actions').removeClass('hidden');
    
    // Réactiver le bouton de réorganisation
    $('#enableReorder').prop('disabled', false);
    
    updateSelectionCount();
}

function clearSelection() {
    exitSelectionMode();
}

function selectAll() {
    // Sélectionner seulement les éléments visibles selon le filtre actuel
    $('.image-select').each(function() {
        const mediaItem = $(this).closest('.image-container');
        // Vérifier si l'élément est visible (pas caché par le filtre)
        if (mediaItem.is(':visible')) {
            $(this).prop('checked', true);
            $(this).closest('.image-container').find('.image-card').addClass('selected');
        }
    });
    updateSelectionCount();
}

function selectNone() {
    $('.image-select').prop('checked', false);
    $('.image-card').removeClass('selected');
    updateSelectionCount();
}

function invertSelection() {
    $('.image-select').each(function() {
        $(this).prop('checked', !$(this).prop('checked'));
        $(this).trigger('change');
    });
    updateSelectionCount();
}

function updateSelectionCount() {
    const selectedCount = $('.image-select:checked').length;
    $('#selected-count').text(selectedCount);
    
    console.log('Selected count:', selectedCount); // Debug
    
    // Mettre à jour l'apparence des cartes
    $('.image-select').each(function() {
        const card = $(this).closest('.image-container').find('.image-card');
        if ($(this).is(':checked')) {
            card.addClass('selected');
        } else {
            card.removeClass('selected');
        }
    });
    
    // Activer/désactiver le bouton de suppression
    if (selectedCount > 0) {
        $('#bulk-actions button[onclick="deleteSelectedImages()"]').prop('disabled', false);
    } else {
        $('#bulk-actions button[onclick="deleteSelectedImages()"]').prop('disabled', true);
    }
}

function deleteSelectedImages() {
    const selectedImages = [];
    $('.image-select:checked').each(function() {
        selectedImages.push($(this).val());
    });
    
    if (selectedImages.length === 0) {
        alert('Aucune image sélectionnée');
        return;
    }
    
    if (!confirm(`Êtes-vous sûr de vouloir supprimer ${selectedImages.length} image(s) sélectionnée(s) ? Cette action est irréversible.`)) {
        return;
    }
    
    // Désactiver le bouton pendant la suppression
    const deleteButton = $('#bulk-actions button[onclick="deleteSelectedImages()"]');
    const originalText = deleteButton.html();
    deleteButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Suppression...');
    
    // Envoyer la requête AJAX
    $.ajax({
        url: 'delete_multiple_images.php',
        type: 'POST',
        data: {
            action: 'delete_multiple',
            category: '<?php echo $category; ?>',
            images: selectedImages
        },
        dataType: 'json',
        success: function(response) {
            // Afficher le message de résultat
            let alertClass = response.success ? 'border-green-400 bg-green-50' : 'border-red-400 bg-red-50';
            let iconClass = response.success ? 'text-green-400' : 'text-red-400';
            let textClass = response.success ? 'text-green-700' : 'text-red-700';
            let icon = response.success ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            let alertHtml = `<div class="p-4 border-l-4 ${alertClass} rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas ${icon} ${iconClass}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm ${textClass}">${response.message}</p>
                    </div>
                </div>
            </div>`;
            
            $('#ajax-messages').html(alertHtml);
            
            if (response.success && response.deleted > 0) {
                // Supprimer les images de l'interface
                selectedImages.forEach(function(imageName) {
                    $(`.image-container[data-image="${imageName}"]`).fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si plus d'images, recharger la page pour mettre à jour l'interface
                        if ($('.image-container').length === 0) {
                            location.reload();
                        }
                    });
                });
                
                // Sortir du mode sélection
                setTimeout(function() {
                    exitSelectionMode();
                }, 500);
            }
            
            // Réactiver le bouton
            deleteButton.prop('disabled', false).html(originalText);
            
            // Faire défiler vers le message
            $('html, body').animate({
                scrollTop: $('#ajax-messages').offset().top - 100
            }, 500);
        },
        error: function(xhr, status, error) {
            console.log('Erreur AJAX:', xhr.responseText);
            $('#ajax-messages').html(`<div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">Erreur lors de la suppression des images. Veuillez réessayer.</p>
                    </div>
                </div>
            </div>`);
            
            deleteButton.prop('disabled', false).html(originalText);
        }
    });
}

// ===== SYSTÈME DE RÉORGANISATION PAR DRAG & DROP =====
document.addEventListener('DOMContentLoaded', function() {
    // Activer le mode de réorganisation
    const enableReorderBtn = document.getElementById('enableReorder');
    if (enableReorderBtn) {
        enableReorderBtn.addEventListener('click', function() {
            console.log('Bouton Réorganiser cliqué'); // Debug
            
            if (selectionMode) {
                alert('Veuillez d\'abord quitter le mode de sélection multiple avant d\'activer la réorganisation');
                return;
            }
            
            // SAUVEGARDER L'ORDRE ORIGINAL AVANT D'ENTRER EN MODE RÉORGANISATION
            saveOriginalOrder();
            
            reorderMode = true;
            
            // Ajouter la classe reorder-mode au conteneur principal
            const mediaGallery = document.getElementById('mediaGallery');
            mediaGallery.classList.add('reorder-mode');
            
            // Afficher/masquer les éléments appropriés
            $('#category-actions').addClass('hidden');
            $('#reorder-actions').removeClass('hidden');
            $('#reorderInstructions').removeClass('hidden');
            $('.image-checkbox').addClass('hidden');
            $('.drag-handle').removeClass('hidden');
            $('#media-filters').addClass('hidden');
            
            // Désactiver les autres boutons
            $('button[onclick="toggleSelectionMode()"]').prop('disabled', true);
            
            // FORCER LA DÉSACTIVATION DE TOUS LES OVERLAYS
            $('.image-container').each(function() {
                const overlay = $(this).find('.bg-black');
                const actions = $(this).find('.image-actions');
                const links = $(this).find('a');
                const buttons = $(this).find('button').not('.drag-handle button');
                
                // Désactiver complètement l'overlay
                overlay.css({
                    'pointer-events': 'none',
                    'display': 'none',
                    'opacity': '0'
                });
                
                // Désactiver les actions
                actions.css({
                    'pointer-events': 'none',
                    'display': 'none',
                    'visibility': 'hidden'
                });
                
                // Désactiver tous les liens et boutons
                links.css('pointer-events', 'none');
                buttons.css('pointer-events', 'none');
            });
            
            // Initialiser Sortable.js sur le conteneur des médias - IMAGES SEULEMENT
            if (mediaGallery) {
                sortableInstance = Sortable.create(mediaGallery, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    // Exclure les vidéos du drag & drop
                    filter: '.media-type-video',
                    preventOnFilter: false,
                    onStart: function(evt) {
                        // Vérifier que c'est bien une image
                        if (evt.item.classList.contains('media-type-video')) {
                            return false;
                        }
                        evt.item.style.opacity = '0.8';
                        evt.item.style.zIndex = '999';
                    },
                    onEnd: function(evt) {
                        evt.item.style.opacity = '1';
                        evt.item.style.zIndex = 'auto';
                    },
                    // Ne permettre le déplacement que des images
                    onMove: function(evt) {
                        return !evt.related.classList.contains('media-type-video');
                    }
                });
            }
            
            console.log('Mode réorganisation activé - Overlay complètement désactivé');
        });
    }

    // Annuler la réorganisation
    const cancelReorderBtn = document.getElementById('cancelReorder');
    if (cancelReorderBtn) {
        cancelReorderBtn.addEventListener('click', function() {
            console.log('Bouton Annuler cliqué - Restauration de l\'ordre original'); // Debug
            
            // RESTAURER L'ORDRE ORIGINAL AVANT DE SORTIR DU MODE
            restoreOriginalOrder();
            
            // Puis sortir du mode réorganisation
            exitReorderMode();
        });
    }

    // Enregistrer l'ordre
    const saveOrderBtn = document.getElementById('saveOrder');
    if (saveOrderBtn) {
        saveOrderBtn.addEventListener('click', function() {
            console.log('Bouton Enregistrer cliqué'); // Debug
            
            const mediaItems = document.querySelectorAll('.media-item');
            const imageOrder = [];
            
            // Récupérer uniquement les images dans l'ordre actuel
            mediaItems.forEach(item => {
                if (item.getAttribute('data-type') === 'image') {
                    const imageName = item.getAttribute('data-image');
                    if (imageName) {
                        imageOrder.push(imageName);
                    }
                }
            });
            
            if (imageOrder.length === 0) {
                alert('Aucune image à réorganiser');
                return;
            }
            
            console.log('Ordre des images à sauvegarder:', imageOrder);
            
            // Désactiver le bouton et afficher le loader
            const saveBtn = document.getElementById('saveOrder');
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sauvegarde...';
            
            // Envoyer la requête AJAX
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    reorder: 'true',
                    imageOrder: imageOrder
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Afficher le message de succès
                        $('#ajax-messages').html(`
                            <div class="p-4 border-l-4 border-green-400 bg-green-50 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-green-700">${response.message}</p>
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        // Réactiver le bouton immédiatement
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalText;
                        
                        // Sortir du mode réorganisation après un délai
                        setTimeout(function() {
                            exitReorderMode();
                        }, 2000);
                        
                    } else {
                        // Afficher le message d'erreur
                        $('#ajax-messages').html(`
                            <div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700">${response.message}</p>
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        // Réactiver le bouton
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalText;
                    }
                    
                    // Faire défiler vers le message
                    $('html, body').animate({
                        scrollTop: $('#ajax-messages').offset().top - 100
                    }, 500);
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    let errorMessage = 'Erreur lors de la sauvegarde de l\'ordre.';
                    
                    if (xhr.status === 0) {
                        errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Erreur serveur. Veuillez réessayer plus tard.';
                    } else if (xhr.responseText) {
                        errorMessage = 'Erreur: ' + xhr.responseText.substring(0, 100) + '...';
                    }
                    
                    $('#ajax-messages').html(`
                        <div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">${errorMessage}</p>
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs text-red-600">Détails techniques</summary>
                                        <pre class="text-xs mt-1 bg-red-100 p-2 rounded">${xhr.responseText || 'Aucune réponse du serveur'}</pre>
                                    </details>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    // Réactiver le bouton
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            });
        });
    }
    
    console.log('Événements de réorganisation attachés avec succès');
});

// Fonction pour sauvegarder l'ordre original des éléments
function saveOriginalOrder() {
    originalOrder = [];
    const mediaItems = document.querySelectorAll('.media-item');
    
    mediaItems.forEach(item => {
        originalOrder.push({
            element: item.cloneNode(true), // Clone complet de l'élément
            dataImage: item.getAttribute('data-image'),
            dataType: item.getAttribute('data-type')
        });
    });
    
    console.log('Ordre original sauvegardé:', originalOrder.length, 'éléments');
}

// Fonction pour restaurer l'ordre original
function restoreOriginalOrder() {
    if (originalOrder.length === 0) {
        console.log('Aucun ordre original à restaurer');
        return;
    }
    
    const mediaGallery = document.getElementById('mediaGallery');
    if (!mediaGallery) {
        console.log('Conteneur mediaGallery non trouvé');
        return;
    }
    
    console.log('Restauration de l\'ordre original avec', originalOrder.length, 'éléments');
    
    // Vider le conteneur
    mediaGallery.innerHTML = '';
    
    // Restaurer les éléments dans leur ordre original
    originalOrder.forEach(item => {
        const restoredElement = item.element.cloneNode(true);
        mediaGallery.appendChild(restoredElement);
    });
    
    // Réattacher les événements après la restauration
    reattachEvents();
    
    console.log('Ordre original restauré avec succès');
}

// Fonction pour réattacher les événements après restauration
function reattachEvents() {
    // Réattacher les événements de modal vidéo
    $('button[onclick^="openVideoModal"]').each(function() {
        const onclick = $(this).attr('onclick');
        if (onclick) {
            $(this).off('click').on('click', function() {
                eval(onclick);
            });
        }
    });
    
    // Réattacher les événements de sélection
    $('.image-select').off('change').on('change', function() {
        updateSelectionCount();
    });
    
    console.log('Événements réattachés après restauration');
}

function exitReorderMode() {
    reorderMode = false;
    
    // Enlever la classe reorder-mode
    const mediaGallery = document.getElementById('mediaGallery');
    if (mediaGallery) {
        mediaGallery.classList.remove('reorder-mode');
    }
    
    // Détruire l'instance Sortable
    if (sortableInstance) {
        sortableInstance.destroy();
        sortableInstance = null;
    }
    
    // RÉACTIVER TOUS LES OVERLAYS ET BOUTONS
    $('.image-container').each(function() {
        const overlay = $(this).find('.bg-black');
        const actions = $(this).find('.image-actions');
        const links = $(this).find('a');
        const buttons = $(this).find('button');
        
        // Réactiver l'overlay
        overlay.css({
            'pointer-events': '',
            'display': '',
            'opacity': ''
        });
        
        // Réactiver les actions
        actions.css({
            'pointer-events': '',
            'display': '',
            'visibility': ''
        });
        
        // Réactiver tous les liens et boutons
        links.css('pointer-events', '');
        buttons.css('pointer-events', '');
    });
    
    // Remettre l'interface normale
    $('#category-actions').removeClass('hidden');
    $('#reorder-actions').addClass('hidden');
    $('#reorderInstructions').addClass('hidden');
    $('.drag-handle').addClass('hidden');
    $('#media-filters').removeClass('hidden');
    
    // Réactiver les autres boutons
    $('button[onclick="toggleSelectionMode()"]').prop('disabled', false);
    
    // Vider la sauvegarde de l'ordre original
    originalOrder = [];
    
    console.log('Mode réorganisation désactivé - Overlay réactivé - Ordre original vidé');
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
    background-color: #e5e7eb;
    border: 2px dashed #9ca3af;
}

.sortable-chosen {
    transform: rotate(2deg);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    z-index: 999;
}

.sortable-drag {
    opacity: 0.8;
    transform: rotate(5deg);
}

/* Styles pour le mode sélection */
.image-card.selection-mode {
    cursor: pointer;
    transition: all 0.2s ease;
}

.image-card.selection-mode:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #3b82f6;
}

.image-card.selected {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
    background-color: #eff6ff !important;
}

/* Styles pour le mode réorganisation - COMPLÈTEMENT DÉSACTIVER L'OVERLAY */
.reorder-mode .image-container .group:hover .bg-black,
.reorder-mode .image-container .bg-black {
    background-color: transparent !important;
    opacity: 0 !important;
    pointer-events: none !important;
    display: none !important;
}

.reorder-mode .image-actions {
    display: none !important;
    pointer-events: none !important;
    visibility: hidden !important;
}

.reorder-mode .image-container {
    cursor: move !important;
}

.reorder-mode .image-container:hover {
    cursor: move !important;
}

/* Forcer la désactivation de tous les liens et boutons d'action en mode réorganisation */
.reorder-mode .image-container a,
.reorder-mode .image-container button:not(.drag-handle button) {
    pointer-events: none !important;
    display: none !important;
}

/* Style pour les poignées de drag */
.drag-handle {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: 1px solid #1e40af;
    margin-bottom: 2px;
    user-select: none;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.drag-handle:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Filtres de média */
.media-filter-btn.active {
    background-color: #3b82f6 !important;
    color: white !important;
    border-color: #2563eb !important;
}

/* Animation pour les éléments filtrés */
.image-container {
    transition: all 0.3s ease;
}

.image-container[style*="display: none"] {
    opacity: 0;
    transform: scale(0.8);
}
</style>