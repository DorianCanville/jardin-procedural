<?php
/**
 * Action : Acheter un pack de graines.
 */
require_once __DIR__ . '/../classes/Shop.php';

function handleBuyPack(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['message' => 'Méthode non autorisée.', 'type' => 'error'];
    }

    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_INT);

    if ($price === false || $price === null || $price <= 0) {
        return ['message' => 'Prix invalide.', 'type' => 'error'];
    }

    // Limite anti-abus
    if ($price > 1000000) {
        return ['message' => 'Montant trop élevé.', 'type' => 'error'];
    }

    try {
        $result = Shop::buyPack($price);
        return [
            'message' => "📦 Pack acheté pour {$price} pièces !",
            'type' => 'success',
            'seeds' => $result['seeds'],
            'probabilities' => $result['probabilities'],
        ];
    } catch (Exception $e) {
        return ['message' => $e->getMessage(), 'type' => 'error'];
    }
}
