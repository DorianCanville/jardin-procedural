<?php
/**
 * Action : Récolter une plante mature.
 * Méthode : POST
 * Paramètres : plant_id (string)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=garden');
    exit;
}

$plantId = trim($_POST['plant_id'] ?? '');

// Validation
if ($plantId === '' || !preg_match('/^plant_[a-f0-9]{16}$/', $plantId)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Identifiant de plante invalide.'];
    header('Location: ?page=garden');
    exit;
}

try {
    $result = $game->harvest($plantId);
    $visual = $result['visual'];
    $petals = $result['petals'];
    $rarity = $result['plant']->getRarity();

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => "Recolte de {$visual['name']} [{$rarity}] : +{$petals} petales !",
    ];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header('Location: ?page=garden');
exit;
