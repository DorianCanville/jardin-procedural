<?php
/**
 * Plant — Représente une plante avec génération procédurale d'attributs visuels.
 */
class Plant
{
    private array $data;
    private array $config;

    public function __construct(array $data, array $config)
    {
        $this->data = $data;
        $this->config = $config;
    }

    // --- Accesseurs ---

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getRarity(): string
    {
        return $this->data['rarity'];
    }

    public function getSeed(): int
    {
        return $this->data['seed'];
    }

    public function getPlantedAt(): int
    {
        return $this->data['planted_at'];
    }

    public function getGrowthDuration(): int
    {
        return $this->data['growth_duration'];
    }

    public function getSlot(): int
    {
        return $this->data['slot'];
    }

    public function isHarvested(): bool
    {
        return $this->data['harvested'] ?? false;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    // --- Logique croissance ---

    /**
     * Calcule le pourcentage de croissance actuel (0 à 100).
     */
    public function getGrowthPercent(?int $now = null): float
    {
        $now = $now ?? time();
        $elapsed = $now - $this->data['planted_at'];

        if ($elapsed <= 0) return 0.0;
        if ($elapsed >= $this->data['growth_duration']) return 100.0;

        return round(($elapsed / $this->data['growth_duration']) * 100, 1);
    }

    /**
     * Vérifie si la plante est prête à être récoltée.
     */
    public function isReady(?int $now = null): bool
    {
        return $this->getGrowthPercent($now) >= 100.0;
    }

    /**
     * Temps restant en secondes avant maturité.
     */
    public function getTimeRemaining(?int $now = null): int
    {
        $now = $now ?? time();
        $remaining = ($this->data['planted_at'] + $this->data['growth_duration']) - $now;
        return max(0, $remaining);
    }

    /**
     * Formate le temps restant en texte lisible.
     */
    public function getTimeRemainingFormatted(?int $now = null): string
    {
        $seconds = $this->getTimeRemaining($now);

        if ($seconds <= 0) return 'Prête !';

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($secs > 0 && $hours === 0) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    // --- Récolte ---

    /**
     * Calcule le nombre de pétales produits à la récolte.
     */
    public function calculatePetalYield(): int
    {
        $rarityConfig = $this->config['rarity'][$this->data['rarity']];
        $baseYield = $rarityConfig['petal_yield'];

        // Variation ±20% déterministe basée sur la seed
        $rng = new RNG($this->data['seed'] + 9999);
        $variation = $rng->nextFloat() * 0.4 + 0.8; // 0.8 à 1.2

        return max(1, (int)round($baseYield * $variation));
    }

    /**
     * Valeur en pièces des pétales de cette plante.
     */
    public function getPetalValue(): int
    {
        return $this->config['rarity'][$this->data['rarity']]['petal_value'];
    }

    // --- Génération procédurale visuelle ---

    /**
     * Génère tous les attributs visuels de la plante à partir de sa seed.
     */
    public function getVisualAttributes(): array
    {
        $rng = new RNG($this->data['seed']);
        $rarity = $this->data['rarity'];
        $rarityIndex = array_search($rarity, array_keys($this->config['rarity']));
        $visual = $this->config['visual_attributes'];

        // Nombre de pétales : augmente avec la rareté
        $minPetals = 3 + $rarityIndex;
        $maxPetals = 5 + $rarityIndex * 3;
        $petalCount = $rng->nextRange($minPetals, $maxPetals);

        // Forme des pétales
        $petalShape = $rng->choice($visual['petal_shapes']);

        // Taille (1-5, tend vers plus grand avec rareté)
        $minSize = max(1, $rarityIndex);
        $size = $rng->nextRange($minSize, min(5, $minSize + 3));

        // Nombre de feuilles
        $leafCount = $rng->nextRange(1, 3 + $rarityIndex);

        // Couleurs
        $primaryColor = $rng->choice($visual['colors']);
        $secondaryColor = $rng->choice($visual['colors']);

        // Motif : les rangs élevés débloquent plus de motifs
        $availablePatterns = array_slice($visual['patterns'], 0, min(count($visual['patterns']), 2 + $rarityIndex));
        $pattern = $rng->choice($availablePatterns);

        // Complexité (1-10)
        $complexity = $rng->nextRange(1 + $rarityIndex, min(10, 3 + $rarityIndex * 2));

        // Aura : uniquement pour rang A et S
        $aura = 'aucune';
        if ($rarityIndex >= 4) {
            $availableAuras = array_slice($visual['auras'], 1); // Exclure 'aucune'
            $aura = $rng->choice($availableAuras);
        }

        // Nom procédural
        $name = $this->generateName($rng, $rarity);

        return [
            'name' => $name,
            'petal_count' => $petalCount,
            'petal_shape' => $petalShape,
            'size' => $size,
            'leaf_count' => $leafCount,
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'pattern' => $pattern,
            'complexity' => $complexity,
            'aura' => $aura,
        ];
    }

    /**
     * Génère un nom procédural pour la plante.
     */
    private function generateName(RNG $rng, string $rarity): string
    {
        $prefixes = [
            'E' => ['Herbe', 'Pousse', 'Brin', 'Mousse'],
            'D' => ['Fleur', 'Bourgeon', 'Clochette', 'Primevère'],
            'C' => ['Orchidée', 'Dahlia', 'Lys', 'Iris'],
            'B' => ['Gardénia', 'Camélia', 'Magnolia', 'Amarante'],
            'A' => ['Lotus', 'Astrale', 'Nébuleuse', 'Célestine'],
            'S' => ['Éternelle', 'Mythique', 'Divine', 'Primordiale'],
        ];

        $suffixes = [
            'des vents', 'de lune', 'sauvage', 'du crépuscule',
            'de cristal', 'ardente', 'givrée', 'enchantée',
            'du soleil', 'des abysses', 'stellaire', 'opalescente',
        ];

        $prefix = $rng->choice($prefixes[$rarity] ?? $prefixes['E']);
        $suffix = $rng->choice($suffixes);

        return "{$prefix} {$suffix}";
    }

    // --- Factory ---

    /**
     * Crée une nouvelle plante à partir d'une graine.
     */
    public static function createFromSeed(array $seedData, int $slot, array $config): self
    {
        $rng = new RNG($seedData['seed'] + 7777);
        $rarityConfig = $config['rarity'][$seedData['rarity']];

        // Durée de croissance = base × random(0.85, 1.25)
        $growthMultiplier = 0.85 + $rng->nextFloat() * 0.40;
        $growthDuration = (int)round($rarityConfig['growth_base_seconds'] * $growthMultiplier);

        $plantData = [
            'id' => self::generateId(),
            'seed' => $seedData['seed'],
            'rarity' => $seedData['rarity'],
            'slot' => $slot,
            'planted_at' => time(),
            'growth_duration' => $growthDuration,
            'harvested' => false,
        ];

        return new self($plantData, $config);
    }

    private static function generateId(): string
    {
        return 'plant_' . bin2hex(random_bytes(8));
    }
}
