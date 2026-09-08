<?php
include_once('livedata.php');
include_once('common.php');

$noms_mesos = [
    1 => 'Gener', 2 => 'Febrer', 3 => 'Març', 4 => 'Abril',
    5 => 'Maig', 6 => 'Juny', 7 => 'Juliol', 8 => 'Agost',
    9 => 'Setembre', 10 => 'Octubre', 11 => 'Novembre', 12 => 'Desembre'
];
$mes_actual = $noms_mesos[intval(date('n'))];
$any_actual = date('Y');

$rain_today = isset($weather["rain_today"]) ? floatval($weather["rain_today"]) : 0.0;
$rain_year = isset($weather["rain_year"]) ? floatval($weather["rain_year"]) : 315.6;
$rain_month = isset($weather["rain_month"]) ? floatval($weather["rain_month"]) : 0.4;
$rain_units = !empty($weather["rain_units"]) ? $weather["rain_units"] : "mm";

// Determinació dinàmica de l'hora de màxima intensitat / precipitació
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
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Precipitació Acumulada</span>
</div>
<div style="display: flex; justify-content: space-around; align-items: center; height: 82px; padding: 0 4px;">
    <div style="text-align: center; width: 33%;">
        <span style="font-size: 19px; font-weight: 700; color: #01a4b4;"><?php echo number_format($rain_today, 1); ?></span>
        <small style="font-size: 11px; color: #a0aec0;"><?php echo $rain_units; ?></small><br>
        <span style="font-size: 11.5px; color: #cbd5e0; font-weight: 600;">Avui</span>
        <?php if ($rain_today > 0 && !empty($max_rain_time)): ?>
            <small style="font-size: 10px; color: #38bdf8; display: block; line-height: 1.1; margin-top: 1px;">Màx: <?php echo $max_rain_time; ?>h</small>
        <?php else: ?>
            <small style="font-size: 10px; color: #64748b; display: block; line-height: 1.1; margin-top: 1px;">Sense pluja</small>
        <?php endif; ?>
    </div>
    <div style="height: 46px; width: 1px; background: rgba(255, 255, 255, 0.1);"></div>
    <div style="text-align: center; width: 33%;">
        <span style="font-size: 19px; font-weight: 700; color: #ff8841;"><?php echo number_format($rain_month, 1); ?></span>
        <small style="font-size: 11px; color: #a0aec0;"><?php echo $rain_units; ?></small><br>
        <span style="font-size: 11.5px; color: #cbd5e0; font-weight: 600;"><?php echo substr($mes_actual, 0, 3); ?></span>
        <small style="font-size: 10px; color: #64748b; display: block; line-height: 1.1; margin-top: 1px;">Mes</small>
    </div>
    <div style="height: 46px; width: 1px; background: rgba(255, 255, 255, 0.1);"></div>
    <div style="text-align: center; width: 33%;">
        <span style="font-size: 19px; font-weight: 700; color: #a855f7;"><?php echo number_format($rain_year, 1); ?></span>
        <small style="font-size: 11px; color: #a0aec0;"><?php echo $rain_units; ?></small><br>
        <span style="font-size: 11.5px; color: #cbd5e0; font-weight: 600;"><?php echo $any_actual; ?></span>
        <small style="font-size: 10px; color: #64748b; display: block; line-height: 1.1; margin-top: 1px;">Any</small>
    </div>
</div>
