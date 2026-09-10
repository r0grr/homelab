<?php
include_once('livedata.php');
include_once('common.php');

if (!function_exists('get_temp_badge')) {
    function get_temp_badge($temp) {
        if ($temp >= 35) return 'badge-red';
        if ($temp >= 30) return 'badge-orange';
        if ($temp >= 24) return 'badge-yellow';
        if ($temp >= 15) return 'badge-green';
        if ($temp >= 5)  return 'badge-mint';
        if ($temp >= 0)  return 'badge-purple';
        return 'badge-pink';
    }
}

$noms_mesos = [
    1 => 'Gener', 2 => 'Febrer', 3 => 'Març', 4 => 'Abril',
    5 => 'Maig', 6 => 'Juny', 7 => 'Juliol', 8 => 'Agost',
    9 => 'Setembre', 10 => 'Octubre', 11 => 'Novembre', 12 => 'Desembre'
];
$mes_actual = $noms_mesos[intval(date('n'))];
$any_actual = date('Y');

// Dades d'avui des de livedata / realtime.txt
$max_today = isset($weather["temp_today_high"]) ? floatval($weather["temp_today_high"]) : 23.8;
$min_today = isset($weather["temp_today_low"]) ? floatval($weather["temp_today_low"]) : 15.9;
$max_time  = !empty($weather["maxtemptime"]) ? $weather["maxtemptime"] : "00:00";
$min_time  = !empty($weather["lowtemptime"]) ? $weather["lowtemptime"] : "08:04";

// Dades del mes des de Cumulus MX (month.ini)
$month_ini_file = __DIR__ . '/cumulusmxdata/month.ini';
$month_ini = file_exists($month_ini_file) ? @parse_ini_file($month_ini_file, true) : [];

$max_month = isset($month_ini['Temp']['High']) ? floatval($month_ini['Temp']['High']) : $max_today;
$min_month = isset($month_ini['Temp']['Low']) ? floatval($month_ini['Temp']['Low']) : $min_today;

$time_max_month_raw = !empty($month_ini['Temp']['HTime']) ? strtotime($month_ini['Temp']['HTime']) : time();
$time_min_month_raw = !empty($month_ini['Temp']['LTime']) ? strtotime($month_ini['Temp']['LTime']) : time();

$day_max_month = date('j', $time_max_month_raw);
$day_min_month = date('j', $time_min_month_raw);
$title_max_month = date('j', $time_max_month_raw) . ' ' . $mes_actual . ' (' . date('H:i', $time_max_month_raw) . ')';
$title_min_month = date('j', $time_min_month_raw) . ' ' . $mes_actual . ' (' . date('H:i', $time_min_month_raw) . ')';

// Si avui se supera la màxima o baixa de la mínima del mes, es reflecteix a l'instant
if ($max_today > $max_month) {
    $max_month = $max_today;
    $day_max_month = date('j');
    $title_max_month = "Avui a les $max_time";
}
if ($min_today < $min_month) {
    $min_month = $min_today;
    $day_min_month = date('j');
    $title_min_month = "Avui a les $min_time";
}

$mesos_curts = [
    1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Des'
];

$format_data_curta = function($timestamp) use ($mesos_curts) {
    if (!$timestamp) return '';
    $m = intval(date('n', $timestamp));
    return date('j', $timestamp) . ' ' . ($mesos_curts[$m] ?? date('M', $timestamp));
};

// Rècords de l'any 2026 des de year.ini
$year_ini_file = __DIR__ . '/cumulusmxdata/year.ini';
$year_ini = file_exists($year_ini_file) ? @parse_ini_file($year_ini_file, true) : [];

if (isset($year_ini['Temp']['High']) && is_numeric($year_ini['Temp']['High'])) {
    $max_year = floatval($year_ini['Temp']['High']);
    $ts_high = !empty($year_ini['Temp']['HTime']) ? strtotime($year_ini['Temp']['HTime']) : null;
    $max_year_date = $ts_high ? $format_data_curta($ts_high) : '6 Set';
} else {
    $max_year = 39.2;
    $max_year_date = '6 Set';
}

if (isset($year_ini['Temp']['Low']) && is_numeric($year_ini['Temp']['Low'])) {
    $min_year = floatval($year_ini['Temp']['Low']);
    $ts_low = !empty($year_ini['Temp']['LTime']) ? strtotime($year_ini['Temp']['LTime']) : null;
    $min_year_date = $ts_low ? $format_data_curta($ts_low) : '7 Gen';
} else {
    $min_year = -6.2;
    $min_year_date = '7 Gen';
}

