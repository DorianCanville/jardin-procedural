<?php
/**
 * Action : Planter une graine dans le jardin.
 * Méthode : POST
 * Paramètres : seed_id (string), slot (int)
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=garden');
    exit;
}

$seedId = trim($_POST['seed_id'] ?? '');
$slot = filter_input(INPUT_POST, 'slot', FILTER_VALIDATE_INT);

// Validation
if ($seedId === '' || !preg_match('/^seed_[a-f0-9]{16}$/', $seedId)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Identifiant de graine invalide.'];
    header('Location: ?page=garden');
    exit;
}

if ($slot === false || $slot === null || $slot < 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Emplacement invalide.'];
    header('Location: ?page=garden');
    exit;
}

try {
    $plant = $game->plantSeed($seedId, $slot);
    $visual = $plant->getVisualAttributes();
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => "Graine plantee ! {$visual['name']} pousse dans l'emplacement {$slot}.",
    ];
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header('Location: ?page=garden');
exit;
