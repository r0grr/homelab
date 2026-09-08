<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$baro = isset($weather["barometer"]) ? floatval($weather["barometer"]) : 1020.1;
$baro_inhg = number_format($baro * 0.02953, 2);
$baro_min = isset($weather["barometer_min"]) && floatval($weather["barometer_min"]) > 900 ? floatval($weather["barometer_min"]) : 1017.5;
$baro_max = isset($weather["barometer_max"]) && floatval($weather["barometer_max"]) > 900 ? floatval($weather["barometer_max"]) : 1023.0;
$baro_trend = isset($weather["barometer_trend"]) ? floatval($weather["barometer_trend"]) : 0.7;

$baro_angle = round(($baro - 1000) * 5 - 90, 1);
$trend_txt = ($baro_trend >= 0 ? "Pujant &uarr;" : "Baixant &darr;");
$trend_color = ($baro_trend >= 0 ? "#4FFC37" : "#f37867");
?>
<div class="PWS_module_title">
    <span>Baròmetre - hPa</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Mín Avui<br><b><?php echo number_format($baro_min, 1); ?> hPa</b></div>
        <div class="PWS_div_left" style="border-right-color: #9aba2f;">Tendència<br><b><?php echo ($baro_trend >= 0 ? '+' : '') . number_format($baro_trend, 1); ?> hPa</b></div>
    </div>

    <!-- Middle dial -->
    <div class="PWS_middle">
        <div style="position: relative; width: 130px; height: 130px; margin: 0 auto;">
            <svg width="130" height="130" viewBox="0 0 130 130" xmlns="http://www.w3.org/2000/svg">
                <circle r="63" cx="65" cy="65" fill="none" stroke="#4a5568" stroke-width="3" />
                <circle style="fill: #22262c;" cx="65" cy="65" r="60" />
                
                <text x="56" y="15" fill="#fff" font-size="7">1000</text>
                <text x="90" y="24" fill="#fff" font-size="5">1006</text>
                <text x="107" y="43" fill="#fff" font-size="5">1012</text>
                <text x="112" y="67" fill="#fff" font-size="6">1018</text>
                <text x="107" y="92" fill="#fff" font-size="5">1024</text>
                <text x="87" y="112" fill="#fff" font-size="5">1030</text>
                
                <text x="32" y="24" fill="#fff" font-size="5">994</text>
                <text x="15" y="43" fill="#fff" font-size="5">988</text>
                <text x="7"  y="67" fill="#fff" font-size="6">982</text>
                <text x="14" y="92" fill="#fff" font-size="5">976</text>
                <text x="35" y="112" fill="#fff" font-size="5">970</text>

                <?php for ($a = 0; $a < 180; $a += 15): ?>
                    <line stroke="rgba(255,255,255,0.15)" x1="19" y1="65" x2="111" y2="65" transform="rotate(<?php echo $a; ?> 65 65)" />
                <?php endfor; ?>

                <circle fill="#3a4048" cx="65" cy="65" r="38" />

                <!-- Needle -->
                <polygon points="108 72 122 65 108 58" style="fill: #9aba2f;" transform="rotate(<?php echo $baro_angle; ?> 65 65)" />
            </svg>
            <div style="position: absolute; top: 44px; left: 0; width: 130px; text-align: center; color: #fff;">
                <b style="font-size: 18px; letter-spacing: 0.5px;"><?php echo number_format($baro, 1); ?></b><br>
                <span style="font-size: 11px; color: #a0aec0; text-transform: uppercase;">hPa</span>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #d65b4a;">Màx Avui<br><b><?php echo number_format($baro_max, 1); ?> hPa</b></div>
        <div class="PWS_div_right" style="border-left-color: <?php echo $trend_color; ?>;"><?php echo $trend_txt; ?><br><b><?php echo abs($baro_trend); ?> hPa/h</b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="chartswu/todaybarometer.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Gràfiques</a>
    <span style="color: #4a5568;"> | </span>
    <a href="mapa_isobaric.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Mapa Isobàric</a>
</div>
