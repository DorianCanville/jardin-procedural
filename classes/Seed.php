<?php
require_once __DIR__ . '/RNG.php';
require_once __DIR__ . '/Storage.php';

/**
 * Seed - Représente une graine stockée dans l'inventaire.
 */
class Seed
{
    public string $id;
    public string $rarity;
    public int $plant_seed;
    public int $obtained_at;

    public static function create(string $rarity): self
    {
        $seed = new self();
        $seed->id = bin2hex(random_bytes(8));
        $seed->rarity = $rarity;
        $seed->plant_seed = RNG::generateSeed();
        $seed->obtained_at = time();
        return $seed;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'rarity' => $this->rarity,
            'plant_seed' => $this->plant_seed,
            'obtained_at' => $this->obtained_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        $seed = new self();
        $seed->id = $data['id'];
        $seed->rarity = $data['rarity'];
        $seed->plant_seed = $data['plant_seed'];
        $seed->obtained_at = $data['obtained_at'];
        return $seed;
    }
}
