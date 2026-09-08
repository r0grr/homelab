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
?>
<div class="PWS_module_title">
    <span>Precipitació - mm</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">2026<br><b><?php echo number_format($rain_year, 1); ?> mm</b></div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Setembre<br><b><?php echo number_format($rain_month, 1); ?> mm</b></div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Ahir<br><b><?php echo number_format($rain_yesterday, 1); ?> mm</b></div>
    </div>

    <!-- Middle counter -->
    <div class="PWS_middle">
        <div style="margin-top: 18px;">
            <div style="width: 112px; height: 86px; margin: 0 auto; background: rgba(0, 0, 0, 0.4); border: 2px solid #01a4b4; border-radius: 6px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: inset 0 0 10px rgba(0,0,0,0.6);">
                <span style="font-size: 30px; font-weight: 800; color: #01a4b4; font-family: 'Courier New', monospace;"><?php echo number_format($rain_today, 1); ?></span>
                <span style="font-size: 12px; color: #a0aec0; text-transform: uppercase;">mm avui</span>
            </div>
            <div style="font-size: 12px; color: #cbd5e0; margin-top: 8px;">
                <?php 
                if ($rain_today > 0) {
                    if (!empty($max_rain_time)) {
                        echo '<span style="color:#01a4b4; font-weight: 600;">Hora màx: ' . $max_rain_time . ' h</span>';
                    } else {
                        echo '<span style="color:#01a4b4; font-weight: 600;">' . number_format($rain_today, 1) . ' mm acumulats</span>';
                    }
                } else {
                    echo '<span style="color:#a0aec0;">Sense pluja avui</span>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #718096;">Darrera Hora<br><b><?php echo number_format($rain_lasthour, 1); ?> mm</b></div>
        <div class="PWS_div_right" style="border-left-color: #718096;">Intensitat<br><b><?php echo number_format($rain_rate, 1); ?> mm/h</b></div>
        <div class="PWS_div_right" style="border-left-color: #718096;">Darrera Pluja<br><b><?php echo $last_rain_display; ?></b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="chartswu/todayrainfall.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Gràfiques</a>
    <span style="color: #4a5568;"> | </span>
    <a href="windy-radar.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Radar</a>
</div>
