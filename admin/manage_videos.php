<?php
// Inclure les fichiers nécessaires (en utilisant la version buffered du header)
include "../connect/connect.php";
include "admin_header_buffered.php";  // On utilise la version avec buffer

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

// Vérifier si l'ID du projet est spécifié
if (!isset($_GET['project'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['project'];

// Récupérer les informations du projet
$query = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $projectId]);
$project = $stmt->fetch();

// Vérifier si le projet existe
if (!$project) {
    header("Location: projects.php");
    exit;
}

$message = '';
$error = '';

// Traitement du téléchargement de vidéos et images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['videos'])) {
    $videoProjectDir = "../videos/projects/" . $project['slug'] . "/";
    $imageProjectDir = "../img/projects/" . $project['slug'] . "/";
    
    // Créer les dossiers s'ils n'existent pas
    if (!is_dir($videoProjectDir)) {
        mkdir($videoProjectDir, 0777, true);
    }
    if (!is_dir($imageProjectDir)) {
        mkdir($imageProjectDir, 0777, true);
    }
    
    // Récupérer les fichiers existants pour chaque type
    $existingVideos = glob($videoProjectDir . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
    $existingImages = glob($imageProjectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    
    // Déterminer les numéros déjà utilisés pour les vidéos
    $usedVideoNumbers = [];
    foreach ($existingVideos as $video) {
        $filename = basename($video);
        if (preg_match('/^(\d+)\./', $filename, $matches)) {
            $usedVideoNumbers[] = (int)$matches[1];
        }
    }
    sort($usedVideoNumbers);
    
    // Déterminer les numéros déjà utilisés pour les images
    $usedImageNumbers = [];
    foreach ($existingImages as $image) {
        $filename = basename($image);
        if (preg_match('/^(\d+)\./', $filename, $matches)) {
            $usedImageNumbers[] = (int)$matches[1];
        }
    }
    sort($usedImageNumbers);
    
    // Fonction pour trouver le prochain numéro disponible
    function findNextAvailableNumber($usedNumbers) {
        $nextNumber = 1; // On commence à 1
        foreach ($usedNumbers as $number) {
            if ($number == $nextNumber) {
                $nextNumber++;
            } else {
                // On a trouvé un trou dans la séquence
                break;
            }
        }
        return $nextNumber;
    }
    
    $videoUploadCount = 0;
    $imageUploadCount = 0;
    $errorCount = 0;
    
    // Traiter chaque fichier
    foreach ($_FILES['videos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['videos']['error'][$key] === UPLOAD_ERR_OK) {
            $fileName = $_FILES['videos']['name'][$key];
            $fileType = $_FILES['videos']['type'][$key];
            
            // Vérifier si c'est une vidéo
            if (strpos($fileType, 'video/') === 0) {
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                
                // Trouver le prochain numéro disponible pour les vidéos
                $nextNumber = findNextAvailableNumber($usedVideoNumbers);
                
                $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                $destination = $videoProjectDir . $newFileName;
                
                // Vérifier si le fichier existe déjà
                while (file_exists($destination)) {
                    $nextNumber++;
                    $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                    $destination = $videoProjectDir . $newFileName;
                }
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $videoUploadCount++;
                    $usedVideoNumbers[] = $nextNumber;
                    sort($usedVideoNumbers);
                } else {
                    $errorCount++;
                }
            }
            // Vérifier si c'est une image
            elseif (strpos($fileType, 'image/') === 0) {
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                
                // Trouver le prochain numéro disponible pour les images
                $nextNumber = findNextAvailableNumber($usedImageNumbers);
                
                $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                $destination = $imageProjectDir . $newFileName;
                
                // Vérifier si le fichier existe déjà
                while (file_exists($destination)) {
                    $nextNumber++;
                    $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                    $destination = $imageProjectDir . $newFileName;
                }
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $imageUploadCount++;
                    $usedImageNumbers[] = $nextNumber;
                    sort($usedImageNumbers);
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }
    }
    
    // Préparer les messages pour la session
    $messages = [];
    if ($videoUploadCount > 0) {
        $messages[] = "$videoUploadCount vidéo(s) téléchargée(s)";
    }
    if ($imageUploadCount > 0) {
        $messages[] = "$imageUploadCount image(s) téléchargée(s)";
    }
    
    if (!empty($messages)) {
        $_SESSION['message'] = implode(' et ', $messages) . " avec succès.";
    }
    if ($errorCount > 0) {
        $_SESSION['error'] = "$errorCount fichier(s) n'ont pas pu être téléchargés.";
    }
    
    // Forcer la mise à jour du cache
    clearstatcache();
    
    // Rediriger pour éviter la résoumission du formulaire
    header("Location: manage_videos.php?project=" . $projectId);
    exit;
}

// Récupérer les images et vidéos du projet et les trier par nom de fichier (pour assurer l'ordre numérique)
$projectDir = "../img/projects/" . $project['slug'] . "/";
$videoDir = "../videos/projects/" . $project['slug'] . "/";
$images = [];
$videos = [];

if (is_dir($projectDir)) {
    // Forcer la mise à jour du cache
    clearstatcache();
    
    // Méthode 1: Essayer avec glob() (méthode actuelle)
    $images = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    
    // Méthode 2: Si glob() ne fonctionne pas, utiliser readdir() comme fallback
    if (empty($images)) {
        $handle = opendir($projectDir);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $tempImages = [];
        
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, $imageExtensions)) {
                    $tempImages[] = $projectDir . $file;
                }
            }
        }
        closedir($handle);
        $images = $tempImages;
    }
    
    // Trier les images par nom de fichier pour maintenir l'ordre numérique
    if (!empty($images)) {
        usort($images, function($a, $b) {
            return strnatcmp(basename($a), basename($b));
        });
    }
}

