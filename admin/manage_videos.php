<?php
// Inclure les fichiers nécessaires (en utilisant la version buffered du header)
include "../connect/connect.php";
include "admin_header_buffered.php";  // On utilise la version avec buffer

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

// Traitement du téléchargement de vidéos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['videos'])) {
    $projectDir = "../videos/projects/" . $project['slug'] . "/";
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($projectDir)) {
        mkdir($projectDir, 0777, true);
    }
    
    // Récupérer les vidéos existantes
    $existingVideos = glob($projectDir . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
    
    // Déterminer les numéros déjà utilisés
    $usedNumbers = [];
    foreach ($existingVideos as $video) {
        $filename = basename($video);
        // Extrait le numéro de la vidéo (ex: "01.mp4" -> 1)
        if (preg_match('/^(\d+)\./', $filename, $matches)) {
            $usedNumbers[] = (int)$matches[1];
        }
    }
    
    // Trier les numéros utilisés
    sort($usedNumbers);
    
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
                
                // Trouver le prochain numéro disponible
                $nextNumber = findNextAvailableNumber($usedNumbers);
                $usedNumbers[] = $nextNumber; // Ajouter ce numéro à la liste des utilisés
                sort($usedNumbers); // Retrier la liste
                
                $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                $destination = $projectDir . $newFileName;
                
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
    
    // Préparer les messages pour la session
    if ($uploadCount > 0) {
        $_SESSION['message'] = "$uploadCount vidéo(s) téléchargée(s) avec succès.";
    }
    if ($errorCount > 0) {
        $_SESSION['error'] = "$errorCount vidéo(s) n'ont pas pu être téléchargées.";
    }
    
    // Rediriger pour éviter la résoumission du formulaire
    header("Location: manage_videos.php?project=" . $projectId);
    exit;
}

// Récupérer les vidéos du projet et les trier par nom de fichier
$projectDir = "../videos/projects/" . $project['slug'] . "/";
$videos = [];
if (is_dir($projectDir)) {
    $videos = glob($projectDir . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
    
    // Trier les vidéos par nom de fichier pour maintenir l'ordre numérique
    usort($videos, function($a, $b) {
        return strnatcmp(basename($a), basename($b));
    });
}

// À ce stade, toutes les redirections potentielles ont été traitées
// On peut donc vider le buffer pour afficher la page
ob_end_flush();
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
                    <h3 class="text-lg font-semibold">Ajouter des vidéos</h3>
                    <p class="text-purple-100 text-sm">Téléchargez plusieurs vidéos à la fois</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form method="post" enctype="multipart/form-data" id="upload-form" class="space-y-6">
                <div>
                    <label for="videos" class="block text-sm font-medium text-gray-700 mb-2">
                        Sélectionner des vidéos <span class="text-red-500">*</span>
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
                            <div id="preview-container" class="hidden w-full p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="preview-grid">
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

    <!-- Affichage des vidéos -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Vidéos actuelles</h3>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($videos); ?> vidéo(s)</p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="manage_images.php?project=<?php echo $projectId; ?>"
                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-images mr-2"></i>
                        Gérer les images
                    </a>
                    <a href="edit_project.php?id=<?php echo $projectId; ?>"
                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        Modifier le projet
                    </a>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($videos)): ?>
                <div id="videoGallery" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($videos as $index => $video): ?>
                        <div class="video-container relative group bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" data-video="<?php echo basename($video); ?>">
                            <div class="aspect-w-16 aspect-h-9 bg-gray-100">
                                <video class="w-full h-48 object-cover" controls preload="metadata">
                                    <source src="<?php echo $video; ?>" type="video/<?php echo pathinfo($video, PATHINFO_EXTENSION); ?>">
                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                </video>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900"><?php echo basename($video); ?></h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo round(filesize($video) / 1024 / 1024, 1); ?> MB
                                        </p>
                                    </div>
                                </div>
                                <div class="video-actions mt-3">
                                    <a href="delete_video.php?project=<?php echo $projectId; ?>&video=<?php echo urlencode(basename($video)); ?>" 
                                       class="delete-btn inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 transition-colors duration-200"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vidéo?');">
                                        <i class="fas fa-trash mr-1"></i>
                                        Supprimer
                                    </a>
                                </div>
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
                    <p class="text-gray-600 mb-6">Ce projet n'a pas encore de vidéos. Utilisez le formulaire ci-dessus pour ajouter vos premières vidéos.</p>
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