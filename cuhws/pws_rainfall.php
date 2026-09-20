<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$rain_today = isset($weather["rain_today"]) ? floatval($weather["rain_today"]) : 0.0;
$rain_year = isset($weather["rain_year"]) ? floatval($weather["rain_year"]) : 315.2;
$rain_month = isset($weather["rain_month"]) ? floatval($weather["rain_month"]) : 0.0;
$rain_yesterday = isset($weather["rainydmax"]) ? floatval($weather["rainydmax"]) : 0.0;
$rain_rate = isset($weather["rain_rate"]) ? floatval($weather["rain_rate"]) : 0.0;
$rain_lasthour = isset($weather["rain_lasthour"]) ? floatval($weather["rain_lasthour"]) : 0.0;

// Determinació dinàmica de la data de la darrera pluja
$last_rain_display = '--';
if (!empty($lastraindate)) {
    $last_rain_display = $lastraindate;
} else {
    $rg_file = __DIR__ . '/cumulusdata/realtimegauges.txt';
    if (file_exists($rg_file)) {
        $rg_json = @json_decode(file_get_contents($rg_file), true);
        if (!empty($rg_json['LastRainTipISO']) && $rg_json['LastRainTipISO'] !== '0000-00-00 00:00' && $rg_json['LastRainTipISO'] !== '-----') {
            $ts = strtotime($rg_json['LastRainTipISO']);
            if ($ts > 0) {
                $last_rain_display = date('d-m-Y', $ts);
            }
        }
    }
}
// Determinació dinàmica de l'hora de màxima intensitat o darrera precipitació
$max_rain_time = '';
$rg_file = __DIR__ . '/cumulusdata/realtimegauges.txt';
if (file_exists($rg_file)) {
    $rg_json = @json_decode(file_get_contents($rg_file), true);
    if (!empty($rg_json['rrateTM']) && floatval($rg_json['rrateTM']) > 0 && !empty($rg_json['TrrateTM']) && $rg_json['TrrateTM'] !== '00:00') {
        $max_rain_time = $rg_json['TrrateTM'];
    } elseif (!empty($rg_json['hourlyrainTH']) && floatval($rg_json['hourlyrainTH']) > 0 && !empty($rg_json['ThourlyrainTH']) && $rg_json['ThourlyrainTH'] !== '00:00') {
        $max_rain_time = $rg_json['ThourlyrainTH'];
    } elseif (!empty($rg_json['LastRainTipISO']) && strpos($rg_json['LastRainTipISO'], date('Y-m-d')) === 0) {
        $max_rain_time = date('H:i', strtotime($rg_json['LastRainTipISO']));
    }
}
if (empty($max_rain_time)) {
    $today_ini_file = __DIR__ . '/cumulusmxdata/today.ini';
    if (file_exists($today_ini_file)) {
        $today_ini = @parse_ini_file($today_ini_file, true);
        if (!empty($today_ini['Rain']['HHourlyTime'])) {
            $max_rain_time = date('H:i', strtotime($today_ini['Rain']['HHourlyTime']));
        } elseif (!empty($today_ini['Rain']['LastTip'])) {
            $max_rain_time = date('H:i', strtotime($today_ini['Rain']['LastTip']));
        }
    }
}
// Càlcul de nivell de precipitació (0 a 5 rectangles blaus segons plogui més o menys)
$rain_level = 0;
if ($rain_today > 0 || $rain_rate > 0) {
    $rain_level = 1;
    if ($rain_today >= 2.0 || $rain_rate >= 2.0) {
        $rain_level = 2;
    }
    if ($rain_today >= 10.0 || $rain_rate >= 10.0) {
        $rain_level = 3;
    }
    if ($rain_today >= 25.0 || $rain_rate >= 25.0) {
        $rain_level = 4;
    }
    if ($rain_today >= 50.0 || $rain_rate >= 50.0) {
        $rain_level = 5;
    }
}

// Estils dels 5 rectangles blaus (de baix cap a dalt: 1 a 5)
$blue_fill = '#0284c7';
$blue_border = '#38bdf8';
$empty_fill = 'rgba(30, 41, 59, 0.45)';
$empty_border = 'rgba(255, 255, 255, 0.08)';

$bg1 = ($rain_level >= 1) ? $blue_fill : $empty_fill;
$bdr1 = ($rain_level >= 1) ? $blue_border : $empty_border;

$bg2 = ($rain_level >= 2) ? $blue_fill : $empty_fill;
$bdr2 = ($rain_level >= 2) ? $blue_border : $empty_border;

$bg3 = ($rain_level >= 3) ? $blue_fill : $empty_fill;
$bdr3 = ($rain_level >= 3) ? $blue_border : $empty_border;

