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

// Traitement du téléchargement d'une image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $projectDir = "../img/projects/" . $project['slug'] . "/";
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($projectDir)) {
        mkdir($projectDir, 0777, true);
    }
    
    // Récupérer les images existantes
    $existingImages = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    
    // Déterminer les numéros déjà utilisés
    $usedNumbers = [];
    foreach ($existingImages as $image) {
        $filename = basename($image);
        // Extrait le numéro de l'image (ex: "01.jpg" -> 1)
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
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $fileName = $_FILES['images']['name'][$key];
            $fileType = $_FILES['images']['type'][$key];
            
            // Vérifier si c'est une image
            if (strpos($fileType, 'image/') === 0) {
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
        $_SESSION['message'] = "$uploadCount image(s) téléchargée(s) avec succès.";
    }
    if ($errorCount > 0) {
        $_SESSION['error'] = "$errorCount image(s) n'ont pas pu être téléchargées.";
    }
    
    // Rediriger pour éviter la résoumission du formulaire
    header("Location: manage_images.php?project=" . $projectId);
    exit;
}

// Récupérer les images du projet et les trier par nom de fichier (pour assurer l'ordre numérique)
$projectDir = "../img/projects/" . $project['slug'] . "/";
$images = [];
if (is_dir($projectDir)) {
    $images = glob($projectDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
    
    // Trier les images par nom de fichier pour maintenir l'ordre numérique
    usort($images, function($a, $b) {
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
            
            // Réponse AJAX de succès
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Images réorganisées avec succès']);
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
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
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
                <h1 class="text-2xl font-bold text-gray-900">Gestion des images</h1>
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
                    <span class="text-gray-900 font-medium">Images</span>
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
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-t-lg">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-upload text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Ajouter des images</h3>
                    <p class="text-blue-100 text-sm">Téléchargez plusieurs images à la fois</p>
                </div>
            </div>
        </div>
        <div class="p-6">
            <form method="post" enctype="multipart/form-data" id="upload-form" class="space-y-6">
                <div>
                    <label for="images" class="block text-sm font-medium text-gray-700 mb-2">
                        Sélectionner des images <span class="text-red-500">*</span>
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
                    <h3 class="text-lg font-semibold text-gray-900">Images actuelles</h3>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($images); ?> image(s) • La première image sera utilisée comme image principale</p>
                </div>
                <div class="flex items-center space-x-2">
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
                    <div id="normal-actions" class="flex items-center space-x-2">
                        <?php if (!empty($images)): ?>
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
                        <button id="enableReorder" <?php echo empty($images) ? 'disabled' : ''; ?>
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                            <i class="fas fa-arrows-alt mr-2"></i>
                            Réorganiser
                        </button>
                        <form method="post" class="inline">
                            <input type="hidden" name="reorganize_images" value="1">
                            <button type="submit" <?php echo empty($images) ? 'disabled' : ''; ?>
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
            <?php if (!empty($images)): ?>
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
                                <strong>Mode de réorganisation:</strong> Glissez et déposez les images pour modifier leur ordre. N'oubliez pas de cliquer sur "Enregistrer l'ordre" lorsque vous avez terminé.
                            </p>
                        </div>
                    </div>
                </div>
                <div id="imageGallery" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="image-container relative group" data-image="<?php echo basename($image); ?>">
                            <div class="image-checkbox absolute top-3 right-3 z-10 hidden">
                                <input type="checkbox" class="image-select h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                       value="<?php echo htmlspecialchars(basename($image)); ?>" onchange="updateSelectionCount()">
                            </div>
                            <div class="drag-handle hidden cursor-move bg-blue-50 border border-blue-200 text-center py-2 rounded-t-lg">
                                <i class="fas fa-grip-vertical text-blue-600"></i>
                                <span class="text-xs text-blue-700 ml-2">Déplacer</span>
                            </div>
                            <div class="image-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
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
                                        </div>
                                    </div>
                                    <div class="image-actions mt-3">
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
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-images text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune image</h3>
                    <p class="text-gray-600 mb-6">Ce projet n'a pas encore d'images. Utilisez le formulaire ci-dessus pour ajouter vos premières images.</p>
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
    $('.image-checkbox').addClass('hidden');
    $('.image-card').removeClass('selection-mode selected');
    $('.image-select').prop('checked', false);
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
    $('.image-select').prop('checked', true);
    $('.image-card').addClass('selected');
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

document.addEventListener('DOMContentLoaded', function() {
    const imageGallery = document.getElementById('imageGallery');
    const enableReorderBtn = document.getElementById('enableReorder');
    const saveOrderBtn = document.getElementById('saveOrder');
    const cancelReorderBtn = document.getElementById('cancelReorder');
    const reorderInstructions = document.getElementById('reorderInstructions');
    
    let sortable = null;
    let originalOrder = [];
    
    // Vérifier que les éléments existent
    if (!imageGallery || !enableReorderBtn || !saveOrderBtn || !cancelReorderBtn) {
        console.log('Certains éléments du DOM ne sont pas trouvés');
        return;
    }
    
    // Enregistrer l'ordre original des images
    function captureOriginalOrder() {
        originalOrder = [];
        document.querySelectorAll('.image-container').forEach(container => {
            originalOrder.push(container.getAttribute('data-image'));
        });
    }
    
    // Activer le mode de réorganisation
    enableReorderBtn.addEventListener('click', function() {
        // Vérifier qu'on n'est pas en mode sélection
        if (selectionMode) {
            alert('Veuillez d\'abord quitter le mode de sélection multiple avant d\'activer la réorganisation');
            return;
        }
        
        reorderMode = true;
        captureOriginalOrder();
        
        // Mettre à jour l'interface
        enableReorderBtn.classList.add('hidden');
        saveOrderBtn.classList.remove('hidden');
        cancelReorderBtn.classList.remove('hidden');
        if (reorderInstructions) {
            reorderInstructions.classList.remove('hidden');
        }
        
        // Afficher les poignées de glissement et masquer les boutons de suppression
        const dragHandles = document.querySelectorAll('.drag-handle');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const imageCheckboxes = document.querySelectorAll('.image-checkbox');
        
        dragHandles.forEach(handle => {
            handle.classList.remove('hidden');
        });
        
        deleteButtons.forEach(btn => {
            btn.style.display = 'none';
        });
        
        // S'assurer que les cases à cocher sont masquées et désactiver le bouton de sélection
        imageCheckboxes.forEach(checkbox => {
            checkbox.classList.add('hidden');
        });
        
        // Désactiver le bouton de sélection multiple pendant la réorganisation
        const selectionButton = document.querySelector('button[onclick="toggleSelectionMode()"]');
        if (selectionButton) {
            selectionButton.disabled = true;
        }
        
        // Activer Sortable.js
        sortable = new Sortable(imageGallery, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function() {
                updateMainImageBadge();
            }
        });
    });
    
    // Mettre à jour le badge de l'image principale
    function updateMainImageBadge() {
        const badges = document.querySelectorAll('.main-image-badge');
        badges.forEach(badge => {
            badge.style.display = 'none';
        });
        
        const firstImage = imageGallery.querySelector('.image-container');
        if (firstImage) {
            const badge = firstImage.querySelector('.main-image-badge');
            if (badge) {
                badge.style.display = 'inline-flex';
            } else {
                const newBadge = document.createElement('span');
                newBadge.className = 'main-image-badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1';
                newBadge.innerHTML = '<i class="fas fa-star mr-1"></i>Image principale';
                const cardText = firstImage.querySelector('.p-4');
                if (cardText) {
                    cardText.appendChild(newBadge);
                }
            }
        }
    }
    
    // Enregistrer le nouvel ordre
    saveOrderBtn.addEventListener('click', function() {
        const imageOrder = [];
        document.querySelectorAll('.image-container').forEach(container => {
            imageOrder.push(container.getAttribute('data-image'));
        });
        
        // Désactiver le bouton pendant l'enregistrement
        saveOrderBtn.disabled = true;
        saveOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';
        
        // Envoyer l'ordre au serveur via AJAX
        const formData = new FormData();
        formData.append('reorder', 'true');
        imageOrder.forEach(img => {
            formData.append('imageOrder[]', img);
        });
        
        fetch('manage_images.php?project=<?php echo $projectId; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if(response.ok) {
                return response.json().catch(() => {
                    // Si la conversion en JSON échoue, recharger la page
                    location.reload();
                    return { success: true };
                });
            } else {
                throw new Error('Erreur réseau');
            }
        })
        .then(data => {
            if (data && data.success) {
                location.reload();
            } else if (data && data.message) {
                alert('Erreur: ' + data.message);
                // Réactiver le bouton
                saveOrderBtn.disabled = false;
                saveOrderBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer l\'ordre';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            location.reload();
        });
    });
    
    // Annuler la réorganisation
    cancelReorderBtn.addEventListener('click', function() {
        exitReorderMode();
    });
    
    // Fonction pour quitter le mode réorganisation
    function exitReorderMode() {
        reorderMode = false;
        
        // Restaurer l'ordre original
        const fragment = document.createDocumentFragment();
        originalOrder.forEach(imageName => {
            const container = document.querySelector(`.image-container[data-image="${imageName}"]`);
            if (container) {
                fragment.appendChild(container);
            }
        });
        
        imageGallery.innerHTML = '';
        imageGallery.appendChild(fragment);
        
        // Réinitialiser l'interface
        enableReorderBtn.classList.remove('hidden');
        saveOrderBtn.classList.add('hidden');
        cancelReorderBtn.classList.add('hidden');
        if (reorderInstructions) {
            reorderInstructions.classList.add('hidden');
        }
        
        // Masquer les poignées et réafficher les boutons
        const dragHandles = document.querySelectorAll('.drag-handle');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        
        dragHandles.forEach(handle => {
            handle.classList.add('hidden');
        });
        
        deleteButtons.forEach(btn => {
            btn.style.display = 'inline-flex';
        });
        
        // Réactiver le bouton de sélection multiple
        const selectionButton = document.querySelector('button[onclick="toggleSelectionMode()"]');
        if (selectionButton) {
            selectionButton.disabled = false;
        }
        
        // Réinitialiser le badge de l'image principale
        updateMainImageBadge();
        
        // Détruire l'objet Sortable
        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }
    
    // Permettre de cliquer sur la carte pour sélectionner/désélectionner
    $(document).on('click', '.image-card', function(e) {
        // Ne fonctionner que si on est en mode sélection
        if (!selectionMode) return;
        
        // Éviter le conflit avec les boutons d'action et les poignées de drag
        if ($(e.target).closest('.image-actions, .image-checkbox, .drag-handle').length > 0) {
            return;
        }
        
        const checkbox = $(this).find('.image-select');
        checkbox.prop('checked', !checkbox.prop('checked'));
        checkbox.trigger('change');
    });
    
    // Gestion avancée du drag and drop pour l'upload avec prévisualisation
    console.log('Script drag & drop initialisé (manage_images)'); // Debug
    
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
        console.error('Certains éléments requis pour le drag & drop sont manquants (manage_images)');
        console.log('dropZone:', dropZone);
        console.log('fileInput:', fileInput);
        console.log('dropContent:', dropContent);
        return;
    }

    console.log('Tous les éléments trouvés, initialisation du drag & drop (manage_images)'); // Debug

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
        console.log('Zone de drop activée (manage_images)'); // Debug
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight() {
        console.log('Zone de drop désactivée (manage_images)'); // Debug
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }

    function handleDrop(e) {
        console.log('Fichiers déposés (manage_images)'); // Debug
        const dt = e.dataTransfer;
        const files = dt.files;
        
        console.log('Nombre de fichiers:', files.length); // Debug
        
        fileInput.files = files;
        handleFiles(files);
    }

    // Gestion du changement de fichiers
    fileInput.addEventListener('change', function(e) {
        console.log('Fichiers sélectionnés via input (manage_images)'); // Debug
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        console.log('Traitement des fichiers:', files.length); // Debug
        
        if (files.length === 0) {
            showDropContent();
            return;
        }

        // Filtrer les fichiers image uniquement
        const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
        
        console.log('Fichiers image valides:', imageFiles.length); // Debug
        
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

    // Gestion de la soumission du formulaire avec progress
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        console.log('Soumission du formulaire (manage_images)'); // Debug
        
        if (fileInput.files.length === 0) {
            alert('Veuillez sélectionner au moins une image');
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
            console.error('Erreur réseau (manage_images)'); // Debug
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
    
    console.log('Drag & drop complètement initialisé (manage_images)'); // Debug
    
    // Initialiser les événements quand la page est chargée
    updateSelectionCount();
});
</script>

<style>
.image-card.selection-mode {
    cursor: pointer;
    transition: all 0.3s ease;
}

.image-card.selected {
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
    #imageGallery {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}

@media (min-width: 640px) and (max-width: 768px) {
    #imageGallery {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php include "admin_footer.php"; ?>