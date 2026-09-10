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

$max_wind_today = isset($weather["wind_speed_max"]) ? intval($weather["wind_speed_max"]) : 0;
$max_gust_today = isset($weather["wind_gust_speed_max"]) ? intval($weather["wind_gust_speed_max"]) : 0;
$max_wind_time = !empty($weather["maxwindtime"]) ? $weather["maxwindtime"] : "--:--";
$max_gust_time = !empty($weather["maxgusttime"]) ? $weather["maxgusttime"] : "--:--";

$month_ini_file = __DIR__ . '/cumulusmxdata/month.ini';
$month_ini = file_exists($month_ini_file) ? @parse_ini_file($month_ini_file, true) : [];

$max_wind_month = isset($month_ini['Wind']['Speed']) ? round(floatval($month_ini['Wind']['Speed'])) : $max_wind_today;
$max_gust_month = isset($month_ini['Wind']['Gust']) ? round(floatval($month_ini['Wind']['Gust'])) : $max_gust_today;

$day_wind_month = !empty($month_ini['Wind']['SpTime']) ? date('j', strtotime($month_ini['Wind']['SpTime'])) : date('j');
$day_gust_month = !empty($month_ini['Wind']['Time']) ? date('j', strtotime($month_ini['Wind']['Time'])) : date('j');

if ($max_wind_today > $max_wind_month) {
    $max_wind_month = $max_wind_today;
    $day_wind_month = date('j');
}
if ($max_gust_today > $max_gust_month) {
    $max_gust_month = $max_gust_today;
    $day_gust_month = date('j');
}

$max_wind_year = 54;
$max_gust_year = 72;
?>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Vent i Ràfegues - km/h</span>
</div>
<div style="padding: 4px 6px; box-sizing: border-box;">
<table style="width: 100%; height: 80px; font-size: 12px; border-collapse: collapse; text-align: center; table-layout: fixed;">
    <thead>
        <tr style="color: #94a3b8; font-size: 11px; border-bottom: 1px solid rgba(255,255,255,0.08); height: 18px;">
            <th style="font-weight: 600; width: 56px; text-align: left; padding-left: 2px; white-space: nowrap;">Període</th>
            <th style="font-weight: 600; text-align: center; white-space: nowrap;">Vent Mitjà</th>
            <th style="font-weight: 600; text-align: center; white-space: nowrap;">Ràfega</th>
        </tr>
    </thead>
    <tbody>
        <tr style="height: 21px;">
            <td style="text-align: left; padding-left: 2px; font-weight: 700; color: #cbd5e1; white-space: nowrap;">Avui</td>
            <td style="white-space: nowrap;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span class="badge-slate" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 19px; border-radius: 4px; font-weight: 600; font-size: 11.5px; font-variant-numeric: tabular-nums; box-sizing: border-box; flex-shrink: 0;"><?php echo $max_wind_today; ?></span>
                    <small style="width: 36px; text-align: left; font-size: 10px; color: #a0aec0; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;"><?php echo $max_wind_time; ?></small>
                </div>
            </td>
            <td style="white-space: nowrap;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span class="badge-slate" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 19px; border-radius: 4px; font-weight: 600; font-size: 11.5px; font-variant-numeric: tabular-nums; box-sizing: border-box; flex-shrink: 0;"><?php echo $max_gust_today; ?></span>
                    <small style="width: 36px; text-align: left; font-size: 10px; color: #a0aec0; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;"><?php echo $max_gust_time; ?></small>
                </div>
            </td>
        </tr>
        <tr style="height: 21px;">
            <td style="text-align: left; padding-left: 2px; font-weight: 700; color: #cbd5e1; white-space: nowrap;"><?php echo $mes_actual; ?></td>
            <td style="white-space: nowrap;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span class="badge-slate" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 19px; border-radius: 4px; font-weight: 600; font-size: 11.5px; font-variant-numeric: tabular-nums; box-sizing: border-box; flex-shrink: 0;"><?php echo $max_wind_month; ?></span>
                    <small style="width: 36px; text-align: left; font-size: 10px; color: #a0aec0; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;"><?php echo $day_wind_month; ?> <?php echo substr($mes_actual, 0, 3); ?></small>
                </div>
            </td>
            <td style="white-space: nowrap;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span class="badge-slate" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 19px; border-radius: 4px; font-weight: 600; font-size: 11.5px; font-variant-numeric: tabular-nums; box-sizing: border-box; flex-shrink: 0;"><?php echo $max_gust_month; ?></span>
                    <small style="width: 36px; text-align: left; font-size: 10px; color: #a0aec0; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;"><?php echo $day_gust_month; ?> <?php echo substr($mes_actual, 0, 3); ?></small>
                </div>
            </td>
        </tr>
        <tr style="height: 21px;">
            <td style="text-align: left; padding-left: 2px; font-weight: 700; color: #cbd5e1; white-space: nowrap;"><?php echo $any_actual; ?></td>
            <td style="white-space: nowrap;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span class="badge-orange" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 19px; border-radius: 4px; font-weight: 600; font-size: 11.5px; font-variant-numeric: tabular-nums; box-sizing: border-box; flex-shrink: 0;"><?php echo $max_wind_year; ?></span>
                    <small style="width: 36px; text-align: left; font-size: 10px; color: #a0aec0; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;">12 Feb</small>
                </div>
            </td>
            <td style="white-space: nowrap;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <span class="badge-red" style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 19px; border-radius: 4px; font-weight: 600; font-size: 11.5px; font-variant-numeric: tabular-nums; box-sizing: border-box; flex-shrink: 0;"><?php echo $max_gust_year; ?></span>
                    <small style="width: 36px; text-align: left; font-size: 10px; color: #a0aec0; font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;">24 Mar</small>
                </div>
            </td>
        </tr>
    </tbody>
</table>
</div>
