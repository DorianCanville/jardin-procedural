<?php
require_once __DIR__ . '/RNG.php';
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Seed.php';

/**
 * Shop - Gestion de la boutique et des packs de graines.
 * Le prix du pack influence les probabilités de rareté via une formule logarithmique.
 */
class Shop
{
    /**
     * Calcule les probabilités ajustées en fonction du prix du pack.
     * Formule logarithmique à rendement décroissant.
     */
    public static function calculateProbabilities(int $price): array
    {
        $config = Storage::read('config.json');
        $minPrice = $config['pack']['min_price'];

        if ($price < $minPrice) {
            throw new InvalidArgumentException("Prix minimum: {$minPrice} pièces");
        }

        $baseProbabilities = [];
        foreach ($config['rarities'] as $rank => $data) {
            $baseProbabilities[$rank] = $data['base_probability'];
        }

        // Facteur de boost : log(price/min) donne un bonus progressif décroissant
        // À 10 pièces: boost = 0, À 100: boost ≈ 2.3, À 1000: boost ≈ 4.6
        $boost = log(max(1, $price / $minPrice));

        // Le boost réduit les chances communes et augmente les rares
        $adjusted = [];
        $rarityOrder = ['E', 'D', 'C', 'B', 'A', 'S'];

        foreach ($rarityOrder as $index => $rank) {
            $base = $baseProbabilities[$rank];
            if ($index <= 1) {
                // E et D : réduction proportionnelle au boost
                $adjusted[$rank] = max(5, $base - ($boost * (3 - $index) * 1.5));
            } else {
                // C, B, A, S : augmentation proportionnelle au boost et à la rareté
                $adjusted[$rank] = $base + ($boost * $index * 0.8);
            }
        }

        // Normalisation pour que le total fasse 100
        $total = array_sum($adjusted);
        foreach ($adjusted as $rank => &$prob) {
            $prob = round(($prob / $total) * 100, 2);
        }

        return $adjusted;
    }

    /**
     * Achète un pack de graines.
     * @return array ['seeds' => Seed[], 'probabilities' => array]
     */
    public static function buyPack(int $price): array
    {
        $config = Storage::read('config.json');
        $player = Storage::read('player.json');

        // Validations
        if ($price < $config['pack']['min_price']) {
            throw new InvalidArgumentException("Prix minimum: {$config['pack']['min_price']} pièces");
        }

        if ($player['gold'] < $price) {
            throw new RuntimeException("Fonds insuffisants ({$player['gold']} pièces disponibles)");
        }

        $probabilities = self::calculateProbabilities($price);
        $seedCount = $config['pack']['seeds_per_pack'];
        $seeds = [];

        for ($i = 0; $i < $seedCount; $i++) {
            // Utilise mt_rand pour le tirage (non reproductible, c'est voulu pour le gacha)
            $rng = new RNG(RNG::generateSeed());
            $rarity = $rng->weightedChoice($probabilities);
            $seeds[] = Seed::create($rarity);
        }

        // Déduire l'or
        $player['gold'] -= $price;
        $player['stats']['total_gold_spent'] += $price;
        $player['stats']['total_packs_bought']++;
        Storage::write('player.json', $player);

        // Ajouter graines à l'inventaire
        $inventory = Storage::read('inventory.json');
        foreach ($seeds as $seed) {
            $inventory['seeds'][] = $seed->toArray();
        }
        Storage::write('inventory.json', $inventory);

        return ['seeds' => $seeds, 'probabilities' => $probabilities];
    }

    /**
     * Vend des pétales d'une rareté donnée.
     */
    public static function sellPetals(string $rarity, int $quantity): int
    {
        $config = Storage::read('config.json');
        $player = Storage::read('player.json');

        if (!isset($config['petal_sell_prices'][$rarity])) {
            throw new InvalidArgumentException("Rareté invalide: {$rarity}");
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantité invalide");
        }

        if (($player['petals'][$rarity] ?? 0) < $quantity) {
            throw new RuntimeException("Pas assez de pétales {$rarity}");
        }

        $pricePerPetal = $config['petal_sell_prices'][$rarity];
        $totalGold = $pricePerPetal * $quantity;

        $player['petals'][$rarity] -= $quantity;
        $player['gold'] += $totalGold;
        $player['stats']['total_gold_earned'] += $totalGold;
        Storage::write('player.json', $player);

        return $totalGold;
    }

    /**
     * Retourne les infos de la boutique pour l'affichage.
     */
    public static function getShopInfo(): array
    {
        $config = Storage::read('config.json');
        return [
            'min_price' => $config['pack']['min_price'],
            'seeds_per_pack' => $config['pack']['seeds_per_pack'],
            'sell_prices' => $config['petal_sell_prices'],
        ];
    }
}
