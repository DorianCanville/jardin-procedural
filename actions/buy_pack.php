<?php
/**
 * Action : Acheter un pack de graines.
 * Méthode : POST
 * Paramètres : price (int)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=shop');
    exit;
}

$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_INT);

// Validation
if ($price === false || $price === null || $price < 1) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Montant invalide.'];
    header('Location: ?page=shop');
    exit;
}

// Protection anti-triche : limite maximale raisonnable
if ($price > 100000) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Montant trop eleve.'];
    header('Location: ?page=shop');
    exit;
}

try {
    $shop = new Shop($game->getStorage(), $game->getConfig());
    $result = $shop->buyPack($price);

    // Stocker le résultat pour affichage
    $_SESSION['last_purchase'] = [
        'seeds' => array_map(fn(Seed $s) => $s->toArray(), $result['seeds']),
        'price' => $price,
    ];

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => "Pack achete pour {$price} pieces ! 3 graines obtenues.",
    ];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header('Location: ?page=shop');
exit;