if (is_dir($videoDir)) {
    // Forcer la mise à jour du cache
    clearstatcache();
    
    // Récupérer les vidéos et les trier par nom de fichier
    $videos = glob($videoDir . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
    usort($videos, function($a, $b) {
        return strnatcmp(basename($a), basename($b));
    });
}
?>

<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion des vidéos</h1>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($project['title']); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" 
                   target="_blank"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    Voir le projet
                </a>
                <nav class="flex space-x-2 text-sm">
                    <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                    <span class="text-gray-400">/</span>
                    <a href="projects.php" class="text-gray-500 hover:text-gray-700">Projets</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900 font-medium">Vidéos</span>
                </nav>
            </div>
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

    <!-- Formulaire d'upload -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-t-lg">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-video text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Ajouter des vidéos et images</h3>
                    <p class="text-purple-100 text-sm">Téléchargez plusieurs vidéos et images à la fois</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form method="post" enctype="multipart/form-data" id="upload-form" class="space-y-6">
                <div>
                    <label for="videos" class="block text-sm font-medium text-gray-700 mb-2">
                        Sélectionner des vidéos et images <span class="text-red-500">*</span>
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
                            <input id="videos" name="videos[]" type="file" class="hidden" multiple accept="video/*,image/*" required>
                        </label>
                    </div>
                    <div id="file-info" class="hidden mt-4 p-4 bg-purple-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-purple-500 mr-2"></i>
                            <span id="file-count" class="text-sm text-purple-700 font-medium"></span>
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
        </div>
    </div>

    <!-- Affichage des vidéos et images -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Médias du projet</h3>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($images); ?> image(s) • <?php echo count($videos); ?> vidéo(s) • La première image sera utilisée comme image principale</p>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Filtres de type de média -->
                    <div id="media-filters" class="flex items-center space-x-1 mr-4">
                        <button type="button" onclick="filterMedia('all')" id="filter-all"
                                class="media-filter-btn active inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-th mr-1"></i>
                            Tout
                        </button>
                        <button type="button" onclick="filterMedia('images')" id="filter-images"
                                class="media-filter-btn inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-images mr-1"></i>
                            Photos
                        </button>
                        <button type="button" onclick="filterMedia('videos')" id="filter-videos"
                                class="media-filter-btn inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-video mr-1"></i>
                            Vidéos
                        </button>
                    </div>

                    <a href="edit_project.php?id=<?php echo $projectId; ?>"
                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        Modifier le projet
                    </a>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($images) || !empty($videos)): ?>
                <!-- Conteneur principal pour tous les médias -->
                <div id="mediaGallery" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <!-- Images -->
                    <?php foreach ($images as $index => $image): ?>
                        <div class="media-item image-container relative group media-type-image" data-image="<?php echo basename($image); ?>" data-type="image">
                            <div class="media-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="aspect-w-16 aspect-h-12 bg-gray-100">
                                    <img src="<?php echo $image; ?>" 
                                         alt="Image" 
                                         class="w-full h-48 object-cover">
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900"><?php echo basename($image); ?></h4>
                                            <?php if ($index === 0): ?>
                                                <span class="main-image-badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                    <i class="fas fa-star mr-1"></i>
                                                    Image principale
                                                </span>
                                            <?php endif; ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                                <i class="fas fa-image mr-1"></i>
                                                Image
                                            </span>
                                        </div>
                                    </div>
                                    <div class="media-actions mt-3">
                                        <a href="delete_image.php?project=<?php echo $projectId; ?>&image=<?php echo urlencode(basename($image)); ?>" 
                                           class="delete-btn inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-200"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');">
                                            <i class="fas fa-trash mr-1"></i>
                                            Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Vidéos -->
                    <?php foreach ($videos as $index => $video): ?>
                        <div class="media-item video-container relative group media-type-video" data-video="<?php echo basename($video); ?>" data-type="video">
                            <div class="media-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="aspect-w-16 aspect-h-12 bg-gray-100 relative">
                                    <video class="w-full h-48 object-cover" preload="metadata" muted>
                                        <source src="<?php echo $video; ?>" type="video/<?php echo pathinfo($video, PATHINFO_EXTENSION); ?>">
                                    </video>
                                    <!-- Overlay de lecture -->
                                    <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-200 cursor-pointer" onclick="openVideoModal('<?php echo $video; ?>')">
                                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <i class="fas fa-play text-white text-lg"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900"><?php echo basename($video); ?></h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mt-1">
                                                <i class="fas fa-video mr-1"></i>
                                                Vidéo
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo round(filesize($video) / 1024 / 1024, 1); ?> MB
                                            </p>
                                        </div>
                                    </div>
                                    <div class="media-actions mt-3">
                                        <a href="delete_video.php?project=<?php echo $projectId; ?>&video=<?php echo urlencode(basename($video)); ?>" 
                                           class="delete-btn inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-200"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vidéo?');">
                                            <i class="fas fa-trash mr-1"></i>
                                            Supprimer
                                        </a>
                                    </div>
                                </div>
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
                    <p class="text-gray-600 mb-6">Ce projet n'a pas encore d'images ou de vidéos. Utilisez le formulaire ci-dessus pour ajouter vos premiers médias.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
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

        // Filtrer les fichiers vidéo ET image
        const mediaFiles = Array.from(files).filter(file => 
            file.type.startsWith('video/') || file.type.startsWith('image/')
        );
        
        if (mediaFiles.length === 0) {
            alert('Veuillez sélectionner uniquement des fichiers vidéo (MP4, AVI, MOV, etc.) ou des images (PNG, JPG, JPEG, GIF)');
            fileInput.value = '';
            showDropContent();
            return;
        }

        // Compter séparément les vidéos et images
        const videoCount = mediaFiles.filter(f => f.type.startsWith('video/')).length;
        const imageCount = mediaFiles.filter(f => f.type.startsWith('image/')).length;
        
        let countText = '';
        if (videoCount > 0 && imageCount > 0) {
            countText = `${videoCount} vidéo(s) et ${imageCount} image(s) sélectionnée(s)`;
        } else if (videoCount > 0) {
            countText = `${videoCount} vidéo(s) sélectionnée(s)`;
        } else {
            countText = `${imageCount} image(s) sélectionnée(s)`;
        }

        // Afficher les informations
        fileCount.textContent = countText;
        fileInfo.classList.remove('hidden');

        // Générer les aperçus
        previewGrid.innerHTML = '';
        dropContent.classList.add('hidden');
        previewContainer.classList.remove('hidden');

        mediaFiles.forEach((file, index) => {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative group bg-gray-100 rounded-lg p-4';
            
            if (file.type.startsWith('video/')) {
                // Aperçu pour vidéo
                previewDiv.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-video text-purple-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate" title="${file.name}">${file.name}</p>
                            <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB • Vidéo</p>
                        </div>
                        <button type="button" onclick="removeFile(${index}, event)" 
                                class="w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            } else if (file.type.startsWith('image/')) {
                // Aperçu pour image avec prévisualisation
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-200">
                                <img src="${e.target.result}" class="w-full h-full object-cover" alt="Aperçu">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate" title="${file.name}">${file.name}</p>
                                <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB • Image</p>
                            </div>
                            <button type="button" onclick="removeFile(${index}, event)" 
                                    class="w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
            
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

// Fonctions de filtrage des médias (copié de manage_images.php)
function filterMedia(type) {
    // Réinitialiser les boutons de filtre
    document.querySelectorAll('.media-filter-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-500', 'text-white');
        btn.classList.add('text-gray-700', 'bg-white');
    });
    
    // Activer le bouton sélectionné
    const activeBtn = document.getElementById(`filter-${type}`);
    activeBtn.classList.add('active', 'bg-blue-500', 'text-white');
    activeBtn.classList.remove('text-gray-700', 'bg-white');
    
    // Filtrer les éléments
    const mediaItems = document.querySelectorAll('.media-item');
    
    mediaItems.forEach(item => {
        const itemType = item.getAttribute('data-type');
        
        if (type === 'all') {
            item.style.display = 'block';
        } else if (type === 'images' && itemType === 'image') {
            item.style.display = 'block';
        } else if (type === 'videos' && itemType === 'video') {
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
                <button onclick="closeVideoModal()" style="position: absolute; top: -40px; right: 0; background: #baff00; color: #222; border: none; font-size: 24px; cursor: pointer; width: 40px; height: 40px; font-weight: 700;">&times;</button>
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