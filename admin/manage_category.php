
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

// Vérifier si la catégorie est spécifiée et valide
if (!isset($_GET['category']) || !in_array($_GET['category'], $categories)) {
    header("Location: portfolio.php");
    exit;
}

$category = $_GET['category'];
$categoryDir = $portfolioMainDir . $category . "/";

// Messages de feedback
$message = '';
$error = '';

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

// Récupérer les images de cette catégorie
$categoryImages = [];
if (is_dir($categoryDir)) {
    $images = glob($categoryDir . "*.{jpg,jpeg,png,gif,PNG}", GLOB_BRACE);
    foreach ($images as $image) {
        $categoryImages[] = [
            'path' => $image,
            'name' => basename($image),
            'size' => filesize($image),
            'url' => $image,
            'modified' => filemtime($image)
        ];
    }
    
    // Trier par date de modification (plus récent en premier)
    usort($categoryImages, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Calculer les statistiques
$totalImages = count($categoryImages);
$totalSize = array_sum(array_column($categoryImages, 'size'));

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
                        <i class="fas fa-images mr-3"></i>
                        Images de "<?php echo htmlspecialchars($category); ?>"
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Triées par date de modification (plus récentes en premier)</p>
                </div>
                <?php if ($totalImages > 0): ?>
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
                        <div id="category-actions" class="flex items-center space-x-2">
                            <button type="button" onclick="toggleSelectionMode()"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-check-square mr-2"></i>
                                Sélection multiple
                            </button>
                            <a href="empty_portfolio_category.php?category=<?php echo urlencode($category); ?>&redirect=manage_category" 
                               onclick="return confirm('Êtes-vous sûr de vouloir vider cette catégorie ? Toutes les images seront supprimées mais la catégorie restera.');"
                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 transition-colors duration-200">
                                <i class="fas fa-broom mr-2"></i>
                                Vider
                            </a>
                            <a href="delete_portfolio_category.php?category=<?php echo urlencode($category); ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer complètement cette catégorie ? Le dossier et toutes les images seront supprimés.');"
                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer catégorie
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($categoryImages)): ?>
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
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php foreach ($categoryImages as $image): ?>
                        <div class="image-container relative group" data-image="<?php echo htmlspecialchars($image['name']); ?>">
                            <div class="image-checkbox absolute top-3 right-3 z-10 hidden">
                                <input type="checkbox" class="image-select h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                       value="<?php echo htmlspecialchars($image['name']); ?>" onchange="updateSelectionCount()">
                            </div>
                            <div class="image-card bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-200">
                                <div class="aspect-w-1 aspect-h-1 bg-gray-100">
                                    <img src="<?php echo $image['url']; ?>" 
                                         alt="Image" 
                                         class="w-full h-32 object-cover">
                                </div>
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2 image-actions">
                                        <a href="<?php echo $image['url']; ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200"
                                           title="Voir en grand">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                        <a href="delete_portfolio_image.php?category=<?php echo urlencode($category); ?>&image=<?php echo urlencode($image['name']); ?>&redirect=manage_category" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');" 
                                           class="inline-flex items-center p-2 border border-transparent rounded-full text-white bg-red-600 hover:bg-red-700 transition-colors duration-200"
                                           title="Supprimer">
                                            <i class="fas fa-trash text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <h6 class="text-xs font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($image['name']); ?>">
                                        <?php echo htmlspecialchars($image['name']); ?>
                                    </h6>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <p><?php echo round($image['size'] / 1024, 1); ?> KB</p>
                                        <p><?php echo date('d/m/Y H:i', $image['modified']); ?></p>
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
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
<script>
let selectionMode = false;

function toggleSelectionMode() {
    selectionMode = !selectionMode;
    
    if (selectionMode) {
        // Activer le mode sélection
        $('.image-checkbox').removeClass('hidden');
        $('.image-card').addClass('selection-mode');
        $('#selection-controls').removeClass('hidden');
        $('#bulk-actions').removeClass('hidden');
        $('#category-actions').addClass('hidden');
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
    $('#category-actions').removeClass('hidden');
    
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

// Permettre de cliquer sur la carte pour sélectionner/désélectionner
$(document).on('click', '.image-card.selection-mode', function(e) {
    // Éviter le conflit avec les boutons d'action
    if ($(e.target).closest('.image-actions, .image-checkbox').length > 0) {
        return;
    }
    
    const checkbox = $(this).find('.image-select');
    checkbox.prop('checked', !checkbox.prop('checked'));
    checkbox.trigger('change');
});

// Initialiser les événements quand la page est chargée
$(document).ready(function() {
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

@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 640px) and (max-width: 768px) {
    .sm\:grid-cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
</style>

<?php include "admin_footer.php"; ?>