<?php
// Inclure les fichiers nécessaires
include "../connect/connect.php";
include "admin_header_buffered.php";

// Récupérer les catégories de portfolio disponibles
$portfolioMainDir = "../videos/portfolio/";
$categories = [];

if (is_dir($portfolioMainDir)) {
    $dirs = scandir($portfolioMainDir);
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir($portfolioMainDir . $dir)) {
            $categories[] = $dir;
        }
    }
}

// Traitement du téléchargement de vidéos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['videos'])) {
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
        foreach ($_FILES['videos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['videos']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['videos']['name'][$key];
                $fileType = $_FILES['videos']['type'][$key];
                
                // Vérifier si c'est une vidéo
                if (strpos($fileType, 'video/') === 0) {
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
            $_SESSION['message'] = "$uploadCount vidéo(s) téléchargée(s) avec succès dans la catégorie $category.";
        }
        if ($errorCount > 0) {
            $_SESSION['error'] = "$errorCount vidéo(s) n'ont pas pu être téléchargées.";
        }
    }
    
    // Rediriger pour éviter la resoumission du formulaire
    header("Location: manage_portfolio_videos.php");
    exit;
}

// Traitement de la suppression d'une vidéo
if (isset($_GET['delete']) && isset($_GET['category'])) {
    $videoToDelete = $_GET['delete'];
    $category = $_GET['category'];
    
    if (in_array($category, $categories)) {
        $videoPath = $portfolioMainDir . $category . "/" . $videoToDelete;
        
        if (file_exists($videoPath) && unlink($videoPath)) {
            $_SESSION['message'] = "Vidéo supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Impossible de supprimer la vidéo.";
        }
    }
    
    // Rediriger pour éviter la resoumission
    header("Location: manage_portfolio_videos.php");
    exit;
}

