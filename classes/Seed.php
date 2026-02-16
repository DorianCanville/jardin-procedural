<?php
/**
 * Seed — Représente une graine avec sa rareté et sa seed RNG.
 */
class Seed
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

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

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Génère un pack de graines avec probabilités ajustées selon le prix payé.
     *
     * @param int $price Montant payé pour le pack
     * @param array $config Configuration globale
     * @return array Liste de Seed
     */
    public static function generatePack(int $price, array $config): array
    {
        $packSize = $config['shop']['pack_size'];
        $adjustedWeights = self::calculateAdjustedProbabilities($price, $config);

        $seeds = [];
        for ($i = 0; $i < $packSize; $i++) {
            // Utilise random_int pour le tirage non déterministe (achat = aléatoire vrai)
            $rarity = self::rollRarity($adjustedWeights);
            $seedValue = RNG::generateSeed();

            $seeds[] = new self([
                'id' => 'seed_' . bin2hex(random_bytes(8)),
                'rarity' => $rarity,
                'seed' => $seedValue,
                'created_at' => time(),
            ]);
        }

        return $seeds;
    }

    /**
     * Calcule les probabilités ajustées selon le prix avec formule logarithmique.
     *
     * Plus le joueur paie, plus les rangs rares ont de chances d'apparaître,
     * mais avec des rendements décroissants.
     *
     * Formule : bonus = log2(price / min_price) × facteur_rang
     */
    public static function calculateAdjustedProbabilities(int $price, array $config): array
    {
        $minPrice = $config['shop']['min_price'];
        $baseProbabilities = [];

        foreach ($config['rarity'] as $rank => $rData) {
            $baseProbabilities[$rank] = $rData['probability'];
        }

        // Si prix minimum, pas de bonus
        if ($price <= $minPrice) {
            return $baseProbabilities;
        }

        // Facteur logarithmique à rendements décroissants
        $ratio = $price / $minPrice;
        $logBoost = log($ratio, 2); // log base 2

        // Facteurs de boost par rang (les rangs rares bénéficient plus)
        $boostFactors = [
            'E' => -2.0,  // Les communes diminuent
            'D' => -0.5,
            'C' =>  0.5,
            'B' =>  1.0,
            'A' =>  1.5,
            'S' =>  2.0,
        ];

        $adjusted = [];
        foreach ($baseProbabilities as $rank => $baseProb) {
            $factor = $boostFactors[$rank] ?? 0;
            $newProb = $baseProb + ($logBoost * $factor);
            $adjusted[$rank] = max(0.5, $newProb); // Minimum 0.5% pour tout rang
        }

        // Normaliser pour que le total fasse 100
        $total = array_sum($adjusted);
        foreach ($adjusted as $rank => &$prob) {
            $prob = round(($prob / $total) * 100, 2);
        }

        return $adjusted;
    }

    /**
     * Tire une rareté selon les poids donnés (aléatoire cryptographique).
     */
    private static function rollRarity(array $weights): string
    {
        $total = array_sum($weights);
        $roll = random_int(0, (int)($total * 10000)) / 10000;

        $cumulative = 0.0;
        foreach ($weights as $rank => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $rank;
            }
        }

        return array_key_last($weights);
    }
}
