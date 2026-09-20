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
        'badge' => 'DAVIS EASTER EGG',
        'badge_bg' => 'rgba(239, 68, 68, 0.2)',
        'badge_color' => '#ef4444',
        'title' => 'Plou a bots i barrals!',
        'subtitle' => "IT'S RAINING CATS AND DOGS! (" . number_format($rainRate, 1) . " mm/h)",
        'accent' => '#38bdf8'
    ];
}
// 2. Aguanta't el barret! (Ràfega >= 45 km/h o vent sostingut >= 35 km/h)
elseif ($windGust >= 45 || $windSpeed >= 35) {
    $active_message = [
        'icon' => '🎩💨',
        'badge' => 'DAVIS VP2 ALERT',
        'badge_bg' => 'rgba(245, 158, 11, 0.2)',
        'badge_color' => '#f59e0b',
        'title' => "Aguanta't el barret!",
        'subtitle' => "HOLD ON TO YOUR HAT! (Ràfega " . number_format($windGust, 0) . " km/h)",
        'accent' => '#fbbf24'
    ];
}
// 3. Temps ideal per volar estels (Vent sostingut 15-26 km/h i sense pluja)
elseif ($windSpeed >= 15 && $windSpeed <= 26 && $rainRate == 0 && $rainToday == 0) {
    $active_message = [
        'icon' => '🪁',
        'badge' => 'DAVIS TICKER',
        'badge_bg' => 'rgba(16, 185, 129, 0.2)',
        'badge_color' => '#10b981',
        'title' => "Temps per volar estels",
        'subtitle' => "GOOD KITE FLYING WEATHER (" . number_format($windSpeed, 0) . " km/h sostingut)",
        'accent' => '#34d399'
    ];
}
// 4. Risc de pluja engelant (Temp <= 0.5°C i pluja activa)
elseif ($temp <= 0.5 && ($rainRate > 0 || $rainToday > 0)) {
    $active_message = [
        'icon' => '❄️⚠️',
        'badge' => 'DAVIS VP2 ALERT',
        'badge_bg' => 'rgba(168, 85, 247, 0.2)',
        'badge_color' => '#a855f7',
        'title' => "Risc de pluja engelant",
        'subtitle' => "FREEZING RAIN POSSIBLE (" . number_format($temp, 1) . "°C)",
        'accent' => '#c084fc'
    ];
}
// 5. Alerta de calor extrema (Índex de calor >= 40°C)
elseif ($heatIndex >= 40) {
    $active_message = [
        'icon' => '🔥⚠️',
        'badge' => 'HEAT ADVISORY',
        'badge_bg' => 'rgba(239, 68, 68, 0.2)',
        'badge_color' => '#ef4444',
        'title' => "Alerta calor extrema",
        'subtitle' => "DANGER! EXTREME HEAT INDEX (" . number_format($heatIndex, 1) . "°C)",
        'accent' => '#f87171'
    ];
}
// 6. Índex UV molt alt (UV >= 8)
elseif ($uv >= 8) {
    $active_message = [
        'icon' => '☀️🧴',
        'badge' => 'UV ADVISORY',
        'badge_bg' => 'rgba(245, 158, 11, 0.2)',
        'badge_color' => '#f59e0b',
        'title' => "Índex UV molt alt",
        'subtitle' => "HIGH UV INDEX (UV " . number_format($uv, 1) . ") - WEAR SUN PROTECTION",
        'accent' => '#fbbf24'
    ];
}
// 7. Estat normal: Pronòstic baromètric de la consola Davis
else {
    $active_message = [
        'icon' => '📡',
        'badge' => 'DAVIS TICKER',
        'badge_bg' => 'rgba(56, 189, 248, 0.15)',
        'badge_color' => '#38bdf8',
        'title' => 'Consola Davis VP2',
        'subtitle' => $davisForecast,
        'accent' => '#38bdf8'
    ];
}
?>
<style>
.davis-ticker-marquee {
    display: inline-block;
    white-space: nowrap;
    animation: davisTickerScroll 18s linear infinite;
    padding-left: 100%;
}
.davis-ticker-box:hover .davis-ticker-marquee {
    animation-play-state: paused;
}
@keyframes davisTickerScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}
</style>
<div class="PWS_module_title" style="padding-top: 2px; display: flex; justify-content: space-between; align-items: center;">
    <span>Consola Davis &bull; Ticker</span>
    <span style="font-size: 9px; font-weight: 800; color: <?php echo $active_message['badge_color']; ?>; background: <?php echo $active_message['badge_bg']; ?>; padding: 1px 5px; border-radius: 3px; border: 1px solid <?php echo $active_message['badge_color']; ?>40; letter-spacing: 0.3px;">
        <?php echo $active_message['badge']; ?>
    </span>
</div>
<div class="davis-ticker-box" style="padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; height: 80px; box-sizing: border-box;" title="<?php echo htmlspecialchars($active_message['title'] . ': ' . $active_message['subtitle']); ?>">
    <!-- Icona d'estil LCD de la consola -->
    <div style="width: 50px; height: 52px; background: rgba(15, 23, 42, 0.85); border: 1px solid <?php echo $active_message['accent']; ?>60; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; box-shadow: inset 0 1px 4px rgba(0,0,0,0.5), 0 0 8px <?php echo $active_message['accent']; ?>25;">
        <span><?php echo $active_message['icon']; ?></span>
    </div>

    <!-- Tira de text estil marquesina LCD Davis -->
    <div style="font-size: 11px; line-height: 1.25; text-align: left; margin-left: 8px; overflow: hidden; flex: 1; display: flex; flex-direction: column; justify-content: center;">
        <div style="font-weight: 800; color: #f8fafc; font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px;">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?php echo $active_message['accent']; ?>; box-shadow: 0 0 5px <?php echo $active_message['accent']; ?>;"></span>
            <?php echo $active_message['title']; ?>
        </div>
        <div style="background: rgba(0, 0, 0, 0.45); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 4px; padding: 3px 6px; margin-top: 3px; overflow: hidden; white-space: nowrap; position: relative;">
            <span class="davis-ticker-marquee" style="font-size: 10px; color: <?php echo $active_message['accent']; ?>; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, monospace;">
                <?php echo $active_message['subtitle']; ?> &bull; Davis Vantage Pro2 Plus Sallent
            </span>
        </div>
        <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">
            Font: Dades consola en directe
        </div>
    </div>
</div>
