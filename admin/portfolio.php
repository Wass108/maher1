<?php
// Inclure les fichiers nécessaires
include "../connect/connect.php";
include "admin_header_buffered.php";

// Fonction pour convertir les valeurs PHP (ex: "2M") en bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}

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

// Messages de feedback
$message = '';
$error = '';

// Traitement du téléchargement d'images et vidéos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $category = $_POST['category'] ?? '';
    
    if (!in_array($category, $categories)) {
        $_SESSION['error'] = "Catégorie invalide.";
    } else {
        // Vérifier les limites PHP
        $maxFileUploads = (int)ini_get('max_file_uploads');
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        
        // Compter le nombre de fichiers reçus
        $fileCount = 0;
        if (isset($_FILES['images']['tmp_name']) && is_array($_FILES['images']['tmp_name'])) {
            $fileCount = count($_FILES['images']['tmp_name']);
        }
        
        // Vérification des limites
        if ($fileCount > $maxFileUploads) {
            $_SESSION['error'] = "Trop de fichiers sélectionnés ! Maximum autorisé : $maxFileUploads fichiers à la fois. Vous avez sélectionné $fileCount fichiers.";
        } else {
            $imageCategoryDir = $portfolioMainDir . $category . "/";
            $videoCategoryDir = $portfolioVideoDir . $category . "/";
            
            // Créer les dossiers s'ils n'existent pas
            if (!is_dir($imageCategoryDir)) {
                mkdir($imageCategoryDir, 0777, true);
            }
            if (!is_dir($videoCategoryDir)) {
                mkdir($videoCategoryDir, 0777, true);
            }
            
            $imageUploadCount = 0;
            $videoUploadCount = 0;
            $errorCount = 0;
            $errorDetails = [];
            
            // Traiter chaque fichier
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['images']['name'][$key];
                    $fileType = $_FILES['images']['type'][$key];
                    $fileSize = $_FILES['images']['size'][$key];
                    
                    // Détecter le type de fichier par extension aussi (pour plus de fiabilité)
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'ogv'];
                    
                    $isImage = (strpos($fileType, 'image/') === 0) || in_array($extension, $imageExtensions);
                    $isVideo = (strpos($fileType, 'video/') === 0) || in_array($extension, $videoExtensions);
                    
                    if ($isImage) {
                        // Générer un nom unique basé sur le timestamp pour les images
                        $newFileName = time() . '_' . $key . '.' . $extension;
                        $destination = $imageCategoryDir . $newFileName;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $imageUploadCount++;
                        } else {
                            $errorCount++;
                            $errorDetails[] = "Impossible de sauvegarder l'image: $fileName";
                        }
                    } elseif ($isVideo) {
                        // Générer un nom unique basé sur le timestamp pour les vidéos
                        $newFileName = time() . '_' . $key . '.' . $extension;
                        $destination = $videoCategoryDir . $newFileName;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $videoUploadCount++;
                        } else {
                            $errorCount++;
                            $errorDetails[] = "Impossible de sauvegarder la vidéo: $fileName";
                        }
                    } else {
                        $errorCount++;
                        $errorDetails[] = "Format non supporté: $fileName";
                    }
                } else {
                    $errorCount++;
                    $fileName = $_FILES['images']['name'][$key];
                    
                    // Messages d'erreur détaillés selon le code d'erreur
                    switch ($_FILES['images']['error'][$key]) {
                        case UPLOAD_ERR_INI_SIZE:
                            $errorDetails[] = "$fileName : Fichier trop volumineux (limite serveur: $uploadMaxFilesize)";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errorDetails[] = "$fileName : Fichier trop volumineux (limite formulaire)";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorDetails[] = "$fileName : Upload incomplet";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorDetails[] = "$fileName : Aucun fichier reçu";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorDetails[] = "$fileName : Dossier temporaire manquant";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorDetails[] = "$fileName : Impossible d'écrire sur le disque";
                            break;
                        default:
                            $errorDetails[] = "$fileName : Erreur inconnue";
                    }
                }
            }
            
            // Messages de feedback stockés en session
            $messages = [];
            if ($imageUploadCount > 0) {
                $messages[] = "$imageUploadCount image(s) téléchargée(s)";
            }
            if ($videoUploadCount > 0) {
                $messages[] = "$videoUploadCount vidéo(s) téléchargée(s)";
            }
            
            if (!empty($messages)) {
                $_SESSION['message'] = implode(' et ', $messages) . " avec succès dans la catégorie $category.";
            }
            if ($errorCount > 0) {
                $errorSummary = "$errorCount fichier(s) n'ont pas pu être téléchargés";
                if (!empty($errorDetails)) {
                    $errorSummary .= " :\n• " . implode("\n• ", array_slice($errorDetails, 0, 5));
                    if (count($errorDetails) > 5) {
                        $errorSummary .= "\n• ... et " . (count($errorDetails) - 5) . " autre(s) erreur(s)";
                    }
                }
                $_SESSION['error'] = $errorSummary;
            }
        }
    }
    
    // Rediriger pour éviter la resoumission du formulaire
    header("Location: portfolio.php");
    exit;
}

