<?php
/**
 * Action : Vendre des pétales contre des pièces.
 * Méthode : POST
 * Paramètres : amount (int)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=shop');
    exit;
}

$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);

// Validation
if ($amount === false || $amount === null || $amount < 1) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Quantite invalide.'];
    header('Location: ?page=shop');
    exit;
}

if ($amount > 1000000) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Quantite trop elevee.'];
    header('Location: ?page=shop');
    exit;
}

try {
    $shop = new Shop($game->getStorage(), $game->getConfig());
    $result = $shop->sellPetals($amount);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => "{$amount} petales vendus pour {$result['gold_earned']} pieces !",
    ];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header('Location: ?page=shop');
exit;
