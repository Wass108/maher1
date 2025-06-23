<?php
// Inclure les fichiers nécessaires (en utilisant la version buffered du header)
include "../connect/connect.php";
include "admin_header_buffered.php";  // On utilise désormais la version avec buffer

$message = '';
$error = '';

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
        // Vérifier si le slug existe déjà
        $query = "SELECT COUNT(*) as count FROM projects WHERE slug = :slug";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error = "Ce slug est déjà utilisé. Veuillez en choisir un autre.";
        } else {
            // Insérer le projet dans la base de données
            $query = "INSERT INTO projects (title, slug, description, year, category, client, website, btnText, featured, hidden, created_at) 
                      VALUES (:title, :slug, :description, :year, :category, :client, :website, :btnText, :featured, :hidden, NOW())";
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
                'hidden' => $hidden
            ]);
            
            if ($result) {
                $projectId = $pdo->lastInsertId();
                
                // Créer le dossier pour les images du projet
                $projectDir = "../img/projects/" . $slug . "/";
                if (!is_dir($projectDir)) {
                    mkdir($projectDir, 0777, true);
                }
                
                // Rediriger vers la page de gestion des images du projet
                $_SESSION['message'] = "Le projet a été créé avec succès. Vous pouvez maintenant ajouter des images.";
                header("Location: manage_images.php?project=" . $projectId);
                exit;
            } else {
                $error = "Une erreur est survenue lors de l'ajout du projet.";
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
                <h1 class="text-2xl font-bold text-gray-900">Ajouter un projet</h1>
                <p class="text-gray-600 mt-1">Créez un nouveau projet d'architecture</p>
            </div>
            <nav class="flex space-x-2 text-sm">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Accueil</a>
                <span class="text-gray-400">/</span>
                <a href="projects.php" class="text-gray-500 hover:text-gray-700">Projets</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">Ajouter un projet</span>
            </nav>
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
            <h3 class="text-lg font-semibold text-gray-900">Informations du projet</h3>
            <p class="text-sm text-gray-600 mt-1">Remplissez les détails de votre nouveau projet</p>
        </div>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="p-6 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Titre du projet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           placeholder="Entrez le titre du projet">
                </div>
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                        Slug <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="slug" 
                           name="slug" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           placeholder="url-du-projet">
                    <p class="mt-1 text-xs text-gray-500">Utilisé pour l'URL du projet. Lettres minuscules, chiffres et tirets uniquement.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                    <select id="category" 
                            name="category"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                        <option value="loge">Logements</option>
                        <option value="hotels">Hotels</option>
                        <option value="indus">Industries</option>
                        <option value="bim">BIM</option>
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                    <input type="number" 
                           id="year" 
                           name="year" 
                           min="2000" 
                           max="2099"
                           value="<?php echo date('Y'); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                </div>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="description" 
                          name="description" 
                          rows="5"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                          placeholder="Décrivez votre projet..."></textarea>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="client" class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                    <input type="text" 
                           id="client" 
                           name="client"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           placeholder="Nom du client">
                </div>
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">Site web</label>
                    <input type="url" 
                           id="website" 
                           name="website"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                           placeholder="https://exemple.com">
                </div>
            </div>
            <div>
                <label for="btnText" class="block text-sm font-medium text-gray-700 mb-2">Texte du bouton</label>
                <input type="text" 
                       id="btnText" 
                       name="btnText" 
                       value="Voir le projet"
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
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="projects.php" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer le projet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Génération automatique du slug à partir du titre
    document.getElementById('title').addEventListener('input', function() {
        var title = this.value;
        var slug = title.toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/_+/g, '_')
                        .replace(/^_|_$/g, '');
        document.getElementById('slug').value = slug;
    });
    
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
    });
</script>

<?php include "admin_footer.php"; ?>