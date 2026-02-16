<?php
/**
 * Action : Récolter une plante prête.
 */
require_once __DIR__ . '/../classes/Game.php';

function handleHarvest(): array
{
    $plantId = $_GET['id'] ?? '';

    if (empty($plantId) || !preg_match('/^[a-f0-9]{16}$/', $plantId)) {
        return ['message' => 'ID de plante invalide.', 'type' => 'error'];
    }

    try {
        $result = Game::harvest($plantId);
        return [
            'message' => "🌸 Récolte de {$result['name']} (rang {$result['rarity']}) : +{$result['petals']} pétales !",
            'type' => 'success',
        ];
    } catch (Exception $e) {
        return ['message' => $e->getMessage(), 'type' => 'error'];
    }
}
