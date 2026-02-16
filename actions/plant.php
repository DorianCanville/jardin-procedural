<?php
/**
 * Action : Planter une graine depuis l'inventaire.
 */
require_once __DIR__ . '/../classes/Game.php';

function handlePlant(): array
{
    $seedId = $_GET['id'] ?? '';

    if (empty($seedId) || !preg_match('/^[a-f0-9]{16}$/', $seedId)) {
        return ['message' => 'ID de graine invalide.', 'type' => 'error'];
    }

    try {
        $plant = Game::plantSeed($seedId);
        $time = $plant->getFormattedRemainingTime();
        return [
            'message' => "🌱 {$plant->name} (rang {$plant->rarity}) plantée ! Temps de croissance : {$time}",
            'type' => 'success',
        ];
    } catch (Exception $e) {
        return ['message' => $e->getMessage(), 'type' => 'error'];
    }
}
