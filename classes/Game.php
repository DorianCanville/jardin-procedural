<?php
/**
 * Game — Contrôleur principal : plantation, récolte, état du jeu.
 */
class Game
{
    private Storage $storage;
    private array $config;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
        $this->config = $storage->read('config.json');

        // Initialiser created_at si premier lancement
        $player = $this->storage->read('player.json');
        if ($player['created_at'] === 0) {
            $player['created_at'] = time();
            $this->storage->write('player.json', $player);
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getStorage(): Storage
    {
        return $this->storage;
    }

    // --- Jardin ---

    /**
     * Retourne toutes les plantes du jardin avec leur état actuel.
     */
    public function getGarden(): array
    {
        $plantsData = $this->storage->read('plants.json');
        $plants = [];

        foreach ($plantsData as $pd) {
            $plant = new Plant($pd, $this->config);
            $plants[] = [
                'plant' => $plant,
                'data' => $pd,
                'percent' => $plant->getGrowthPercent(),
                'ready' => $plant->isReady(),
                'remaining' => $plant->getTimeRemainingFormatted(),
                'visual' => $plant->getVisualAttributes(),
            ];
        }

        return $plants;
    }

    /**
     * Retourne les emplacements libres du jardin.
     */
    public function getFreeSlots(): array
    {
        $maxSlots = $this->config['garden']['max_slots'];
        $plantsData = $this->storage->read('plants.json');

        $usedSlots = array_column($plantsData, 'slot');
        $freeSlots = [];

        for ($i = 0; $i < $maxSlots; $i++) {
            if (!in_array($i, $usedSlots, true)) {
                $freeSlots[] = $i;
            }
        }

        return $freeSlots;
    }

    // --- Plantation ---

    /**
     * Plante une graine de l'inventaire dans un emplacement du jardin.
     */
    public function plantSeed(string $seedId, int $slot): Plant
    {
        // Valider l'emplacement
        $maxSlots = $this->config['garden']['max_slots'];
        if ($slot < 0 || $slot >= $maxSlots) {
            throw new InvalidArgumentException("Emplacement invalide : {$slot}");
        }

        // Vérifier que l'emplacement est libre
        $freeSlots = $this->getFreeSlots();
        if (!in_array($slot, $freeSlots, true)) {
            throw new RuntimeException("L'emplacement {$slot} est déjà occupé.");
        }

        // Trouver la graine dans l'inventaire
        $inventory = $this->storage->read('inventory.json');
        $seedIndex = null;
        $seedData = null;

        foreach ($inventory['seeds'] as $i => $s) {
            if ($s['id'] === $seedId) {
                $seedIndex = $i;
                $seedData = $s;
                break;
            }
        }

        if ($seedData === null) {
            throw new RuntimeException("Graine introuvable : {$seedId}");
        }

        // Créer la plante
        $plant = Plant::createFromSeed($seedData, $slot, $this->config);

        // Retirer la graine de l'inventaire
        array_splice($inventory['seeds'], $seedIndex, 1);
        $this->storage->write('inventory.json', $inventory);

        // Ajouter la plante au jardin
        $this->storage->update('plants.json', function (array $plants) use ($plant) {
            $plants[] = $plant->toArray();
            return $plants;
        });

        // Stats joueur
        $this->storage->update('player.json', function (array $player) {
            $player['total_planted']++;
            return $player;
        });

        return $plant;
    }

    // --- Récolte ---

    /**
     * Récolte une plante mature.
     */
    public function harvest(string $plantId): array
    {
        $plantsData = $this->storage->read('plants.json');
        $plantIndex = null;
        $plantData = null;

        foreach ($plantsData as $i => $pd) {
            if ($pd['id'] === $plantId) {
                $plantIndex = $i;
                $plantData = $pd;
                break;
            }
        }

        if ($plantData === null) {
            throw new RuntimeException("Plante introuvable : {$plantId}");
        }

        $plant = new Plant($plantData, $this->config);

        // Vérification serveur : la plante est-elle prête ?
        if (!$plant->isReady()) {
            $remaining = $plant->getTimeRemainingFormatted();
            throw new RuntimeException("Cette plante n'est pas encore prête. Temps restant : {$remaining}");
        }

        // Calculer la récolte
        $petalYield = $plant->calculatePetalYield();
        $visual = $plant->getVisualAttributes();

        // Retirer la plante du jardin
        array_splice($plantsData, $plantIndex, 1);
        $this->storage->write('plants.json', $plantsData);

        // Ajouter au joueur
        $this->storage->update('player.json', function (array $player) use ($petalYield) {
            $player['petals'] += $petalYield;
            $player['total_harvested']++;
            return $player;
        });

        // Archiver la plante dans l'inventaire
        $this->storage->update('inventory.json', function (array $inv) use ($plantData, $petalYield, $visual) {
            $inv['harvested_plants'][] = [
                'plant' => $plantData,
                'visual' => $visual,
                'petals_earned' => $petalYield,
                'harvested_at' => time(),
            ];
            return $inv;
        });

        return [
            'plant' => $plant,
            'petals' => $petalYield,
            'visual' => $visual,
        ];
    }

    // --- Statistiques ---

    /**
     * Retourne les stats complètes du joueur.
     */
    public function getStats(): array
    {
        $player = $this->storage->read('player.json');
        $inventory = $this->storage->read('inventory.json');
        $plants = $this->storage->read('plants.json');

        $seedsByRarity = [];
        foreach ($inventory['seeds'] as $s) {
            $r = $s['rarity'];
            $seedsByRarity[$r] = ($seedsByRarity[$r] ?? 0) + 1;
        }

        $harvestedByRarity = [];
        foreach ($inventory['harvested_plants'] as $h) {
            $r = $h['plant']['rarity'];
            $harvestedByRarity[$r] = ($harvestedByRarity[$r] ?? 0) + 1;
        }

        return [
            'player' => $player,
            'seeds_count' => count($inventory['seeds']),
            'seeds_by_rarity' => $seedsByRarity,
            'plants_growing' => count($plants),
            'total_harvested_plants' => count($inventory['harvested_plants']),
            'harvested_by_rarity' => $harvestedByRarity,
            'play_time' => $player['created_at'] > 0 ? time() - $player['created_at'] : 0,
        ];
    }
}
