<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

// Carreguem la previsió oficial d'Open-Meteo per a Sallent (actualització automàtica cada 30 minuts)
$ds_file = __DIR__ . '/jsondata/darksky-cat.txt';
if (!file_exists($ds_file) || (time() - filemtime($ds_file) > 1800) || filesize($ds_file) < 200) {
    @include_once(__DIR__ . '/jsondata/openmeteo_fetch.php');
}

$curr_temp = isset($weather["temp"]) ? floatval($weather["temp"]) : 18.0;
$d0_max = round($curr_temp + 3); $d0_min = round($curr_temp - 5); $d0_icon = 'pws_icons/clear_day.svg'; $d0_prob = '0%';
$d0_night_icon = 'pws_icons/few_night.svg';
$d1_max = round($curr_temp + 3); $d1_min = round($curr_temp - 5); $d1_icon = 'pws_icons/clear_day.svg'; $d1_prob = '5%';
$d1_night_icon = 'pws_icons/few_night.svg';

if (file_exists($ds_file)) {
    $fct = json_decode(file_get_contents($ds_file), true);
    if (!empty($fct['daily']['data'])) {
        $daily = $fct['daily']['data'];
        if (isset($daily[0])) {
            $d0_max = round($daily[0]['temperatureMax']);
            $d0_min = round($daily[0]['temperatureMin']);
            $d0_prob = round($daily[0]['precipProbability'] * 100) . '%';
            $ic0 = $daily[0]['icon'];
            if (file_exists(__DIR__ . "/css/darkskyicons/{$ic0}.svg")) {
                $d0_icon = "css/darkskyicons/{$ic0}.svg";
            }
        }
        if (isset($daily[1])) {
            $d1_max = round($daily[1]['temperatureMax']);
            $d1_min = round($daily[1]['temperatureMin']);
            $d1_prob = round($daily[1]['precipProbability'] * 100) . '%';
            $ic1 = $daily[1]['icon'];
            if (file_exists(__DIR__ . "/css/darkskyicons/{$ic1}.svg")) {
                $d1_icon = "css/darkskyicons/{$ic1}.svg";
            }
        }
    }
}

