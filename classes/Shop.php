<?php
/**
 * Shop — Gestion de la boutique : achat de packs, vente de pétales.
 */
class Shop
{
    private Storage $storage;
    private array $config;

    public function __construct(Storage $storage, array $config)
    {
        $this->storage = $storage;
        $this->config = $config;
    }

    /**
     * Achète un pack de graines.
     *
     * @param int $price Montant à dépenser
     * @return array ['seeds' => Seed[], 'player' => array]
     */
    public function buyPack(int $price): array
    {
        $minPrice = $this->config['shop']['min_price'];

        if ($price < $minPrice) {
            throw new InvalidArgumentException("Prix minimum : {$minPrice} pièces.");
        }

        // Vérifier solde joueur
        $player = $this->storage->read('player.json');
        if ($player['gold'] < $price) {
            throw new RuntimeException("Fonds insuffisants. Vous avez {$player['gold']} pièces.");
        }

        // Générer le pack
        $seeds = Seed::generatePack($price, $this->config);

        // Débiter le joueur
        $player['gold'] -= $price;
        $player['total_packs_bought']++;
        $player['total_gold_spent'] += $price;

        // Mettre à jour la meilleure rareté
        $rarityOrder = array_keys($this->config['rarity']);
        foreach ($seeds as $seed) {
            $seedRarityIdx = array_search($seed->getRarity(), $rarityOrder);
            $bestRarityIdx = array_search($player['best_rarity'], $rarityOrder);
            if ($seedRarityIdx > $bestRarityIdx) {
                $player['best_rarity'] = $seed->getRarity();
            }
        }

        $this->storage->write('player.json', $player);

        // Ajouter les graines à l'inventaire
        $this->storage->update('inventory.json', function (array $inv) use ($seeds) {
            foreach ($seeds as $seed) {
                $inv['seeds'][] = $seed->toArray();
            }
            return $inv;
        });

        return [
            'seeds' => $seeds,
            'player' => $player,
        ];
    }

    /**
     * Vend des pétales contre des pièces.
     *
     * @param int $amount Nombre de pétales à vendre
     * @return array ['gold_earned' => int, 'player' => array]
     */
    public function sellPetals(int $amount): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Quantité invalide.");
        }

        $player = $this->storage->read('player.json');

        if ($player['petals'] < $amount) {
            throw new RuntimeException("Vous n'avez que {$player['petals']} pétales.");
        }

        // 1 pétale = 1 pièce (prix de base)
        $goldEarned = $amount;

        $player['petals'] -= $amount;
        $player['gold'] += $goldEarned;
        $player['total_sold'] += $amount;
        $player['total_gold_earned'] += $goldEarned;

        $this->storage->write('player.json', $player);

        return [
            'gold_earned' => $goldEarned,
            'player' => $player,
        ];
    }

    /**
     * Calcule et retourne les probabilités pour un prix donné (aperçu boutique).
     */
    public function getPackPreview(int $price): array
    {
        return Seed::calculateAdjustedProbabilities(
            max($price, $this->config['shop']['min_price']),
            $this->config
        );
    }
}
