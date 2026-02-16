<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Plant.php';
require_once __DIR__ . '/Seed.php';
require_once __DIR__ . '/Shop.php';

/**
 * Game - Contrôleur principal du jeu.
 * Gère les actions du joueur et coordonne les autres classes.
 */
class Game
{
    /**
     * Initialise le joueur si c'est sa première visite.
     */
    public static function init(): void
    {
        $player = Storage::read('player.json');
        if (($player['created_at'] ?? 0) === 0) {
            $config = Storage::read('config.json');
            $player['gold'] = $config['starting_gold'];
            $player['created_at'] = time();
            Storage::write('player.json', $player);
        }
    }

    /**
     * Plante une graine depuis l'inventaire dans le jardin.
     */
    public static function plantSeed(string $seedId): Plant
    {
        $config = Storage::read('config.json');
        $plants = Storage::read('plants.json');
        $inventory = Storage::read('inventory.json');

        // Vérifier le nombre de slots
        if (count($plants['garden']) >= $config['garden_max_slots']) {
            throw new RuntimeException("Jardin plein ! ({$config['garden_max_slots']} emplacements max)");
        }

        // Trouver la graine dans l'inventaire
        $seedIndex = null;
        $seedData = null;
        foreach ($inventory['seeds'] as $index => $s) {
            if ($s['id'] === $seedId) {
                $seedIndex = $index;
                $seedData = $s;
                break;
            }
        }

        if ($seedData === null) {
            throw new InvalidArgumentException("Graine introuvable: {$seedId}");
        }

        // Générer la plante
        $plant = Plant::generate($seedData['rarity'], $seedData['plant_seed']);
        $plant->planted_at = time();

        // Retirer la graine de l'inventaire
        array_splice($inventory['seeds'], $seedIndex, 1);
        Storage::write('inventory.json', $inventory);

        // Ajouter au jardin
        $plants['garden'][] = $plant->toArray();
        Storage::write('plants.json', $plants);

        return $plant;
    }

    /**
     * Récolte une plante prête.
     * @return array ['petals' => int, 'rarity' => string, 'name' => string]
     */
    public static function harvest(string $plantId): array
    {
        $plants = Storage::read('plants.json');
        $player = Storage::read('player.json');

        $plantIndex = null;
        $plantData = null;
        foreach ($plants['garden'] as $index => $p) {
            if ($p['id'] === $plantId) {
                $plantIndex = $index;
                $plantData = $p;
                break;
            }
        }

        if ($plantData === null) {
            throw new InvalidArgumentException("Plante introuvable: {$plantId}");
        }

        $plant = Plant::fromArray($plantData);

        // Vérification serveur : la plante doit avoir fini de pousser
        if (!$plant->isGrown()) {
            $remaining = $plant->getFormattedRemainingTime();
            throw new RuntimeException("Plante pas encore prête ! Temps restant: {$remaining}");
        }

        $petals = $plant->petal_yield;

        // Ajouter les pétales au joueur
        $player['petals'][$plant->rarity] = ($player['petals'][$plant->rarity] ?? 0) + $petals;
        $player['stats']['total_petals_harvested'] += $petals;
        $player['stats']['total_plants_grown']++;

        // Mise à jour de la plante la plus rare
        $rarityOrder = ['E', 'D', 'C', 'B', 'A', 'S'];
        $currentBest = array_search($player['stats']['rarest_plant'], $rarityOrder);
        $harvestedRarity = array_search($plant->rarity, $rarityOrder);
        if ($harvestedRarity > $currentBest) {
            $player['stats']['rarest_plant'] = $plant->rarity;
        }

        Storage::write('player.json', $player);

        // Retirer la plante du jardin
        array_splice($plants['garden'], $plantIndex, 1);
        Storage::write('plants.json', $plants);

        return [
            'petals' => $petals,
            'rarity' => $plant->rarity,
            'name' => $plant->name,
        ];
    }

    /**
     * Retourne l'état actuel du jardin avec les calculs de croissance.
     */
    public static function getGardenState(): array
    {
        $plants = Storage::read('plants.json');
        $result = [];

        foreach ($plants['garden'] as $plantData) {
            $plant = Plant::fromArray($plantData);
            $result[] = [
                'plant' => $plant,
                'growth_percent' => $plant->getGrowthPercent(),
                'remaining_time' => $plant->getFormattedRemainingTime(),
                'is_grown' => $plant->isGrown(),
            ];
        }

        return $result;
    }

    /**
     * Retourne les graines de l'inventaire.
     */
    public static function getInventory(): array
    {
        $inventory = Storage::read('inventory.json');
        $seeds = [];
        foreach ($inventory['seeds'] as $seedData) {
            $seeds[] = Seed::fromArray($seedData);
        }
        return $seeds;
    }

    /**
     * Retourne les données du joueur.
     */
    public static function getPlayer(): array
    {
        return Storage::read('player.json');
    }

    /**
     * Réinitialise complètement le jeu.
     */
    public static function resetGame(): void
    {
        $config = Storage::read('config.json');
        Storage::write('player.json', [
            'name' => 'Jardinier',
            'gold' => $config['starting_gold'],
            'petals' => ['E' => 0, 'D' => 0, 'C' => 0, 'B' => 0, 'A' => 0, 'S' => 0],
            'stats' => [
                'total_plants_grown' => 0,
                'total_petals_harvested' => 0,
                'total_gold_earned' => 0,
                'total_gold_spent' => 0,
                'total_packs_bought' => 0,
                'rarest_plant' => 'E',
            ],
            'created_at' => time(),
        ]);
        Storage::write('plants.json', ['garden' => []]);
        Storage::write('inventory.json', ['seeds' => []]);
    }
}