// Carreguem el valor en temps real de Cumulus MX (pronòstic i alertes de la consola Davis)
$cmx_status_text = '';
$rt_file = __DIR__ . '/cumulusdata/realtimegauges.txt';
if (file_exists($rt_file)) {
    $rt_json = json_decode(@file_get_contents($rt_file), true);
    if ($rt_json) {
        $f_rrate = isset($rt_json['rrate']) ? floatval($rt_json['rrate']) : 0.0;
        $f_wgust = isset($rt_json['wgust']) ? floatval($rt_json['wgust']) : 0.0;
        $f_wspeed = isset($rt_json['wspeed']) ? floatval($rt_json['wspeed']) : 0.0;
        $f_temp = isset($rt_json['temp']) ? floatval($rt_json['temp']) : 20.0;
        $f_rfall = isset($rt_json['rfall']) ? floatval($rt_json['rfall']) : 0.0;
        
        if ($f_rrate >= 25.4) {
            $cmx_status_text = "🐱🐶 Plou a bots i barrals! (IT'S RAINING CATS AND DOGS - " . number_format($f_rrate, 1) . " mm/h)";
        } elseif ($f_wgust >= 45 || $f_wspeed >= 35) {
            $cmx_status_text = "🎩💨 Aguanta't el barret! (HOLD ON TO YOUR HAT - Ràfega " . number_format($f_wgust, 0) . " km/h)";
        } elseif ($f_wspeed >= 15 && $f_wspeed <= 26 && $f_rrate == 0 && $f_rfall == 0) {
            $cmx_status_text = "🪁 Temps per volar estels (GOOD KITE FLYING WEATHER - " . number_format($f_wspeed, 0) . " km/h)";
        } elseif ($f_temp <= 0.5 && ($f_rrate > 0 || $f_rfall > 0)) {
            $cmx_status_text = "❄️⚠️ Risc de pluja engelant (FREEZING RAIN POSSIBLE - " . number_format($f_temp, 1) . "°C)";
        } elseif (!empty($rt_json['forecast'])) {
            $cmx_status_text = trim(html_entity_decode($rt_json['forecast'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }
}
if (empty($cmx_status_text)) {
    $cmx_status_text = 'Estació meteorològica connectada sense avisos especials.';
}
?>
<style>
.pws_forecast_marquee_wrap {
    margin: 3px 8px 4px 8px;
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 4px;
    padding: 3px 8px;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.4);
    height: 22px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
}
.pws_forecast_marquee_track {
    display: inline-block;
    white-space: nowrap;
    will-change: transform;
    font-size: 10.5px;
    color: #e2e8f0;
    font-family: Arial, sans-serif;
    letter-spacing: 0.2px;
}
.pws_forecast_marquee_wrap:hover .pws_forecast_marquee_track {
    animation-play-state: paused !important;
}
@keyframes pwsMarqueePingPong {
    0%, 15% {
        transform: translateX(0px);
    }
    85%, 100% {
        transform: translateX(var(--pws-fct-scroll, -250px));
    }
}
</style>

<div class="PWS_module_title">
    <span>Previsió Meteorològica</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('d/m H:i'); ?></span>
</div>

<!-- Ticker d'informació de l'estació Cumulus MX (valor dinàmic de canvas_status) -->
<div id="pws_fct_marquee_wrap" class="pws_forecast_marquee_wrap" title="Informació de l'estació (Cumulus MX): <?php echo htmlspecialchars($cmx_status_text, ENT_QUOTES, 'UTF-8'); ?>">
    <div id="pws_fct_marquee_track" class="pws_forecast_marquee_track">
        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="#ff8841" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 5px; display: inline-block;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg><span style="color: #ff8841; font-weight: 700; font-size: 9.5px; text-transform: uppercase; margin-right: 5px; letter-spacing: 0.4px;">Estació:</span><span><?php echo htmlspecialchars($cmx_status_text, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
</div>

<div style="height: 154px; padding: 2px 6px; box-sizing: border-box;">
    <!-- Vista 1: Taula de previsió -->
    <div id="pws_fct_view_table" style="display: block; width: 100%; height: 100%;">
        <table style="width: 100%; height: 148px; font-size: 13px; text-align: center; border-collapse: collapse;">
            <tr style="height: 20px; font-weight: 700;">
                <td><span style="color: #FF7C39;">Avui</span></td>
                <td><span style="color: #01A4B4;">Nit</span></td>
                <td><span style="color: #FF7C39;">Demà</span></td>
                <td><span style="color: #01A4B4;">Nit</span></td>
            </tr>
            <tr>
                <td><img src="<?php echo $d0_icon; ?>" width="48" height="38" alt="Sol" style="vertical-align: middle;"></td>
                <td><img src="<?php echo $d0_night_icon; ?>" width="48" height="38" alt="Serè" style="vertical-align: middle;"></td>
                <td><img src="<?php echo $d1_icon; ?>" width="48" height="38" alt="Sol" style="vertical-align: middle;"></td>
                <td><img src="<?php echo $d1_night_icon; ?>" width="48" height="38" alt="Serè" style="vertical-align: middle;"></td>
            </tr>
            <tr style="font-size: 17px; font-weight: 700;">
                <td><span style="color: #FF7C39;"><?php echo $d0_max; ?><small>&deg;C</small></span></td>
                <td><span style="color: #01A4B4;"><?php echo $d0_min; ?><small>&deg;C</small></span></td>
                <td><span style="color: #FF7C39;"><?php echo $d1_max; ?><small>&deg;C</small></span></td>
                <td><span style="color: #01A4B4;"><?php echo $d1_min; ?><small>&deg;C</small></span></td>
            </tr>
            <tr style="font-size: 11.5px; color: #a0aec0;">
                <td><?php echo $d0_prob; ?> <svg viewBox="0 0 400 500" width="8px" fill="#01a4b5" stroke="#01a4b5"><path d="M256,0 C256,0 78,209 78,334 C78,432 158,512 256,512 C354,512 433,432 433,334 C433,209 256,0 256,0 Z"/></svg></td>
                <td>0% <svg viewBox="0 0 400 500" width="8px" fill="#01a4b5" stroke="#01a4b5"><path d="M256,0 C256,0 78,209 78,334 C78,432 158,512 256,512 C354,512 433,432 433,334 C433,209 256,0 256,0 Z"/></svg></td>
                <td><?php echo $d1_prob; ?> <svg viewBox="0 0 400 500" width="8px" fill="#01a4b5" stroke="#01a4b5"><path d="M256,0 C256,0 78,209 78,334 C78,432 158,512 256,512 C354,512 433,432 433,334 C433,209 256,0 256,0 Z"/></svg></td>
                <td>10% <svg viewBox="0 0 400 500" width="8px" fill="#01a4b5" stroke="#01a4b5"><path d="M256,0 C256,0 78,209 78,334 C78,432 158,512 256,512 C354,512 433,432 433,334 C433,209 256,0 256,0 Z"/></svg></td>
            </tr>
        </table>
    </div>
</div>

<div class="PWS_module_footer">
    <a href="wetterzentrale.php" data-featherlight="iframe" title="Diagrama d'Ensembles GFS (Wetterzentrale)"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Ensembles GFS</a>
    <span style="color: #4a5568;"> | </span>
    <a href="meteocat_modal.php" data-featherlight="iframe" title="Predicció oficial Meteocat Sallent"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Meteocat</a>
    <span style="color: #4a5568;"> | </span>
    <a href="estofex.php" data-featherlight="iframe" title="Previsió europea de tempestes severes (ESTOFEX)"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> ESTOFEX</a>
</div>

<script>
(function() {
    function initPwsFctMarquee() {
        var wrap = document.getElementById('pws_fct_marquee_wrap');
        var track = document.getElementById('pws_fct_marquee_track');
        if (!wrap || !track) return;
        var diff = track.scrollWidth - wrap.clientWidth;
        if (diff > 4) {
            wrap.style.setProperty('--pws-fct-scroll', '-' + (diff + 12) + 'px');
            var duration = Math.max(10, Math.round(diff / 22));
            track.style.animation = 'pwsMarqueePingPong ' + duration + 's ease-in-out infinite alternate';
        } else {
            track.style.animation = 'none';
            track.style.transform = 'none';
        }
    }
    initPwsFctMarquee();
    setTimeout(initPwsFctMarquee, 300);
    window.addEventListener('resize', initPwsFctMarquee);
})();
</script>
