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

// Avaluació de condicions dels missatges i Easter Eggs de la consola Davis
$active_message = null;

// 1. Easter Egg mític: It's raining cats and dogs! (Rain rate >= 25.4 mm/h o 1.0 in/h)
if ($rainRate >= 25.4) {
    $active_message = [
        'icon' => '🐱🐶',
        'title' => 'Plou a bots i barrals!',
        'subtitle' => "IT'S RAINING CATS AND DOGS (" . number_format($rainRate, 1) . " mm/h)",
        'accent' => '#38bdf8'
    ];
}
// 2. Aguanta't el barret! (Ràfega >= 45 km/h o vent sostingut >= 35 km/h)
elseif ($windGust >= 45 || $windSpeed >= 35) {
    $active_message = [
        'icon' => '🎩💨',
        'title' => "Aguanta't el barret!",
        'subtitle' => "HOLD ON TO YOUR HAT! (Ràfega " . number_format($windGust, 0) . " km/h)",
        'accent' => '#fbbf24'
    ];
}
// 3. Temps ideal per volar estels (Vent sostingut 15-26 km/h i sense pluja)
elseif ($windSpeed >= 15 && $windSpeed <= 26 && $rainRate == 0 && $rainToday == 0) {
    $active_message = [
        'icon' => '🪁',
        'title' => "Temps per volar estels",
        'subtitle' => "GOOD KITE FLYING WEATHER (" . number_format($windSpeed, 0) . " km/h)",
        'accent' => '#34d399'
    ];
}
// 4. Risc de pluja engelant (Temp <= 0.5°C i pluja activa)
elseif ($temp <= 0.5 && ($rainRate > 0 || $rainToday > 0)) {
    $active_message = [
        'icon' => '❄️⚠️',
        'title' => "Risc de pluja engelant",
        'subtitle' => "FREEZING RAIN POSSIBLE (" . number_format($temp, 1) . "°C)",
        'accent' => '#c084fc'
    ];
}
// 5. Alerta de calor extrema (Índex de calor >= 40°C)
elseif ($heatIndex >= 40) {
    $active_message = [
        'icon' => '🔥⚠️',
        'title' => "Alerta calor extrema",
        'subtitle' => "EXTREME HEAT INDEX (" . number_format($heatIndex, 1) . "°C)",
        'accent' => '#f87171'
    ];
}
// 6. Índex UV molt alt (UV >= 8)
elseif ($uv >= 8) {
    $active_message = [
        'icon' => '☀️🧴',
        'title' => "Índex UV molt alt",
        'subtitle' => "HIGH UV INDEX (UV " . number_format($uv, 1) . ")",
        'accent' => '#fbbf24'
    ];
}
// 7. Estat normal: Pronòstic baromètric de la consola Davis
else {
    $active_message = [
        'icon' => '📡',
        'title' => "Pronòstic de l'estació",
        'subtitle' => $davisForecast,
        'accent' => '#38bdf8'
    ];
}
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
}
.davis-top-ticker-track {
    display: inline-block;
    white-space: nowrap;
    will-change: transform;
    font-size: 10.5px;
    font-weight: 600;
    font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif;
}
@keyframes davisTopPingPong {
    0%, 15% { transform: translateX(0px); }
    85%, 100% { transform: translateX(var(--davis-top-scroll, -150px)); }
}
.davis-ticker-box:hover .davis-top-ticker-track {
    animation-play-state: paused !important;
}
</style>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Consola Davis &bull; Ticker</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('d/m H:i'); ?></span>
</div>
<div class="davis-ticker-box" style="padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; height: 80px; box-sizing: border-box;" title="<?php echo htmlspecialchars($active_message['title'] . ': ' . $active_message['subtitle']); ?>">
    <!-- Icona d'estil LCD de la consola -->
    <div style="width: 48px; height: 50px; background: rgba(15, 23, 42, 0.85); border: 1px solid <?php echo $active_message['accent']; ?>60; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; box-shadow: inset 0 1px 4px rgba(0,0,0,0.5), 0 0 8px <?php echo $active_message['accent']; ?>25;">
        <span><?php echo $active_message['icon']; ?></span>
    </div>

    <!-- Informació neta de la consola -->
    <div style="font-size: 11px; line-height: 1.3; text-align: left; margin-left: 8px; overflow: hidden; flex: 1; display: flex; flex-direction: column; justify-content: center;">
        <div style="font-weight: 800; color: #f8fafc; font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px;">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?php echo $active_message['accent']; ?>; box-shadow: 0 0 5px <?php echo $active_message['accent']; ?>;"></span>
            <?php echo $active_message['title']; ?>
        </div>
        <div id="davis_top_wrap" class="davis-top-ticker-wrap">
            <span id="davis_top_track" class="davis-top-ticker-track" style="color: <?php echo $active_message['accent']; ?>;">
                <?php echo htmlspecialchars($active_message['subtitle']); ?>
            </span>
        </div>
    </div>
</div>
<script>
(function() {
    function initDavisTopTicker() {
        var wrap = document.getElementById('davis_top_wrap');
        var track = document.getElementById('davis_top_track');
        if (!wrap || !track) return;
        var diff = track.scrollWidth - wrap.clientWidth;
        if (diff > 4) {
            wrap.style.setProperty('--davis-top-scroll', '-' + (diff + 8) + 'px');
            var duration = Math.max(8, Math.round(diff / 20));
            track.style.animation = 'davisTopPingPong ' + duration + 's ease-in-out infinite alternate';
        } else {
            track.style.animation = 'none';
            track.style.transform = 'none';
        }
    }
    initDavisTopTicker();
    setTimeout(initDavisTopTicker, 250);
})();
</script>
