<?php
/**
 * Jardin Procédural - Point d'entrée principal
 * Router simple basé sur les paramètres GET.
 */

// Configuration erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Chargement des classes
require_once __DIR__ . '/classes/Storage.php';
require_once __DIR__ . '/classes/RNG.php';
require_once __DIR__ . '/classes/Plant.php';
require_once __DIR__ . '/classes/Seed.php';
require_once __DIR__ . '/classes/Shop.php';
require_once __DIR__ . '/classes/Game.php';
require_once __DIR__ . '/classes/Renderer.php';

// Chargement des actions
require_once __DIR__ . '/actions/plant.php';
require_once __DIR__ . '/actions/harvest.php';
require_once __DIR__ . '/actions/buy_pack.php';
require_once __DIR__ . '/actions/sell.php';

// Initialiser le joueur si nécessaire
Game::init();

// Router
$page = $_GET['page'] ?? 'garden';
$action = $_GET['action'] ?? '';

// Sécurité : seules les pages valides
$validPages = ['garden', 'shop', 'inventory', 'stats'];
if (!in_array($page, $validPages, true)) {
    $page = 'garden';
}

try {
    switch ($page) {
        case 'garden':
            $message = $_GET['msg'] ?? null;
            $messageType = 'success';

            if ($action === 'harvest') {
                $result = handleHarvest();
                $message = $result['message'];
                $messageType = $result['type'];
            } elseif ($action === 'reset') {
                Game::resetGame();
                $message = '🔄 Jeu réinitialisé !';
                $messageType = 'info';
            }

            $gardenState = Game::getGardenState();
            echo Renderer::gardenPage($gardenState, $message, $messageType);
            break;

        case 'shop':
            $message = null;
            $messageType = 'success';
            $revealedSeeds = null;
            $probabilities = null;

            if ($action === 'buy') {
                $result = handleBuyPack();
                $message = $result['message'];
                $messageType = $result['type'];
                $revealedSeeds = $result['seeds'] ?? null;
                $probabilities = $result['probabilities'] ?? null;
            } elseif ($action === 'sell') {
                $result = handleSell();
                $message = $result['message'];
                $messageType = $result['type'];
            }

            echo Renderer::shopPage($message, $messageType, $revealedSeeds, $probabilities);
            break;

        case 'inventory':
            $message = null;
            $messageType = 'success';

            if ($action === 'plant') {
                $result = handlePlant();
                $message = $result['message'];
                $messageType = $result['type'];
                // Rediriger vers le jardin après plantation réussie
                if ($result['type'] === 'success') {
                    header('Location: ?page=garden&msg=' . urlencode($result['message']));
                    exit;
                }
            }

            $seeds = Game::getInventory();
            echo Renderer::inventoryPage($seeds, $message, $messageType);
            break;

        case 'stats':
            echo Renderer::statsPage();
            break;
    }

} catch(Throwable $e) {
    // Page d'erreur gracieuse
    echo Renderer::header('Erreur', '');
    echo Renderer::flash('❌ Erreur: ' . $e->getMessage(), 'error');
    echo '<p><a href="?page=garden" class="btn btn-primary">Retour au jardin</a></p>';
    echo Renderer::footer();
}
