<?php
require_once __DIR__ . '/RNG.php';
require_once __DIR__ . '/Storage.php';

/**
 * Plant - Représente une plante avec ses attributs procéduraux.
 */
class Plant
{
    public string $id;
    public string $rarity;
    public string $name;
    public int $seed;
    public int $planted_at;
    public int $growth_duration;
    public bool $ready;
    public int $petal_yield;

    // Attributs procéduraux visuels
    public int $petal_count;
    public string $petal_shape;
    public float $size;
    public int $leaf_count;
    public string $primary_color;
    public string $secondary_color;
    public string $pattern;
    public int $complexity;
    public ?string $aura;

    /**
     * Crée une plante à partir d'une seed et d'un rang de rareté.
     */
    public static function generate(string $rarity, int $plantSeed): self
    {
        $config = Storage::read('config.json');
        $rarityConfig = $config['rarities'][$rarity];
        $rng = new RNG($plantSeed);

        $plant = new self();
        $plant->id = self::generateId();
        $plant->rarity = $rarity;
        $plant->seed = $plantSeed;
        $plant->planted_at = 0; // Sera défini à la plantation
        $plant->ready = false;

        // Durée de croissance avec variation
        $variation = $config['growth_time_variation'];
        $multiplier = $variation[0] + $rng->nextFloat() * ($variation[1] - $variation[0]);
        $plant->growth_duration = (int) round($rarityConfig['growth_time_seconds'] * $multiplier);

        // Production de pétales avec variation ±20%
        $yieldVar = $config['petal_yield_variation'];
        $yieldMult = (1.0 - $yieldVar) + $rng->nextFloat() * ($yieldVar * 2);
        $plant->petal_yield = max(1, (int) round($rarityConfig['petal_yield'] * $yieldMult));

        // Génération procédurale des attributs visuels
        $plant->generateVisualAttributes($rng, $config, $rarity);

        // Nom procédural
        $plant->name = self::generateName($rng, $rarity);

        return $plant;
    }

    /**
     * Génère les attributs visuels procéduraux basés sur la seed.
     */
    private function generateVisualAttributes(RNG $rng, array $config, string $rarity): void
    {
        $rarityIndex = array_search($rarity, array_keys($config['rarities']));

        // Plus la rareté est haute, plus les attributs sont complexes
        $this->petal_count = $rng->nextInt(3 + $rarityIndex, 6 + $rarityIndex * 3);
        $this->petal_shape = $rng->choose($config['petal_shapes']);
        $this->size = round(0.5 + $rng->nextFloat() * (0.5 + $rarityIndex * 0.3), 2);
        $this->leaf_count = $rng->nextInt(1 + $rarityIndex, 3 + $rarityIndex * 2);
        $this->primary_color = $rng->choose($config['primary_colors']);
        $this->secondary_color = $rng->choose($config['secondary_colors']);
        $this->pattern = $rng->choose($config['patterns']);
        $this->complexity = min(10, $rng->nextInt(1 + $rarityIndex, 3 + $rarityIndex * 2));

        // Aura uniquement pour les rangs A et S
        $this->aura = null;
        if (in_array($rarity, ['A', 'S'])) {
            $this->aura = $rng->choose($config['aura_colors']);
        }
    }

    /**
     * Génère un nom procédural pour la plante.
     */
    private static function generateName(RNG $rng, string $rarity): string
    {
        $prefixes = [
            'E' => ['Herbe', 'Pousse', 'Brin', 'Mousse'],
            'D' => ['Fleur', 'Bouton', 'Tige', 'Bourgeon'],
            'C' => ['Rose', 'Liane', 'Orchidée', 'Jasmin'],
            'B' => ['Lotus', 'Dahlia', 'Iris', 'Pivoine'],
            'A' => ['Amarante', 'Chrysanthème', 'Azalée', 'Camélia'],
            'S' => ['Éternelle', 'Céleste', 'Mystique', 'Divine'],
        ];

        $suffixes = [
            'E' => ['sauvage', 'des prés', 'simple', 'modeste'],
            'D' => ['des bois', 'printanière', 'délicate', 'timide'],
            'C' => ['enchantée', 'secrète', 'mystérieuse', 'rare'],
            'B' => ['précieuse', 'ancienne', 'noble', 'protégée'],
            'A' => ['légendaire', 'ancestrale', 'éternelle', 'sacrée'],
            'S' => ['des dieux', 'primordiale', 'suprême', 'absolue'],
        ];

        $prefix = $rng->choose($prefixes[$rarity] ?? $prefixes['E']);
        $suffix = $rng->choose($suffixes[$rarity] ?? $suffixes['E']);

        return "{$prefix} {$suffix}";
    }

    /**
     * Vérifie si la plante a fini de pousser.
     */
    public function isGrown(): bool
    {
        if ($this->planted_at === 0) {
            return false;
        }
        return time() >= ($this->planted_at + $this->growth_duration);
    }

    /**
     * Retourne le pourcentage de croissance (0-100).
     */
    public function getGrowthPercent(): float
    {
        if ($this->planted_at === 0) {
            return 0.0;
        }
        if ($this->isGrown()) {
            return 100.0;
        }
        $elapsed = time() - $this->planted_at;
        return min(100.0, round(($elapsed / $this->growth_duration) * 100, 1));
    }

    /**
     * Retourne le temps restant en secondes.
     */
    public function getRemainingTime(): int
    {
        if ($this->isGrown()) {
            return 0;
        }
        return max(0, ($this->planted_at + $this->growth_duration) - time());
    }

    /**
     * Formate le temps restant pour l'affichage.
     */
    public function getFormattedRemainingTime(): string
    {
        $seconds = $this->getRemainingTime();
        if ($seconds <= 0) {
            return 'Prête !';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $secs);
        }
        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $secs);
        }
        return sprintf('%ds', $secs);
    }

    /**
     * Sérialise la plante en tableau pour stockage JSON.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rarity' => $this->rarity,
            'name' => $this->name,
            'seed' => $this->seed,
            'planted_at' => $this->planted_at,
            'growth_duration' => $this->growth_duration,
            'ready' => $this->ready,
            'petal_yield' => $this->petal_yield,
            'petal_count' => $this->petal_count,
            'petal_shape' => $this->petal_shape,
            'size' => $this->size,
            'leaf_count' => $this->leaf_count,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'pattern' => $this->pattern,
            'complexity' => $this->complexity,
            'aura' => $this->aura,
        ];
    }

    /**
     * Reconstruit une plante depuis un tableau JSON.
     */
    public static function fromArray(array $data): self
    {
        $plant = new self();
        $plant->id = $data['id'];
        $plant->rarity = $data['rarity'];
        $plant->name = $data['name'];
        $plant->seed = $data['seed'];
        $plant->planted_at = $data['planted_at'];
        $plant->growth_duration = $data['growth_duration'];
        $plant->ready = $data['ready'] ?? false;
        $plant->petal_yield = $data['petal_yield'];
        $plant->petal_count = $data['petal_count'];
        $plant->petal_shape = $data['petal_shape'];
        $plant->size = $data['size'];
        $plant->leaf_count = $data['leaf_count'];
        $plant->primary_color = $data['primary_color'];
        $plant->secondary_color = $data['secondary_color'];
        $plant->pattern = $data['pattern'];
        $plant->complexity = $data['complexity'];
        $plant->aura = $data['aura'] ?? null;
        return $plant;
    }

    private static function generateId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