if ($max_month > $max_year) {
    $max_year = $max_month;
    $max_year_date = $day_max_month . ' ' . ($mesos_curts[intval(date('n'))] ?? 'Set');
}
if ($min_month < $min_year) {
    $min_year = $min_month;
    $min_year_date = $day_min_month . ' ' . ($mesos_curts[intval(date('n'))] ?? 'Set');
}
?>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Temp. Màx - Mín &deg;C</span>
</div>
<div style="padding: 4px 8px; box-sizing: border-box;">
<table style="width: 100%; height: 80px; font-size: 12px; border-collapse: collapse; text-align: center;">
    <thead>
        <tr style="color: #94a3b8; font-size: 11px; border-bottom: 1px solid rgba(255,255,255,0.08); height: 18px;">
            <th style="font-weight: 600; width: 28%; text-align: left; padding-left: 4px; white-space: nowrap;">Període</th>
            <th style="font-weight: 600; width: 36%; white-space: nowrap;">Màxima</th>
            <th style="font-weight: 600; width: 36%; text-align: right; padding-right: 4px; white-space: nowrap;">Mínima</th>
        </tr>
    </thead>
    <tbody>
        <tr style="height: 21px;">
            <td style="text-align: left; padding-left: 4px; font-weight: 700; color: #cbd5e1; white-space: nowrap;">Avui</td>
            <td style="white-space: nowrap;"><span class="<?php echo get_temp_badge($max_today); ?>" style="padding: 2px 5px; border-radius: 4px; font-weight: 600;"><?php echo number_format($max_today, 1); ?>&deg;</span> <small style="font-size: 10.5px; color: #a0aec0;"><?php echo $max_time; ?></small></td>
            <td style="text-align: right; padding-right: 4px; white-space: nowrap;"><span class="<?php echo get_temp_badge($min_today); ?>" style="padding: 2px 5px; border-radius: 4px; font-weight: 600;"><?php echo number_format($min_today, 1); ?>&deg;</span> <small style="font-size: 10.5px; color: #a0aec0;"><?php echo $min_time; ?></small></td>
        </tr>
        <tr style="height: 21px;">
            <td style="text-align: left; padding-left: 4px; font-weight: 700; color: #cbd5e1; white-space: nowrap;"><?php echo $mes_actual; ?></td>
            <td style="white-space: nowrap;"><span class="<?php echo get_temp_badge($max_month); ?>" style="padding: 2px 5px; border-radius: 4px; font-weight: 600;" title="<?php echo $title_max_month; ?>"><?php echo number_format($max_month, 1); ?>&deg;</span> <small style="font-size: 10.5px; color: #a0aec0;"><?php echo $day_max_month; ?> <?php echo substr($mes_actual, 0, 3); ?></small></td>
            <td style="text-align: right; padding-right: 4px; white-space: nowrap;"><span class="<?php echo get_temp_badge($min_month); ?>" style="padding: 2px 5px; border-radius: 4px; font-weight: 600;" title="<?php echo $title_min_month; ?>"><?php echo number_format($min_month, 1); ?>&deg;</span> <small style="font-size: 10.5px; color: #a0aec0;"><?php echo $day_min_month; ?> <?php echo substr($mes_actual, 0, 3); ?></small></td>
        </tr>
        <tr style="height: 21px;">
            <td style="text-align: left; padding-left: 4px; font-weight: 700; color: #cbd5e1; white-space: nowrap;"><?php echo $any_actual; ?></td>
            <td style="white-space: nowrap;"><span class="<?php echo get_temp_badge($max_year); ?>" style="padding: 2px 5px; border-radius: 4px; font-weight: 600;"><?php echo number_format($max_year, 1); ?>&deg;</span> <small style="font-size: 10.5px; color: #a0aec0;"><?php echo $max_year_date; ?></small></td>
            <td style="text-align: right; padding-right: 4px; white-space: nowrap;"><span class="<?php echo get_temp_badge($min_year); ?>" style="padding: 2px 5px; border-radius: 4px; font-weight: 600;"><?php echo number_format($min_year, 1); ?>&deg;</span> <small style="font-size: 10.5px; color: #a0aec0;"><?php echo $min_year_date; ?></small></td>
        </tr>
    </tbody>
</table>
</div>
