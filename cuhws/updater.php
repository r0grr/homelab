<!-- begin updater.php for MeteoSallent (PWS Dashboard redesign) -->
<?php
include_once('settings.php');
include_once('settings1.php');
include_once('common.php');
date_default_timezone_set($TZ);
?>
<script>
function safeUpdate(id, newHtml) {
    var el = document.getElementById(id);
    if (!el || !newHtml) return;
    if (el.innerHTML.trim() !== newHtml.trim()) {
        el.innerHTML = newHtml;
    }
}

function updateModule(id, scriptUrl, intervalMs) {
    $.ajax({
        cache: false,
        type: "GET",
        url: scriptUrl,
        success: function(data) {
            safeUpdate(id, data);
            if (intervalMs && intervalMs > 0) {
                setTimeout(function() { updateModule(id, scriptUrl, intervalMs); }, intervalMs);
            }
        },
        error: function() {
            if (intervalMs && intervalMs > 0) {
                setTimeout(function() { updateModule(id, scriptUrl, intervalMs * 2); }, intervalMs * 2);
            }
        }
    });
}

$(document).ready(function() {
    // Telemetria de l'estació meteorològica Davis Vantage Pro2 (Actualització cada 10 minuts / 600000 ms)
    setTimeout(function() { updateModule("pws_temperature", "pws_temperature.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_wind", "pws_wind.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_rainfall", "pws_rainfall.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_barometer", "pws_barometer.php", 600000); }, 600000);
    
    // Tira superior de l'estació Davis (Actualització cada 10 minuts / 600000 ms)
    setTimeout(function() { updateModule("pws_toptemp", "pws_toptemp.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_topextra", "pws_topextra.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_topwind", "pws_topwind.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_toprain", "pws_toprain.php", 600000); }, 600000);

    // Sensors addicionals de l'estació Davis (Solar/UV, Consola interior) (10 minuts / 600000 ms)
    setTimeout(function() { updateModule("pws_solaruv", "pws_solaruv.php", 600000); }, 600000);
    setTimeout(function() { updateModule("pws_indoor", "pws_indoor.php", 600000); }, 600000);

    // Serveis externs i astronòmics (conserven els seus intervals predeterminats)
    setTimeout(function() { updateModule("pws_topeq", "pws_topeq.php", 60000); }, 60000);           // Terratrèmols USGS
    setTimeout(function() { updateModule("pws_currentsky", "pws_currentsky.php", 30000); }, 30000);   // Condicions de cel METAR
    setTimeout(function() { updateModule("pws_aqi", "pws_aqi.php", 60000); }, 60000);                 // Qualitat de l'aire AQI
    setTimeout(function() { updateModule("pws_sun", "pws_sun.php", 120000); }, 120000);               // Posició del Sol
    setTimeout(function() { updateModule("pws_moon", "pws_moon.php", 300000); }, 300000);             // Fase lunar
    setTimeout(function() { updateModule("pws_forecast", "pws_forecast.php", 300000); }, 300000);     // Previsió meteorològica
    setTimeout(function() { updateModule("pws_fwi", "pws_fwi.php", 300000); }, 300000);               // Pla Alfa Generalitat

    // Sincronització de dades de gràfics amb Cumulus MX (cada 10 minuts / 600000 ms)
    setInterval(function() { $.get("cumulus_charts_bridge.php"); }, 600000);
});
</script>
