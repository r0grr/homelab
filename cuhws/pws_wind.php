<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$wind_speed = isset($weather["wind_speed"]) ? floatval($weather["wind_speed"]) : 3.0;
$wind_gust = isset($weather["wind_gust_speed"]) ? floatval($weather["wind_gust_speed"]) : 5.0;
$wind_avg = isset($weather["wind_speed_avg"]) ? floatval($weather["wind_speed_avg"]) : 3.0;
$wind_dir = isset($weather["wind_direction"]) ? intval($weather["wind_direction"]) : 174;
$wind_dir_avg = isset($weather["wind_direction_avg"]) ? intval($weather["wind_direction_avg"]) : 165;
$wind_max_gust = isset($weather["wind_gust_speed_max"]) ? floatval($weather["wind_gust_speed_max"]) : 39.0;
$wind_run = isset($weather["wind_run"]) ? floatval($weather["wind_run"]) : 91.1;

// Beaufort calculation
$bft = 0;
$bft_text = "Calma";
if ($wind_speed < 1) { $bft = 0; $bft_text = "Calma"; }
elseif ($wind_speed <= 5) { $bft = 1; $bft_text = "Ventolina"; }
elseif ($wind_speed <= 11) { $bft = 2; $bft_text = "Vent fluixet"; }
elseif ($wind_speed <= 19) { $bft = 3; $bft_text = "Vent fluix"; }
elseif ($wind_speed <= 28) { $bft = 4; $bft_text = "Vent moderat"; }
elseif ($wind_speed <= 38) { $bft = 5; $bft_text = "Vent fresquet"; }
elseif ($wind_speed <= 49) { $bft = 6; $bft_text = "Vent fresc"; }
elseif ($wind_speed <= 61) { $bft = 7; $bft_text = "Vent fort"; }
else { $bft = 8; $bft_text = "Molt fort"; }

function degToCompass($deg) {
    $dirs = array("N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW");
    $val = round(($deg % 360) / 22.5);
    return $dirs[$val % 16];
}
$dir_txt = degToCompass($wind_dir);
$dir_avg_txt = degToCompass($wind_dir_avg);
?>
<div class="PWS_module_title">
    <span>Vent | Ràfega - km/h</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #9aba2f;">Vent (Mitjà)<br><b><?php echo number_format($wind_avg, 1); ?> km/h</b></div>
        <div class="PWS_div_left" style="border-right-color: #ecb454;"><?php echo $bft; ?> Bft<br><b><?php echo $bft_text; ?></b></div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Direcció (Mitj)<br><b><?php echo $dir_avg_txt; ?> <?php echo $wind_dir_avg; ?>&deg;</b></div>
    </div>

    <!-- Middle Compass dial -->
    <div class="PWS_middle">
        <div style="position: relative; width: 130px; height: 130px; margin: 0 auto;">
            <svg width="130" height="130" viewBox="0 0 130 130" xmlns="http://www.w3.org/2000/svg">
                <circle r="63" cx="65" cy="65" fill="none" stroke="#4a5568" stroke-width="3" />
                <circle style="fill: #22262c;" cx="65" cy="65" r="60" />
                
                <text x="63" y="16" fill="#fff" font-size="8" font-weight="bold">N</text>
                <text x="35" y="20" fill="#a0aec0" font-size="5">NNW</text>
                <text x="80" y="20" fill="#a0aec0" font-size="5">NNE</text>
                <text x="22" y="31" fill="#fff" font-size="7">NW</text>
                <text x="98" y="31" fill="#fff" font-size="7">NE</text>
                <text x="8"  y="67" fill="#fff" font-size="8" font-weight="bold">W</text>
                <text x="114" y="67" fill="#fff" font-size="8" font-weight="bold">E</text>
                <text x="22" y="105" fill="#fff" font-size="7">SW</text>
                <text x="99" y="105" fill="#fff" font-size="7">SE</text>
                <text x="63" y="121" fill="#fff" font-size="8" font-weight="bold">S</text>

                <?php for ($a = 0; $a < 180; $a += 22.5): ?>
                    <line stroke="rgba(255,255,255,0.15)" x1="19" y1="65" x2="111" y2="65" transform="rotate(<?php echo $a; ?> 65 65)" />
                <?php endfor; ?>

                <circle fill="#3a4048" cx="65" cy="65" r="38" />

                <!-- Needles -->
                <!-- Current wind: green -->
                <polygon points="114 65 128 58 128 72" style="fill: #9aba2f;" transform="rotate(<?php echo ($wind_dir - 90); ?> 65 65)" />
                <!-- Avg wind: red -->
                <polygon points="116 65 126 61 126 69" style="fill: #d65b4a;" transform="rotate(<?php echo ($wind_dir_avg - 90); ?> 65 65)" />
            </svg>
            <div style="position: absolute; top: 44px; left: 0; width: 130px; text-align: center; color: #fff;">
                <span style="font-size: 17px; font-weight: 800;"><?php echo round($wind_speed); ?></span>
                <span style="color: #a0aec0;">|</span>
                <span style="font-size: 17px; font-weight: 800; color: #ff8841;"><?php echo round($wind_gust); ?></span><br>
                <span style="font-size: 11px; color: #cbd5e0; font-weight: 600;"><?php echo $wind_dir; ?>&deg; <b><?php echo $dir_txt; ?></b></span>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #ff8841;">Ràfega (Màx)<br><b><?php echo number_format($wind_max_gust, 1); ?> km/h</b></div>
        <div class="PWS_div_right" style="border-left-color: #01a4b4;">Recorregut<br><b><?php echo number_format($wind_run, 1); ?> km</b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="chartswu/todaywindspeedgust.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Gràfiques</a>
    <span style="color: #4a5568;"> | </span>
    <a href="windy-wind.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Previsió</a>
</div>
