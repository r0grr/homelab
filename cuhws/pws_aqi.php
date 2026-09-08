<?php
// Mòdul oficial de Qualitat de l'Aire per a Sallent via The Weather Channel (weather.com)
include_once(__DIR__ . '/jsondata/weather_aqi.php');
$aqi_data = get_weather_aqi();

$aqi = isset($aqi_data['aqi']) ? intval($aqi_data['aqi']) : 61;
$category = $aqi_data['category'] ?? 'Moderada';
$color = $aqi_data['color'] ?? '#f1c40f';
$hora = $aqi_data['hora'] ?? date('H:i');
$pm25 = isset($aqi_data['pm25']) ? floatval($aqi_data['pm25']) : 12.8;
$pm10 = isset($aqi_data['pm10']) ? floatval($aqi_data['pm10']) : 20.1;
$o3   = isset($aqi_data['o3']) ? floatval($aqi_data['o3']) : 128.3;
$no2  = isset($aqi_data['no2']) ? floatval($aqi_data['no2']) : 12.0;
$primary_pol = $aqi_data['primary_pollutant'] ?? 'PM2.5';
$msg = $aqi_data['message'] ?? "Qualitat de l'aire acceptable.";

// Icon determination
$aq_icon = 'img/aq_yellow.svg';
if ($aqi <= 50) {
    $aq_icon = 'img/aq_green.svg';
} elseif ($aqi <= 100) {
    $aq_icon = 'img/aq_yellow.svg';
} elseif ($aqi <= 150) {
    $aq_icon = 'img/aq_orange.svg';
} else {
    $aq_icon = 'img/aq_red.svg';
}
if (!file_exists(__DIR__ . '/' . $aq_icon)) {
    $aq_icon = 'img/aq_green.svg';
}
?>
<div class="PWS_module_title">
    <span>Qualitat Aire &bull; Weather.com</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo $hora; ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: <?php echo $color; ?>;">Principal<br><b><?php echo $primary_pol; ?></b></div>
        <div class="PWS_div_left" style="border-right-color: #01a4b4;">Ozó (O3)<br><b><?php echo $o3; ?></b> &micro;g/m&sup3;</div>
        <div class="PWS_div_left" style="border-right-color: #9aba2f;">Diòxid N (NO2)<br><b><?php echo $no2; ?></b> &micro;g/m&sup3;</div>
    </div>

    <!-- Middle Dial & AQI -->
    <div class="PWS_middle">
        <div style="margin-top: 8px;">
            <div style="font-size: 12.5px; margin-bottom: 4px; color: #a0aec0; font-weight: 600;">
                Índex AQI (EPA)
            </div>
            <div style="width: 66px; height: 66px; margin: 0 auto; background: rgba(0, 0, 0, 0.4); border: 3px solid <?php echo $color; ?>; border-radius: 50%; display: flex; flex-direction: column; justify-content: center; align-items: center; box-shadow: 0 0 12px rgba(241, 196, 15, 0.3);">
                <span style="font-size: 24px; font-weight: 800; color: <?php echo $color; ?>; line-height: 1;"><?php echo $aqi; ?></span>
            </div>
            <div style="margin-top: 8px;">
                <span style="background: <?php echo $color; ?>; color: #000; font-weight: 800; font-size: 12px; padding: 2px 10px; border-radius: 4px; text-transform: uppercase;">
                    <?php echo $category; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: <?php echo $color; ?>;">PM2.5<br><b><?php echo $pm25; ?></b> &micro;g/m&sup3;</div>
        <div class="PWS_div_right" style="border-left-color: #01a4b4;">PM10<br><b><?php echo $pm10; ?></b> &micro;g/m&sup3;</div>
        <div class="PWS_div_right" style="border-left-color: #27ae60;">Sallent<br><b style="color: #00ff66;">Copernicus</b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="https://weather.com/ca-AD/es/barcelona/city/sallent/air-quality" target="_blank"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Dades Weather.com Sallent</a>
</div>
