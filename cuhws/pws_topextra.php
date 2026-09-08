<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

if (!function_exists('get_temp_badge')) {
    function get_temp_badge($temp) {
        if ($temp === null || $temp === '') return 'badge-slate';
        if ($temp >= 35) return 'badge-red';
        if ($temp >= 30) return 'badge-orange';
        if ($temp >= 24) return 'badge-yellow';
        if ($temp >= 15) return 'badge-green';
        if ($temp >= 5)  return 'badge-mint';
        if ($temp >= 0)  return 'badge-purple';
        return 'badge-pink';
    }
}

$ch2_temp = null;
$ch3_temp = null;
$ch2_hum  = null;
$last_time = date('H:i');

$ch2_min = null; $ch2_max = null;
$ch3_min = null; $ch3_max = null;

$log_dir = __DIR__ . '/cumulusmxdata';
$current_month = date('Ym');
$extra_log_file = "$log_dir/ExtraLog$current_month.txt";

if (file_exists($extra_log_file) && is_readable($extra_log_file)) {
    // 1. Llegir l'últim registre (últims 2KB del fitxer)
    $f = @fopen($extra_log_file, 'r');
    if ($f) {
        $fsize = filesize($extra_log_file);
        $read_len = min(4096, $fsize);
        fseek($f, -$read_len, SEEK_END);
        $text = fread($f, $read_len);
        fclose($f);

        $lines = array_filter(array_map('trim', explode("\n", $text)));
        if (!empty($lines)) {
            $last_line = end($lines);
            $cols = str_getcsv($last_line);
            if (!empty($cols[1])) {
                $last_time = $cols[1];
            }
            if (isset($cols[2]) && is_numeric($cols[2])) {
                $ch2_temp = floatval($cols[2]);
            }
            if (isset($cols[3]) && is_numeric($cols[3])) {
                $ch3_temp = floatval($cols[3]);
            }
            if (isset($cols[12]) && is_numeric($cols[12])) {
                $ch2_hum = intval($cols[12]);
            }
        }
    }

    // 2. Extreure màximes i mínimes d'avui (llegint només les línies de la data d'avui)
    $today_prefix = date('d/m/y');
    $handle = @fopen($extra_log_file, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, $today_prefix) === 0) {
                $c = str_getcsv($line);
                if (isset($c[2]) && is_numeric($c[2])) {
                    $v = floatval($c[2]);
                    if ($ch2_min === null || $v < $ch2_min) $ch2_min = $v;
                    if ($ch2_max === null || $v > $ch2_max) $ch2_max = $v;
                }
                if (isset($c[3]) && is_numeric($c[3])) {
                    $v = floatval($c[3]);
                    if ($ch3_min === null || $v < $ch3_min) $ch3_min = $v;
                    if ($ch3_max === null || $v > $ch3_max) $ch3_max = $v;
                }
            }
        }
        fclose($handle);
    }
}

// Fallback a la API de Cumulus MX si no s'ha pogut llegir ExtraLog
if ($ch2_temp === null || $ch3_temp === null) {
    $ctx = stream_context_create(['http' => ['timeout' => 1.0]]);
    $api_temp_json = @file_get_contents('http://cumulusmx:8998/api/graphdata/extratemp.json', false, $ctx);
    if ($api_temp_json) {
        $api_data = @json_decode($api_temp_json, true);
        if (isset($api_data['Sensor 2 (T/H Exterior)']) && !empty($api_data['Sensor 2 (T/H Exterior)'])) {
            $last_entry = end($api_data['Sensor 2 (T/H Exterior)']);
            $ch2_temp = floatval($last_entry[1]);
        }
        if (isset($api_data['Dipòsit Buffer SH30 (Calefacció)']) && !empty($api_data['Dipòsit Buffer SH30 (Calefacció)'])) {
            $last_entry = end($api_data['Dipòsit Buffer SH30 (Calefacció)']);
            $ch3_temp = floatval($last_entry[1]);
        }
    }
    if ($ch2_hum === null) {
        $api_hum_json = @file_get_contents('http://cumulusmx:8998/api/graphdata/extrahum.json', false, $ctx);
        if ($api_hum_json) {
            $api_hum_data = @json_decode($api_hum_json, true);
            if (isset($api_hum_data['Sensor 2 (Humitat Exterior)']) && !empty($api_hum_data['Sensor 2 (Humitat Exterior)'])) {
                $last_entry = end($api_hum_data['Sensor 2 (Humitat Exterior)']);
                $ch2_hum = intval($last_entry[1]);
            }
        }
    }
}

