<?php
header('Content-Type: text/html; charset=UTF-8');

$cache_file = __DIR__ . '/jsondata/estofex_cache.json';
$cache_ttl = 1200; // 20 minuts de cau per màxima rapidesa

$data = null;
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
    $data = @json_decode(file_get_contents($cache_file), true);
}

if (!$data) {
    $url = "https://www.estofex.org/cgi-bin/polygon/showforecast.cgi?listvalid=yes";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MeteoSallent/2.0 (Weather Station)');
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $has_forecast = false;
    $fcst_file = '';
    $issued = '';
    $period = '';
    $forecaster = '';
    $threat_level = '';
    $map_url = '';

    if ($http_code == 200 && !empty($html)) {
        // Busquem la fila de la taula amb Storm Forecast
        if (preg_match('/fcstfile=([a-zA-Z0-9_\.]+_stormforecast\.xml)/i', $html, $m)) {
            $has_forecast = true;
            $fcst_file = $m[1];
            $map_url = "https://www.estofex.org/forecasts/tempmap/" . $fcst_file . ".png";

            if (preg_match('/issued:\s*([^<]+)/i', $html, $mi)) {
                $issued = trim($mi[1]);
            }
            if (preg_match('/graphics\/([0-9]+)\.png/i', $html, $ml)) {
                $threat_level = 'Nivell ' . $ml[1];
            }
            if (preg_match('/<TD><P CLASS=small>([^<]+-[^<]+UTC)<\/P><\/TD>/i', $html, $mp)) {
                $period = trim(str_replace('<BR>', ' ', $mp[1]));
            }
            if (preg_match('/<TD><P CLASS=small>([A-Z]+)<\/P><\/TD>\s*<\/TR>/i', $html, $mf)) {
                $forecaster = trim($mf[1]);
            }
        }
    }

    $data = [
        'has_forecast' => $has_forecast,
        'fcst_file'    => $fcst_file,
        'map_url'      => $map_url,
        'issued'       => $issued,
        'period'       => $period,
        'forecaster'   => $forecaster,
        'threat_level' => $threat_level,
        'updated_at'   => time()
    ];

    if (!is_dir(__DIR__ . '/jsondata')) {
        @mkdir(__DIR__ . '/jsondata', 0755, true);
    }
    @file_put_contents($cache_file, json_encode($data, JSON_PRETTY_PRINT));
}

$has_forecast = !empty($data['has_forecast']);
$map_url      = $data['map_url'] ?? '';
$issued       = $data['issued'] ?? '';
$period       = $data['period'] ?? '';
$forecaster   = $data['forecaster'] ?? '';
$threat_level = $data['threat_level'] ?? '';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storm Forecast &bull; ESTOFEX European Severe Storms</title>
    <style>
        * { box-sizing: border-box; }
        body, html {
            margin: 0;
            padding: 0;
            background: #0f172a;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            text-align: center;
            height: 100%;
            overflow-y: auto;
        }
        .header {
            padding: 10px 16px;
            background: #1e293b;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header .title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #ffffff;
        }
        .header a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            background: rgba(56, 189, 248, 0.12);
            border-radius: 5px;
            border: 1px solid rgba(56, 189, 248, 0.35);
            transition: background 0.2s;
        }
        .header a:hover {
            background: rgba(56, 189, 248, 0.25);
        }
        .content {
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .badge-box {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 12px;
        }
        .badge-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 4px 10px;
            border-radius: 6px;
            color: #cbd5e1;
        }
        .badge-level {
            background: #ef4444;
            color: #ffffff;
            font-weight: 700;
            border: 1px solid #dc2626;
        }
        .img-card {
            background: #ffffff;
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
            max-width: 900px;
            width: 100%;
        }
        img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 4px;
        }
        .no-forecast-card {
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            padding: 24px;
            max-width: 600px;
            width: 100%;
            margin: 20px auto;
            text-align: center;
        }
        .footer-note {
            margin-top: 14px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <span>Previsió Europea de Tempestes Severes &bull; ESTOFEX</span>
        </div>
        <a href="https://www.estofex.org/forecasts" target="_blank" rel="noopener noreferrer">↗ Web Oficial ESTOFEX</a>
    </div>

    <div class="content">
        <?php if ($has_forecast && !empty($map_url)): ?>
            <div class="badge-box">
                <?php if (!empty($threat_level)): ?>
                    <div class="badge-item badge-level"><?php echo htmlspecialchars($threat_level); ?></div>
                <?php endif; ?>
                <?php if (!empty($period)): ?>
                    <div class="badge-item"><b>Validesa:</b> <?php echo htmlspecialchars($period); ?></div>
                <?php endif; ?>
                <?php if (!empty($issued)): ?>
                    <div class="badge-item"><b>Emès:</b> <?php echo htmlspecialchars($issued); ?></div>
                <?php endif; ?>
                <?php if (!empty($forecaster)): ?>
                    <div class="badge-item"><b>Predictor:</b> <?php echo htmlspecialchars($forecaster); ?></div>
                <?php endif; ?>
            </div>

            <div class="img-card">
                <img src="<?php echo htmlspecialchars($map_url); ?>" alt="Mapa Storm Forecast ESTOFEX" onerror="this.onerror=null; this.src='https://www.estofex.org/forecasts';" />
            </div>

            <div class="footer-note">
                El butlletí ESTOFEX (European Storm Forecast Experiment) indica la probabilitat de fenòmens convectius severs (tempestes amb calamarsa gran, vents huracanats, tornados o pluges torrencials) a tot el continent europeu per a les pròximes 24-48 hores.
            </div>
        <?php else: ?>
            <div class="no-forecast-card">
                <div style="font-size: 32px; margin-bottom: 8px;">⛅</div>
                <h3 style="margin: 0 0 10px 0; color: #38bdf8; font-size: 17px;">No actualitzat avui / Sense avisos actius</h3>
                <p style="font-size: 13px; color: #cbd5e1; margin: 0 0 12px 0; line-height: 1.5;">
                    Actualment no hi ha cap butlletí especial de convecció severa emès per ESTOFEX per a la jornada d'avui a la península Ibèrica.
                </p>
                <div style="font-size: 12px; color: #94a3b8;">
                    Quan es produeixin situacions meteorològiques de tempestes severes o inestabilitat destacada, el mapa s'actualitzarà automàticament de forma diària.
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
