<?php
include_once('livedata.php');
include_once('common.php');
include_once('metar34get.php');
date_default_timezone_set($TZ);

$sun_info = date_sun_info(time(), $lat, $lon);
$is_day = (time() >= $sun_info['sunrise'] && time() < $sun_info['sunset']);

// Icona METAR sincronitzada amb fallback
$icon_path = 'css/icons/' . (!empty($sky_icon) ? $sky_icon : ($is_day ? 'clear.svg' : 'nt_clear.svg'));
if (!file_exists(__DIR__ . '/' . $icon_path)) {
    $icon_path = $is_day ? 'pws_icons/clear_day.svg' : 'pws_icons/clear_night.svg';
}

$title = !empty($sky_title_cat) ? $sky_title_cat : ($is_day ? 'Cel Serè' : 'Nit Serena');
$subtitle = !empty($sky_desc_cat) ? $sky_desc_cat : 'Sense nuvolositat';
$coverage = !empty($sky_coverage_pct) ? $sky_coverage_pct : '0%';

// Visibilitat METAR
if (isset($metar34visibility)) {
    if ($metar34visibility >= 10000 || ($metar34clouds ?? '') === 'CAVOK') {
        $visibility_str = '> 10 km';
    } else {
        $visibility_str = number_format($metar34visibility / 1000, 1) . ' km';
    }
} else {
    $visibility_str = '10 km';
}

// Base de núvols en metres des de l'estació Davis de Sallent (livedata / Cumulus MX)
$cloudbase_m = isset($weather["cloudbase"]) && floatval($weather["cloudbase"]) > 0 
    ? round(floatval($weather["cloudbase"]) * 0.3048) 
    : 1220;
?>
<div class="PWS_module_title">
    <span>Estat del Cel</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div style="height: 180px; padding: 16px 14px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-around; align-items: center; text-align: center;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 16px;">
        <img src="<?php echo $icon_path; ?>" width="76" height="54" alt="<?php echo htmlspecialchars($title); ?>" style="vertical-align: middle; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));">
        <div style="text-align: left; line-height: 1.4;">
            <b style="color: #f7fafc; font-size: 16px; letter-spacing: 0.3px;"><?php echo $title; ?></b><br>
            <span style="font-size: 13px; color: #a0aec0;"><?php echo $subtitle; ?></span>
        </div>
    </div>
    <div style="width: 100%; display: flex; justify-content: space-around; background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 6px; padding: 10px 4px; margin-top: 8px;">
        <div style="text-align: center; flex: 1; border-right: 1px solid rgba(255,255,255,0.08);">
            <span style="font-size: 11.5px; color: #a0aec0;">Visibilitat</span><br>
            <b style="color: #9aba2f; font-size: 14px;"><?php echo $visibility_str; ?></b>
        </div>
        <div style="text-align: center; flex: 1; border-right: 1px solid rgba(255,255,255,0.08);">
            <span style="font-size: 11.5px; color: #a0aec0;">Base Núvols</span><br>
            <b style="color: #01a4b4; font-size: 14px;"><?php echo $cloudbase_m; ?> m</b>
        </div>
        <div style="text-align: center; flex: 1;">
            <span style="font-size: 11.5px; color: #a0aec0;">Cobertura</span><br>
            <b style="color: #ff8841; font-size: 14px;"><?php echo $coverage; ?></b>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="metarnearby.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Metar LEBL</a>
    <span style="color: #4a5568;"> | </span>
    <a href="windy-radar.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Radar</a>
</div>
