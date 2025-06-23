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

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestion des images - <?php echo htmlspecialchars($project['title']); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="dashboard.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="projects.php">Projets</a></li>
                    <li class="breadcrumb-item active">Gestion des images</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($reorganizeMessage)): ?>
            <div class="alert alert-success"><?php echo $reorganizeMessage; ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ajouter des images</h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group mb-3">
                        <label for="images">Sélectionner des images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                        <small class="form-text text-muted">Vous pouvez sélectionner plusieurs images à la fois.</small>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Télécharger</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Images actuelles</h3>
                <div class="card-tools">
                    <!-- Boutons de sélection multiple (cachés par défaut) -->
                    <div class="btn-group btn-group-sm" role="group" id="bulk-actions" style="display: none;">
                        <button type="button" class="btn btn-danger" onclick="deleteSelectedImages()" disabled>
                            <i class="fa fa-trash"></i> Supprimer sélectionnées (<span id="selected-count">0</span>)
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearSelection()">
                            <i class="fa fa-times"></i> Annuler
                        </button>
                    </div>
                    
                    <!-- Boutons normaux -->
                    <div id="normal-actions">
                        <?php if (!empty($images)): ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="toggleSelectionMode()">
                                <i class="fa fa-check-square-o"></i> Sélection multiple
                            </button>
                        <?php endif; ?>
                        <button id="saveOrder" class="btn btn-success" style="display:none;">
                            <i class="fa fa-save"></i> Enregistrer l'ordre
                        </button>
                        <button id="cancelReorder" class="btn btn-secondary" style="display:none;">
                            <i class="fa fa-times"></i> Annuler
                        </button>
                        <button id="enableReorder" class="btn btn-primary" <?php echo empty($images) ? 'disabled' : ''; ?>>
                            <i class="fa fa-arrows"></i> Réorganiser les images
                        </button>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="reorganize_images" value="1">
                            <button type="submit" class="btn btn-warning" <?php echo empty($images) ? 'disabled' : ''; ?>>
                                <i class="fa fa-sort-numeric-asc"></i> Renuméroter les images
                            </button>
                        </form>
                        <a href="delete_all_images.php?project=<?php echo $projectId; ?>" class="btn btn-danger" <?php echo empty($images) ? 'disabled' : ''; ?>>
                            <i class="fa fa-trash"></i> Supprimer toutes les images
                        </a>
                        <a href="edit_project.php?id=<?php echo $projectId; ?>" class="btn btn-info">
                            <i class="fa fa-pencil"></i> Modifier le projet
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($images)): ?>
                    <!-- Alert zone pour les messages AJAX -->
                    <div id="ajax-messages"></div>
                    
                    <!-- Contrôles de sélection (cachés par défaut) -->
                    <div class="row mb-3" id="selection-controls" style="display: none;">
                        <div class="col-12">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                    <i class="fa fa-check-square"></i> Tout sélectionner
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectNone()">
                                    <i class="fa fa-square-o"></i> Tout désélectionner
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="invertSelection()">
                                    <i class="fa fa-exchange"></i> Inverser la sélection
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <p><strong>Note:</strong> L'image affichée en premier sera utilisée comme image principale du projet dans les listes.</p>
                        <p id="reorderInstructions" style="display:none;"><strong>Mode de réorganisation:</strong> Vous pouvez maintenant glisser et déposer les images pour modifier leur ordre. N'oubliez pas de cliquer sur "Enregistrer l'ordre" lorsque vous avez terminé.</p>
                    </div>
                    <div id="imageGallery" class="row">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="col-md-3 mb-4 image-container" data-image="<?php echo basename($image); ?>">
                                <div class="card image-card">
                                    <!-- Case à cocher (masquée par défaut) -->
                                    <div class="image-checkbox" style="display: none;">
                                        <input type="checkbox" class="image-select" value="<?php echo htmlspecialchars(basename($image)); ?>" 
                                               onchange="updateSelectionCount()">
                                    </div>
                                    
                                    <div class="drag-handle" style="display:none; cursor:move; background-color: #f4f6f9; text-align: center; padding: 5px;">
                                        <i class="fa fa-arrows"></i> Déplacer
                                    </div>
                                    <img src="<?php echo $image; ?>" class="card-img-top" alt="Image">
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo basename($image); ?></h5>
                                        <p class="card-text">
                                            <?php if ($index === 0): ?>
                                                <span class="badge bg-success main-image-badge">Image principale</span>
                                            <?php endif; ?>
                                        </p>
                                        <div class="image-actions">
                                            <a href="delete_image.php?project=<?php echo $projectId; ?>&image=<?php echo urlencode(basename($image)); ?>" class="btn btn-danger delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');">
                                                <i class="fa fa-trash"></i> Supprimer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center">Aucune image n'est disponible pour ce projet.</p>
                    <p class="text-center">Utilisez le formulaire ci-dessus pour ajouter des images.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Script pour le glisser-déposer -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        $('.image-checkbox').show();
        $('.image-card').addClass('selection-mode');
        $('#selection-controls').show();
        $('#bulk-actions').show();
        $('#normal-actions').hide();
        
        // Désactiver le bouton de réorganisation pendant la sélection
        $('#enableReorder').prop('disabled', true);
        
        // Changer le texte du bouton
        $('button[onclick="toggleSelectionMode()"]').html('<i class="fa fa-times"></i> Annuler sélection');
        $('button[onclick="toggleSelectionMode()"]').attr('onclick', 'exitSelectionMode()');
    } else {
        exitSelectionMode();
    }
}

