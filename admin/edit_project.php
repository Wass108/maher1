<?php
// Inclure les fichiers nécessaires (en utilisant la version buffered du header)
include "../connect/connect.php";
include "admin_header_buffered.php";  // On utilise désormais la version avec buffer

$message = '';
$error = '';

// Vérifier si l'ID du projet est spécifié
if (!isset($_GET['id'])) {
    header("Location: projects.php");
    exit;
}

$projectId = $_GET['id'];

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

// Si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $year = trim($_POST['year']);
    $category = trim($_POST['category']);
    $client = trim($_POST['client']);
    $website = trim($_POST['website']);
    $btnText = trim($_POST['btnText']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $hidden = isset($_POST['hidden']) ? 1 : 0;
    
    // Logique pour empêcher qu'un projet soit à la fois en une et masqué
    if ($featured == 1 && $hidden == 1) {
        // Si les deux sont cochés, on privilégie "masqué" et on désactive "en une"
        $featured = 0;
        $error = "Un projet ne peut pas être à la fois en une et masqué. Le projet sera masqué mais pas mis en une.";
    }
    
    // Valider les données
    if (empty($title) || empty($slug)) {
        $error .= "Le titre et le slug sont obligatoires.";
    } else {
        // Vérifier si le slug existe déjà (sauf pour le projet actuel)
        $query = "SELECT COUNT(*) as count FROM projects WHERE slug = :slug AND id != :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['slug' => $slug, 'id' => $projectId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error = "Ce slug est déjà utilisé. Veuillez en choisir un autre.";
        } else {
            // Si le slug a changé, renommer le dossier des images
            if ($slug !== $project['slug']) {
                $oldDir = "../img/projects/" . $project['slug'] . "/";
                $newDir = "../img/projects/" . $slug . "/";
                
                if (is_dir($oldDir)) {
                    // Créer le nouveau dossier s'il n'existe pas
                    if (!is_dir($newDir)) {
                        mkdir($newDir, 0777, true);
                    }
                    
                    // Copier tous les fichiers du vieux dossier vers le nouveau
                    $files = glob($oldDir . "*.*");
                    foreach($files as $file) {
                        $fileName = basename($file);
                        copy($file, $newDir . $fileName);
                    }
                    
                    // Supprimer l'ancien dossier et son contenu
                    array_map('unlink', glob($oldDir . "*.*"));
                    rmdir($oldDir);
                }
            }
            
            // Mettre à jour le projet dans la base de données
            $query = "UPDATE projects SET 
                      title = :title, 
                      slug = :slug, 
                      description = :description, 
                      year = :year, 
                      category = :category, 
                      client = :client, 
                      website = :website, 
                      btnText = :btnText, 
                      featured = :featured, 
                      hidden = :hidden, 
                      updated_at = NOW()
                      WHERE id = :id";
                      
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'year' => $year,
                'category' => $category,
                'client' => $client,
                'website' => $website,
                'btnText' => $btnText,
                'featured' => $featured,
                'hidden' => $hidden,
                'id' => $projectId
            ]);
            
            if ($result) {
                // S'assurer que le dossier pour les images existe
                $projectDir = "../img/projects/" . $slug . "/";
                if (!is_dir($projectDir)) {
                    mkdir($projectDir, 0777, true);
                }
                
                $_SESSION['message'] = "Le projet a été mis à jour avec succès.";
                header("Location: projects.php");
                exit;
            } else {
                $error = "Une erreur est survenue lors de la mise à jour du projet.";
            }
        }
    }
}

// À ce stade, toutes les redirections potentielles ont été traitées
// On peut donc vider le buffer pour afficher la page
ob_end_flush();
?>
<div class="bg-white border-b border-gray-200 mb-6">
    <div class="px-6 py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Modifier le projet</h1>
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
                    <span class="text-gray-900 font-medium">Modifier</span>
                </nav>
            </div>
        </div>
    </div>
