<?php
/**
 * RNG — Générateur pseudo-aléatoire déterministe basé sur une seed.
 * Utilise un LCG (Linear Congruential Generator) pour la reproductibilité.
 */
class RNG
{
    private int $state;
    private int $initialSeed;

    // Constantes LCG (valeurs de Numerical Recipes)
    private const A = 1664525;
    private const C = 1013904223;
    private const M = 0xFFFFFFFF; // 2^32

    public function __construct(int $seed)
    {
        $this->initialSeed = $seed;
        $this->state = $seed & self::M;
    }

    public function getSeed(): int
    {
        return $this->initialSeed;
    }

    /**
     * Génère le prochain entier pseudo-aléatoire [0, M].
     */
    public function nextInt(): int
    {
        $this->state = (self::A * $this->state + self::C) & self::M;
        return $this->state;
    }

    /**
     * Génère un float dans [0, 1).
     */
    public function nextFloat(): float
    {
        return $this->nextInt() / (self::M + 1);
    }

    /**
     * Génère un entier dans [min, max] inclus.
     */
    public function nextRange(int $min, int $max): int
    {
        return $min + (int)($this->nextFloat() * ($max - $min + 1));
    }

    /**
     * Sélection pondérée : choisit un élément selon des poids.
     * @param array $weights ['clé' => poids, ...]
     * @return string La clé sélectionnée
     */
    public function weightedChoice(array $weights): string
    {
        $total = array_sum($weights);
        $roll = $this->nextFloat() * $total;

        $cumulative = 0.0;
        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($roll < $cumulative) {
                return (string)$key;
            }
        }

        // Fallback : retourne la dernière clé
        return (string)array_key_last($weights);
    }

    /**
     * Choisit un élément aléatoire dans un tableau indexé.
     */
    public function choice(array $items): mixed
    {
        if (empty($items)) {
            throw new InvalidArgumentException("Tableau vide pour choice()");
        }
        $items = array_values($items);
        return $items[$this->nextRange(0, count($items) - 1)];
    }

    /**
     * Génère une seed aléatoire non déterministe (pour créer de nouvelles plantes).
     */
    public static function generateSeed(): int
    {
        return random_int(0, PHP_INT_MAX);
    }
}