// Traitement de la suppression d'une image
if (isset($_GET['delete']) && isset($_GET['category'])) {
    $imageToDelete = $_GET['delete'];
    $category = $_GET['category'];
    
    if (in_array($category, $categories)) {
        $imagePath = $portfolioMainDir . $category . "/" . $imageToDelete;
        
        if (file_exists($imagePath) && unlink($imagePath)) {
            $_SESSION['message'] = "Image supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Impossible de supprimer l'image.";
        }
    }
    
    // Rediriger pour éviter la resoumission
    header("Location: portfolio.php");
    exit;
}

// Récupérer toutes les images et vidéos par catégorie
$portfolioImages = [];
$portfolioVideos = [];
$portfolioMedia = []; // Nouveau tableau pour tout regrouper

foreach ($categories as $category) {
    // Images
    $categoryPath = $portfolioMainDir . $category . "/";
    $portfolioImages[$category] = [];
    
    if (is_dir($categoryPath)) {
        $images = glob($categoryPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        foreach ($images as $image) {
            $portfolioImages[$category][] = [
                'path' => $image,
                'name' => basename($image),
                'size' => filesize($image),
                'url' => $image,
                'type' => 'image'
            ];
        }
    }
    
    // Vidéos
    $categoryVideoPath = $portfolioVideoDir . $category . "/";
    $portfolioVideos[$category] = [];
    
    if (is_dir($categoryVideoPath)) {
        $videos = glob($categoryVideoPath . "*.{mp4,avi,mov,wmv,flv,webm,mkv,3gp,ogv}", GLOB_BRACE);
        foreach ($videos as $video) {
            $portfolioVideos[$category][] = [
                'path' => $video,
                'name' => basename($video),
                'size' => filesize($video),
                'url' => $video,
                'type' => 'video'
            ];
        }
    }
    
    // Combiner images et vidéos pour cette catégorie
    $portfolioMedia[$category] = array_merge($portfolioImages[$category], $portfolioVideos[$category]);
}

ob_end_flush();
?>
<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion du Portfolio</h1>
                <p class="text-gray-600 mt-1"><?php echo count($categories); ?> catégorie(s) trouvée(s)</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Portfolio</span>
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
    <?php if (empty($categories)): ?>
        <div class="p-4 border-l-4 border-yellow-400 bg-yellow-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Aucune catégorie trouvée</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Aucun dossier de catégorie n'a été trouvé dans le répertoire portfolio.</p>
                        <p class="mt-1">Vous pouvez créer une nouvelle catégorie en utilisant le bouton "Gérer les catégories" ci-dessous.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($categories)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-folder text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Catégories</p>
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
                        <p class="text-sm font-medium text-gray-600">Total Images</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $totalImages = 0;
                            foreach ($portfolioImages as $images) {
                                $totalImages += count($images);
                            }
                            echo $totalImages;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Moyenne par catégorie</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo count($categories) > 0 ? round($totalImages / count($categories), 1) : 0; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-t-lg">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-upload text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Ajouter des images et vidéos au portfolio</h3>
                        <p class="text-blue-100 text-sm">Téléchargez plusieurs images et vidéos dans une catégorie existante</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="manage_portfolio_categories.php" 
                       class="inline-flex items-center px-4 py-2 border border-white border-opacity-30 text-sm font-medium rounded-md text-white bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors duration-200">
                        <i class="fas fa-cog mr-2"></i>
                        Gérer les catégories
                    </a>
                    <a href="manage_portfolio_videos.php" 
                       class="inline-flex items-center px-4 py-2 border border-white border-opacity-30 text-sm font-medium rounded-md text-white bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors duration-200">
                        <i class="fas fa-video mr-2"></i>
                        Gérer les vidéos
                    </a>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($categories)): ?>
                <form method="post" enctype="multipart/form-data" id="upload-form" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                Catégorie <span class="text-red-500">*</span>
                            </label>
                            <select id="category" 
                                    name="category" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="images" class="block text-sm font-medium text-gray-700 mb-2">
                                Images et vidéos <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="images" id="drop-zone" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                    <div id="drop-content" class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                            <i class="fas fa-cloud-upload-alt text-2xl text-blue-600"></i>
                                        </div>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez vos fichiers
                                        </p>
                                        <p class="text-xs text-gray-500">Images (PNG, JPG, JPEG, GIF) ou Vidéos (MP4, AVI, MOV, etc.)</p>
                                    </div>
                                    <div id="preview-container" class="hidden w-full p-4 max-h-64 overflow-y-auto border-t border-gray-200">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="preview-grid">
                                        </div>
                                        <div class="mt-4 flex justify-between items-center border-t border-gray-200 pt-4">
                                            <button type="button" onclick="clearAllFiles(event)" 
                                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                                <i class="fas fa-trash mr-1"></i>
                                                Tout supprimer
                                            </button>
                                            <span id="preview-file-count" class="text-xs text-gray-500"></span>
                                        </div>
                                    </div>
                                    <input id="images" name="images[]" type="file" class="hidden" multiple accept="image/*,video/*" required>
                                </label>
                            </div>                    
                            <div id="file-info" class="hidden mt-4 p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span id="file-count" class="text-sm text-blue-700 font-medium"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="upload-progress" class="hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Téléchargement en cours...</span>
                            <span id="progress-percent" class="text-sm font-medium text-gray-700">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" id="upload-btn"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <i class="fas fa-upload mr-2"></i>
                            Télécharger les images
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-folder-plus text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune catégorie disponible</h3>
                    <p class="text-gray-600 mb-4">Créez d'abord une catégorie pour pouvoir ajouter des images.</p>
                    <a href="manage_portfolio_categories.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Créer une catégorie
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php foreach ($categories as $category): ?>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <?php echo htmlspecialchars($category); ?>
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?php echo count($portfolioImages[$category]); ?> image(s)
                            </span>
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <?php echo count($portfolioVideos[$category]); ?> vidéo(s)
                            </span>
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php 
                            $categorySize = 0;
                            foreach ($portfolioMedia[$category] as $media) {
                                $categorySize += $media['size'];
                            }
                            echo 'Taille totale: ' . round($categorySize / 1024 / 1024, 2) . ' MB • ' . count($portfolioMedia[$category]) . ' média(s)';
                            ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Filtres de type de média -->
                        <div class="flex items-center space-x-1 mr-4">
                            <button type="button" onclick="filterMedia('all', '<?php echo htmlspecialchars($category); ?>')" 
                                    class="media-filter-btn active inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-th mr-1"></i>
                                Tout
                            </button>
                            <button type="button" onclick="filterMedia('images', '<?php echo htmlspecialchars($category); ?>')" 
                                    class="media-filter-btn inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-images mr-1"></i>
                                Photos
                            </button>
                            <button type="button" onclick="filterMedia('videos', '<?php echo htmlspecialchars($category); ?>')" 
                                    class="media-filter-btn inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-video mr-1"></i>
                                Vidéos
                            </button>
                        </div>
                        
                        <a href="manage_category.php?category=<?php echo urlencode($category); ?>" 
                           class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-cog mr-1"></i>
                            Gérer
                        </a>
                        <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 transition-colors duration-200">
                            <i class="fas fa-plus mr-1"></i>
                            Ajouter
                        </a>
                        <button type="button" onclick="emptyCategory('<?php echo htmlspecialchars($category); ?>')"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200">
                            <i class="fas fa-broom mr-1"></i>
                            Vider
                        </button>
                        <button type="button" onclick="deleteAllCategory('<?php echo htmlspecialchars($category); ?>')"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                            <i class="fas fa-trash mr-1"></i>
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <?php if (!empty($portfolioMedia[$category])): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" data-category="<?php echo htmlspecialchars($category); ?>">
                        <!-- Images -->
                        <?php foreach ($portfolioImages[$category] as $image): ?>
                            <div class="media-item media-type-image group relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="aspect-w-1 aspect-h-1 bg-gray-100">
                                    <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                         alt="Image" 
                                         class="w-full h-32 object-cover">
                                </div>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2">
                                        <a href="<?php echo htmlspecialchars($image['url']); ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200"
                                           title="Voir l'image">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                        <a href="?delete=<?php echo urlencode($image['name']); ?>&category=<?php echo urlencode($category); ?>" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-red-600 hover:bg-red-700 transition-colors duration-200"
                                           title="Supprimer l'image">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="p-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-image mr-1"></i>
                                            Image
                                        </span>
                                    </div>
                                    <h6 class="text-xs font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($image['name']); ?>">
                                        <?php echo htmlspecialchars(substr($image['name'], 0, 15) . (strlen($image['name']) > 15 ? '...' : '')); ?>
                                    </h6>
                                    <p class="text-xs text-gray-500"><?php echo round($image['size'] / 1024, 1); ?> KB</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Vidéos -->
                        <?php foreach ($portfolioVideos[$category] as $video): ?>
                            <div class="media-item media-type-video group relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="aspect-w-16 aspect-h-12 bg-gray-100 relative">
                                    <video class="w-full h-32 object-cover" preload="metadata" muted>
                                        <source src="<?php echo $video['url']; ?>" type="video/<?php echo pathinfo($video['url'], PATHINFO_EXTENSION); ?>">
                                    </video>
                                    <!-- Overlay de lecture -->
                                    <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-200 cursor-pointer" onclick="openVideoModal('<?php echo $video['url']; ?>')">
                                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <i class="fas fa-play text-white text-sm"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2">
                                        <button onclick="openVideoModal('<?php echo $video['url']; ?>')" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-purple-600 hover:bg-purple-700 transition-colors duration-200"
                                           title="Lire la vidéo">
                                            <i class="fas fa-play text-sm"></i>
                                        </button>
                                        <a href="?delete=<?php echo urlencode($video['name']); ?>&category=<?php echo urlencode($category); ?>&type=video" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vidéo?');" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-red-600 hover:bg-red-700 transition-colors duration-200"
                                           title="Supprimer la vidéo">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="p-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="fas fa-video mr-1"></i>
                                            Vidéo
                                        </span>
                                    </div>
                                    <h6 class="text-xs font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($video['name']); ?>">
                                        <?php echo htmlspecialchars(substr($video['name'], 0, 15) . (strlen($video['name']) > 15 ? '...' : '')); ?>
                                    </h6>
                                    <p class="text-xs text-gray-500"><?php echo round($video['size'] / 1024 / 1024, 1); ?> MB</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-photo-video text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun média</h3>
                        <p class="text-gray-600 mb-4">Cette catégorie ne contient pas encore d'images ou de vidéos.</p>
                        <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter des médias
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
<script>
function deleteAllCategory(category) {
    if (confirm('Êtes-vous sûr de vouloir supprimer TOUTES les images de la catégorie "' + category + '" ? Cette action est irréversible.')) {
        window.location.href = 'delete_portfolio_category.php?category=' + encodeURIComponent(category);
    }
}

