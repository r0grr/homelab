<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

// Carreguem la previsió oficial d'Open-Meteo per a Sallent si existeix
$ds_file = __DIR__ . '/jsondata/darksky-cat.txt';
$d0_max = 31; $d0_min = 18; $d0_icon = 'pws_icons/clear_day.svg'; $d0_prob = '0%';
$d0_night_icon = 'pws_icons/few_night.svg';
$d1_max = 32; $d1_min = 19; $d1_icon = 'pws_icons/clear_day.svg'; $d1_prob = '5%';
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

// Carreguem el valor en temps real de Cumulus MX (canvas_status: pronòstic / estat de la Davis)
$cmx_status_text = '';
$rt_file = __DIR__ . '/cumulusdata/realtimegauges.txt';
if (file_exists($rt_file)) {
    $rt_json = json_decode(@file_get_contents($rt_file), true);
    if (!empty($rt_json['forecast'])) {
        $cmx_status_text = trim(html_entity_decode($rt_json['forecast'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
if (empty($cmx_status_text)) {
    $cmx_status_text = 'Estació meteorològica connectada sense avisos especials.';
}
?>
<style>
.pws_fct_tab_btn {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #cbd5e0;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    cursor: pointer;
    line-height: 13px;
    transition: all 0.2s ease;
}
.pws_fct_tab_btn:hover {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
}
.pws_fct_tab_btn.active {
    background: #ff8841;
    color: #ffffff;
    border-color: #ff8841;
    box-shadow: 0 0 6px rgba(255, 136, 65, 0.5);
}
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

<div class="PWS_module_title" style="display: flex; justify-content: space-between; align-items: center;">
    <span>Previsió Meteorològica</span>
    <div style="display: flex; align-items: center; gap: 5px;">
        <button id="btn_fct_tab_table" onclick="pwsSwitchForecastTab('table')" class="pws_fct_tab_btn active" type="button" title="Previsió 4 dies">Previsió</button>
        <button id="btn_fct_tab_map" onclick="pwsSwitchForecastTab('map')" class="pws_fct_tab_btn" type="button" title="Mapa Ensembles GFS Wetterzentrale">Ensembles</button>
        <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
    </div>
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

    <!-- Vista 2: Mapa Ensembles GFS Wetterzentrale -->
    <div id="pws_fct_view_map" style="display: none; width: 100%; height: 100%; text-align: center; box-sizing: border-box;">
        <a href="wetterzentrale.php" data-featherlight="iframe" title="Clica per ampliar el mapa d'ensembles GFS" style="display: block; text-decoration: none; height: 100%;">
            <div style="background: #ffffff; border-radius: 4px; padding: 2px; height: 122px; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.5);">
                <img src="https://wetterzentrale.de/es/ens_image.php?geoid=34523&var=201&model=gfs&member=ENS&bw=1" alt="Ensembles GFS Sallent" style="max-height: 118px; max-width: 100%; object-fit: contain;" />
            </div>
            <div style="margin-top: 5px; font-size: 11px; color: #38bdf8; font-weight: 700;">
                🔍 Clica per ampliar en pantalla completa
            </div>
        </a>
    </div>
</div>

<div class="PWS_module_footer">
    <a href="wetterzentrale.php" data-featherlight="iframe" title="Diagrama d'Ensembles GFS (Wetterzentrale)"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Ensembles GFS</a>
    <span style="color: #4a5568;"> | </span>
    <a href="meteocat_modal.php" data-featherlight="iframe" title="Predicció oficial Meteocat Sallent"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Meteocat Sallent</a>
    <span style="color: #4a5568;"> | </span>
    <a href="estofex.php" data-featherlight="iframe" title="Previsió europea de tempestes severes (ESTOFEX)"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Storm Forecast</a>
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

if (typeof window.pwsSwitchForecastTab === 'undefined') {
    window.pwsSwitchForecastTab = function(tab) {
        var viewTable = document.getElementById('pws_fct_view_table');
        var viewMap = document.getElementById('pws_fct_view_map');
        var btnTable = document.getElementById('btn_fct_tab_table');
        var btnMap = document.getElementById('btn_fct_tab_map');
        if (tab === 'map') {
            if (viewTable) viewTable.style.display = 'none';
            if (viewMap) viewMap.style.display = 'block';
            if (btnTable) btnTable.classList.remove('active');
            if (btnMap) btnMap.classList.add('active');
        } else {
            if (viewTable) viewTable.style.display = 'block';
            if (viewMap) viewMap.style.display = 'none';
            if (btnTable) btnTable.classList.add('active');
            if (btnMap) btnMap.classList.remove('active');
        }
    };
}
</script>
