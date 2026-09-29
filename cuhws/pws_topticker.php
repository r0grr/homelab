<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

// Dades de telemetria des de Cumulus MX o livedata
$rg_file = __DIR__ . '/cumulusdata/realtimegauges.txt';
$temp = 20.0;
$windSpeed = 0.0;
$windGust = 0.0;
$rainRate = 0.0;
$rainToday = 0.0;
$heatIndex = 20.0;
$uv = 0.0;
$davisForecast = "";

if (file_exists($rg_file)) {
    $rg_json = @json_decode(file_get_contents($rg_file), true);
    if ($rg_json) {
        $temp = isset($rg_json['temp']) ? floatval($rg_json['temp']) : (isset($weather['temp']) ? floatval($weather['temp']) : 20.0);
        $windSpeed = isset($rg_json['wspeed']) ? floatval($rg_json['wspeed']) : (isset($weather['wind_speed']) ? floatval($weather['wind_speed']) : 0.0);
        $windGust = isset($rg_json['wgust']) ? floatval($rg_json['wgust']) : (isset($weather['wind_gust']) ? floatval($weather['wind_gust']) : 0.0);
        $rainRate = isset($rg_json['rrate']) ? floatval($rg_json['rrate']) : (isset($weather['rain_rate']) ? floatval($weather['rain_rate']) : 0.0);
        $rainToday = isset($rg_json['rfall']) ? floatval($rg_json['rfall']) : (isset($weather['rain_today']) ? floatval($weather['rain_today']) : 0.0);
        $heatIndex = isset($rg_json['heatindex']) ? floatval($rg_json['heatindex']) : $temp;
        $uv = isset($rg_json['UV']) ? floatval($rg_json['UV']) : (isset($weather['uv']) ? floatval($weather['uv']) : 0.0);
        if (!empty($rg_json['forecast'])) {
            $davisForecast = trim(html_entity_decode($rg_json['forecast'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }
}

if (empty($davisForecast)) {
    $davisForecast = "Temps estable amb pocs canvis de temperatura.";
}

// Comprovació estricta de les condicions reals que activen els missatges/Easter Eggs a la consola Davis física
$active_messages = [];

// 1. Plou a bots i barrals (> 25.4 mm/h = 1 in/h)
if ($rainRate >= 25.4) {
    $active_messages[] = [
        'icons' => ['🐱', '🐶'],
        'title' => 'Plou a bots i barrals!',
        'subtitle' => "IT'S RAINING CATS AND DOGS! (" . number_format($rainRate, 1) . " mm/h en directe)",
        'accent' => '#38bdf8'
    ];
}

// 2. Aguanta't el barret (ratxa >= 45 km/h o vent sostingut >= 35 km/h)
if ($windGust >= 45 || $windSpeed >= 35) {
    $active_messages[] = [
        'icons' => ['🎩', '💨'],
        'title' => "Aguanta't el barret!",
        'subtitle' => "HOLD ON TO YOUR HAT! (Ràfega " . number_format($windGust, 0) . " km/h en directe)",
        'accent' => '#fbbf24'
    ];
}

// 3. Temps per volar estels (vent sostingut entre 15 i 26 km/h i sense pluja)
if ($windSpeed >= 15 && $windSpeed <= 26 && $rainRate == 0 && $rainToday == 0) {
    $active_messages[] = [
        'icons' => ['🪁', '💨'],
        'title' => "Temps per volar estels",
        'subtitle' => "GOOD KITE FLYING WEATHER (Vent sostingut: " . number_format($windSpeed, 0) . " km/h)",
        'accent' => '#34d399'
    ];
}

// 4. Risc de pluja engelant (temperatura <= 0.5°C i precipitació)
if ($temp <= 0.5 && ($rainRate > 0 || $rainToday > 0)) {
    $active_messages[] = [
        'icons' => ['❄️', '⚠️'],
        'title' => "Risc de pluja engelant",
        'subtitle' => "FREEZING RAIN POSSIBLE (" . number_format($temp, 1) . "°C en directe)",
        'accent' => '#c084fc'
    ];
}

// 5. Alerta per calor extrema (índex de calor >= 40°C)
if ($heatIndex >= 40) {
    $active_messages[] = [
        'icons' => ['🔥', '⚠️'],
        'title' => "Alerta calor extrema",
        'subtitle' => "DANGER! EXTREME HEAT (" . number_format($heatIndex, 1) . "°C)",
        'accent' => '#f87171'
    ];
}

// Llista de missatges final:
// El pronòstic oficial sempre hi és present com a eix base.
// Si hi ha Easter Eggs realment activats per les dades meteorològiques del moment, s'afegeixen a la rotació.
// Si NO hi ha cap condició activa, NOMÉS es mostra el pronòstic de l'estació de manera estable (sense rotar estats falsos).
$all_messages = [];
$all_messages[] = [
    'icons' => ['📡'],
    'title' => "Pronòstic de l'estació",
    'subtitle' => $davisForecast,
    'accent' => '#38bdf8'
];

foreach ($active_messages as $am) {
    $all_messages[] = $am;
}

$first = $all_messages[0];
?>
<style>
.davis-top-ticker-wrap {
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 4px;
    padding: 3px 6px;
    margin-top: 4px;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    box-sizing: border-box;
    width: 100%;
}
.davis-top-ticker-track {
    display: inline-block;
    white-space: nowrap;
    will-change: transform;
    font-size: 10.5px;
    font-weight: 600;
    font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif;
    padding-left: 100%;
    animation: davisTickerMarquee 16s linear infinite;
}
@keyframes davisTickerMarquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}
.davis-ticker-box:hover .davis-top-ticker-track {
    animation-play-state: paused !important;
}
</style>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Consola Davis &bull; Ticker</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('d/m H:i'); ?></span>
</div>
<div class="davis-ticker-box" style="padding: 4px 8px; display: flex; align-items: center; justify-content: space-between; height: 82px; box-sizing: border-box;" title="<?php echo htmlspecialchars($first['title'] . ': ' . $first['subtitle']); ?>">
    <!-- Quadre d'icona més alt perquè càpiguen còmodament els dos emojis quan s'activen -->
    <div id="davis_top_icon_box" style="width: 46px; height: 68px; background: rgba(15, 23, 42, 0.85); border: 1px solid <?php echo $first['accent']; ?>60; border-radius: 7px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; flex-shrink: 0; box-shadow: inset 0 1px 4px rgba(0,0,0,0.5), 0 0 8px <?php echo $first['accent']; ?>25; transition: all 0.4s ease;">
        <?php foreach ($first['icons'] as $ic): ?>
            <span style="font-size: <?php echo count($first['icons']) > 1 ? '19px' : '24px'; ?>; line-height: 1;"><?php echo $ic; ?></span>
        <?php endforeach; ?>
    </div>

    <!-- Informació neta de la consola Davis -->
    <div style="font-size: 11px; line-height: 1.3; text-align: left; margin-left: 8px; overflow: hidden; flex: 1; display: flex; flex-direction: column; justify-content: center;">
        <div style="font-weight: 800; color: #f8fafc; font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px;">
            <span id="davis_top_dot" style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?php echo $first['accent']; ?>; box-shadow: 0 0 5px <?php echo $first['accent']; ?>; transition: all 0.4s ease;"></span>
            <span id="davis_top_title"><?php echo $first['title']; ?></span>
        </div>
        <div id="davis_top_wrap" class="davis-top-ticker-wrap">
            <span id="davis_top_track" class="davis-top-ticker-track" style="color: <?php echo $first['accent']; ?>;">
                <?php echo htmlspecialchars($first['subtitle']); ?>
            </span>
        </div>
    </div>
</div>
<script>
(function() {
    // 1. Neteja de temporitzadors previs per evitar acumulacions d'updater.php
    if (window._davisTickerTimer) {
        clearInterval(window._davisTickerTimer);
        window._davisTickerTimer = null;
    }
    
    var messages = <?php echo json_encode($all_messages); ?>;
    // Si només hi ha el pronòstic oficial (situació normal sense alertes/easter eggs actius), no cal rotar
    if (!messages || messages.length <= 1) return;
    
    if (window._davisTickerIdx === undefined) {
        window._davisTickerIdx = 0;
    }
    
    var iconBoxEl = document.getElementById('davis_top_icon_box');
    var titleEl = document.getElementById('davis_top_title');
    var dotEl = document.getElementById('davis_top_dot');
    var trackEl = document.getElementById('davis_top_track');
    
    function setItem(i) {
        var msg = messages[i];
        if (!msg) return;
        if (iconBoxEl && msg.icons) {
            var iconHtml = '';
            var sz = msg.icons.length > 1 ? '19px' : '24px';
            for (var j = 0; j < msg.icons.length; j++) {
                iconHtml += '<span style="font-size: ' + sz + '; line-height: 1;">' + msg.icons[j] + '</span>';
            }
            iconBoxEl.innerHTML = iconHtml;
            iconBoxEl.style.borderColor = msg.accent + '60';
            iconBoxEl.style.boxShadow = 'inset 0 1px 4px rgba(0,0,0,0.5), 0 0 8px ' + msg.accent + '25';
        }
        if (titleEl) titleEl.textContent = msg.title;
        if (dotEl) {
            dotEl.style.background = msg.accent;
            dotEl.style.boxShadow = '0 0 5px ' + msg.accent;
        }
        if (trackEl) {
            trackEl.style.color = msg.accent;
            trackEl.textContent = msg.subtitle;
        }
    }
    
    // Si hi ha un estat actiu en temps real, alternem amb el pronòstic de manera pausada
    window._davisTickerIdx = window._davisTickerIdx % messages.length;
    setItem(window._davisTickerIdx);
    
    window._davisTickerTimer = setInterval(function() {
        window._davisTickerIdx = (window._davisTickerIdx + 1) % messages.length;
        setItem(window._davisTickerIdx);
    }, 18000);
})();
</script>