function emptyCategory(category) {
    if (confirm('Êtes-vous sûr de vouloir vider la catégorie "' + category + '" ? Toutes les images seront supprimées, mais la catégorie restera.')) {
        window.location.href = 'empty_portfolio_category.php?category=' + encodeURIComponent(category);
    }
}

// Gestion avancée du drag and drop pour l'upload avec prévisualisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script drag & drop initialisé'); // Debug
    
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('images');
    const dropContent = document.getElementById('drop-content');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const fileInfo = document.getElementById('file-info');
    const fileCount = document.getElementById('file-count');
    const uploadForm = document.getElementById('upload-form');
    const uploadBtn = document.getElementById('upload-btn');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressPercent = document.getElementById('progress-percent');

    // Vérification que tous les éléments existent
    if (!dropZone || !fileInput || !dropContent || !previewContainer || !previewGrid || 
        !fileInfo || !fileCount || !uploadForm || !uploadBtn || !uploadProgress || 
        !progressBar || !progressPercent) {
        console.error('Certains éléments requis pour le drag & drop sont manquants');
        console.log('dropZone:', dropZone);
        console.log('fileInput:', fileInput);
        console.log('dropContent:', dropContent);
        return;
    }

    console.log('Tous les éléments trouvés, initialisation du drag & drop'); // Debug

    // Gestion du drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    dropZone.addEventListener('drop', handleDrop, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        console.log('Zone de drop activée'); // Debug
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight() {
        console.log('Zone de drop désactivée'); // Debug
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }

    function handleDrop(e) {
        console.log('Fichiers déposés'); // Debug
        const dt = e.dataTransfer;
        const files = dt.files;
        
        console.log('Nombre de fichiers:', files.length); // Debug
        
        fileInput.files = files;
        handleFiles(files);
    }

    // Gestion du changement de fichiers
    fileInput.addEventListener('change', function(e) {
        console.log('Fichiers sélectionnés via input'); // Debug
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        console.log('Traitement des fichiers:', files.length); // Debug
        
        if (files.length === 0) {
            showDropContent();
            return;
        }

        // Vérifier la limite de fichiers côté client
        const maxFiles = <?php echo (int)ini_get('max_file_uploads'); ?>;
        if (files.length > maxFiles) {
            alert(`Trop de fichiers sélectionnés ! Maximum autorisé : ${maxFiles} fichiers à la fois.\nVous avez sélectionné ${files.length} fichiers.\n\nVeuillez réduire le nombre de fichiers et réessayer.`);
            fileInput.value = '';
            showDropContent();
            return;
        }

        // Filtrer les fichiers médias (images ET vidéos)
        const mediaFiles = Array.from(files).filter(file => 
            file.type.startsWith('image/') || file.type.startsWith('video/')
        );
        
        console.log('Fichiers média valides:', mediaFiles.length); // Debug
        
        if (mediaFiles.length === 0) {
            alert('Veuillez sélectionner uniquement des fichiers image ou vidéo');
            fileInput.value = '';
            showDropContent();
            return;
        }

        // Vérifier la taille totale approximative (limite post_max_size)
        const postMaxSizeBytes = <?php echo return_bytes(ini_get('post_max_size')); ?>;
        const uploadMaxFilesizeBytes = <?php echo return_bytes(ini_get('upload_max_filesize')); ?>;
        
        let totalSize = 0;
        let oversizedFiles = [];
        
        for (let file of mediaFiles) {
            totalSize += file.size;
            if (file.size > uploadMaxFilesizeBytes) {
                oversizedFiles.push(file.name);
            }
        }
        
        if (oversizedFiles.length > 0) {
            alert(`Les fichiers suivants sont trop volumineux :\n• ${oversizedFiles.join('\n• ')}\n\nTaille maximum par fichier : <?php echo ini_get('upload_max_filesize'); ?>`);
            fileInput.value = '';
            showDropContent();
            return;
        }
        
        if (totalSize > postMaxSizeBytes) {
            const totalSizeMB = Math.round(totalSize / 1024 / 1024);
            const maxSizeMB = Math.round(postMaxSizeBytes / 1024 / 1024);
            alert(`La taille totale des fichiers (${totalSizeMB} MB) dépasse la limite autorisée (${maxSizeMB} MB).\n\nVeuillez réduire le nombre de fichiers ou choisir des fichiers plus petits.`);
            fileInput.value = '';
            showDropContent();
            return;
        }

        // Compter les types de fichiers
        const imageFiles = mediaFiles.filter(file => file.type.startsWith('image/'));
        const videoFiles = mediaFiles.filter(file => file.type.startsWith('video/'));
        
        let countText = '';
        if (imageFiles.length > 0 && videoFiles.length > 0) {
            countText = `${imageFiles.length} image(s) et ${videoFiles.length} vidéo(s) sélectionnée(s)`;
        } else if (imageFiles.length > 0) {
            countText = `${imageFiles.length} image(s) sélectionnée(s)`;
        } else {
            countText = `${videoFiles.length} vidéo(s) sélectionnée(s)`;
        }
        
        // Ajouter la taille totale
        const totalSizeMB = Math.round(totalSize / 1024 / 1024 * 100) / 100;
        countText += ` (${totalSizeMB} MB total)`;

        // Afficher les informations
        fileCount.textContent = countText;
        fileInfo.classList.remove('hidden');

        // Générer les aperçus
        previewGrid.innerHTML = '';
        dropContent.classList.add('hidden');
        previewContainer.classList.remove('hidden');

        mediaFiles.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                // Aperçu pour image
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'relative group';
                    previewDiv.innerHTML = `
                        <div class="aspect-w-1 aspect-h-1 bg-gray-100 rounded-lg overflow-hidden">
                            <img src="${e.target.result}" class="w-full h-20 object-cover">
                        </div>
                        <div class="absolute top-1 right-1">
                            <button type="button" onclick="removeFile(${index}, event)" 
                                    class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-600 mt-1 truncate" title="${file.name}">${file.name}</p>
                    `;
                    previewGrid.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                // Aperçu pour vidéo
                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group';
                previewDiv.innerHTML = `
                    <div class="aspect-w-1 aspect-h-1 bg-gray-800 rounded-lg overflow-hidden flex items-center justify-center">
                        <i class="fas fa-video text-white text-2xl"></i>
                    </div>
                    <div class="absolute top-1 right-1">
                        <button type="button" onclick="removeFile(${index}, event)" 
                                class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1 truncate" title="${file.name}">${file.name}</p>
                `;
                previewGrid.appendChild(previewDiv);
            }
        });

        // Activer le bouton d'upload
        uploadBtn.disabled = false;
    }

    function showDropContent() {
        dropContent.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        fileInfo.classList.add('hidden');
        uploadBtn.disabled = true;
    }

    // Fonction pour supprimer un fichier (accessible globalement)
    window.removeFile = function(index, event) {
        // Empêcher la propagation de l'événement pour éviter d'ouvrir la sélection de fichiers
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        console.log('Suppression du fichier à l\'index:', index); // Debug
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files);
        
        files.forEach((file, i) => {
            if (i !== index) {
                dt.items.add(file);
            }
        });
        
        fileInput.files = dt.files;
        handleFiles(fileInput.files);
    };

    // Fonction pour supprimer tous les fichiers
    window.clearAllFiles = function(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        if (confirm('Êtes-vous sûr de vouloir supprimer tous les fichiers sélectionnés ?')) {
            fileInput.value = '';
            showDropContent();
        }
    };

    // Gestion de la soumission du formulaire avec progress
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('Soumission du formulaire'); // Debug
        
        if (fileInput.files.length === 0) {
            alert('Veuillez sélectionner au moins une image');
            return;
        }

        const categorySelect = document.getElementById('category');
        if (!categorySelect || !categorySelect.value) {
            alert('Veuillez sélectionner une catégorie');
            return;
        }

        // Préparer l'upload avec progress
        const formData = new FormData(uploadForm);
        
        // Afficher la barre de progression
        uploadProgress.classList.remove('hidden');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Téléchargement...';

        // Simuler un upload avec XMLHttpRequest pour avoir le contrôle du progress
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressPercent.textContent = Math.round(percentComplete) + '%';
            }
        });

        xhr.onload = function() {
            console.log('Upload terminé, status:', xhr.status); // Debug
            if (xhr.status === 200) {
                // Redirection sera gérée par PHP
                window.location.reload();
            } else {
                alert('Erreur lors du téléchargement');
                resetUploadState();
            }
        };

        xhr.onerror = function() {
            console.error('Erreur réseau'); // Debug
            alert('Erreur réseau lors du téléchargement');
            resetUploadState();
        };

        xhr.open('POST', uploadForm.action || window.location.href);
        xhr.send(formData);
    });

    function resetUploadState() {
        uploadProgress.classList.add('hidden');
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Télécharger les images';
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
    }

    // Initialiser l'état
    showDropContent();
    
    console.log('Drag & drop complètement initialisé'); // Debug
});

