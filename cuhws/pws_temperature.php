<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$cur_temp = isset($weather["temp"]) ? floatval($weather["temp"]) : 30.6;
$heat_index = isset($weather["heat_index"]) ? floatval($weather["heat_index"]) : (isset($weather["temp_feel"]) ? floatval($weather["temp_feel"]) : $cur_temp);
$thsw = isset($weather["thsw"]) ? floatval($weather["thsw"]) : (isset($cumulus[58]) && $cumulus[58] !== '' ? floatval($cumulus[58]) : (isset($weather["heat_index"]) ? floatval($weather["heat_index"]) : $cur_temp));
$hum = isset($weather["humidity"]) ? intval($weather["humidity"]) : 56;
$max_temp = isset($weather["temp_today_high"]) ? floatval($weather["temp_today_high"]) : 38.7;
$min_temp = isset($weather["temp_today_low"]) && floatval($weather["temp_today_low"]) > -40 ? floatval($weather["temp_today_low"]) : 18.2;
$maxtemptime = isset($weather["maxtemptime"]) ? $weather["maxtemptime"] : '';
$lowtemptime = isset($weather["lowtemptime"]) ? $weather["lowtemptime"] : '';
$feel = isset($weather["temp_feel"]) ? floatval($weather["temp_feel"]) : $cur_temp;
$dew = isset($weather["dewpoint"]) ? floatval($weather["dewpoint"]) : 20.8;
$wetbulb = isset($weather["wetbulb"]) ? floatval($weather["wetbulb"]) : round($cur_temp * atan(0.151977 * pow($hum + 8.313659, 0.5)) + atan($cur_temp + $hum) - atan($hum - 1.676331) + 0.00391838 * pow($hum, 1.5) * atan(0.023101 * $hum) - 4.686035, 1);
$trend = isset($weather["temp_trend"]) ? floatval($weather["temp_trend"]) : -1.7;

