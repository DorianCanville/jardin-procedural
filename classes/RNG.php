<?php
/**
 * RNG - Générateur de nombres pseudo-aléatoires à seed stable
 * Permet la reproduction exacte d'une séquence pour une seed donnée.
 */
class RNG
{
    private int $seed;
    private int $current;

    public function __construct(int $seed)
    {
        $this->seed = $seed;
        $this->current = $seed;
    }

    public function getSeed(): int
    {
        return $this->seed;
    }

    /**
     * Génère le prochain nombre pseudo-aléatoire (algorithme LCG).
     * Retourne un entier entre 0 et 2^31-1.
     */
    public function next(): int
    {
        // Linear Congruential Generator (paramètres de Numerical Recipes)
        $this->current = ($this->current * 1103515245 + 12345) & 0x7FFFFFFF;
        return $this->current;
    }

    /**
     * Retourne un flottant entre 0.0 et 1.0 (exclus).
     */
    public function nextFloat(): float
    {
        return $this->next() / 0x80000000;
    }

    /**
     * Retourne un entier dans l'intervalle [min, max].
     */
    public function nextInt(int $min, int $max): int
    {
        return $min + ($this->next() % ($max - $min + 1));
    }

    /**
     * Choisit un élément dans un tableau.
     */
    public function choose(array $items): mixed
    {
        if (empty($items)) {
            return null;
        }
        $index = $this->next() % count($items);
        return array_values($items)[$index];
    }

    /**
     * Sélection pondérée : choisit une clé parmi des poids.
     * @param array $weights ['E' => 40, 'D' => 25, ...]
     */
    public function weightedChoice(array $weights): string
    {
        $total = array_sum($weights);
        $roll = $this->nextFloat() * $total;
        $cumulative = 0.0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($roll < $cumulative) {
                return (string) $key;
            }
        }

        // Fallback (ne devrait jamais arriver)
        return (string) array_key_last($weights);
    }

    /**
     * Génère une seed aléatoire unique.
     */
    public static function generateSeed(): int
    {
        return mt_rand(1, 0x7FFFFFFF);
    }
}