function exitSelectionMode() {
    selectionMode = false;
    
    // Désactiver le mode sélection
    $('.image-checkbox').hide();
    $('.image-card').removeClass('selection-mode selected');
    $('.image-select').prop('checked', false);
    $('#selection-controls').hide();
    $('#bulk-actions').hide();
    $('#normal-actions').show();
    
    // Réactiver le bouton de réorganisation
    $('#enableReorder').prop('disabled', false);
    
    // Remettre le texte original du bouton
    $('button[onclick="exitSelectionMode()"]').html('<i class="fa fa-check-square-o"></i> Sélection multiple');
    $('button[onclick="exitSelectionMode()"]').attr('onclick', 'toggleSelectionMode()');
    
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
        $('#bulk-actions button.btn-danger').prop('disabled', false);
    } else {
        $('#bulk-actions button.btn-danger').prop('disabled', true);
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
    const deleteButton = $('#bulk-actions button.btn-danger');
    const originalText = deleteButton.html();
    deleteButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Suppression...');
    
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
            let alertClass = response.success ? 'alert-success' : 'alert-danger';
            let alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${response.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
            $('#ajax-messages').html(`<div class="alert alert-danger alert-dismissible fade show" role="alert">
                Erreur lors de la suppression des images. Veuillez réessayer.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
        enableReorderBtn.style.display = 'none';
        saveOrderBtn.style.display = 'inline-block';
        cancelReorderBtn.style.display = 'inline-block';
        if (reorderInstructions) {
            reorderInstructions.style.display = 'block';
        }
        
        // Afficher les poignées de glissement et masquer les boutons de suppression
        const dragHandles = document.querySelectorAll('.drag-handle');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const imageCheckboxes = document.querySelectorAll('.image-checkbox');
        
        dragHandles.forEach(handle => {
            handle.style.display = 'block';
        });
        
        deleteButtons.forEach(btn => {
            btn.style.display = 'none';
        });
        
        // S'assurer que les cases à cocher sont masquées et désactiver le bouton de sélection
        imageCheckboxes.forEach(checkbox => {
            checkbox.style.display = 'none';
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
                badge.style.display = 'inline-block';
            } else {
                const newBadge = document.createElement('span');
                newBadge.className = 'badge bg-success main-image-badge';
                newBadge.textContent = 'Image principale';
                const cardText = firstImage.querySelector('.card-text');
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
        saveOrderBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Enregistrement...';
        
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
                saveOrderBtn.innerHTML = '<i class="fa fa-save"></i> Enregistrer l\'ordre';
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
        enableReorderBtn.style.display = 'inline-block';
        saveOrderBtn.style.display = 'none';
        cancelReorderBtn.style.display = 'none';
        if (reorderInstructions) {
            reorderInstructions.style.display = 'none';
        }
        
        // Masquer les poignées et réafficher les boutons
        const dragHandles = document.querySelectorAll('.drag-handle');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        
        dragHandles.forEach(handle => {
            handle.style.display = 'none';
        });
        
        deleteButtons.forEach(btn => {
            btn.style.display = 'inline-block';
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
    
    // Initialiser les événements quand la page est chargée
    updateSelectionCount();
});
</script>

<style>
/* Styles pour la sélection multiple */
.image-card.selection-mode {
    cursor: pointer;
    transition: all 0.3s ease;
}

.image-card.selected {
    border: 2px solid #007bff;
    background-color: #e3f2fd;
    transform: scale(0.98);
}

.image-checkbox {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px;
    border-radius: 3px;
}

.image-checkbox input[type="checkbox"] {
    transform: scale(1.5);
}

.image-container {
    position: relative;
}

.image-card {
    transition: all 0.3s ease;
}

.image-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    background: rgba(0, 123, 255, 0.1);
    border: 1px dashed #007bff;
    font-weight: bold;
    color: #007bff;
    user-select: none;
}

.drag-handle:hover {
    background: rgba(0, 123, 255, 0.2);
}
</style>

<?php include "admin_footer.php"; ?>