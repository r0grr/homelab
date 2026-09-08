<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$temp_in = isset($weather["temp_indoor"]) ? floatval($weather["temp_indoor"]) : 26.0;
$hum_in  = isset($weather["humidity_indoor"]) ? intval($weather["humidity_indoor"]) : 60;
$feel_in = isset($weather["temp_indoor_feel"]) ? floatval($weather["temp_indoor_feel"]) : $temp_in;
?>
<div class="PWS_module_title">
    <span>Temperatura Interior &deg;C</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left: Feels like -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #8DFC2D;">
            Sensació<br><b><?php echo number_format($feel_in, 1); ?>&deg;C</b>
        </div>
        <div style="text-align: center; margin-top: 6px;">
            <svg width="44px" height="82px" viewBox="0 0 48 84">
                <rect x="8" y="2" width="32" height="80" rx="5" fill="rgba(255,255,255,0.06)" />
                <rect x="11" y="69" width="26" height="10" rx="2" fill="#01a4b4" />
                <rect x="11" y="56" width="26" height="10" rx="2" fill="<?php echo ($feel_in > 18 ? '#00bfa5' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="43" width="26" height="10" rx="2" fill="<?php echo ($feel_in > 21 ? '#8DFC2D' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="30" width="26" height="10" rx="2" fill="<?php echo ($feel_in > 24 ? '#ecb454' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="17" width="26" height="10" rx="2" fill="<?php echo ($feel_in > 27 ? '#ff8841' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="4"  width="26" height="10" rx="2" fill="<?php echo ($feel_in > 30 ? '#f37867' : 'rgba(255,255,255,0.1)'); ?>" />
            </svg>
        </div>
    </div>

    <!-- Middle: House graphic -->
    <div class="PWS_middle">
        <div style="position: relative; width: 100px; height: 100px; margin: 8px auto 0 auto;">
            <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <!-- Roof -->
                <polygon points="50,15 90,45 10,45" fill="#ecb454" />
                <polygon points="70,22 80,22 80,38 70,30" fill="#a0aec0" />
                <!-- House body -->
                <rect x="20" y="45" width="60" height="45" fill="#3a4048" rx="2" />
                <!-- Door -->
                <rect x="42" y="65" width="16" height="25" fill="#22262c" rx="1" />
            </svg>
            <div style="position: absolute; top: 45px; left: 0; width: 100px; text-align: center; color: #fff;">
                <b style="font-size: 20px; line-height: 1;"><?php echo number_format($temp_in, 1); ?>&deg;</b>
            </div>
        </div>
        <div style="font-size: 11px; color: #a0aec0; margin-top: 2px;">
            Consola Davis VP2
        </div>
    </div>

    <!-- Right: Humidity -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #01a4b4;">
            Humitat<br><b><?php echo $hum_in; ?>%</b>
        </div>
        <div style="text-align: center; margin-top: 6px;">
            <svg width="44px" height="82px" viewBox="0 0 48 84">
                <rect x="8" y="2" width="32" height="80" rx="5" fill="rgba(255,255,255,0.06)" />
                <rect x="11" y="69" width="26" height="10" rx="2" fill="#01a4b4" />
                <rect x="11" y="56" width="26" height="10" rx="2" fill="<?php echo ($hum_in > 35 ? '#00bfa5' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="43" width="26" height="10" rx="2" fill="<?php echo ($hum_in > 45 ? '#8DFC2D' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="30" width="26" height="10" rx="2" fill="<?php echo ($hum_in > 55 ? '#ecb454' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="17" width="26" height="10" rx="2" fill="<?php echo ($hum_in > 65 ? '#ff8841' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="4"  width="26" height="10" rx="2" fill="<?php echo ($hum_in > 75 ? '#f37867' : 'rgba(255,255,255,0.1)'); ?>" />
            </svg>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="homeindoor.php" data-featherlight="iframe" data-featherlight-variant="featherlight-vertical" class="featherlight-vertical-link"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Guia Confort Interior</a>
</div>