// Dynamic circle background color based on temperature range: -10°C to 45°C
if ($cur_temp < -5) {
    // Congelació severa (< -5°C)
    $grad_start = '#2b0975';
    $grad_end   = '#3a86ff';
    $temp_glow  = 'rgba(58, 134, 255, 0.6)';
    $text_color = '#ffffff';
} elseif ($cur_temp < 0) {
    // Glaçada (-5°C a 0°C)
    $grad_start = '#0077b6';
    $grad_end   = '#00b4d8';
    $temp_glow  = 'rgba(0, 180, 216, 0.6)';
    $text_color = '#ffffff';
} elseif ($cur_temp < 6) {
    // Molt fred (0°C a 6°C)
    $grad_start = '#0096c7';
    $grad_end   = '#48cae4';
    $temp_glow  = 'rgba(72, 202, 228, 0.5)';
    $text_color = '#0f172a';
} elseif ($cur_temp < 12) {
    // Fred (6°C a 12°C)
    $grad_start = '#06d6a0';
    $grad_end   = '#00b4d8';
    $temp_glow  = 'rgba(6, 214, 160, 0.5)';
    $text_color = '#0f172a';
} elseif ($cur_temp < 18) {
    // Fresqueta / Suau (12°C a 18°C)
    $grad_start = '#52b788';
    $grad_end   = '#99d98c';
    $temp_glow  = 'rgba(82, 183, 136, 0.45)';
    $text_color = '#0f172a';
} elseif ($cur_temp < 24) {
    // Confortable / Primavera (18°C a 24°C)
    $grad_start = '#9ef01a';
    $grad_end   = '#d4d700';
    $temp_glow  = 'rgba(212, 215, 0, 0.45)';
    $text_color = '#0f172a';
} elseif ($cur_temp < 29) {
    // Calor moderada (24°C a 29°C)
    $grad_start = '#ffd166';
    $grad_end   = '#f4a261';
    $temp_glow  = 'rgba(244, 162, 97, 0.5)';
    $text_color = '#0f172a';
} elseif ($cur_temp < 34) {
    // Calor notable (29°C a 34°C)
    $grad_start = '#fb8500';
    $grad_end   = '#f35b04';
    $temp_glow  = 'rgba(243, 91, 4, 0.55)';
    $text_color = '#ffffff';
} elseif ($cur_temp < 39) {
    // Molta calor / Onada de calor (34°C a 39°C)
    $grad_start = '#d90429';
    $grad_end   = '#ef233c';
    $temp_glow  = 'rgba(239, 35, 60, 0.65)';
    $text_color = '#ffffff';
} else {
    // Calor extrema (>= 39°C fins a 45°C+)
    $grad_start = '#6a040f';
    $grad_end   = '#d00000';
    $temp_glow  = 'rgba(208, 0, 0, 0.75)';
    $text_color = '#ffffff';
}
?>
<div class="PWS_module_title">
    <span>Temperatura &deg;C</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="8" height="8" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values: 1. Mín Avui, 2. Índex THSW, 3. Sensació, 4. Diferència tº 24h -->
    <div class="PWS_left">
        <div class="PWS_div_left PWS_div_temp" style="border-right-color: #007aff;" title="<?php echo $lowtemptime ? "Hora mínima: $lowtemptime" : "Mínima d'avui"; ?>">Mín Avui<br><b><?php echo number_format($min_temp, 1); ?>&deg;C</b></div>
        <div class="PWS_div_left PWS_div_temp" style="border-right-color: #ffb703;" title="Índex THSW (Temperatura, Humitat, Sol i Vent)">Índex THSW<br><b><?php echo number_format($thsw, 1); ?>&deg;C</b></div>
        <div class="PWS_div_left PWS_div_temp" style="border-right-color: #40FC39;">Sensació<br><b><?php echo number_format($feel, 1); ?>&deg;C</b></div>
        <div class="PWS_div_left PWS_div_temp" style="border-right-color: #00d2d3;" title="Tendència de la temperatura en l'última hora">Tendència 1h<br><b><?php echo ($trend > 0 ? '+' : '') . number_format($trend, 1); ?>&deg;C/h <?php echo ($trend >= 0 ? '&uarr;' : '&darr;'); ?></b></div>
    </div>

    <!-- Middle temperature circle & humidity underneath -->
    <div class="PWS_middle">
        <div style="position: relative; width: 124px; height: 124px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
            <svg width="124" height="124" viewBox="0 0 130 130" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="pws_temp_grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:<?php echo $grad_start; ?>;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:<?php echo $grad_end; ?>;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <circle style="stroke: rgba(255,255,255,0.18); stroke-width: 1.5; filter: drop-shadow(0 0 10px <?php echo $temp_glow; ?>);" fill="url(#pws_temp_grad)" cx="65" cy="65" r="52" />
            </svg>
            <div style="position: absolute; top: 0; left: 0; width: 124px; height: 124px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: <?php echo $text_color; ?>; text-shadow: 0 0 8px <?php echo ($text_color == '#ffffff' ? 'rgba(0,0,0,0.85)' : 'rgba(255,255,255,0.8)'); ?>;">
                <b style="font-size: 23px; line-height: 1.05;"><?php echo number_format($cur_temp, 1); ?>&deg;</b>
                <span style="font-size: 11px; font-weight: 800; margin-top: 2px;">
                    <?php echo ($trend < 0 ? '&darr; ' . number_format(abs($trend), 1) . '&deg;/h' : ($trend > 0 ? '&uarr; ' . number_format($trend, 1) . '&deg;/h' : '&rarr; 0.0&deg;/h')); ?>
                </span>
                <span style="font-size: 10px; font-weight: 700; margin-top: 2px; opacity: 0.9;">&uarr;<?php echo number_format($max_temp, 1); ?>&deg; &darr;<?php echo number_format($min_temp, 1); ?>&deg;</span>
            </div>
        </div>
        <div style="text-align: center; margin-top: 3px; font-size: 12.5px; font-weight: 700; color: #cbd5e1;">
            Humitat: <b style="color: #01a4b4;"><?php echo $hum; ?>%</b>
        </div>
    </div>

    <!-- Right values: 1. Màx Avui, 2. Índex Calor, 3. Punt de Rosada, 4. Bulb humit -->
    <div class="PWS_right">
        <div class="PWS_div_right PWS_div_temp" style="border-left-color: #ff3b30;" title="<?php echo $maxtemptime ? "Hora màxima: $maxtemptime" : "Màxima d'avui"; ?>">Màx Avui<br><b><?php echo number_format($max_temp, 1); ?>&deg;C</b></div>
        <div class="PWS_div_right PWS_div_temp" style="border-left-color: #ff8841;">Índex Calor<br><b><?php echo number_format($heat_index, 1); ?>&deg;C</b></div>
        <div class="PWS_div_right PWS_div_temp" style="border-left-color: #48FB9E;">Punt Rosada<br><b><?php echo number_format($dew, 1); ?>&deg;C</b></div>
        <div class="PWS_div_right PWS_div_temp" style="border-left-color: #3EFB58;">Bulb Humit<br><b><?php echo number_format($wetbulb, 1); ?>&deg;C</b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="chartswu/todaytemperature.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Gràfiques</a>
    <span style="color: #4a5568;"> | </span>
    <a href="meteocat_modal.php" data-featherlight="iframe" title="Predicció oficial per a Sallent (Meteocat)"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Previsió Meteocat</a>
</div>
