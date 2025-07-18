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

// Traitement du téléchargement d'images et vidéos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
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
        header("Location: manage_images.php?project=" . $projectId);
        exit;
    }

    $imageProjectDir = "../img/projects/" . $project['slug'] . "/";
    $videoProjectDir = "../videos/projects/" . $project['slug'] . "/";
    
    // Créer les dossiers s'ils n'existent pas
    if (!is_dir($imageProjectDir)) {
        mkdir($imageProjectDir, 0777, true);
        usleep(100000); // 0.1 seconde
    }
    if (!is_dir($videoProjectDir)) {
        mkdir($videoProjectDir, 0777, true);
        usleep(100000); // 0.1 seconde
    }
    
    // Forcer la mise à jour du cache avant de lire les fichiers existants
    clearstatcache();
    
    // Récupérer les fichiers existants pour chaque type
    $existingImages = glob($imageProjectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    $existingVideos = glob($videoProjectDir . "*.{mp4,avi,mov,wmv,flv,webm,mkv}", GLOB_BRACE);
    
    // Déterminer les numéros déjà utilisés pour les images
    $usedImageNumbers = [];
    foreach ($existingImages as $image) {
        $filename = basename($image);
        if (preg_match('/^(\d+)\./', $filename, $matches)) {
            $usedImageNumbers[] = (int)$matches[1];
        }
    }
    
    // Déterminer les numéros déjà utilisés pour les vidéos
    $usedVideoNumbers = [];
    foreach ($existingVideos as $video) {
        $filename = basename($video);
        if (preg_match('/^(\d+)\./', $filename, $matches)) {
            $usedVideoNumbers[] = (int)$matches[1];
        }
    }
    
    // Trier les numéros utilisés
    sort($usedImageNumbers);
    sort($usedVideoNumbers);
    
    // Fonction pour trouver le prochain numéro disponible
    function findNextAvailableNumber($usedNumbers) {
        $nextNumber = 1;
        foreach ($usedNumbers as $number) {
            if ($number == $nextNumber) {
                $nextNumber++;
            } else {
                break;
            }
        }
        return $nextNumber;
    }
    
    $imageUploadCount = 0;
    $videoUploadCount = 0;
    $errorCount = 0;
    $debugMessages = [];
    
    // Traiter chaque fichier
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $fileName = $_FILES['images']['name'][$key];
            $fileType = $_FILES['images']['type'][$key];
            $fileSize = $_FILES['images']['size'][$key];
            
            // Debug: ajouter des informations sur le fichier
            $debugMessages[] = "Fichier: $fileName, Type: $fileType, Taille: " . round($fileSize/1024/1024, 2) . "MB";
            
            // Détecter le type de fichier par extension aussi (pour plus de fiabilité)
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'ogv'];
            
            $isImage = (strpos($fileType, 'image/') === 0) || in_array($extension, $imageExtensions);
            $isVideo = (strpos($fileType, 'video/') === 0) || in_array($extension, $videoExtensions);
            
            // Vérifier si c'est une image
            if ($isImage) {
                // Trouver le prochain numéro disponible pour les images
                $nextNumber = findNextAvailableNumber($usedImageNumbers);
                
                $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                $destination = $imageProjectDir . $newFileName;
                
                // Vérifier si le fichier existe déjà (sécurité supplémentaire)
                while (file_exists($destination)) {
                    $nextNumber++;
                    $newFileName = sprintf("%02d.%s", $nextNumber, $extension);
                    $destination = $imageProjectDir . $newFileName;
                }
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $imageUploadCount++;
                    $usedImageNumbers[] = $nextNumber;
                    sort($usedImageNumbers);
                    $debugMessages[] = "✓ Image uploadée: $newFileName";
                    
                    // Forcer la synchronisation du fichier
                    if (function_exists('fsync')) {
                        $handle = fopen($destination, 'r');
                        if ($handle) {
                            fsync($handle);
                            fclose($handle);
                        }
                    }
                } else {
                    $errorCount++;
                    $debugMessages[] = "✗ Erreur upload image: $fileName";
                }
            }
            // Vérifier si c'est une vidéo
            elseif ($isVideo) {
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
                    $debugMessages[] = "✓ Vidéo uploadée: $newFileName";
                } else {
                    $errorCount++;
                    $debugMessages[] = "✗ Erreur upload vidéo: $fileName";
                }
            } else {
                $errorCount++;
                $debugMessages[] = "✗ Type de fichier non supporté: $fileName (Type: $fileType, Extension: $extension)";
            }
        } else {
            $errorCount++;
            $errorMsg = '';
            switch ($_FILES['images']['error'][$key]) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = 'Fichier trop volumineux';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = 'Upload partiel';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = 'Aucun fichier';
                    break;
                default:
                    $errorMsg = 'Erreur inconnue (' . $_FILES['images']['error'][$key] . ')';
            }
            $debugMessages[] = "✗ Erreur fichier: " . $_FILES['images']['name'][$key] . " - $errorMsg";
        }
    }
    
    // Préparer les messages pour la session
    $messages = [];
    if ($imageUploadCount > 0) {
        $messages[] = "$imageUploadCount image(s) téléchargée(s)";
    }
    if ($videoUploadCount > 0) {
        $messages[] = "$videoUploadCount vidéo(s) téléchargée(s)";
    }
    
    if (!empty($messages)) {
        $_SESSION['message'] = implode(' et ', $messages) . " avec succès.";
    }
    if ($errorCount > 0) {
        $_SESSION['error'] = "$errorCount fichier(s) n'ont pas pu être téléchargés.";
    }
    
    // En mode debug, ajouter les messages de debug
    if (isset($_GET['debug'])) {
        $_SESSION['debug'] = implode('<br>', $debugMessages);
    }
    
    // Forcer plusieurs fois la mise à jour du cache et attendre
    clearstatcache();
    usleep(200000); // 0.2 seconde
    clearstatcache();
    
    // Rediriger pour éviter la résoumission du formulaire
    header("Location: manage_images.php?project=" . $projectId . "&refreshed=1");
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