</div>
<div class="px-6">
    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 border-l-4 border-red-400 bg-red-50 rounded-md">
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
        <div class="mb-6 p-4 border-l-4 border-green-400 bg-green-50 rounded-md">
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
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Informations du projet</h3>
                    <p class="text-sm text-gray-600 mt-1">Modifiez les détails de votre projet</p>
                </div>
                <a href="manage_images.php?project=<?php echo $projectId; ?>" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 transition-colors duration-200">
                    <i class="fas fa-images mr-2"></i>
                    Gérer les images
                </a>
            </div>
        </div>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $projectId); ?>" class="p-6 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Titre du projet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           required
                           value="<?php echo htmlspecialchars($project['title']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                </div>
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                        Slug <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="slug" 
                           name="slug" 
                           required
                           value="<?php echo htmlspecialchars($project['slug']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <p class="mt-1 text-xs text-gray-500">Utilisé pour l'URL du projet. Lettres minuscules, chiffres et tirets uniquement.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                    <select id="category" 
                            name="category"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                        <option value="loge" <?php echo $project['category'] === 'loge' ? 'selected' : ''; ?>>Logements</option>
                        <option value="hotels" <?php echo $project['category'] === 'hotels' ? 'selected' : ''; ?>>Hotels</option>
                        <option value="indus" <?php echo $project['category'] === 'indus' ? 'selected' : ''; ?>>Industries</option>
                        <option value="bim" <?php echo $project['category'] === 'bim' ? 'selected' : ''; ?>>BIM</option>
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                    <input type="number" 
                           id="year" 
                           name="year" 
                           min="2000" 
                           max="2099"
                           value="<?php echo htmlspecialchars($project['year']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                </div>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="description" 
                          name="description" 
                          rows="5"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                          placeholder="Décrivez votre projet..."><?php echo htmlspecialchars($project['description']); ?></textarea>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="client" class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                    <input type="text" 
                           id="client" 
                           name="client"
                           value="<?php echo htmlspecialchars($project['client']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           placeholder="Nom du client">
                </div>
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">Site web</label>
                    <input type="url" 
                           id="website" 
                           name="website"
                           value="<?php echo htmlspecialchars($project['website']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           placeholder="https://exemple.com">
                </div>
            </div>
            <div>
                <label for="btnText" class="block text-sm font-medium text-gray-700 mb-2">Texte du bouton</label>
                <input type="text" 
                       id="btnText" 
                       name="btnText" 
                       value="<?php echo htmlspecialchars($project['btnText']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
            </div>
            <div class="bg-gray-50 rounded-lg p-6">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Options d'affichage</h4>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   id="featured" 
                                   name="featured"
                                   <?php echo $project['featured'] == 1 ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-3 text-sm text-gray-700">
                                <span class="font-medium">Mettre en Une</span><br>
                                <span class="text-gray-500">Afficher sur la page d'accueil</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   id="hidden" 
                                   name="hidden"
                                   <?php echo $project['hidden'] == 1 ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                            <span class="ml-3 text-sm text-gray-700">
                                <span class="font-medium">Masquer le projet</span><br>
                                <span class="text-gray-500">Ne pas afficher sur le site</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-2">
                            <p class="text-xs text-blue-700">
                                Un projet ne peut pas être à la fois en Une et masqué. Si vous cochez les deux options, l'option "masquer" sera prioritaire.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-blue-50 rounded-lg p-6">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Aperçu et actions</h4>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-eye text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">État actuel</p>
                            <p class="text-xs text-gray-600">
                                <?php if($project['hidden'] == 1): ?>
                                    <span class="text-red-600">Masqué</span>
                                <?php elseif($project['featured'] == 1): ?>
                                    <span class="text-green-600">En Une + Visible</span>
                                <?php else: ?>
                                    <span class="text-blue-600">Visible</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-images text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Images</p>
                            <p class="text-xs text-gray-600">
                                <?php 
                                $imageCount = 0;
                                $projectFolder = "../img/projects/" . $project['slug'] . "/";
                                if (is_dir($projectFolder)) {
                                    $images = glob($projectFolder . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);
                                    $imageCount = count($images);
                                }
                                echo $imageCount . " image(s)";
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-link text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">URL</p>
                            <p class="text-xs text-gray-600 truncate">/project.php?project=<?php echo htmlspecialchars($project['slug']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex space-x-3">
                    <a href="projects.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour à la liste
                    </a>
                    <a href="manage_images.php?project=<?php echo $projectId; ?>" 
                       class="px-4 py-2 border border-purple-300 rounded-md text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                        <i class="fas fa-images mr-2"></i>
                        Gérer les images
                    </a>
                </div>
                <div class="flex space-x-3">
                    <a href="../project.php?project=<?php echo urlencode($project['slug']); ?>" 
                       target="_blank"
                       class="px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Prévisualiser
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
<script>
    // Ajouter un script pour gérer l'interaction entre les deux checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        var featuredCheckbox = document.getElementById('featured');
        var hiddenCheckbox = document.getElementById('hidden');
        
        hiddenCheckbox.addEventListener('change', function() {
            if(this.checked) {
                // Si "masqué" est coché, on décoche "en une"
                featuredCheckbox.checked = false;
                featuredCheckbox.disabled = true;
                featuredCheckbox.parentElement.style.opacity = '0.5';
            } else {
                // Si "masqué" est décoché, on permet de cocher "en une"
                featuredCheckbox.disabled = false;
                featuredCheckbox.parentElement.style.opacity = '1';
            }
        });
        
        featuredCheckbox.addEventListener('change', function() {
            if(this.checked) {
                // Si "en une" est coché, on décoche "masqué"
                hiddenCheckbox.checked = false;
            }
        });
        
        // Initialiser l'état au chargement de la page
        if(hiddenCheckbox.checked) {
            featuredCheckbox.disabled = true;
            featuredCheckbox.parentElement.style.opacity = '0.5';
        }
    });
</script>

<?php include "admin_footer.php"; ?>