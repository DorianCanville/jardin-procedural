# 🌿 Jardin Procédural - Jeu de Culture de Plantes

Jeu de culture de plantes procédurales en PHP vanilla avec stockage JSON.

## Installation

1. **Installer PHP 8+** sur votre machine :
   - **Windows** : Télécharger depuis [windows.php.net](https://windows.php.net/download/) et ajouter au PATH
   - **Mac** : `brew install php`
   - **Linux** : `sudo apt install php`

2. **Lancer le serveur** depuis le dossier du projet :
   ```bash
   cd /chemin/vers/TestGab
   php -S localhost:8000
   ```

3. **Ouvrir** `http://localhost:8000` dans un navigateur.

> **Alternative** : Placer le dossier dans un serveur Apache/Nginx avec PHP (XAMPP, Laragon, WAMP, etc.) et accéder via `http://localhost/TestGab/`.

## Structure du projet

```
TestGab/
├── index.php              # Router principal
├── classes/
│   ├── RNG.php            # Générateur pseudo-aléatoire à seed stable (LCG)
│   ├── Storage.php        # Lecture/écriture JSON avec verrouillage fichier
│   ├── Plant.php          # Modèle plante + génération procédurale
│   ├── Seed.php           # Modèle graine
│   ├── Shop.php           # Boutique, packs, vente pétales
│   ├── Game.php           # Contrôleur principal
│   └── Renderer.php       # Interface HTML/CSS
├── actions/
│   ├── plant.php          # Action : planter une graine
│   ├── harvest.php        # Action : récolter une plante
│   ├── buy_pack.php       # Action : acheter un pack
│   └── sell.php           # Action : vendre des pétales
└── data/
    ├── config.json        # Configuration (raretés, prix, couleurs)
    ├── player.json        # Données joueur (or, pétales, stats)
    ├── plants.json        # Jardin (plantes en croissance)
    └── inventory.json     # Inventaire de graines
```

## Gameplay

1. **Boutique** → Acheter un pack de graines (3 graines par pack)
2. **Inventaire** → Planter une graine dans le jardin
3. **Jardin** → Attendre la croissance en temps réel, puis récolter
4. **Boutique** → Vendre les pétales récoltés pour gagner de l'or
5. **Répéter** en investissant plus pour des plantes plus rares !

## Système de rareté

| Rang | Label       | Probabilité base | Croissance | Pétales | Prix/pétale |
|------|-------------|-----------------|------------|---------|-------------|
| E    | Commune     | 40%             | 2 min      | ~3      | 2 💰        |
| D    | Peu commune | 25%             | 5 min      | ~5      | 5 💰        |
| C    | Rare        | 15%             | 15 min     | ~8      | 12 💰       |
| B    | Très rare   | 10%             | 45 min     | ~12     | 25 💰       |
| A    | Épique      | 7%              | 2h         | ~18     | 60 💰       |
| S    | Légendaire  | 3%              | 6h         | ~30     | 150 💰      |

## Algorithmes clés

- **RNG** : Linear Congruential Generator à seed stable pour reproduction procédurale
- **Probabilités dynamiques** : `boost = ln(prix / prix_min)` → redistribution pondérée
- **Croissance offline** : Horodatage UNIX à la plantation, calcul du delta au chargement
- **Anti-triche** : Validation serveur de l'état de croissance avant récolte

## Idées d'extensions

- Collection / Pokédex de plantes découvertes
- Système de croisement entre plantes
- Événements saisonniers avec plantes exclusives
- Succès / achievements
- Amélioration du jardin (plus de slots)
- Engrais pour accélérer la croissance
- Marché entre joueurs
- Génération SVG procédurale des plantes (au lieu d'émojis)
- Sauvegarde/export de la partie
- Mode sombre / thèmes
