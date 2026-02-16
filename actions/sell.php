<?php
/**
 * Action : Vendre des pétales.
 */
require_once __DIR__ . '/../classes/Shop.php';

function handleSell(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['message' => 'Méthode non autorisée.', 'type' => 'error'];
    }

    $rarity = $_POST['rarity'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    // Validation de la rareté
    if (!in_array($rarity, ['E', 'D', 'C', 'B', 'A', 'S'], true)) {
        return ['message' => 'Rareté invalide.', 'type' => 'error'];
    }

    if ($quantity === false || $quantity === null || $quantity <= 0) {
        return ['message' => 'Quantité invalide.', 'type' => 'error'];
    }

    if ($quantity > 999999) {
        return ['message' => 'Quantité trop élevée.', 'type' => 'error'];
    }

    try {
        $gold = Shop::sellPetals($rarity, $quantity);
        return [
            'message' => "💰 {$quantity} pétales {$rarity} vendus pour {$gold} pièces !",
            'type' => 'success',
        ];
    } catch (Exception $e) {
        return ['message' => $e->getMessage(), 'type' => 'error'];
    }
}
