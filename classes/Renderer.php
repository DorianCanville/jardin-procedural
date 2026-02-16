<?php
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/Plant.php';

/**
 * Renderer - Génère l'interface HTML du jeu.
 */
class Renderer
{
    /**
     * Rend le header HTML avec CSS inline.
     */
    public static function header(string $title, string $currentPage = ''): string
    {
        $player = Storage::read('player.json');
        $config = Storage::read('config.json');
        $gold = $player['gold'];

        // Compter pétales totaux
        $totalPetals = array_sum($player['petals']);

        $navItems = [
            'garden' => '🌱 Jardin',
            'shop' => '🛒 Boutique',
            'inventory' => '🎒 Inventaire',
            'stats' => '📊 Stats',
        ];

        $nav = '';
        foreach ($navItems as $page => $label) {
            $active = ($page === $currentPage) ? 'nav-active' : '';
            $nav .= "<a href=\"?page={$page}\" class=\"nav-link {$active}\">{$label}</a>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Jardin Procédural</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        .top-bar {
            background: rgba(0,0,0,0.4);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            flex-wrap: wrap;
            gap: 10px;
        }
        .game-title {
            font-size: 1.4em;
            font-weight: bold;
            color: #6BCB77;
        }
        .player-info {
            display: flex;
            gap: 20px;
            font-size: 0.95em;
        }
        .gold { color: #FFD700; font-weight: bold; }
        .petals-count { color: #FF69B4; }
        nav {
            background: rgba(0,0,0,0.3);
            padding: 8px 24px;
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        .nav-link {
            color: #aaa;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-active { background: rgba(107,203,119,0.2); color: #6BCB77 !important; }
        .container { max-width: 1100px; margin: 20px auto; padding: 0 20px; }
        .page-title { font-size: 1.6em; margin-bottom: 20px; color: #6BCB77; }
        .card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-2px); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }
        .btn {
            display: inline-block;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            text-decoration: none;
            color: #fff;
            transition: all 0.2s;
        }
        .btn-primary { background: #6BCB77; color: #1a1a2e; }
        .btn-primary:hover { background: #5ab868; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-gold { background: #FFD700; color: #1a1a2e; }
        .btn-gold:hover { background: #e6c200; }
        .btn-sm { padding: 5px 12px; font-size: 0.8em; }
        .rarity-E { color: #8BC34A; }
        .rarity-D { color: #2196F3; }
        .rarity-C { color: #9C27B0; }
        .rarity-B { color: #FF9800; }
        .rarity-A { color: #F44336; }
        .rarity-S { color: #FFD700; text-shadow: 0 0 10px rgba(255,215,0,0.5); }
        .rarity-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85em;
        }
        .rarity-badge-E { background: rgba(139,195,74,0.2); color: #8BC34A; border: 1px solid #8BC34A; }
        .rarity-badge-D { background: rgba(33,150,243,0.2); color: #2196F3; border: 1px solid #2196F3; }
        .rarity-badge-C { background: rgba(156,39,176,0.2); color: #9C27B0; border: 1px solid #9C27B0; }
        .rarity-badge-B { background: rgba(255,152,0,0.2); color: #FF9800; border: 1px solid #FF9800; }
        .rarity-badge-A { background: rgba(244,67,54,0.2); color: #F44336; border: 1px solid #F44336; }
        .rarity-badge-S { background: rgba(255,215,0,0.2); color: #FFD700; border: 1px solid #FFD700; }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 500;
        }
        .alert-success { background: rgba(107,203,119,0.2); border: 1px solid #6BCB77; color: #6BCB77; }
        .alert-error { background: rgba(231,76,60,0.2); border: 1px solid #e74c3c; color: #e74c3c; }
        .alert-info { background: rgba(33,150,243,0.2); border: 1px solid #2196F3; color: #64B5F6; }
        .plant-visual {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4em;
            border-radius: 8px;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
        }
        .plant-info { font-size: 0.85em; color: #aaa; margin-top: 4px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 600; }
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px;
            background: rgba(0,0,0,0.3);
            color: #e0e0e0;
            font-size: 0.95em;
        }
        .form-input:focus { outline: none; border-color: #6BCB77; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1.1em;
        }
        .seed-reveal {
            text-align: center;
            padding: 16px;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        .aura {
            animation: glow 2s ease-in-out infinite alternate;
        }
        @keyframes glow {
            from { box-shadow: 0 0 10px var(--aura-color); }
            to { box-shadow: 0 0 25px var(--aura-color), 0 0 50px var(--aura-color); }
        }
        .prob-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .prob-table th, .prob-table td { padding: 6px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .prob-table th { color: #6BCB77; font-size: 0.85em; text-transform: uppercase; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .stat-value { font-size: 1.8em; font-weight: bold; color: #6BCB77; }
        .stat-label { font-size: 0.8em; color: #888; margin-top: 4px; }
        .auto-refresh { font-size: 0.75em; color: #555; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="game-title">🌿 Jardin Procédural</div>
        <div class="player-info">
            <span class="gold">💰 {$gold} pièces</span>
            <span class="petals-count">🌸 {$totalPetals} pétales</span>
        </div>
    </div>
    <nav>{$nav}</nav>
    <div class="container">
HTML;
    }

    public static function footer(bool $autoRefresh = false): string
    {
        $refreshScript = '';
        if ($autoRefresh) {
            $refreshScript = '<script>setTimeout(()=>location.reload(), 10000);</script>';
            $refreshScript .= '<p class="auto-refresh">⟳ Actualisation auto toutes les 10s</p>';
        }

        return <<<HTML
    </div>
    <div style="text-align:center;padding:20px;color:#444;font-size:0.8em;">
        <a href="?page=garden&action=reset" onclick="return confirm('Réinitialiser complètement le jeu ?')" style="color:#555;text-decoration:none;">🔄 Réinitialiser le jeu</a>
    </div>
    {$refreshScript}
</body>
</html>
HTML;
    }

    /**
     * Rend un message flash (succès/erreur).
     */
    public static function flash(string $message, string $type = 'success'): string
    {
        $safeType = in_array($type, ['success', 'error', 'info'], true) ? $type : 'info';
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return "<div class=\"alert alert-{$safeType}\">{$safeMessage}</div>";
    }

    /**
     * Rend le visuel d'une plante (émojis + CSS procédural).
     */
    public static function renderPlantVisual(Plant $plant, float $growthPercent = 100): string
    {
        $config = Storage::read('config.json');
        $rarityColor = $config['rarities'][$plant->rarity]['color'] ?? '#8BC34A';

        // Émoji de la plante basé sur la croissance
        if ($growthPercent < 25) {
            $emoji = '🌱';
        } elseif ($growthPercent < 50) {
            $emoji = '🌿';
        } elseif ($growthPercent < 75) {
            $emoji = '☘️';
        } elseif ($growthPercent < 100) {
            $emoji = '🌷';
        } else {
            $flowerEmojis = ['🌸', '🌺', '🌻', '🌹', '💐', '🌼', '🏵️', '💮'];
            $rng = new RNG($plant->seed);
            $emoji = $rng->choose($flowerEmojis);
        }

        $bgGradient = "linear-gradient(135deg, {$plant->primary_color}22, {$plant->secondary_color}33)";
        $auraClass = $plant->aura ? 'aura' : '';
        $auraStyle = $plant->aura ? "--aura-color: {$plant->aura};" : '';
        $sizeEm = 3 + ($plant->size * 2);

        return <<<HTML
<div class="plant-visual {$auraClass}" style="background: {$bgGradient}; {$auraStyle}">
    <span style="font-size: {$sizeEm}em;">{$emoji}</span>
</div>
HTML;
    }

    /**
     * Page du jardin.
     */
    public static function gardenPage(array $gardenState, ?string $message = null, string $messageType = 'success'): string
    {
        $html = self::header('Jardin', 'garden');
        $html .= '<h1 class="page-title">🌱 Mon Jardin</h1>';

        if ($message) {
            $html .= self::flash($message, $messageType);
        }

        $config = Storage::read('config.json');

        if (empty($gardenState)) {
            $html .= '<div class="empty-state">';
            $html .= '<p style="font-size:3em;margin-bottom:16px;">🏜️</p>';
            $html .= '<p>Votre jardin est vide.</p>';
            $html .= '<p style="margin-top:8px;"><a href="?page=shop" class="btn btn-primary">Acheter des graines</a> ';
            $html .= 'ou <a href="?page=inventory" class="btn btn-gold">Planter depuis l\'inventaire</a></p>';
            $html .= '</div>';
        } else {
            $html .= '<div class="grid">';
            foreach ($gardenState as $state) {
                /** @var Plant $plant */
                $plant = $state['plant'];
                $percent = $state['growth_percent'];
                $remaining = $state['remaining_time'];
                $isGrown = $state['is_grown'];
                $rarityLabel = $config['rarities'][$plant->rarity]['label'];

                $progressColor = $isGrown ? '#6BCB77' : '#4D96FF';

                $html .= '<div class="card">';
                $html .= self::renderPlantVisual($plant, $percent);
                $html .= "<div style=\"display:flex;justify-content:space-between;align-items:center;\">";
                $html .= "<strong class=\"rarity-{$plant->rarity}\">{$plant->name}</strong>";
                $html .= "<span class=\"rarity-badge rarity-badge-{$plant->rarity}\">{$plant->rarity} - {$rarityLabel}</span>";
                $html .= "</div>";

                $html .= '<div class="progress-bar"><div class="progress-fill" style="width:' . $percent . '%;background:' . $progressColor . ';"></div></div>';

                if ($isGrown) {
                    $html .= "<div style=\"display:flex;justify-content:space-between;align-items:center;\">";
                    $html .= "<span style=\"color:#6BCB77;\">✅ Prête ! ({$plant->petal_yield} pétales)</span>";
                    $html .= "<a href=\"?page=garden&action=harvest&id={$plant->id}\" class=\"btn btn-primary btn-sm\">🌸 Récolter</a>";
                    $html .= "</div>";
                } else {
                    $html .= "<div class=\"plant-info\">⏳ {$remaining} ({$percent}%)</div>";
                }

                // Détails procéduraux
                $html .= '<div class="plant-info" style="margin-top:8px;font-size:0.78em;">';
                $html .= "🌸 {$plant->petal_count} pétales ({$plant->petal_shape}) · ";
                $html .= "🍃 {$plant->leaf_count} feuilles · ";
                $html .= "✨ Complexité {$plant->complexity}/10 · ";
                $html .= "🎨 {$plant->pattern}";
                if ($plant->aura) {
                    $html .= " · <span style=\"color:{$plant->aura};\">✦ Aura</span>";
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $hasGrowing = false;
        foreach ($gardenState as $state) {
            if (!$state['is_grown']) {
                $hasGrowing = true;
                break;
            }
        }

        $html .= self::footer($hasGrowing);
        return $html;
    }

    /**
     * Page de la boutique.
     */
    public static function shopPage(?string $message = null, string $messageType = 'success', ?array $revealedSeeds = null, ?array $probabilities = null): string
    {
        $html = self::header('Boutique', 'shop');
        $html .= '<h1 class="page-title">🛒 Boutique</h1>';

        if ($message) {
            $html .= self::flash($message, $messageType);
        }

        $config = Storage::read('config.json');
        $player = Storage::read('player.json');

        // Révélation de graines achetées
        if ($revealedSeeds) {
            $html .= '<div class="card" style="border-color:#FFD700;">';
            $html .= '<h3 style="text-align:center;margin-bottom:16px;color:#FFD700;">🎉 Graines obtenues !</h3>';
            $html .= '<div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">';
            foreach ($revealedSeeds as $seed) {
                $rarityLabel = $config['rarities'][$seed->rarity]['label'];
                $rarityColor = $config['rarities'][$seed->rarity]['color'];
                $html .= '<div class="seed-reveal">';
                $html .= "<div style=\"font-size:3em;\">🌰</div>";
                $html .= "<div class=\"rarity-badge rarity-badge-{$seed->rarity}\" style=\"margin-top:8px;\">{$seed->rarity} - {$rarityLabel}</div>";
                $html .= '</div>';
            }
            $html .= '</div>';

            if ($probabilities) {
                $html .= '<details style="margin-top:16px;"><summary style="cursor:pointer;color:#888;">📊 Probabilités utilisées</summary>';
                $html .= '<table class="prob-table">';
                $html .= '<tr><th>Rang</th><th>Probabilité</th></tr>';
                foreach ($probabilities as $rank => $prob) {
                    $html .= "<tr><td class=\"rarity-{$rank}\">{$rank} - {$config['rarities'][$rank]['label']}</td><td>{$prob}%</td></tr>";
                }
                $html .= '</table></details>';
            }
            $html .= '</div>';
        }

        // Formulaire d'achat
        $html .= '<div class="card">';
        $html .= '<h3 style="margin-bottom:12px;">📦 Acheter un pack de graines</h3>';
        $html .= "<p style=\"color:#888;margin-bottom:16px;\">Chaque pack contient {$config['pack']['seeds_per_pack']} graines. Plus vous investissez, plus les chances de rareté augmentent !</p>";
        $html .= '<form method="POST" action="?page=shop&action=buy">';
        $html .= '<div class="form-group">';
        $html .= "<label>💰 Montant à investir (min {$config['pack']['min_price']}, max {$player['gold']} disponibles)</label>";
        $html .= "<input type=\"number\" name=\"price\" class=\"form-input\" min=\"{$config['pack']['min_price']}\" max=\"{$player['gold']}\" value=\"{$config['pack']['min_price']}\" required>";
        $html .= '</div>';
        $html .= '<button type="submit" class="btn btn-gold">🎰 Acheter le pack</button>';
        $html .= '</form>';

        // Aperçu des probabilités
        $html .= '<div style="margin-top:20px;">';
        $html .= '<h4 style="color:#888;margin-bottom:8px;">📊 Aperçu des probabilités par prix</h4>';
        $html .= '<table class="prob-table">';
        $html .= '<tr><th>Rang</th><th>Base (10💰)</th><th>50💰</th><th>200💰</th><th>1000💰</th></tr>';

        $previewPrices = [10, 50, 200, 1000];
        $previewProbs = [];
        foreach ($previewPrices as $p) {
            try {
                $previewProbs[$p] = Shop::calculateProbabilities($p);
            } catch (Exception $e) {
                $previewProbs[$p] = [];
            }
        }

        foreach (['E', 'D', 'C', 'B', 'A', 'S'] as $rank) {
            $html .= "<tr><td class=\"rarity-{$rank}\">{$rank} - {$config['rarities'][$rank]['label']}</td>";
            foreach ($previewPrices as $p) {
                $val = $previewProbs[$p][$rank] ?? '-';
                $html .= "<td>{$val}%</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</table></div>';
        $html .= '</div>';

        // Section vente de pétales
        $html .= '<div class="card">';
        $html .= '<h3 style="margin-bottom:12px;">💰 Vendre des pétales</h3>';
        $html .= '<table class="prob-table">';
        $html .= '<tr><th>Rareté</th><th>En stock</th><th>Prix unitaire</th><th>Action</th></tr>';

        foreach ($config['petal_sell_prices'] as $rank => $price) {
            $stock = $player['petals'][$rank] ?? 0;
            $html .= "<tr>";
            $html .= "<td class=\"rarity-{$rank}\">{$rank} - {$config['rarities'][$rank]['label']}</td>";
            $html .= "<td>{$stock}</td>";
            $html .= "<td>{$price} 💰</td>";
            $html .= '<td>';
            if ($stock > 0) {
                $html .= "<form method=\"POST\" action=\"?page=shop&action=sell\" style=\"display:inline;\">";
                $html .= "<input type=\"hidden\" name=\"rarity\" value=\"{$rank}\">";
                $html .= "<input type=\"number\" name=\"quantity\" class=\"form-input\" style=\"width:60px;display:inline-block;\" min=\"1\" max=\"{$stock}\" value=\"{$stock}\">";
                $html .= " <button type=\"submit\" class=\"btn btn-primary btn-sm\">Vendre</button>";
                $html .= '</form>';
            } else {
                $html .= '<span style="color:#555;">—</span>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</table></div>';

        $html .= self::footer();
        return $html;
    }

    /**
     * Page d'inventaire (graines).
     */
    public static function inventoryPage(array $seeds, ?string $message = null, string $messageType = 'success'): string
    {
        $html = self::header('Inventaire', 'inventory');
        $html .= '<h1 class="page-title">🎒 Inventaire de graines</h1>';

        if ($message) {
            $html .= self::flash($message, $messageType);
        }

        $config = Storage::read('config.json');

        if (empty($seeds)) {
            $html .= '<div class="empty-state">';
            $html .= '<p style="font-size:3em;margin-bottom:16px;">🌰</p>';
            $html .= '<p>Aucune graine en stock.</p>';
            $html .= '<p style="margin-top:8px;"><a href="?page=shop" class="btn btn-gold">Acheter un pack</a></p>';
            $html .= '</div>';
        } else {
            // Grouper par rareté
            $grouped = [];
            foreach ($seeds as $seed) {
                $grouped[$seed->rarity][] = $seed;
            }

            // Trier par rareté (S en premier)
            $order = array_flip(['S', 'A', 'B', 'C', 'D', 'E']);
            uksort($grouped, fn($a, $b) => ($order[$a] ?? 99) - ($order[$b] ?? 99));

            $html .= '<div class="grid">';
            foreach ($grouped as $rarity => $raritySeeds) {
                foreach ($raritySeeds as $seed) {
                    $rarityLabel = $config['rarities'][$rarity]['label'];
                    $growthTime = $config['rarities'][$rarity]['growth_time_seconds'];

                    // Formater le temps de croissance
                    if ($growthTime >= 3600) {
                        $timeStr = round($growthTime / 3600, 1) . 'h';
                    } elseif ($growthTime >= 60) {
                        $timeStr = round($growthTime / 60) . 'min';
                    } else {
                        $timeStr = $growthTime . 's';
                    }

                    $html .= '<div class="card">';
                    $html .= "<div style=\"text-align:center;font-size:2.5em;margin-bottom:8px;\">🌰</div>";
                    $html .= "<div style=\"text-align:center;\">";
                    $html .= "<span class=\"rarity-badge rarity-badge-{$rarity}\">{$rarity} - {$rarityLabel}</span>";
                    $html .= "</div>";
                    $html .= "<div class=\"plant-info\" style=\"text-align:center;margin-top:8px;\">⏱️ ~{$timeStr} · 🌸 ~{$config['rarities'][$rarity]['petal_yield']} pétales</div>";
                    $html .= "<div style=\"text-align:center;margin-top:12px;\">";
                    $html .= "<a href=\"?page=inventory&action=plant&id={$seed->id}\" class=\"btn btn-primary\">🌱 Planter</a>";
                    $html .= "</div>";
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
        }

        $html .= self::footer();
        return $html;
    }

    /**
     * Page de statistiques.
     */
    public static function statsPage(): string
    {
        $html = self::header('Statistiques', 'stats');
        $html .= '<h1 class="page-title">📊 Statistiques</h1>';

        $player = Storage::read('player.json');
        $config = Storage::read('config.json');
        $stats = $player['stats'];
        $rarityLabel = $config['rarities'][$stats['rarest_plant']]['label'] ?? 'Aucune';

        $html .= '<div class="stats-grid">';

        $statItems = [
            ['💰', $player['gold'], 'Pièces d\'or'],
            ['🌱', $stats['total_plants_grown'], 'Plantes cultivées'],
            ['🌸', $stats['total_petals_harvested'], 'Pétales récoltés'],
            ['💵', $stats['total_gold_earned'], 'Or gagné (ventes)'],
            ['🛒', $stats['total_gold_spent'], 'Or dépensé'],
            ['📦', $stats['total_packs_bought'], 'Packs achetés'],
            ['⭐', $stats['rarest_plant'] . " ({$rarityLabel})", 'Plante la plus rare'],
        ];

        foreach ($statItems as [$icon, $value, $label]) {
            $html .= '<div class="stat-card">';
            $html .= "<div style=\"font-size:1.5em;\">{$icon}</div>";
            $html .= "<div class=\"stat-value\">{$value}</div>";
            $html .= "<div class=\"stat-label\">{$label}</div>";
            $html .= '</div>';
        }
        $html .= '</div>';

        // Détail des pétales
        $html .= '<div class="card" style="margin-top:20px;">';
        $html .= '<h3 style="margin-bottom:12px;">🌸 Détail des pétales</h3>';
        $html .= '<table class="prob-table">';
        $html .= '<tr><th>Rareté</th><th>En stock</th><th>Valeur unitaire</th><th>Valeur totale</th></tr>';

        $totalValue = 0;
        foreach ($config['petal_sell_prices'] as $rank => $price) {
            $stock = $player['petals'][$rank] ?? 0;
            $value = $stock * $price;
            $totalValue += $value;
            $html .= "<tr>";
            $html .= "<td class=\"rarity-{$rank}\">{$rank} - {$config['rarities'][$rank]['label']}</td>";
            $html .= "<td>{$stock}</td>";
            $html .= "<td>{$price} 💰</td>";
            $html .= "<td>{$value} 💰</td>";
            $html .= '</tr>';
        }
        $html .= "<tr style=\"font-weight:bold;\"><td colspan=\"3\">Valeur totale</td><td>{$totalValue} 💰</td></tr>";
        $html .= '</table></div>';

        // Richesse totale
        $totalWealth = $player['gold'] + $totalValue;
        $html .= "<div class=\"card\" style=\"text-align:center;\">";
        $html .= "<div style=\"font-size:0.9em;color:#888;\">Richesse totale</div>";
        $html .= "<div style=\"font-size:2em;color:#FFD700;font-weight:bold;\">💰 {$totalWealth} pièces</div>";
        $html .= "</div>";

        $html .= self::footer();
        return $html;
    }
}
