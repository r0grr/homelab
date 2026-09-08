<?php
include_once("livedata.php");
include_once("common.php");
include_once("settings1.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");

$current_baro = isset($weather["barometer"]) ? floatval($weather["barometer"]) : 1013.2;
$trend_val = isset($weather["barometer_trend"]) ? floatval($weather["barometer_trend"]) : 0.0;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Mapa Isobàric i Pressió en Superfície</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #1a1d21;
    color: #cbd5e0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 12px;
    height: 100vh;
    overflow-x: hidden;
    overflow-y: auto;
    padding: 0;
}
.weather34darkbrowser {
    position: relative;
    background: 0;
    width: 100%;
    height: 32px;
    margin: 0 auto;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    padding-top: 32px;
    background-image: 
        radial-gradient(circle, #EB7061 5px, transparent 6px), 
        radial-gradient(circle, #F5D160 5px, transparent 6px), 
        radial-gradient(circle, #81D982 5px, transparent 6px), 
        linear-gradient(to bottom, rgba(45, 48, 53, 0.9) 30px, transparent 0);
    background-position: 8px 9px, 24px 9px, 40px 9px, 0 0;
    background-size: 12px 12px, 12px 12px, 12px 12px, 100%;
    background-repeat: no-repeat;
    box-sizing: border-box;
}
.weather34darkbrowser[url]:after {
    content: attr(url);
    color: #ddd;
    font-size: 12px;
    line-height: 20px;
    position: absolute;
    left: 0;
    right: 0;
    top: 5px;
    margin: 0 10px 0 60px;
    padding: 0 10px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    height: 20px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}
.top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    padding: 6px 12px;
    background: rgba(30, 34, 40, 0.95);
    border-bottom: 1px solid rgba(80, 85, 95, 0.4);
}
.stat-chip {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #a0aec0;
}
.stat-chip b {
    color: #00A4B4;
    font-size: 13px;
}
.tab-btns {
    display: flex;
    gap: 6px;
}
.tab-btn {
    padding: 4px 10px;
    background: rgba(45, 50, 58, 0.9);
    border: 1px solid rgba(80, 85, 95, 0.6);
    color: #cbd5e0;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.tab-btn:hover {
    background: rgba(60, 68, 78, 0.9);
    color: #fff;
    border-color: #00A4B4;
}
.tab-btn.active {
    background: #00A4B4;
    color: #fff;
    border-color: #00A4B4;
    box-shadow: 0 2px 6px rgba(0, 164, 180, 0.4);
}
.map-view {
    display: none;
    width: 100%;
    height: calc(100vh - 75px);
    min-height: 380px;
}
.map-view.active {
    display: block;
}
iframe.map-frame {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
}
.img-container {
    width: 100%;
    height: 100%;
    overflow: auto;
    text-align: center;
    padding: 10px;
    background: #111418;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
}
.img-container img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    border: 1px solid rgba(80, 85, 95, 0.5);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.6);
}
.chart-caption {
    font-size: 11px;
    color: #8fa0b2;
    margin-top: 6px;
    margin-bottom: 12px;
}
@media screen and (max-width: 640px) {
    .top-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .tab-btns {
        justify-content: center;
    }
    .map-view {
        height: calc(100vh - 105px);
    }
}
</style>
</head>
<body>
<div class="weather34darkbrowser" url="Mapa Isobàric &bull; Pressió Atmosfèrica en Superfície (hPa) &bull; Sallent"></div>

<div class="top-bar">
    <div class="stat-chip">
        <span>Pressió actual a Sallent:</span>
        <b><?php echo number_format($current_baro, 1); ?> hPa</b>
        <span style="color: <?php echo ($trend_val >= 0 ? '#9aba2f' : '#d65b4a'); ?>;">
            (<?php echo ($trend_val > 0 ? '+' : '') . number_format($trend_val, 1); ?> hPa/h)
        </span>
    </div>
    <div class="tab-btns">
        <button type="button" class="tab-btn active" onclick="showTab('windy', this)">Interactiu (Windy)</button>
        <button type="button" class="tab-btn" onclick="showTab('dwd', this)">Carta DWD (Fronts i Isòbares)</button>
        <button type="button" class="tab-btn" onclick="showTab('metoffice', this)">Met Office Bracknell</button>
    </div>
</div>

<!-- Tab 1: Interactive Windy Pressure & Isobars Map -->
<div id="view-windy" class="map-view active">
    <iframe class="map-frame" src="https://embed.windy.com/embed2.html?lat=42.0&amp;lon=2.0&amp;zoom=5&amp;level=surface&amp;overlay=pressure&amp;menu=&amp;message=&amp;marker=true&amp;calendar=now&amp;pressure=true&amp;type=map&amp;location=coordinates&amp;detail=true&amp;detailLat=41.817&amp;detailLon=1.895&amp;metricWind=km%2Fh&amp;metricTemp=%C2%B0C&amp;radarRange=-1" scrolling="no"></iframe>
</div>

<!-- Tab 2: DWD Surface Analysis -->
<div id="view-dwd" class="map-view">
    <div class="img-container">
        <img src="https://www.wetterzentrale.de/maps/DWDOH00.png" alt="Carta Sinòptica DWD Isòbares en Superfície" loading="lazy">
        <div class="chart-caption">Carta d'anàlisi de pressió en superfície (isòbares i fronts) del Servei Meteorològic Alemany (DWD). Actualització cada 6 hores.</div>
    </div>
</div>

<!-- Tab 3: Met Office Bracknell Surface Analysis -->
<div id="view-metoffice" class="map-view">
    <div class="img-container">
        <img src="https://www.wetterzentrale.de/maps/bracka.gif" alt="Carta Sinòptica Met Office Bracknell" loading="lazy">
        <div class="chart-caption">Carta oficial d'anàlisi sinòptica de superfície (fronts, borrasques i anticiclons) del Met Office del Regne Unit.</div>
    </div>
</div>

<script>
function showTab(id, btn) {
    document.querySelectorAll(".map-view").forEach(function(el) { el.classList.remove("active"); });
    document.querySelectorAll(".tab-btn").forEach(function(b) { b.classList.remove("active"); });
    
    var view = document.getElementById("view-" + id);
    if (view) view.classList.add("active");
    if (btn) btn.classList.add("active");
}
</script>
</body>
</html>
