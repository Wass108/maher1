<?php
// Ce header est conçu pour permettre les redirections même après son inclusion
// Il utilise output buffering pour retarder l'envoi du HTML au navigateur

// Activer la mise en buffer de sortie
ob_start();

// Inclure la vérification d'authentification
include 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration BYM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'slide-down': 'slideDown 0.2s ease-out',
                        'fade-in': 'fadeIn 0.15s ease-in',
                    },
                    keyframes: {
                        slideDown: {
                            '0%': { opacity: '0', transform: 'translateY(-10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b border-gray-200 fixed w-full z-30 top-0">
        <div class="px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="../img/logo.png" alt="BYM Logo" class="h-8 w-auto mr-3">
                        <h1 class="text-xl font-bold text-gray-900">BYM Admin</h1>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="../index.php" target="_blank" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium flex items-center transition-colors duration-200">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Voir le site
                    </a>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 rounded-md px-3 py-2 transition-colors duration-200">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-gray-600 text-sm"></i>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 focus:outline-none" style="display: none;">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                                <i class="fas fa-user-circle mr-2"></i>
                                Mon profil
                            </a>
                            <hr class="my-1">
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition-colors duration-150">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
                <div class="md:hidden flex items-center">
                    <button type="button" class="text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-500 p-2" x-data="{ open: false }" @click="open = !open">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <div class="fixed inset-y-0 left-0 z-20 w-64 bg-white border-r border-gray-200 pt-16 overflow-y-auto">
        <div class="px-3 py-6">
            <nav class="space-y-1">
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-gray-100 text-gray-900 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-150">
                    <i class="fas fa-chart-line <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Tableau de bord
                </a>
                <a href="projects.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'bg-gray-100 text-gray-900 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-150">
                    <i class="fas fa-building <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Projets
                </a>
                <a href="portfolio.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'bg-gray-100 text-gray-900 border-r-2 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-150">
                    <i class="fas fa-images <?php echo basename($_SERVER['PHP_SELF']) == 'portfolio.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?> mr-3"></i>
                    Portfolio
                </a>
            </nav>
        </div>
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-gray-50">
            <div class="text-center">
                <p class="text-xs text-gray-400">© 2025 BYM Architecture</p>
            </div>
        </div>
    </div>

    <div class="pl-64 pt-16">
        <main class="min-h-screen">
<?php
// Le buffer continue à capturer tout le HTML jusqu'à ce qu'il soit explicitement vidé
?>