$title_ch2 = ($ch2_min !== null && $ch2_max !== null) ? "Canal 2 Exterior - Mín: " . number_format($ch2_min, 1) . "°C | Màx: " . number_format($ch2_max, 1) . "°C" : "Canal 2: T/H Exterior";
$title_ch3 = ($ch3_min !== null && $ch3_max !== null) ? "Canal 3 Buffer SH30 - Mín: " . number_format($ch3_min, 1) . "°C | Màx: " . number_format($ch3_max, 1) . "°C" : "Canal 3: Dipòsit Buffer SH30";
?>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Canals 2 i 3 &bull; Davis VP2</span>
    <span class="PWS_ol_time" style="font-size: 10px; opacity: 0.8;"><?php echo $last_time; ?></span>
</div>
<div style="padding: 4px 8px; box-sizing: border-box;">
<table style="width: 100%; height: 80px; font-size: 12px; border-collapse: collapse; text-align: center;">
    <thead>
        <tr style="color: #94a3b8; font-size: 11px; border-bottom: 1px solid rgba(255,255,255,0.08); height: 18px;">
            <th style="font-weight: 600; width: 44%; text-align: left; padding-left: 4px; white-space: nowrap;">Sensor</th>
            <th style="font-weight: 600; width: 30%; white-space: nowrap;">Temp</th>
            <th style="font-weight: 600; width: 26%; text-align: right; padding-right: 4px; white-space: nowrap;">Humitat</th>
        </tr>
    </thead>
    <tbody>
        <tr style="height: 30px; border-bottom: 1px solid rgba(255,255,255,0.04);" title="<?php echo htmlspecialchars($title_ch2); ?>">
            <td style="text-align: left; padding-left: 4px; line-height: 1.15;">
                <b style="color: #cbd5e1; font-size: 11.5px; display: block;">Canal 2</b>
                <span style="font-size: 10px; color: #94a3b8;">T/H Exterior</span>
            </td>
            <td style="white-space: nowrap;">
                <?php if ($ch2_temp !== null): ?>
                    <span class="<?php echo get_temp_badge($ch2_temp); ?>" style="padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 12.5px;">
                        <?php echo number_format($ch2_temp, 1); ?>&deg;
                    </span>
                <?php else: ?>
                    <span style="color: #64748b;">--</span>
                <?php endif; ?>
            </td>
            <td style="text-align: right; padding-right: 4px; white-space: nowrap;">
                <?php if ($ch2_hum !== null): ?>
                    <b style="color: #00d2d3; font-size: 12.5px;"><?php echo $ch2_hum; ?>%</b>
                <?php else: ?>
                    <span style="color: #64748b;">--</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr style="height: 30px;" title="<?php echo htmlspecialchars($title_ch3); ?>">
            <td style="text-align: left; padding-left: 4px; line-height: 1.15;">
                <b style="color: #cbd5e1; font-size: 11.5px; display: block;">Canal 3</b>
                <span style="font-size: 10px; color: #94a3b8;">Buffer SH30</span>
            </td>
            <td style="white-space: nowrap;">
                <?php if ($ch3_temp !== null): ?>
                    <span class="<?php echo get_temp_badge($ch3_temp); ?>" style="padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 12.5px;">
                        <?php echo number_format($ch3_temp, 1); ?>&deg;
                    </span>
                <?php else: ?>
                    <span style="color: #64748b;">--</span>
                <?php endif; ?>
            </td>
            <td style="text-align: right; padding-right: 4px; white-space: nowrap;">
                <span style="color: #64748b; font-size: 11px;">&mdash;</span>
            </td>
        </tr>
    </tbody>
</table>
</div>