$bg4 = ($rain_level >= 4) ? $blue_fill : $empty_fill;
$bdr4 = ($rain_level >= 4) ? $blue_border : $empty_border;

$bg5 = ($rain_level >= 5) ? $blue_fill : $empty_fill;
$bdr5 = ($rain_level >= 5) ? $blue_border : $empty_border;
?>
<div class="PWS_module_title">
    <span>Precipitació - mm</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('d/m H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">2026<br><b><?php echo number_format($rain_year, 1); ?> mm</b></div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Setembre<br><b><?php echo number_format($rain_month, 1); ?> mm</b></div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Ahir<br><b><?php echo number_format($rain_yesterday, 1); ?> mm</b></div>
    </div>

    <!-- Middle counter amb 5 rectangles blaus que s'omplen gradualment -->
    <div class="PWS_middle">
        <div style="position: relative; width: 118px; height: 116px; margin: 0 auto; display: flex; flex-direction: column; justify-content: space-between; padding: 4px; box-sizing: border-box; background: rgba(15, 23, 42, 0.55); border: 1.5px solid rgba(56, 189, 248, 0.35); border-radius: 8px;">
            <!-- 5 rectangles de baix cap a dalt (Rectangle 5 a dalt, Rectangle 1 a baix) -->
            <div title="Nivell 5: Precipitació torrencial (≥ 50 mm)" style="height: 18px; border-radius: 4px; background: <?php echo $bg5; ?>; border: 1px solid <?php echo $bdr5; ?>; box-sizing: border-box; transition: all 0.3s;"></div>
            <div title="Nivell 4: Precipitació abundant (≥ 25 mm)" style="height: 18px; border-radius: 4px; background: <?php echo $bg4; ?>; border: 1px solid <?php echo $bdr4; ?>; box-sizing: border-box; transition: all 0.3s;"></div>
            <div title="Nivell 3: Precipitació moderada (≥ 10 mm)" style="height: 18px; border-radius: 4px; background: <?php echo $bg3; ?>; border: 1px solid <?php echo $bdr3; ?>; box-sizing: border-box; transition: all 0.3s;"></div>
            <div title="Nivell 2: Precipitació feble (≥ 2 mm)" style="height: 18px; border-radius: 4px; background: <?php echo $bg2; ?>; border: 1px solid <?php echo $bdr2; ?>; box-sizing: border-box; transition: all 0.3s;"></div>
            <div title="Nivell 1: Precipitació molt feble (> 0 mm)" style="height: 18px; border-radius: 4px; background: <?php echo $bg1; ?>; border: 1px solid <?php echo $bdr1; ?>; box-sizing: border-box; transition: all 0.3s;"></div>

            <!-- Placa central amb el valor numèric de pluja -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90px; padding: 4px 0; background: rgba(15, 23, 42, 0.84); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); border: 1.5px solid rgba(56, 189, 248, 0.5); border-radius: 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.65); pointer-events: none;">
                <span style="font-size: 24px; font-weight: 800; color: #38bdf8; line-height: 1.05; font-family: -apple-system, BlinkMacSystemFont, Arial, sans-serif;">
                    <?php echo number_format($rain_today, 1); ?>
                </span>
                <span style="font-size: 10px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; margin-top: 1px; letter-spacing: 0.5px;">
                    mm avui
                </span>
            </div>
        </div>
        <div style="text-align: center; margin-top: 5px; font-size: 11.5px; font-weight: 700;">
            <?php 
            if ($rain_rate > 0) {
                echo '<span style="color:#38bdf8;">Plou: ' . number_format($rain_rate, 1) . ' mm/h</span>';
            } elseif ($rain_today > 0) {
                if (!empty($max_rain_time)) {
                    echo '<span style="color:#01a4b4;">Hora màx: ' . $max_rain_time . ' h</span>';
                } else {
                    echo '<span style="color:#01a4b4;">' . number_format($rain_today, 1) . ' mm acumulats</span>';
                }
            } else {
                echo '<span style="color:#a0aec0;">Sense pluja avui</span>';
            }
            ?>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #718096;">Darrera Hora<br><b><?php echo number_format($rain_lasthour, 1); ?> mm</b></div>
        <div class="PWS_div_right pws_has_time" style="border-left-color: #718096;" title="Intensitat màxima de pluja avui">
            Intensitat<br><b><?php echo number_format($rain_rate, 1); ?> mm/h</b>
            <span class="pws_val_time"><?php echo !empty($max_rain_time) ? $max_rain_time . ' h' : '--:--'; ?></span>
        </div>
        <div class="PWS_div_right" style="border-left-color: #718096;">Darrera Pluja<br><b><?php echo $last_rain_display; ?></b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="chartswu/todayrainfall.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Gràfiques</a>
    <span style="color: #4a5568;"> | </span>
    <a href="windy-radar.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Radar</a>
</div>