// Fonctions de filtrage des médias (copié de manage_images.php)
function filterMedia(type, category) {
    // Trouver le conteneur de cette catégorie
    const categoryContainer = document.querySelector(`[data-category="${category}"]`);
    if (!categoryContainer) return;
    
    // Réinitialiser les boutons de filtre pour cette catégorie
    const categorySection = categoryContainer.closest('.bg-white');
    const filterButtons = categorySection.querySelectorAll('.media-filter-btn');
    
    filterButtons.forEach(btn => {
        btn.classList.remove('active', 'bg-blue-500', 'text-white');
        btn.classList.add('text-gray-700', 'bg-white');
    });
    
    // Activer le bouton sélectionné
    const activeBtn = categorySection.querySelector(`button[onclick*="'${type}'"]`);
    if (activeBtn) {
        activeBtn.classList.add('active', 'bg-blue-500', 'text-white');
        activeBtn.classList.remove('text-gray-700', 'bg-white');
    }
    
    // Filtrer les éléments
    const mediaItems = categoryContainer.querySelectorAll('.media-item');
    
    mediaItems.forEach(item => {
        if (type === 'all') {
            item.style.display = 'block';
        } else if (type === 'images' && item.classList.contains('media-type-image')) {
            item.style.display = 'block';
        } else if (type === 'videos' && item.classList.contains('media-type-video')) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Fonction pour ouvrir la modal vidéo (copié de manage_images.php)
function openVideoModal(videoPath) {
    // Créer la modal si elle n'existe pas
    let modal = document.getElementById('videoModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'videoModal';
        modal.style.cssText = 'display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center;';
        
        modal.innerHTML = `
            <div style="position: relative; max-width: 90%; max-height: 90%;">
                <button onclick="closeVideoModal()" style="position: absolute; top: -40px; right: 0; background: #3b82f6; color: white; border: none; font-size: 24px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; font-weight: 700;">&times;</button>
                <video id="modalVideo" controls style="width: 100%; height: auto; max-height: 80vh;">
                    <source src="" type="video/mp4">
                    Votre navigateur ne supporte pas la lecture de vidéos.
                </video>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fermer en cliquant à l'extérieur
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeVideoModal();
            }
        });
    }
    
    const video = document.getElementById('modalVideo');
    const source = video.querySelector('source');
    
    source.src = videoPath;
    const extension = videoPath.split('.').pop().toLowerCase();
    
    // Déterminer le bon type MIME
    let mimeType = 'video/mp4';
    switch(extension) {
        case 'mp4': mimeType = 'video/mp4'; break;
        case 'webm': mimeType = 'video/webm'; break;
        case 'mov': mimeType = 'video/quicktime'; break;
        case 'avi': mimeType = 'video/x-msvideo'; break;
        case 'wmv': mimeType = 'video/x-ms-wmv'; break;
        case 'flv': mimeType = 'video/x-flv'; break;
        case 'mkv': mimeType = 'video/x-matroska'; break;
    }
    source.type = mimeType;
    
    video.load();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    video.addEventListener('loadeddata', function() {
        video.play().catch(e => console.log('Autoplay bloqué:', e));
    }, { once: true });
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const video = document.getElementById('modalVideo');
    
    if (video) {
        video.pause();
        video.currentTime = 0;
    }
    if (modal) {
        modal.style.display = 'none';
    }
    document.body.style.overflow = 'auto';
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeVideoModal();
    }
});
</script>

<?php include "admin_footer.php"; ?>