// Récupérer toutes les vidéos par catégorie
$portfolioVideos = [];
foreach ($categories as $category) {
    $categoryPath = $portfolioMainDir . $category . "/";
    $portfolioVideos[$category] = [];
    
    if (is_dir($categoryPath)) {
        $videos = glob($categoryPath . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
        foreach ($videos as $video) {
            $portfolioVideos[$category][] = [
                'path' => $video,
                'name' => basename($video),
                'size' => filesize($video),
                'url' => $video // Garder le chemin relatif complet depuis admin/
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
                <h1 class="text-2xl font-bold text-gray-900">Gestion des vidéos du Portfolio</h1>
                <p class="text-gray-600 mt-1"><?php echo count($categories); ?> catégorie(s) trouvée(s)</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Vidéos Portfolio</span>
            </nav>
        </div>
    </div>
</div>

<div class="px-6 space-y-6">
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

    <!-- Statistiques -->
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
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-video text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Vidéos</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        $totalVideos = 0;
                        foreach ($portfolioVideos as $videos) {
                            $totalVideos += count($videos);
                        }
                        echo $totalVideos;
                        ?>
                    </p>
                </div>
            </div>
        </div>        
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-hdd text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600">Espace utilisé</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php 
                        $totalSize = 0;
                        foreach ($portfolioVideos as $videos) {
                            foreach ($videos as $video) {
                                $totalSize += $video['size'];
                            }
                        }
                        echo round($totalSize / 1024 / 1024, 1) . ' MB';
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire d'upload -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-t-lg">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-upload text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Ajouter des vidéos au portfolio</h3>
                        <p class="text-purple-100 text-sm">Téléchargez plusieurs vidéos dans une catégorie existante</p>
                    </div>
                </div>
                <a href="portfolio.php" 
                   class="inline-flex items-center px-4 py-2 border border-white border-opacity-30 text-sm font-medium rounded-md text-white bg-white bg-opacity-10 hover:bg-opacity-20 transition-colors duration-200">
                    <i class="fas fa-images mr-2"></i>
                    Gérer les images
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
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="videos" class="block text-sm font-medium text-gray-700 mb-2">
                                Vidéos <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="videos" id="drop-zone" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                                    <div id="drop-content" class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                                            <i class="fas fa-cloud-upload-alt text-2xl text-purple-600"></i>
                                        </div>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez vos vidéos
                                        </p>
                                        <p class="text-xs text-gray-500">MP4, AVI, MOV, WMV, FLV, WEBM, MKV (plusieurs fichiers acceptés)</p>
                                    </div>
                                    <div id="preview-container" class="hidden w-full p-4 max-h-64 overflow-y-auto border-t border-gray-200">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="preview-grid">
                                        </div>
                                    </div>
                                    <input id="videos" name="videos[]" type="file" class="hidden" multiple accept="video/*" required>
                                </label>
                            </div>                    
                            <div id="file-info" class="hidden mt-4 p-4 bg-purple-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-purple-500 mr-2"></i>
                                    <span id="file-count" class="text-sm text-purple-700 font-medium"></span>
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
                            <div id="progress-bar" class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" id="upload-btn"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <i class="fas fa-upload mr-2"></i>
                            Télécharger les vidéos
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-folder-plus text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune catégorie disponible</h3>
                    <p class="text-gray-600 mb-4">Créez d'abord une catégorie dans le portfolio pour pouvoir ajouter des vidéos.</p>
                    <a href="portfolio.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        <i class="fas fa-images mr-2"></i>
                        Gérer le portfolio
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Affichage des vidéos par catégorie -->
    <?php foreach ($categories as $category): ?>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <?php echo htmlspecialchars($category); ?>
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <?php echo count($portfolioVideos[$category]); ?> vidéo(s)
                            </span>
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php 
                            $categorySize = 0;
                            foreach ($portfolioVideos[$category] as $video) {
                                $categorySize += $video['size'];
                            }
                            echo 'Taille totale: ' . round($categorySize / 1024 / 1024, 2) . ' MB';
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <?php if (!empty($portfolioVideos[$category])): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($portfolioVideos[$category] as $video): ?>
                            <div class="group relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="aspect-w-16 aspect-h-9 bg-gray-100">
                                    <video class="w-full h-32 object-cover" controls preload="metadata">
                                        <source src="<?php echo htmlspecialchars($video['url']); ?>" type="video/<?php echo pathinfo($video['url'], PATHINFO_EXTENSION); ?>">
                                        Votre navigateur ne supporte pas la lecture de vidéos.
                                    </video>
                                </div>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2">
                                        <a href="<?php echo htmlspecialchars($video['url']); ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200"
                                           title="Voir la vidéo">
                                            <i class="fas fa-play text-sm"></i>
                                        </a>
                                        <a href="?delete=<?php echo urlencode($video['name']); ?>&category=<?php echo urlencode($category); ?>" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vidéo?');" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-red-600 hover:bg-red-700 transition-colors duration-200"
                                           title="Supprimer la vidéo">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="p-2">
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
                            <i class="fas fa-video text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune vidéo</h3>
                        <p class="text-gray-600 mb-4">Cette catégorie ne contient pas encore de vidéos.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('videos');
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

    if (!dropZone || !fileInput || !dropContent || !previewContainer || !previewGrid || 
        !fileInfo || !fileCount || !uploadForm || !uploadBtn || !uploadProgress || 
        !progressBar || !progressPercent) {
        console.error('Certains éléments requis pour le drag & drop sont manquants');
        return;
    }

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
        dropZone.classList.add('border-purple-500', 'bg-purple-50');
    }

    function unhighlight() {
        dropZone.classList.remove('border-purple-500', 'bg-purple-50');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFiles(files);
    }

    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length === 0) {
            showDropContent();
            return;
        }

        // Filtrer les fichiers vidéo uniquement
        const videoFiles = Array.from(files).filter(file => file.type.startsWith('video/'));
        
        if (videoFiles.length === 0) {
            alert('Veuillez sélectionner uniquement des fichiers vidéo (MP4, AVI, MOV, etc.)');
            fileInput.value = '';
            showDropContent();
            return;
        }

        // Afficher les informations
        fileCount.textContent = `${videoFiles.length} vidéo(s) sélectionnée(s)`;
        fileInfo.classList.remove('hidden');

        // Générer les aperçus
        previewGrid.innerHTML = '';
        dropContent.classList.add('hidden');
        previewContainer.classList.remove('hidden');

        videoFiles.forEach((file, index) => {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative group bg-gray-100 rounded-lg p-4';
            previewDiv.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-video text-purple-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" title="${file.name}">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB</p>
                    </div>
                    <button type="button" onclick="removeFile(${index}, event)" 
                            class="w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            previewGrid.appendChild(previewDiv);
        });

        uploadBtn.disabled = false;
    }

    function showDropContent() {
        dropContent.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        fileInfo.classList.add('hidden');
        uploadBtn.disabled = true;
    }

    window.removeFile = function(index, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
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

    // Gestion de la soumission du formulaire
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (fileInput.files.length === 0) {
            alert('Veuillez sélectionner au moins une vidéo');
            return;
        }

        const categorySelect = document.getElementById('category');
        if (!categorySelect || !categorySelect.value) {
            alert('Veuillez sélectionner une catégorie');
            return;
        }

        const formData = new FormData(uploadForm);
        
        uploadProgress.classList.remove('hidden');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Téléchargement...';

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
        uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Télécharger les vidéos';
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
    }

    showDropContent();
});
</script>

<?php include "admin_footer.php"; ?>