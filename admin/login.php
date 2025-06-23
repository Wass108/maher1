<?php
session_start();
include "../connect/connect.php";

// Si l'utilisateur est déjà connecté, le rediriger vers le tableau de bord
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Vérifier si l'utilisateur existe dans la base de données
    $query = "SELECT * FROM admin_users WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    // Si l'utilisateur existe et que le mot de passe est correct
    if ($user && password_verify($password, $user['password'])) {
        // Créer une session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        
        // Rediriger vers le tableau de bord
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Identifiants incorrects. Veuillez réessayer.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Administration BYM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Carte de connexion -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            
            <!-- En-tête avec logo -->
            <div class="bg-gray-900 px-8 py-12 text-center">
                <div class="mb-4">
                    <img src="../img/logo.png" alt="BYM Logo" class="mx-auto h-16 w-auto">
                </div>
                <h1 class="text-2xl font-light text-white tracking-wide">ADMINISTRATION</h1>
                <p class="text-gray-300 text-sm mt-2">Espace de gestion BYM</p>
            </div>

            <!-- Contenu du formulaire -->
            <div class="px-8 py-8">
                
                <!-- Message d'erreur -->
                <?php if(!empty($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 rounded">
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

                <!-- Formulaire -->
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6">
                    
                    <!-- Nom d'utilisateur -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom d'utilisateur
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition-colors duration-200"
                            placeholder="Votre nom d'utilisateur"
                        >
                    </div>

                    <!-- Mot de passe -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Mot de passe
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition-colors duration-200"
                            placeholder="Votre mot de passe"
                        >
                    </div>

                    <!-- Bouton de connexion -->
                    <div class="pt-4">
                        <button 
                            type="submit" 
                            class="w-full bg-gray-900 text-white py-3 px-4 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-colors duration-200 font-medium"
                        >
                            Se connecter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pied de page -->
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center">
                    © 2025 Tous droits réservés.
                </p>
            </div>
        </div>
    </div>

</body>
</html>