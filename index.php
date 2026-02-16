<?php
/**
 * FloraGen — Jeu de culture de plantes procédurales
 * Point d'entrée principal et routeur.
 */

declare(strict_types=1);
session_start();

// Autoload des classes
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialisation
$storage = new Storage(__DIR__ . '/data');
$game = new Game($storage);
$renderer = new Renderer($game->getConfig());

// Routage
$page = $_GET['page'] ?? 'garden';
$action = $_GET['do'] ?? null;

// Traitement des actions POST
if ($page === 'action' && $action !== null) {
    $allowedActions = ['plant', 'harvest', 'buy_pack', 'sell'];

    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Action inconnue.'];
        header('Location: ?page=garden');
        exit;
    }

    $actionFile = __DIR__ . '/actions/' . $action . '.php';
    if (file_exists($actionFile)) {
        require $actionFile;
    }
    exit;
}

// Récupérer les messages flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Rendu des pages
$allowedPages = ['garden', 'shop', 'inventory', 'stats'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'garden';
}

$content = '';

switch ($page) {
    case 'garden':
        $garden = $game->getGarden();
        $freeSlots = $game->getFreeSlots();
        $player = $storage->read('player.json');
        $inventory = $storage->read('inventory.json');

        if ($flash) {
            $content .= $renderer->renderMessage($flash['message'], $flash['type'], 'garden');
            $content .= '<hr style="border-color:#333;margin:1rem 0">';
        }

        $content .= $renderer->renderGarden($garden, $freeSlots, $player, $inventory['seeds']);
        break;

    case 'shop':
        $player = $storage->read('player.json');

        // Récupérer le résultat du dernier achat si présent
        $lastPurchase = null;
        if (isset($_SESSION['last_purchase'])) {
            $purchaseData = $_SESSION['last_purchase'];
            unset($_SESSION['last_purchase']);

            // Reconstituer les objets Seed
            $lastPurchase = [
                'seeds' => array_map(fn(array $s) => new Seed($s), $purchaseData['seeds']),
                'price' => $purchaseData['price'],
            ];
        }

        $error = ($flash && $flash['type'] === 'error') ? $flash['message'] : null;
        $success = ($flash && $flash['type'] === 'success') ? $flash['message'] : null;

        $content .= $renderer->renderShop($player, $lastPurchase, $error, $success);
        break;

    case 'inventory':
        $player = $storage->read('player.json');
        $inventory = $storage->read('inventory.json');

        if ($flash) {
            $content .= $renderer->renderMessage($flash['message'], $flash['type'], 'inventory');
            $content .= '<hr style="border-color:#333;margin:1rem 0">';
        }

        $content .= $renderer->renderInventory($inventory, $player);
        break;

    case 'stats':
        $stats = $game->getStats();
        $content .= $renderer->renderStats($stats);
        break;
}

// Titres
$titles = [
    'garden' => 'Jardin',
    'shop' => 'Boutique',
    'inventory' => 'Inventaire',
    'stats' => 'Statistiques',
];

echo $renderer->layout($titles[$page] ?? 'FloraGen', $content, $page);
