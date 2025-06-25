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

// Traitement du téléchargement d'images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $category = $_POST['category'] ?? '';
    
    if (!in_array($category, $categories)) {
        $_SESSION['error'] = "Catégorie invalide.";
    } else {
        $categoryDir = $portfolioMainDir . $category . "/";
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($categoryDir)) {
            mkdir($categoryDir, 0777, true);
        }
        
        $uploadCount = 0;
        $errorCount = 0;
        
        // Traiter chaque fichier
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['images']['name'][$key];
                $fileType = $_FILES['images']['type'][$key];
                
                // Vérifier si c'est une image
                if (strpos($fileType, 'image/') === 0) {
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    
                    // Générer un nom unique basé sur le timestamp
                    $newFileName = time() . '_' . $key . '.' . $extension;
                    $destination = $categoryDir . $newFileName;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $uploadCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
        }
        
        // Messages de feedback stockés en session
        if ($uploadCount > 0) {
            $_SESSION['message'] = "$uploadCount image(s) téléchargée(s) avec succès dans la catégorie $category.";
        }
        if ($errorCount > 0) {
            $_SESSION['error'] = "$errorCount image(s) n'ont pas pu être téléchargées.";
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

// Récupérer toutes les images par catégorie
$portfolioImages = [];
foreach ($categories as $category) {
    $categoryPath = $portfolioMainDir . $category . "/";
    $portfolioImages[$category] = [];
    
    if (is_dir($categoryPath)) {
        $images = glob($categoryPath . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
        foreach ($images as $image) {
            $portfolioImages[$category][] = [
                'path' => $image,
                'name' => basename($image),
                'size' => filesize($image),
                'url' => $image // Garder le chemin relatif complet depuis admin/
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
                        <h3 class="text-lg font-semibold">Ajouter des images au portfolio</h3>
                        <p class="text-blue-100 text-sm">Téléchargez plusieurs images dans une catégorie existante</p>
                    </div>
                </div>
                <a href="manage_portfolio_categories.php" 
                   class="inline-flex items-center px-4 py-2 border border-white border-opacity-30 text-sm font-medium rounded-md text-white bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors duration-200">
                    <i class="fas fa-cog mr-2"></i>
                    Gérer les catégories
                </a>
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
                                Images <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="images" id="drop-zone" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                    <div id="drop-content" class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                            <i class="fas fa-cloud-upload-alt text-2xl text-blue-600"></i>
                                        </div>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez vos images
                                        </p>
                                        <p class="text-xs text-gray-500">PNG, JPG, JPEG ou GIF (plusieurs fichiers acceptés)</p>
                                    </div>
                                    <div id="preview-container" class="hidden w-full p-4">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="preview-grid">
                                        </div>
                                    </div>
                                    <input id="images" name="images[]" type="file" class="hidden" multiple accept="image/*" required>
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
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php 
                            $categorySize = 0;
                            foreach ($portfolioImages[$category] as $img) {
                                $categorySize += $img['size'];
                            }
                            echo 'Taille totale: ' . round($categorySize / 1024 / 1024, 2) . ' MB';
                            ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
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
                <?php if (!empty($portfolioImages[$category])): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach ($portfolioImages[$category] as $image): ?>
                            <div class="group relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
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
                                    <h6 class="text-xs font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($image['name']); ?>">
                                        <?php echo htmlspecialchars(substr($image['name'], 0, 15) . (strlen($image['name']) > 15 ? '...' : '')); ?>
                                    </h6>
                                    <p class="text-xs text-gray-500"><?php echo round($image['size'] / 1024, 1); ?> KB</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-images text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune image</h3>
                        <p class="text-gray-600 mb-4">Cette catégorie ne contient pas encore d'images.</p>
                        <a href="add_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter des images
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
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight() {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        fileInput.files = files;
        handleFiles(files);
    }

    // Gestion du changement de fichiers
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length === 0) {
            showDropContent();
            return;
        }

        // Filtrer les fichiers image uniquement
        const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
        
        if (imageFiles.length === 0) {
            alert('Veuillez sélectionner uniquement des fichiers image (PNG, JPG, JPEG, GIF)');
            fileInput.value = '';
            showDropContent();
            return;
        }

        // Afficher les informations
        fileCount.textContent = `${imageFiles.length} image(s) sélectionnée(s)`;
        fileInfo.classList.remove('hidden');

        // Générer les aperçus
        previewGrid.innerHTML = '';
        dropContent.classList.add('hidden');
        previewContainer.classList.remove('hidden');

        imageFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group';
                previewDiv.innerHTML = `
                    <div class="aspect-w-1 aspect-h-1 bg-gray-100 rounded-lg overflow-hidden">
                        <img src="${e.target.result}" class="w-full h-20 object-cover">
                    </div>
                    <div class="absolute top-1 right-1">
                        <button type="button" onclick="removeFile(${index})" 
                                class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1 truncate" title="${file.name}">${file.name}</p>
                `;
                previewGrid.appendChild(previewDiv);
            };
            reader.readAsDataURL(file);
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
    window.removeFile = function(index) {
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

    // Gestion de la soumission du formulaire avec progress
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (fileInput.files.length === 0) {
            alert('Veuillez sélectionner au moins une image');
            return;
        }

        const categorySelect = document.getElementById('category');
        if (!categorySelect.value) {
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
            if (xhr.status === 200) {
                // Redirection sera gérée par PHP
                window.location.reload();
            } else {
                alert('Erreur lors du téléchargement');
                resetUploadState();
            }
        };

        xhr.onerror = function() {
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
});
</script>

<?php include "admin_footer.php"; ?>