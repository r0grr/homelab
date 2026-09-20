<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$hum = isset($weather["humidity"]) ? intval($weather["humidity"]) : 50;
$cur_temp = isset($weather["temp"]) ? floatval($weather["temp"]) : 20.0;
$dew = isset($weather["dewpoint"]) ? floatval($weather["dewpoint"]) : 12.0;
$wetbulb = isset($weather["wetbulb"]) ? floatval($weather["wetbulb"]) : round($cur_temp * atan(0.151977 * pow(max(0, $hum) + 8.313659, 0.5)) + atan($cur_temp + $hum) - atan($hum - 1.676331) + 0.00391838 * pow(max(0, $hum), 1.5) * atan(0.023101 * $hum) - 4.686035, 1);

// Dades de màximes i mínimes d'humitat i hores
$hum_max = null;
$hum_max_time = '--:--';
$hum_min = null;
$hum_min_time = '--:--';

$rg_file = __DIR__ . '/cumulusdata/realtimegauges.txt';
if (file_exists($rg_file)) {
    $rg_json = @json_decode(file_get_contents($rg_file), true);
    if (!empty($rg_json['humTH']) && is_numeric($rg_json['humTH'])) {
        $hum_max = intval($rg_json['humTH']);
        if (!empty($rg_json['ThumTH'])) $hum_max_time = $rg_json['ThumTH'];
    }
    if (!empty($rg_json['humTL']) && is_numeric($rg_json['humTL'])) {
        $hum_min = intval($rg_json['humTL']);
        if (!empty($rg_json['ThumTL'])) $hum_min_time = $rg_json['ThumTL'];
    }
}

if ($hum_max === null || $hum_min === null) {
    $today_ini_file = __DIR__ . '/cumulusmxdata/today.ini';
    if (file_exists($today_ini_file)) {
        $today_ini = @parse_ini_file($today_ini_file, true);
        if (!empty($today_ini['Humidity']['High'])) {
            $hum_max = intval($today_ini['Humidity']['High']);
            if (!empty($today_ini['Humidity']['HTime'])) {
                $hum_max_time = date('H:i', strtotime($today_ini['Humidity']['HTime']));
            }
        }
        if (!empty($today_ini['Humidity']['Low'])) {
            $hum_min = intval($today_ini['Humidity']['Low']);
            if (!empty($today_ini['Humidity']['LTime'])) {
                $hum_min_time = date('H:i', strtotime($today_ini['Humidity']['LTime']));
            }
        }
    }
}

if ($hum_max === null) $hum_max = $hum;
if ($hum_min === null) $hum_min = $hum;

// Nivell de confort d'humitat
$confort_text = "Confortable";
$confort_color = "#01a4b4";
if ($hum < 30) {
    $confort_text = "Ambient Molt Sec";
    $confort_color = "#f59e0b";
} elseif ($hum < 45) {
    $confort_text = "Ambient Sec";
    $confort_color = "#9aba2f";
} elseif ($hum <= 65) {
    $confort_text = "Confortable / Òptim";
    $confort_color = "#01a4b4";
} elseif ($hum <= 80) {
    $confort_text = "Humit";
    $confort_color = "#0284c7";
} else {
    $confort_text = "Molt Humit / Xafogós";
    $confort_color = "#3b82f6";
}

// Càlcul alçada del líquid dins la gota SVG (escala 0-100% sobre 107px d'alçada útil)
$clamped_hum = max(0, min(100, $hum));
$fill_y = 113 - ($clamped_hum / 100.0 * 107.0);
?>
<div class="PWS_module_title">
    <span>Humitat Relativa %</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('d/m H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values: 1. Màx Avui, 2. Punt de Rosada -->
    <div class="PWS_left">
        <div class="PWS_div_left PWS_div_temp pws_has_time" style="border-right-color: #0284c7;" title="Humitat màxima registrada avui">
            Màx Avui<br><b><?php echo $hum_max; ?>%</b>
            <span class="pws_val_time"><?php echo $hum_max_time; ?> h</span>
        </div>
        <div class="PWS_div_left PWS_div_temp" style="border-right-color: #48FB9E;" title="Punt de Rosada (Dew Point)">
            Punt Rosada<br><b><?php echo number_format($dew, 1); ?>&deg;C</b>
        </div>
    </div>

    <!-- Middle dynamic water droplet (Flat 2D, slightly narrower shape) -->
    <div class="PWS_middle">
        <div style="position: relative; width: 124px; height: 124px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <svg width="114" height="120" viewBox="0 0 114 120" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <clipPath id="pws_droplet_clip">
                        <path d="M 57 6 C 57 6, 12 42, 12 68 A 45 45 0 0 0 102 68 C 102 42, 57 6, 57 6 Z" />
                    </clipPath>
                </defs>

                <!-- Fons buit de la gota (pla 2D) -->
                <path d="M 57 6 C 57 6, 12 42, 12 68 A 45 45 0 0 0 102 68 C 102 42, 57 6, 57 6 Z" 
                      fill="rgba(30, 41, 59, 0.75)" />

                <!-- Aigua que s'omple dinàmicament (color pla 2D) -->
                <g clip-path="url(#pws_droplet_clip)">
                    <rect x="0" y="<?php echo sprintf('%.1f', $fill_y); ?>" width="114" height="120" fill="#0284c7" />
                </g>

                <!-- Contorn exterior fi i nítid de la gota -->
                <path d="M 57 6 C 57 6, 12 42, 12 68 A 45 45 0 0 0 102 68 C 102 42, 57 6, 57 6 Z" 
                      fill="none" stroke="rgba(56, 189, 248, 0.6)" stroke-width="2" />

                <!-- Text pla amb percentatge central -->
                <text x="57" y="74" text-anchor="middle" font-size="24" font-weight="700" fill="#ffffff" style="font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif;">
                    <?php echo $hum; ?><tspan font-size="15" font-weight="600">%</tspan>
                </text>
            </svg>
        </div>
        <div style="text-align: center; margin-top: 2px; font-size: 11.5px; font-weight: 700; color: <?php echo $confort_color; ?>;">
            <?php echo $confort_text; ?>
        </div>
    </div>

    <!-- Right values: 1. Mín Avui, 2. Bulb Humit -->
    <div class="PWS_right">
        <div class="PWS_div_right PWS_div_temp pws_has_time" style="border-left-color: #f59e0b;" title="Humitat mínima registrada avui">
            Mín Avui<br><b><?php echo $hum_min; ?>%</b>
            <span class="pws_val_time"><?php echo $hum_min_time; ?> h</span>
        </div>
        <div class="PWS_div_right PWS_div_temp" style="border-left-color: #00d2d3;" title="Temperatura de Bulb Humit">
            Bulb Humit<br><b><?php echo number_format($wetbulb, 1); ?>&deg;C</b>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="chartswu/humidity.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Gràfiques</a>
    <span style="color: #4a5568;"> | </span>
    <a href="chartswu/todaytemphum.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Temp + Hum</a>
</div>
