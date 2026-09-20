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

// Comprovació d'alertes meteorològiques actives en directe
$is_emergency = false;
$emergency_message = null;

if ($rainRate >= 25.4) {
    $is_emergency = true;
    $emergency_message = [
        'icon' => '🐱',
        'title' => 'Plou a bots i barrals!',
        'subtitle' => "IT'S RAINING CATS AND DOGS! (" . number_format($rainRate, 1) . " mm/h en directe)",
        'accent' => '#38bdf8'
    ];
} elseif ($windGust >= 45 || $windSpeed >= 35) {
    $is_emergency = true;
    $emergency_message = [
        'icon' => '🎩',
        'title' => "Aguanta't el barret!",
        'subtitle' => "HOLD ON TO YOUR HAT! (Ràfega " . number_format($windGust, 0) . " km/h en directe)",
        'accent' => '#fbbf24'
    ];
} elseif ($temp <= 0.5 && ($rainRate > 0 || $rainToday > 0)) {
    $is_emergency = true;
    $emergency_message = [
        'icon' => '❄️',
        'title' => "Risc de pluja engelant",
        'subtitle' => "FREEZING RAIN POSSIBLE (" . number_format($temp, 1) . "°C en directe)",
        'accent' => '#c084fc'
    ];
} elseif ($heatIndex >= 40) {
    $is_emergency = true;
    $emergency_message = [
        'icon' => '🔥',
        'title' => "Alerta calor extrema",
        'subtitle' => "DANGER! EXTREME HEAT (" . number_format($heatIndex, 1) . "°C)",
        'accent' => '#f87171'
    ];
}

// Llista de missatges i Easter Eggs de la consola Davis (cadascun amb EXACTAMENT 1 emoji)
$all_messages = [];

if ($is_emergency && $emergency_message) {
    $all_messages[] = $emergency_message;
} else {
    // 1. Pronòstic baromètric oficial
    $all_messages[] = [
        'icon' => '📡',
        'title' => "Pronòstic de l'estació",
        'subtitle' => $davisForecast,
        'accent' => '#38bdf8'
    ];
    // 2. Easter Egg: It's raining cats and dogs
    $all_messages[] = [
        'icon' => '🐱',
        'title' => "Missatge Davis • Cats & Dogs",
        'subtitle' => "It's raining cats and dogs: plou a bots i barrals si supera 25.4 mm/h (actual: " . number_format($rainRate, 1) . " mm/h)",
        'accent' => '#38bdf8'
    ];
    // 3. Easter Egg: Hold on to your hat
    $all_messages[] = [
        'icon' => '🎩',
        'title' => "Missatge Davis • Hold on to your hat",
        'subtitle' => "Hold on to your hat!: aguanta't el barret si el vent supera 45 km/h (ràfega màx avui: " . number_format($windGust, 0) . " km/h)",
        'accent' => '#fbbf24'
    ];
    // 4. Easter Egg: Good kite flying weather
    $all_messages[] = [
        'icon' => '🪁',
        'title' => "Missatge Davis • Kite flying",
        'subtitle' => "Good kite flying weather: condicions per volar estels amb vent sostingut 15-26 km/h (actual: " . number_format($windSpeed, 0) . " km/h)",
        'accent' => '#34d399'
    ];
    // 5. Easter Egg: Freezing rain warning
    $all_messages[] = [
        'icon' => '❄️',
        'title' => "Missatge Davis • Freezing rain",
        'subtitle' => "Freezing rain warning: avís de pluja engelant si la temp <= 0.5°C (temp actual: " . number_format($temp, 1) . "°C)",
        'accent' => '#c084fc'
    ];
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
    animation: davisTickerMarquee 15s linear infinite;
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
<div class="davis-ticker-box" style="padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; height: 80px; box-sizing: border-box;" title="<?php echo htmlspecialchars($first['title'] . ': ' . $first['subtitle']); ?>">
    <!-- Icona amb EXACTAMENT 1 emoji per estat -->
    <div id="davis_top_icon_box" style="width: 48px; height: 50px; background: rgba(15, 23, 42, 0.85); border: 1px solid <?php echo $first['accent']; ?>60; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; box-shadow: inset 0 1px 4px rgba(0,0,0,0.5), 0 0 8px <?php echo $first['accent']; ?>25; transition: all 0.4s ease;">
        <span id="davis_top_icon"><?php echo $first['icon']; ?></span>
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
    var messages = <?php echo json_encode($all_messages); ?>;
    if (!messages || messages.length <= 1) return;
    
    var isEmergency = <?php echo $is_emergency ? 'true' : 'false'; ?>;
    if (isEmergency) return;
    
    // Manté l'índex entre recàrregues AJAX d'updater.php
    var idx = (window._davisTickerIdx !== undefined) ? window._davisTickerIdx : 0;
    var iconEl = document.getElementById('davis_top_icon');
    var titleEl = document.getElementById('davis_top_title');
    var dotEl = document.getElementById('davis_top_dot');
    var trackEl = document.getElementById('davis_top_track');
    var boxEl = document.getElementById('davis_top_icon_box');
    var lastAdvance = Date.now();
    
    function setItem(i) {
        var msg = messages[i];
        if (!msg) return;
        if (iconEl) iconEl.textContent = msg.icon;
        if (titleEl) titleEl.textContent = msg.title;
        if (dotEl) {
            dotEl.style.background = msg.accent;
            dotEl.style.boxShadow = '0 0 5px ' + msg.accent;
        }
        if (boxEl) {
            boxEl.style.borderColor = msg.accent + '60';
            boxEl.style.boxShadow = 'inset 0 1px 4px rgba(0,0,0,0.5), 0 0 8px ' + msg.accent + '25';
        }
        if (trackEl) {
            trackEl.style.color = msg.accent;
            trackEl.textContent = msg.subtitle;
        }
    }
    
    function advanceNext() {
        var now = Date.now();
        // Evita canvis massa ràpids: el missatge rota sencer per la tira (mínim 8 segons)
        if (now - lastAdvance < 8000) return;
        lastAdvance = now;
        idx = (idx + 1) % messages.length;
        window._davisTickerIdx = idx;
        setItem(idx);
    }
    
    // Si tenim un estat previ guardat, sincronitzem-lo
    if (idx > 0 && idx < messages.length) {
        setItem(idx);
    }
    
    // El missatge canvia de forma natural quan acaba de rodar completament per la tira
    if (trackEl) {
        ['animationiteration', 'webkitAnimationIteration'].forEach(function(evt) {
            trackEl.addEventListener(evt, advanceNext);
        });
    }
    
    // Fallback de seguretat per si l'animació CSS no suporta l'esdeveniment (15.5s)
    setInterval(function() {
        if (Date.now() - lastAdvance >= 15500) {
            advanceNext();
        }
    }, 4000);
})();
</script>
