<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$sun_info = date_sun_info(time(), $lat, $lon);
$sunrise_ts = $sun_info['sunrise'];
$sunset_ts = $sun_info['sunset'];

$sunrise_str = date('H:i', $sunrise_ts);
$sunset_str = date('H:i', $sunset_ts);

$daylight_sec = max(0, $sunset_ts - $sunrise_ts);
$daylight_h = floor($daylight_sec / 3600);
$daylight_m = floor(($daylight_sec % 3600) / 60);

$darkness_sec = 86400 - $daylight_sec;
$darkness_h = floor($darkness_sec / 3600);
$darkness_m = floor(($darkness_sec % 3600) / 60);

$now = time();
$is_day = ($now >= $sunrise_ts && $now < $sunset_ts);
if ($is_day) {
    $rem_sec = $sunset_ts - $now;
    $rem_h = floor($rem_sec / 3600);
    $rem_m = floor(($rem_sec % 3600) / 60);
    $status_txt = "$rem_h h $rem_m min restants";
} else {
    $status_txt = "Nit en curs";
}
?>
<div class="PWS_module_title">
    <span>Posició Solar &bull; Llum Diürna</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #e8c400;">Llum Diürna<br><b><?php echo "$daylight_h h $daylight_m min"; ?></b></div>
        <div class="PWS_div_left" style="border-right-color: #e8c400;">Sortida Sol<br><b><?php echo $sunrise_str; ?></b> Demà</div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Azimut<br><b>165&deg; SSE</b></div>
    </div>

    <!-- Middle Sun Arc -->
    <div class="PWS_middle">
        <div style="position: relative; width: 130px; height: 130px; margin: 8px auto 0;">
            <svg width="130" height="130" viewBox="0 0 130 130" xmlns="http://www.w3.org/2000/svg">
                <circle r="63" cx="65" cy="65" fill="none" stroke="#4a5568" stroke-width="3" />
                <circle r="63" cx="65" cy="65" fill="none" stroke="#e8c400" stroke-width="4" stroke-dasharray="210 400" transform="rotate(-165 65 65)" />
                <circle style="fill: #22262c;" cx="65" cy="65" r="60" />

                <!-- Hour ticks -->
                <text x="46" y="17" fill="#fff" font-size="7">11</text>
                <text x="74" y="17" fill="#fff" font-size="7">13</text>
                <text x="33" y="22" fill="#fff" font-size="7">10</text>
                <text x="85" y="22" fill="#fff" font-size="7">14</text>
                <text x="22" y="31" fill="#fff" font-size="7">09</text>
                <text x="98" y="31" fill="#fff" font-size="7">15</text>
                <text x="12" y="42" fill="#fff" font-size="7">08</text>
                <text x="106" y="42" fill="#fff" font-size="7">16</text>
                <text x="6"  y="67" fill="#fff" font-size="7">06</text>
                <text x="114" y="67" fill="#fff" font-size="7">18</text>
                <text x="14" y="92" fill="#fff" font-size="7">04</text>
                <text x="107" y="92" fill="#fff" font-size="7">20</text>

                <!-- Center disk -->
                <circle fill="#3a4048" cx="65" cy="65" r="38" />

                <!-- Sun pointer -->
                <circle cx="125" cy="65" r="6" fill="#e8c400" transform="rotate(<?php echo ($is_day ? -75 : 105); ?> 65 65)" />
            </svg>
            <div style="position: absolute; top: 43px; left: 0; width: 130px; text-align: center; color: #fff;">
                <b style="font-size: 12.5px; color: #e8c400;"><?php echo ($is_day ? 'Dia' : 'Nit'); ?></b><br>
                <span style="font-size: 14.5px; font-weight: 700;"><?php echo $daylight_h; ?> h <?php echo $daylight_m; ?> m</span><br>
                <span style="font-size: 9.5px; color: #a0aec0;"><?php echo $status_txt; ?></span>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #718096;">Foscor<br><b><?php echo "$darkness_h h $darkness_m min"; ?></b></div>
        <div class="PWS_div_right" style="border-left-color: #e8c400;">Posta Sol<br><b><?php echo $sunset_str; ?></b> Avui</div>
        <div class="PWS_div_right" style="border-left-color: #01a4b4;">Elevació<br><b><?php echo ($is_day ? '45&deg;' : '-15&deg;'); ?></b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="mooninfo.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Dades Sol</a>
</div>
