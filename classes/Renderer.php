<?php
/**
 * Renderer — Génère l'interface HTML/CSS du jeu.
 */
class Renderer
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Enveloppe le contenu dans le layout HTML principal.
     */
    public function layout(string $title, string $content, string $activePage = ''): string
    {
        $css = $this->getCSS();
        $nav = $this->renderNav($activePage);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title} — FloraGen</title>
            <style>{$css}</style>
        </head>
        <body>
            <header>
                <h1>FloraGen</h1>
                <p class="subtitle">Culture de plantes procedurales</p>
            </header>
            {$nav}
            <main>{$content}</main>
            <footer>FloraGen v1.0 — PHP Vanilla — Donnees JSON</footer>
        </body>
        </html>
        HTML;
    }

    private function renderNav(string $active): string
    {
        $pages = [
            'garden' => 'Jardin',
            'shop' => 'Boutique',
            'inventory' => 'Inventaire',
            'stats' => 'Statistiques',
        ];

        $links = '';
        foreach ($pages as $key => $label) {
            $cls = $key === $active ? ' class="active"' : '';
            $links .= "<a href=\"?page={$key}\"{$cls}>{$label}</a>";
        }

        return "<nav>{$links}</nav>";
    }

    // === PAGES ===

    /**
     * Page Jardin : affiche les plantes en croissance et les emplacements libres.
     */
    public function renderGarden(array $gardenData, array $freeSlots, array $player, array $seeds): string
    {
        $maxSlots = $this->config['garden']['max_slots'];
        $html = "<div class=\"page-header\">";
        $html .= "<h2>Mon Jardin</h2>";
        $html .= "<div class=\"resources\"><span class=\"gold\">{$player['gold']} pieces</span> | <span class=\"petals\">{$player['petals']} petales</span></div>";
        $html .= "</div>";

        $html .= '<div class="garden-grid">';

        // Construire la grille complète
        $slots = [];
        for ($i = 0; $i < $maxSlots; $i++) {
            $slots[$i] = null;
        }
        foreach ($gardenData as $gd) {
            $slots[$gd['data']['slot']] = $gd;
        }

        for ($i = 0; $i < $maxSlots; $i++) {
            if ($slots[$i] !== null) {
                $html .= $this->renderPlantSlot($slots[$i], $i);
            } else {
                $html .= $this->renderEmptySlot($i, $seeds);
            }
        }

        $html .= '</div>';

        // Auto-refresh pour la croissance
        $html .= '<script>setTimeout(function(){location.reload();}, 5000);</script>';

        return $html;
    }

    private function renderPlantSlot(array $gd, int $slot): string
    {
        $plant = $gd['plant'];
        $visual = $gd['visual'];
        $rarity = $plant->getRarity();
        $rarityConfig = $this->config['rarity'][$rarity];
        $percent = $gd['percent'];
        $ready = $gd['ready'];
        $remaining = $gd['remaining'];

        $rarityColor = $rarityConfig['color'];
        $statusClass = $ready ? 'ready' : 'growing';

        $plantVisual = $this->renderPlantSVG($visual, $percent);

        $actionBtn = '';
        if ($ready) {
            $actionBtn = "<form method=\"post\" action=\"?page=action&do=harvest\">
                <input type=\"hidden\" name=\"plant_id\" value=\"{$this->esc($plant->getId())}\">
                <button type=\"submit\" class=\"btn btn-harvest\">Recolter</button>
            </form>";
        }

        $barWidth = min(100, $percent);

        return <<<HTML
        <div class="garden-slot {$statusClass}" style="border-color:{$rarityColor}">
            <div class="slot-header">
                <span class="rarity-badge" style="background:{$rarityColor}">{$rarity}</span>
                <span class="plant-name">{$this->esc($visual['name'])}</span>
            </div>
            <div class="plant-visual">{$plantVisual}</div>
            <div class="growth-bar-container">
                <div class="growth-bar" style="width:{$barWidth}%;background:{$rarityColor}"></div>
                <span class="growth-text">{$percent}%</span>
            </div>
            <div class="slot-info">
                <span>{$remaining}</span>
            </div>
            {$actionBtn}
        </div>
        HTML;
    }

    private function renderEmptySlot(int $slot, array $seeds): string
    {
        if (empty($seeds)) {
            return <<<HTML
            <div class="garden-slot empty">
                <div class="empty-label">Emplacement {$slot}</div>
                <p class="empty-text">Aucune graine disponible</p>
            </div>
            HTML;
        }

        $options = '';
        foreach ($seeds as $s) {
            $r = $s['rarity'];
            $label = "{$this->config['rarity'][$r]['label']} [{$r}]";
            $options .= "<option value=\"{$this->esc($s['id'])}\">{$this->esc($label)}</option>";
        }

        return <<<HTML
        <div class="garden-slot empty">
            <div class="empty-label">Emplacement {$slot}</div>
            <form method="post" action="?page=action&do=plant">
                <input type="hidden" name="slot" value="{$slot}">
                <select name="seed_id" class="seed-select">{$options}</select>
                <button type="submit" class="btn btn-plant">Planter</button>
            </form>
        </div>
        HTML;
    }

    /**
     * Génère un SVG procédural pour représenter la plante.
     */
    private function renderPlantSVG(array $visual, float $growthPercent): string
    {
        $scale = max(0.1, $growthPercent / 100);
        $pc = $visual['primary_color'];
        $sc = $visual['secondary_color'];
        $petalCount = $visual['petal_count'];
        $size = $visual['size'];
        $leafCount = $visual['leaf_count'];
        $shape = $visual['petal_shape'];

        $svgSize = 120;
        $cx = $svgSize / 2;
        $cy = $svgSize / 2;

        $svg = "<svg width=\"{$svgSize}\" height=\"{$svgSize}\" viewBox=\"0 0 {$svgSize} {$svgSize}\">";

        // Tige
        $stemHeight = 30 * $scale;
        $stemY1 = $cy + 10;
        $stemY2 = $stemY1 + $stemHeight;
        $svg .= "<line x1=\"{$cx}\" y1=\"{$stemY1}\" x2=\"{$cx}\" y2=\"{$stemY2}\" stroke=\"#4a7c3f\" stroke-width=\"3\" stroke-linecap=\"round\"/>";

        // Feuilles
        for ($i = 0; $i < $leafCount && $i < 4; $i++) {
            $leafY = $stemY1 + ($stemHeight * (($i + 1) / ($leafCount + 1)));
            $dir = ($i % 2 === 0) ? -1 : 1;
            $lx = $cx + ($dir * 15 * $scale);
            $svg .= "<ellipse cx=\"{$lx}\" cy=\"{$leafY}\" rx=\"" . (10 * $scale) . "\" ry=\"" . (5 * $scale) . "\" fill=\"#6abf4b\" transform=\"rotate(" . ($dir * 30) . " {$lx} {$leafY})\"/>";
        }

        // Pétales
        $petalRadius = (8 + $size * 2) * $scale;
        $orbitRadius = $petalRadius * 1.2;

        for ($i = 0; $i < $petalCount; $i++) {
            $angle = (360 / $petalCount) * $i - 90;
            $rad = deg2rad($angle);
            $px = $cx + cos($rad) * $orbitRadius;
            $py = $cy + sin($rad) * $orbitRadius;

            switch ($shape) {
                case 'rond':
                    $svg .= "<circle cx=\"{$px}\" cy=\"{$py}\" r=\"{$petalRadius}\" fill=\"{$pc}\" opacity=\"0.85\"/>";
                    break;
                case 'pointu':
                    $svg .= $this->svgPetalPointy($px, $py, $petalRadius, $angle, $pc);
                    break;
                case 'coeur':
                    $svg .= "<circle cx=\"{$px}\" cy=\"{$py}\" r=\"{$petalRadius}\" fill=\"{$pc}\" opacity=\"0.85\"/>";
                    $px2 = $cx + cos($rad) * ($orbitRadius * 0.7);
                    $py2 = $cy + sin($rad) * ($orbitRadius * 0.7);
                    $svg .= "<circle cx=\"{$px2}\" cy=\"{$py2}\" r=\"" . ($petalRadius * 0.6) . "\" fill=\"{$sc}\" opacity=\"0.6\"/>";
                    break;
                case 'étoile':
                    $svg .= $this->svgPetalStar($px, $py, $petalRadius, $angle, $pc);
                    break;
                default:
                    $svg .= "<ellipse cx=\"{$px}\" cy=\"{$py}\" rx=\"{$petalRadius}\" ry=\"" . ($petalRadius * 0.65) . "\" fill=\"{$pc}\" opacity=\"0.85\" transform=\"rotate({$angle} {$px} {$py})\"/>";
                    break;
            }
        }

        // Centre
        $centerR = max(3, $petalRadius * 0.5);
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$centerR}\" fill=\"{$sc}\"/>";

        // Aura pour rang A/S
        if ($visual['aura'] !== 'aucune') {
            $auraR = $orbitRadius + $petalRadius + 8;
            $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$auraR}\" fill=\"none\" stroke=\"{$pc}\" stroke-width=\"2\" opacity=\"0.3\" stroke-dasharray=\"4 3\"/>";
        }

        $svg .= '</svg>';
        return $svg;
    }

    private function svgPetalPointy(float $x, float $y, float $r, float $angle, string $color): string
    {
        $rad = deg2rad($angle);
        $tipX = $x + cos($rad) * $r * 1.5;
        $tipY = $y + sin($rad) * $r * 1.5;
        $perpRad = deg2rad($angle + 90);
        $w = $r * 0.6;
        $x1 = $x + cos($perpRad) * $w;
        $y1 = $y + sin($perpRad) * $w;
        $x2 = $x - cos($perpRad) * $w;
        $y2 = $y - sin($perpRad) * $w;

        return "<polygon points=\"{$tipX},{$tipY} {$x1},{$y1} {$x2},{$y2}\" fill=\"{$color}\" opacity=\"0.85\"/>";
    }

    private function svgPetalStar(float $x, float $y, float $r, float $angle, string $color): string
    {
        $points = '';
        for ($i = 0; $i < 5; $i++) {
            $a1 = deg2rad($angle + $i * 72);
            $a2 = deg2rad($angle + $i * 72 + 36);
            $points .= ($x + cos($a1) * $r) . ',' . ($y + sin($a1) * $r) . ' ';
            $points .= ($x + cos($a2) * $r * 0.4) . ',' . ($y + sin($a2) * $r * 0.4) . ' ';
        }
        return "<polygon points=\"{$points}\" fill=\"{$color}\" opacity=\"0.85\"/>";
    }

    /**
     * Page Boutique : achat de packs et vente de pétales.
     */
    public function renderShop(array $player, ?array $lastPurchase = null, ?string $error = null, ?string $success = null): string
    {
        $minPrice = $this->config['shop']['min_price'];

        $html = "<div class=\"page-header\">";
        $html .= "<h2>Boutique</h2>";
        $html .= "<div class=\"resources\"><span class=\"gold\">{$player['gold']} pieces</span> | <span class=\"petals\">{$player['petals']} petales</span></div>";
        $html .= "</div>";

        // Messages
        if ($error) {
            $html .= "<div class=\"alert alert-error\">{$this->esc($error)}</div>";
        }
        if ($success) {
            $html .= "<div class=\"alert alert-success\">{$this->esc($success)}</div>";
        }

        // Résultat dernier achat
        if ($lastPurchase) {
            $html .= $this->renderPackResult($lastPurchase);
        }

        // Formulaire achat pack
        $html .= <<<HTML
        <div class="shop-section">
            <h3>Acheter un Pack de Graines (x3)</h3>
            <p>Plus vous investissez, meilleures sont les chances de rarete elevee !</p>
            <form method="post" action="?page=action&do=buy_pack" class="shop-form">
                <label for="price">Montant a depenser (min. {$minPrice}) :</label>
                <input type="number" name="price" id="price" min="{$minPrice}" max="{$player['gold']}" value="{$minPrice}" required>
                <button type="submit" class="btn btn-buy">Acheter le Pack</button>
            </form>
        </div>
        HTML;

        // Aperçu probabilités
        $html .= $this->renderProbabilityPreview();

        // Vente pétales
        if ($player['petals'] > 0) {
            $html .= <<<HTML
            <div class="shop-section">
                <h3>Vendre des Petales</h3>
                <p>Vous avez {$player['petals']} petales. (1 petale = 1 piece)</p>
                <form method="post" action="?page=action&do=sell" class="shop-form">
                    <label for="amount">Quantite a vendre :</label>
                    <input type="number" name="amount" id="amount" min="1" max="{$player['petals']}" value="{$player['petals']}" required>
                    <button type="submit" class="btn btn-sell">Vendre</button>
                </form>
            </div>
            HTML;
        }

        return $html;
    }

    private function renderPackResult(array $purchase): string
    {
        $html = '<div class="pack-result"><h3>Graines obtenues !</h3><div class="seed-cards">';

        foreach ($purchase['seeds'] as $seed) {
            $rarity = $seed->getRarity();
            $rc = $this->config['rarity'][$rarity];
            $tmpPlant = new Plant([
                'id' => 'preview',
                'seed' => $seed->getSeed(),
                'rarity' => $rarity,
                'slot' => 0,
                'planted_at' => time(),
                'growth_duration' => 100,
                'harvested' => false,
            ], $this->config);
            $visual = $tmpPlant->getVisualAttributes();

            $html .= "<div class=\"seed-card\" style=\"border-color:{$rc['color']}\">";
            $html .= "<div class=\"rarity-badge\" style=\"background:{$rc['color']}\">{$rarity} — {$rc['label']}</div>";
            $html .= "<div class=\"plant-visual\">" . $this->renderPlantSVG($visual, 100) . "</div>";
            $html .= "<div class=\"seed-name\">{$this->esc($visual['name'])}</div>";
            $html .= "<div class=\"seed-details\">Petales: {$visual['petal_count']} | Forme: {$this->esc($visual['petal_shape'])} | Taille: {$visual['size']}</div>";
            $html .= "</div>";
        }

        $html .= '</div></div>';
        return $html;
    }

    private function renderProbabilityPreview(): string
    {
        $html = '<div class="shop-section"><h3>Probabilites selon l\'investissement</h3>';
        $html .= '<table class="prob-table"><thead><tr><th>Prix</th>';

        $ranks = array_keys($this->config['rarity']);
        foreach ($ranks as $r) {
            $color = $this->config['rarity'][$r]['color'];
            $html .= "<th style=\"color:{$color}\">{$r}</th>";
        }
        $html .= '</tr></thead><tbody>';

        $prices = [10, 25, 50, 100, 250, 500];
        foreach ($prices as $p) {
            $probs = Seed::calculateAdjustedProbabilities($p, $this->config);
            $html .= "<tr><td>{$p}</td>";
            foreach ($ranks as $r) {
                $val = number_format($probs[$r] ?? 0, 1);
                $html .= "<td>{$val}%</td>";
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    /**
     * Page Inventaire : graines en stock et historique des récoltes.
     */
    public function renderInventory(array $inventory, array $player): string
    {
        $html = "<div class=\"page-header\">";
        $html .= "<h2>Inventaire</h2>";
        $html .= "<div class=\"resources\"><span class=\"gold\">{$player['gold']} pieces</span> | <span class=\"petals\">{$player['petals']} petales</span></div>";
        $html .= "</div>";

        // Graines
        $html .= '<div class="inv-section"><h3>Graines en stock (' . count($inventory['seeds']) . ')</h3>';
        if (empty($inventory['seeds'])) {
            $html .= '<p class="empty-text">Aucune graine. Visitez la boutique !</p>';
        } else {
            $html .= '<div class="seed-list">';
            foreach ($inventory['seeds'] as $s) {
                $r = $s['rarity'];
                $rc = $this->config['rarity'][$r];
                $html .= "<div class=\"seed-item\" style=\"border-left:4px solid {$rc['color']}\">";
                $html .= "<span class=\"rarity-badge\" style=\"background:{$rc['color']}\">{$r}</span> ";
                $html .= "<span>{$rc['label']}</span>";
                $html .= "</div>";
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Historique récoltes
        $html .= '<div class="inv-section"><h3>Plantes recoltees (' . count($inventory['harvested_plants']) . ')</h3>';
        if (!empty($inventory['harvested_plants'])) {
            $html .= '<div class="harvest-list">';
            $reversed = array_reverse($inventory['harvested_plants']);
            foreach (array_slice($reversed, 0, 20) as $h) {
                $r = $h['plant']['rarity'];
                $rc = $this->config['rarity'][$r];
                $name = $h['visual']['name'] ?? 'Inconnue';
                $petals = $h['petals_earned'];
                $date = date('d/m H:i', $h['harvested_at']);

                $html .= "<div class=\"harvest-item\" style=\"border-left:4px solid {$rc['color']}\">";
                $html .= "<span class=\"rarity-badge\" style=\"background:{$rc['color']}\">{$r}</span> ";
                $html .= "<strong>{$this->esc($name)}</strong> — {$petals} petales — {$date}";
                $html .= "</div>";
            }
            $html .= '</div>';
        } else {
            $html .= '<p class="empty-text">Aucune recolte encore.</p>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Page Statistiques.
     */
    public function renderStats(array $stats): string
    {
        $p = $stats['player'];

        $playTime = $this->formatDuration($stats['play_time']);

        $html = "<div class=\"page-header\"><h2>Statistiques</h2></div>";

        $html .= '<div class="stats-grid">';
        $html .= $this->statCard('Pieces', (string)$p['gold'], '#FFD700');
        $html .= $this->statCard('Petales', (string)$p['petals'], '#FF69B4');
        $html .= $this->statCard('Plantes en croissance', (string)$stats['plants_growing'], '#4CAF50');
        $html .= $this->statCard('Graines en stock', (string)$stats['seeds_count'], '#2196F3');
        $html .= $this->statCard('Total plantees', (string)$p['total_planted'], '#8BC34A');
        $html .= $this->statCard('Total recoltees', (string)$p['total_harvested'], '#FF9800');
        $html .= $this->statCard('Packs achetes', (string)$p['total_packs_bought'], '#9C27B0');
        $html .= $this->statCard('Pieces depensees', (string)$p['total_gold_spent'], '#F44336');
        $html .= $this->statCard('Pieces gagnees', (string)$p['total_gold_earned'], '#4CAF50');
        $html .= $this->statCard('Petales vendus', (string)$p['total_sold'], '#E91E63');

        $bestColor = $this->config['rarity'][$p['best_rarity']]['color'];
        $bestLabel = $this->config['rarity'][$p['best_rarity']]['label'];
        $html .= $this->statCard('Meilleure rarete', "{$p['best_rarity']} — {$bestLabel}", $bestColor);
        $html .= $this->statCard('Temps de jeu', $playTime, '#607D8B');
        $html .= '</div>';

        // Répartition récoltes par rareté
        if (!empty($stats['harvested_by_rarity'])) {
            $html .= '<div class="inv-section"><h3>Recoltes par rarete</h3><div class="rarity-bars">';
            foreach ($this->config['rarity'] as $r => $rc) {
                $count = $stats['harvested_by_rarity'][$r] ?? 0;
                $maxCount = max(1, max($stats['harvested_by_rarity']));
                $barW = ($count / $maxCount) * 100;
                $html .= "<div class=\"rarity-bar-row\">";
                $html .= "<span class=\"rarity-badge\" style=\"background:{$rc['color']}\">{$r}</span>";
                $html .= "<div class=\"rarity-bar-bg\"><div class=\"rarity-bar-fill\" style=\"width:{$barW}%;background:{$rc['color']}\"></div></div>";
                $html .= "<span class=\"rarity-bar-count\">{$count}</span>";
                $html .= "</div>";
            }
            $html .= '</div></div>';
        }

        return $html;
    }

    private function statCard(string $label, string $value, string $color): string
    {
        return "<div class=\"stat-card\"><div class=\"stat-value\" style=\"color:{$color}\">{$this->esc($value)}</div><div class=\"stat-label\">{$this->esc($label)}</div></div>";
    }

    /**
     * Page de message (succès, erreur, redirection).
     */
    public function renderMessage(string $message, string $type = 'success', string $backPage = 'garden'): string
    {
        $cls = $type === 'error' ? 'alert-error' : 'alert-success';
        return <<<HTML
        <div class="alert {$cls}">{$this->esc($message)}</div>
        <a href="?page={$backPage}" class="btn btn-back">Retour</a>
        <script>setTimeout(function(){window.location='?page={$backPage}';}, 3000);</script>
        HTML;
    }

    // === UTILITAIRES ===

    private function esc(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) return "{$seconds}s";
        if ($seconds < 3600) return intdiv($seconds, 60) . 'min';

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return "{$h}h {$m}min";
    }

    // === CSS ===

    private function getCSS(): string
    {
        return <<<'CSS'
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #e0e0e0;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #16213e, #0f3460);
            padding: 1.2rem;
            text-align: center;
            border-bottom: 3px solid #533483;
        }

        header h1 {
            font-size: 1.8rem;
            color: #e94560;
            letter-spacing: 2px;
        }

        .subtitle { color: #888; font-size: 0.85rem; margin-top: 0.2rem; }

        nav {
            display: flex;
            justify-content: center;
            gap: 0;
            background: #16213e;
            border-bottom: 1px solid #333;
        }

        nav a {
            color: #aaa;
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }

        nav a:hover { color: #fff; background: rgba(255,255,255,0.05); }
        nav a.active { color: #e94560; border-bottom-color: #e94560; }

        main { max-width: 1000px; margin: 1.5rem auto; padding: 0 1rem; }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .page-header h2 { color: #e94560; }

        .resources {
            background: #16213e;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .gold { color: #FFD700; font-weight: bold; }
        .petals { color: #FF69B4; font-weight: bold; }

        /* Jardin */
        .garden-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .garden-slot {
            background: #16213e;
            border: 2px solid #333;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: transform 0.2s;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .garden-slot:hover { transform: translateY(-2px); }
        .garden-slot.ready { box-shadow: 0 0 15px rgba(76, 175, 80, 0.4); }

        .garden-slot.empty {
            border-style: dashed;
            border-color: #444;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .slot-header { margin-bottom: 0.5rem; }

        .rarity-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            color: #fff;
            font-weight: bold;
            font-size: 0.75rem;
        }

        .plant-name { font-size: 0.85rem; color: #ccc; margin-left: 0.3rem; }

        .plant-visual {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80px;
        }

        .growth-bar-container {
            background: #0f0f23;
            border-radius: 10px;
            height: 20px;
            position: relative;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .growth-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .growth-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.7rem;
            font-weight: bold;
            color: #fff;
            text-shadow: 0 0 3px #000;
        }

        .slot-info { font-size: 0.8rem; color: #999; }

        .empty-label { color: #666; font-size: 0.9rem; margin-bottom: 0.8rem; }
        .empty-text { color: #555; font-size: 0.8rem; }

        .seed-select {
            width: 100%;
            padding: 0.4rem;
            margin: 0.5rem 0;
            background: #0f0f23;
            color: #e0e0e0;
            border: 1px solid #444;
            border-radius: 6px;
        }

        /* Boutons */
        .btn {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
            color: #fff;
            transition: opacity 0.2s;
            text-decoration: none;
        }

        .btn:hover { opacity: 0.85; }

        .btn-plant { background: #4CAF50; }
        .btn-harvest { background: #FF9800; width: 100%; }
        .btn-buy { background: #9C27B0; }
        .btn-sell { background: #2196F3; }
        .btn-back { background: #607D8B; margin-top: 1rem; }

        /* Alertes */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4CAF50; color: #81C784; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #F44336; color: #E57373; }

        /* Boutique */
        .shop-section {
            background: #16213e;
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .shop-section h3 { color: #e94560; margin-bottom: 0.8rem; }
        .shop-section p { color: #aaa; font-size: 0.85rem; margin-bottom: 0.8rem; }

        .shop-form {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .shop-form label { font-size: 0.85rem; }

        .shop-form input[type="number"] {
            width: 120px;
            padding: 0.4rem 0.6rem;
            background: #0f0f23;
            color: #FFD700;
            border: 1px solid #444;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
        }

        /* Pack résultat */
        .pack-result {
            background: #16213e;
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid #533483;
        }

        .pack-result h3 { color: #FFD700; margin-bottom: 1rem; text-align: center; }

        .seed-cards {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .seed-card {
            background: #0f0f23;
            border: 2px solid #444;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            width: 180px;
        }

        .seed-name { font-weight: bold; margin: 0.5rem 0; font-size: 0.85rem; }
        .seed-details { font-size: 0.7rem; color: #888; }

        /* Tableaux */
        .prob-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .prob-table th, .prob-table td {
            padding: 0.5rem;
            text-align: center;
            border-bottom: 1px solid #333;
        }

        .prob-table th { color: #e94560; }

        /* Inventaire */
        .inv-section {
            background: #16213e;
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .inv-section h3 { color: #e94560; margin-bottom: 0.8rem; }

        .seed-list, .harvest-list { display: flex; flex-direction: column; gap: 0.4rem; }

        .seed-item, .harvest-item {
            background: #0f0f23;
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: #16213e;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value { font-size: 1.3rem; font-weight: bold; margin-bottom: 0.3rem; }
        .stat-label { font-size: 0.75rem; color: #888; }

        .rarity-bars { display: flex; flex-direction: column; gap: 0.5rem; }

        .rarity-bar-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rarity-bar-bg {
            flex: 1;
            height: 16px;
            background: #0f0f23;
            border-radius: 8px;
            overflow: hidden;
        }

        .rarity-bar-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s;
        }

        .rarity-bar-count { font-size: 0.8rem; min-width: 30px; text-align: right; }

        footer {
            text-align: center;
            padding: 1.5rem;
            color: #555;
            font-size: 0.75rem;
            border-top: 1px solid #222;
            margin-top: 2rem;
        }

        @media (max-width: 700px) {
            .garden-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .seed-cards { flex-direction: column; align-items: center; }
            .shop-form { flex-direction: column; }
        }
        CSS;
    }
}