// Ajouter un bouton pour réorganiser les images
$reorganizeMessage = '';
if (isset($_POST['reorganize_images']) && !empty($images)) {
    // Réorganiser les images avec une numérotation consécutive
    $tempDir = $projectDir . "temp/";
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // Déplacer temporairement les images avec de nouveaux noms
    $counter = 1;
    $imageMapping = [];
    foreach ($images as $image) {
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        $newName = sprintf("%02d.%s", $counter, $extension);
        $imageMapping[$image] = $newName;
        copy($image, $tempDir . $newName);
        $counter++;
    }
    
    // Supprimer les images originales
    foreach ($images as $image) {
        if (file_exists($image)) {
            unlink($image);
        }
    }
    
    // Déplacer les images réorganisées vers le dossier principal
    foreach ($imageMapping as $oldPath => $newName) {
        rename($tempDir . $newName, $projectDir . $newName);
    }
    
    // Supprimer le dossier temporaire
    rmdir($tempDir);
    
    // Mettre à jour la liste des images
    $images = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    usort($images, function($a, $b) {
        return strnatcmp(basename($a), basename($b));
    });
    
    $reorganizeMessage = "Les images ont été réorganisées avec succès.";
}

// Traitement de la réorganisation des images par AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder']) && $_POST['reorder'] === 'true') {
    // IMPORTANT: Stopper complètement le buffer de sortie pour les requêtes AJAX
    if (ob_get_level()) {
        ob_end_clean(); // Nettoyer le buffer au lieu de le vider
    }
    
    if (isset($_POST['imageOrder']) && is_array($_POST['imageOrder'])) {
        $projectDir = "../img/projects/" . $project['slug'] . "/";
        $tempDir = $projectDir . "temp_reorder/";
        
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
            $existingImages = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            $imageOrder = $_POST['imageOrder'];
            
            // Créer un mapping des images existantes par nom de fichier
            $imageFiles = [];
            foreach ($existingImages as $imagePath) {
                $imageName = basename($imagePath);
                $imageFiles[$imageName] = $imagePath;
            }
            
            // Vérifier que toutes les images demandées existent
            foreach ($imageOrder as $imageName) {
                if (!isset($imageFiles[$imageName])) {
                    throw new Exception("Image non trouvée: " . $imageName);
                }
            }
            
            // Copier les images dans le dossier temporaire avec les nouveaux noms
            $counter = 1;
            $tempMapping = [];
            foreach ($imageOrder as $imageName) {
                $originalPath = $imageFiles[$imageName];
                $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
                $newName = sprintf("%02d.%s", $counter, $extension);
                $tempPath = $tempDir . $newName;
                
                if (!copy($originalPath, $tempPath)) {
                    throw new Exception("Impossible de copier l'image: " . $imageName);
                }
                
                $tempMapping[$newName] = $tempPath;
                $counter++;
            }
            
            // Supprimer tous les fichiers originaux
            foreach ($existingImages as $image) {
                if (file_exists($image)) {
                    if (!unlink($image)) {
                        throw new Exception("Impossible de supprimer l'image originale: " . basename($image));
                    }
                }
            }
            
            // Déplacer les images du dossier temporaire vers le dossier principal
            foreach ($tempMapping as $newName => $tempPath) {
                $finalPath = $projectDir . $newName;
                if (!rename($tempPath, $finalPath)) {
                    throw new Exception("Impossible de déplacer l'image: " . $newName);
                }
            }
            
            // Supprimer le dossier temporaire
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
            
            // Réponse AJAX de succès - AVEC NETTOYAGE COMPLET
            ob_clean(); // S'assurer qu'aucun contenu n'a été envoyé
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode(['success' => true, 'message' => 'Images réorganisées avec succès'], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            // Nettoyer en cas d'erreur
            if (is_dir($tempDir)) {
                $files = glob($tempDir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($tempDir);
            }
            
            ob_clean(); // Nettoyer avant d'envoyer la réponse d'erreur
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE);
    exit;
}

// À ce stade, toutes les redirections potentielles ont été traitées
// On peut donc vider le buffer pour afficher la page
ob_end_flush();
?>
<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion des médias</h1>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($project['title']); ?> • <?php echo count($images); ?> image(s) • <?php echo count($videos); ?> vidéo(s)</p>
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
                    <span class="text-gray-900 font-medium">Médias</span>
                </nav>
            </div>
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
    <?php if (!empty($reorganizeMessage)): ?>
        <div class="p-4 border-l-4 border-green-400 bg-green-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($reorganizeMessage); ?></p>
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
    <?php if (isset($_SESSION['debug'])): ?>
        <div class="p-4 border-l-4 border-yellow-400 bg-yellow-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-bug text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700"><?php echo $_SESSION['debug']; unset($_SESSION['debug']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-t-lg">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-upload text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Ajouter des images et vidéos</h3>
                    <p class="text-blue-100 text-sm">Téléchargez plusieurs images et vidéos à la fois</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form method="post" enctype="multipart/form-data" id="upload-form" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
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
                                        <button type="button" onclick="clearAllFiles()" 
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
        </div>
    </div>
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

                    <div id="bulk-actions" class="hidden space-x-2">
                        <button type="button" onclick="deleteSelectedItems()" disabled
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
                    <div id="normal-actions" class="flex items-center space-x-2">
                        <?php if (!empty($images) || !empty($videos)): ?>
                            <button type="button" onclick="toggleSelectionMode()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-check-square mr-2"></i>
                                Sélection multiple
                            </button>
                        <?php endif; ?>
                        
                        <button id="saveOrder" class="hidden inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer l'ordre
                        </button>
                        <button id="cancelReorder" class="hidden inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-times mr-2"></i>
                            Annuler
                        </button>
                        <button id="enableReorder" <?php echo empty($images) && empty($videos) ? 'disabled' : ''; ?>
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <i class="fas fa-arrows-alt mr-2"></i>
                            Réorganiser
                        </button>
                        <form method="post" class="inline">
                            <input type="hidden" name="reorganize_images" value="1">
                            <button type="submit" <?php echo empty($images) && empty($videos) ? 'disabled' : ''; ?>
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                <i class="fas fa-sort-numeric-down mr-2"></i>
                                Renuméroter
                            </button>
                        </form>
                        
                        <a href="edit_project.php?id=<?php echo $projectId; ?>"
                           class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>
                            Modifier le projet
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($images) || !empty($videos)): ?>
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
                <div id="mediaGallery" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <!-- Images -->
                    <?php foreach ($images as $index => $image): ?>
                        <div class="media-item image-container relative group media-type-image" data-image="<?php echo basename($image); ?>" data-type="image">
                            <div class="media-checkbox absolute top-3 right-3 z-10 hidden">
                                <input type="checkbox" class="media-select h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                       value="<?php echo htmlspecialchars(basename($image)); ?>" data-type="image" onchange="updateSelectionCount()">
                            </div>
                            <div class="drag-handle hidden cursor-move bg-blue-50 border border-blue-200 text-center py-2 rounded-t-lg">
                                <i class="fas fa-grip-vertical text-blue-600"></i>
                                <span class="text-xs text-blue-700 ml-2">Déplacer</span>
                            </div>
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
                            <div class="media-checkbox absolute top-3 right-3 z-10 hidden">
                                <input type="checkbox" class="media-select h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded" 
                                       value="<?php echo htmlspecialchars(basename($video)); ?>" data-type="video" onchange="updateSelectionCount()">
                            </div>
                            <div class="drag-handle hidden cursor-move bg-purple-50 border border-purple-200 text-center py-2 rounded-t-lg">
                                <i class="fas fa-grip-vertical text-purple-600"></i>
                                <span class="text-xs text-purple-700 ml-2">Déplacer</span>
                            </div>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

<script>
// Variables globales pour éviter les conflits
let selectionMode = false;
let reorderMode = false;
let sortableInstance = null;

function toggleSelectionMode() {
    // Vérifier qu'on n'est pas en mode réorganisation
    if (reorderMode) {
        alert('Veuillez d\'abord quitter le mode de réorganisation avant d\'activer la sélection multiple');
        return;
    }
    
    selectionMode = !selectionMode;
    
    if (selectionMode) {
        // Activer le mode sélection
        $('.media-checkbox').removeClass('hidden');
        $('.media-card').addClass('selection-mode');
        $('#selection-controls').removeClass('hidden');
        $('#bulk-actions').removeClass('hidden');
        $('#normal-actions').addClass('hidden');
        
        // Désactiver le bouton de réorganisation pendant la sélection
        $('#enableReorder').prop('disabled', true);
    } else {
        exitSelectionMode();
    }
}

function exitSelectionMode() {
    selectionMode = false;
    
    // Désactiver le mode sélection
    $('.media-checkbox').addClass('hidden');
    $('.media-card').removeClass('selection-mode selected');
    $('.media-select').prop('checked', false);
    $('#selection-controls').addClass('hidden');
     $('#bulk-actions').addClass('hidden');
     $('#normal-actions').removeClass('hidden');
    
    // Réactiver le bouton de réorganisation
    $('#enableReorder').prop('disabled', false);
    
    updateSelectionCount();
}

function clearSelection() {
    exitSelectionMode();
}

function selectAll() {
    // Sélectionner seulement les éléments visibles selon le filtre actuel
    $('.media-select').each(function() {
        const mediaItem = $(this).closest('.media-item');
        // Vérifier si l'élément est visible (pas caché par le filtre)
        if (mediaItem.is(':visible')) {
            $(this).prop('checked', true);
            $(this).closest('.media-item').find('.media-card').addClass('selected');
        }
    });
    updateSelectionCount();
}

function selectNone() {
    $('.media-select').prop('checked', false);
    $('.media-card').removeClass('selected');
    updateSelectionCount();
}

function invertSelection() {
    $('.media-select').each(function() {
        $(this).prop('checked', !$(this).prop('checked'));
        $(this).trigger('change');
    });
    updateSelectionCount();
}

function updateSelectionCount() {
    const selectedCount = $('.media-select:checked').length;
    $('#selected-count').text(selectedCount);
    
    // Mettre à jour l'apparence des cartes
    $('.media-select').each(function() {
        const card = $(this).closest('.media-item').find('.media-card');
        if ($(this).is(':checked')) {
            card.addClass('selected');
        } else {
            card.removeClass('selected');
        }
    });
    
    // Activer/désactiver le bouton de suppression
    if (selectedCount > 0) {
        $('#bulk-actions button[onclick="deleteSelectedItems()"]').prop('disabled', false);
    } else {
        $('#bulk-actions button[onclick="deleteSelectedItems()"]').prop('disabled', true);
    }
}

function deleteSelectedImages() {
    const selectedImages = [];
    $('.media-select:checked').each(function() {
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
        url: 'delete_multiple_project_images.php',
        type: 'POST',
        data: {
            action: 'delete_multiple',
            project: '<?php echo $projectId; ?>',
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
                    $(`.media-item[data-image="${imageName}"]`).fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si plus d'images, recharger la page pour mettre à jour l'interface
                        if ($('.media-item').length === 0) {
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

// Fonctions de filtrage des médias
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

// Fonction pour supprimer les éléments sélectionnés (images et vidéos)
function deleteSelectedItems() {
    const selectedItems = [];
    const selectedImages = [];
    const selectedVideos = [];
    
    $('.media-select:checked').each(function() {
        const value = $(this).val();
        const type = $(this).data('type');
        
        selectedItems.push({name: value, type: type});
        
        if (type === 'image') {
            selectedImages.push(value);
        } else if (type === 'video') {
            selectedVideos.push(value);
        }
    });
    
    if (selectedItems.length === 0) {
        alert('Aucun média sélectionné');
        return;
    }
    
    let confirmText = `Êtes-vous sûr de vouloir supprimer ${selectedItems.length} média(s) sélectionné(s) ?`;
    if (selectedImages.length > 0 && selectedVideos.length > 0) {
        confirmText += `\n(${selectedImages.length} image(s) et ${selectedVideos.length} vidéo(s))`;
    }
    confirmText += '\nCette action est irréversible.';
    
    if (!confirm(confirmText)) {
        return;
    }
    
    // Désactiver le bouton pendant la suppression
    const deleteButton = $('#bulk-actions button[onclick="deleteSelectedItems()"]');
    const originalText = deleteButton.html();
    deleteButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Suppression...');
    
    // Envoyer les requêtes AJAX séparément pour images et vidéos
    let promises = [];
    
    if (selectedImages.length > 0) {
        promises.push(
            $.ajax({
                url: 'delete_multiple_project_images.php',
                type: 'POST',
                data: {
                    action: 'delete_multiple',
                    project: '<?php echo $projectId; ?>',
                    images: selectedImages
                },
                dataType: 'json'
            })
        );
    }
    
    if (selectedVideos.length > 0) {
        promises.push(
            $.ajax({
                url: 'delete_multiple_project_videos.php',
                type: 'POST',
                data: {
                    action: 'delete_multiple',
                    project: '<?php echo $projectId; ?>',
                    videos: selectedVideos
                },
                dataType: 'json'
            })
        );
    }
    
    Promise.all(promises).then(function(responses) {
        let totalDeleted = 0;
        let messages = [];
        let hasError = false;
        
        responses.forEach(function(response) {
            if (response.success) {
                totalDeleted += response.deleted || 0;
                if (response.message) {
                    messages.push(response.message);
                }
            } else {
                hasError = true;
                if (response.message) {
                    messages.push(response.message);
                }
            }
        });
        
        // Afficher le message de résultat
        let alertClass = !hasError ? 'border-green-400 bg-green-50' : 'border-red-400 bg-red-50';
        let iconClass = !hasError ? 'text-green-400' : 'text-red-400';
        let textClass = !hasError ? 'text-green-700' : 'text-red-700';
        let icon = !hasError ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        let alertHtml = `<div class="p-4 border-l-4 ${alertClass} rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas ${icon} ${iconClass}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm ${textClass}">${messages.join('<br>')}</p>
                </div>
            </div>
        </div>`;
        
        $('#ajax-messages').html(alertHtml);
        
        if (totalDeleted > 0) {
            // Supprimer les éléments de l'interface
            selectedItems.forEach(function(item) {
                $(`.media-item[data-${item.type}="${item.name}"]`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Si plus de médias, recharger la page pour mettre à jour l'interface
                    if ($('.media-item').length === 0) {
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
        
    }).catch(function(error) {
        console.log('Erreur AJAX:', error);
        $('#ajax-messages').html(`<div class="p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">Erreur lors de la suppression des médias. Veuillez réessayer.</p>
                </div>
            </div>
        </div>`);
        
        deleteButton.prop('disabled', false).html(originalText);
    });
}

// Fonction pour ouvrir la modal vidéo
function openVideoModal(videoPath) {
    // Ne pas ouvrir la modal si on est en mode sélection
    if (selectionMode) {
        return false;
    }
    
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

// Initialiser l'état général
updateSelectionCount();

// ===== SYSTÈME DE RÉORGANISATION PAR DRAG & DROP =====
// Attendre que le DOM soit complètement chargé
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
            
            reorderMode = true;
            
            // Afficher/masquer les éléments appropriés
            $('#enableReorder').addClass('hidden');
            $('#saveOrder').removeClass('hidden');
            $('#cancelReorder').removeClass('hidden');
            $('#reorderInstructions').removeClass('hidden');
            $('.media-checkbox').addClass('hidden');
            $('.drag-handle').removeClass('hidden');
            $('#media-filters').addClass('hidden');
            
            // Désactiver les autres boutons
            $('button[onclick="toggleSelectionMode()"]').prop('disabled', true);
            
            // Initialiser Sortable.js sur le conteneur des médias - IMAGES SEULEMENT
            const mediaGallery = document.getElementById('mediaGallery');
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
                        evt.item.style.opacity = '0.5';
                    },
                    onEnd: function(evt) {
                        evt.item.style.opacity = '1';
                    },
                    // Ne permettre le déplacement que des images
                    onMove: function(evt) {
                        return !evt.related.classList.contains('media-type-video');
                    }
                });
            }
            
            console.log('Mode réorganisation activé');
        });
    } else {
        console.error('Bouton enableReorder non trouvé dans le DOM');
    }

    // Annuler la réorganisation
    const cancelReorderBtn = document.getElementById('cancelReorder');
    if (cancelReorderBtn) {
        cancelReorderBtn.addEventListener('click', function() {
            console.log('Bouton Annuler cliqué'); // Debug
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
                        
                        // Sortir du mode réorganisation et mettre à jour l'interface
                        setTimeout(function() {
                            exitReorderMode();
                            
                            // Mettre à jour l'ordre visuel dans l'interface
                            updateImageBadges();
                            
                            // Faire défiler vers le message de succès
                            $('html, body').animate({
                                scrollTop: $('#ajax-messages').offset().top - 100
                            }, 500);
                        }, 300);
                        
                    } else {
                        // Afficher l'erreur
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
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX:', xhr.responseText);
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response Status:', xhr.status);
                    
                    // Essayer de parser la réponse pour voir si c'est du JSON
                    let errorMessage = 'Erreur lors de la sauvegarde. Veuillez réessayer.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // Si ce n'est pas du JSON, afficher les premiers caractères de la réponse
                        if (xhr.responseText && xhr.responseText.length > 0) {
                            errorMessage = 'Erreur serveur: ' + xhr.responseText.substring(0, 200) + '...';
                        }
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

function exitReorderMode() {
    reorderMode = false;
    
    // Détruire l'instance Sortable
    if (sortableInstance) {
        sortableInstance.destroy();
        sortableInstance = null;
    }
    
    // Remettre l'interface normale
    $('#enableReorder').removeClass('hidden');
    $('#saveOrder').addClass('hidden');
    $('#cancelReorder').addClass('hidden');
    $('#reorderInstructions').addClass('hidden');
    $('.drag-handle').addClass('hidden');
    $('#media-filters').removeClass('hidden');
    
    // Réactiver les autres boutons
    $('button[onclick="toggleSelectionMode()"]').prop('disabled', false);
    
    console.log('Mode réorganisation désactivé');
}

// ===== FIN DU SYSTÈME DE RÉORGANISATION =====

// Fonction pour mettre à jour les badges "Image principale" après réorganisation
function updateImageBadges() {
    // Supprimer tous les anciens badges "Image principale"
    $('.main-image-badge').remove();
    
    // Trouver la première image dans l'ordre actuel et lui ajouter le badge
    const firstImage = $('.media-type-image').first();
    if (firstImage.length > 0) {
        const imageInfo = firstImage.find('.media-card .p-4 > div > div').first();
        if (imageInfo.length > 0) {
            // Ajouter le nouveau badge "Image principale"
            const badge = $(`
                <span class="main-image-badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                    <i class="fas fa-star mr-1"></i>
                    Image principale
                </span>
            `);
            
            // Insérer le badge après le titre de l'image
            imageInfo.find('h4').after(badge);
        }
    }
    
    console.log('Badges mis à jour - nouvelle image principale assignée');
}

// Permettre de cliquer sur la carte pour sélectionner/désélectionner
$(document).on('click', '.media-card.selection-mode', function(e) {
    // Éviter le conflit avec les boutons d'action et les cases à cocher
    if ($(e.target).closest('.media-actions, .media-checkbox, .drag-handle').length > 0) {
        return;
    }
    
    // Trouver la case à cocher correspondante
    const checkbox = $(this).closest('.media-item').find('.media-select');
    
    // Inverser l'état de la case à cocher
    checkbox.prop('checked', !checkbox.prop('checked'));
    checkbox.trigger('change');
});

// ===== SYSTÈME DE PREVIEW D'UPLOAD =====
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('images');
    const dropZone = document.getElementById('drop-zone');
    const dropContent = document.getElementById('drop-content');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const fileInfo = document.getElementById('file-info');
    const fileCount = document.getElementById('file-count');

    // Gestion du drag & drop
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
    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        dropZone.classList.add('upload-highlight');
    }

    function unhighlight(e) {
        dropZone.classList.remove('upload-highlight');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFiles(files);
    }

    function createPreview(file, index) {
        const isImage = file.type.startsWith('image/');
        const isVideo = file.type.startsWith('video/');
        
        const previewItem = document.createElement('div');
        previewItem.className = 'relative bg-gray-100 rounded-lg overflow-hidden';
        previewItem.setAttribute('data-file-index', index);
        
        if (isImage) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="w-full h-20 object-cover">
                    <div class="absolute top-1 right-1 bg-blue-500 text-white text-xs px-1 rounded">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="absolute top-1 left-1">
                        <button type="button" onclick="removeFileFromPreview(${index}, event)" 
                                class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200"
                                title="Supprimer ce fichier">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-2">
                        <p class="text-xs text-gray-600 truncate">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB</p>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        } else if (isVideo) {
            previewItem.innerHTML = `
                <div class="w-full h-20 bg-gray-300 flex items-center justify-center">
                    <i class="fas fa-video text-gray-500 text-2xl"></i>
                </div>
                <div class="absolute top-1 right-1 bg-purple-500 text-white text-xs px-1 rounded">
                    <i class="fas fa-video"></i>
                </div>
                <div class="absolute top-1 left-1">
                    <button type="button" onclick="removeFileFromPreview(${index}, event)" 
                            class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200"
                            title="Supprimer ce fichier">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-2">
                    <p class="text-xs text-gray-600 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(1)} MB</p>
                </div>
            `;
        } else {
            previewItem.innerHTML = `
                <div class="w-full h-20 bg-red-100 flex items-center justify-center">
                    <i class="fas fa-file text-red-400 text-2xl"></i>
                </div>
                <div class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1 rounded">
                    <i class="fas fa-exclamation"></i>
                </div>
                <div class="absolute top-1 left-1">
                    <button type="button" onclick="removeFileFromPreview(${index}, event)" 
                            class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200"
                            title="Supprimer ce fichier">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-2">
                    <p class="text-xs text-red-600 truncate">${file.name}</p>
                    <p class="text-xs text-red-500">Non supporté</p>
                </div>
            `;
        }
        
        previewGrid.appendChild(previewItem);
    }

    function handleFiles(files) {
        const filesArray = Array.from(files);
        
        if (filesArray.length > 0) {
            // Vérifier la limite de fichiers côté client
            const maxFiles = <?php echo (int)ini_get('max_file_uploads'); ?>;
            if (filesArray.length > maxFiles) {
                alert(`Trop de fichiers sélectionnés ! Maximum autorisé : ${maxFiles} fichiers à la fois.\nVous avez sélectionné ${filesArray.length} fichiers.\n\nVeuillez réduire le nombre de fichiers et réessayer.`);
                fileInput.value = '';
                resetPreview();
                return;
            }

            // Vérifier la taille totale approximative (limite post_max_size)
            const postMaxSizeBytes = <?php echo return_bytes(ini_get('post_max_size')); ?>;
            const uploadMaxFilesizeBytes = <?php echo return_bytes(ini_get('upload_max_filesize')); ?>;
            
            let totalSize = 0;
            let oversizedFiles = [];
            
            for (let file of filesArray) {
                totalSize += file.size;
                if (file.size > uploadMaxFilesizeBytes) {
                    oversizedFiles.push(file.name);
                }
            }
            
            if (oversizedFiles.length > 0) {
                alert(`Les fichiers suivants sont trop volumineux :\n• ${oversizedFiles.join('\n• ')}\n\nTaille maximum par fichier : <?php echo ini_get('upload_max_filesize'); ?>`);
                fileInput.value = '';
                resetPreview();
                return;
            }
            
            if (totalSize > postMaxSizeBytes) {
                const totalSizeMB = Math.round(totalSize / 1024 / 1024);
                const maxSizeMB = Math.round(postMaxSizeBytes / 1024 / 1024);
                alert(`La taille totale des fichiers (${totalSizeMB} MB) dépasse la limite autorisée (${maxSizeMB} MB).\n\nVeuillez réduire le nombre de fichiers ou choisir des fichiers plus petits.`);
                fileInput.value = '';
                resetPreview();
                return;
            }

            // Afficher le nombre de fichiers avec la taille totale
            const totalSizeMB = Math.round(totalSize / 1024 / 1024 * 100) / 100;
            fileCount.textContent = `${filesArray.length} fichier(s) sélectionné(s) (${totalSizeMB} MB total)`;
            fileInfo.classList.remove('hidden');
            
            // Masquer le contenu par défaut et afficher les previews
            dropContent.classList.add('hidden');
            previewContainer.classList.remove('hidden');
            
            // Vider la grille de preview
            previewGrid.innerHTML = '';
            
            // Créer les previews
            filesArray.forEach((file, index) => {
                createPreview(file, index);
            });
        } else {
            // Réinitialiser si aucun fichier
            resetPreview();
        }
    }

    // Fonction pour supprimer un fichier de la preview (accessible globalement)
    window.removeFileFromPreview = function(indexToRemove, event) {
        // IMPORTANT: Empêcher la propagation pour éviter d'ouvrir la sélection de fichiers
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files);
        
        // Recréer la liste des fichiers en excluant celui à supprimer
        files.forEach((file, i) => {
            if (i !== indexToRemove) {
                dt.items.add(file);
            }
        });
        
        // Mettre à jour l'input file
        fileInput.files = dt.files;
        
        // Rafraîchir la preview
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
            resetPreview();
        }
    };

    function resetPreview() {
        dropContent.classList.remove('hidden');
        previewContainer.classList.add('hidden');
        fileInfo.classList.add('hidden');
        previewGrid.innerHTML = '';
        fileCount.textContent = '';
    }

    // Réinitialiser les previews après soumission réussie
    const uploadForm = document.getElementById('upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            setTimeout(function() {
                resetPreview();
                fileInput.value = '';
            }, 1000);
        });
    }
});
// ===== FIN DU SYSTÈME DE PREVIEW =====
</script>

<style>
.media-card.selection-mode {
    cursor: pointer;
    transition: all 0.3s ease;
}

.media-card.selected {
    border: 2px solid #3b82f6;
    background-color: #eff6ff;
    transform: scale(0.98);
}

.drag-handle:hover {
    background-color: #dbeafe !important;
}

.upload-highlight {
    border-color: #3b82f6 !important;
    background-color: #eff6ff !important;
}

@media (max-width: 640px) {
    #mediaGallery {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}

@media (min-width: 640px) and (max-width: 768px) {
    #mediaGallery {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php include "admin_footer.php"; ?>