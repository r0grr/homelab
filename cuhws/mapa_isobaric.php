<?php
include_once("livedata.php");
include_once("common.php");
include_once("settings1.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");

$current_baro = isset($weather["barometer"]) ? floatval($weather["barometer"]) : 1013.2;
$trend_val = isset($weather["barometer_trend"]) ? floatval($weather["barometer_trend"]) : 0.0;

$cache_dir = __DIR__ . "/chartcache";
if (!file_exists($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}

// 1. DWD (Deutscher Wetterdienst)
$dwd_page_url = "https://www.dwd.de/EN/ourservices/hobbymet_wcharts_europe/hobbyeuropecharts.html";
$dwd_remote_url = "https://www.dwd.de/DWD/wetter/wv_spez/hobbymet/wetterkarten/bwk_bodendruck_na_ana.png";
$dwd_cache_file = $cache_dir . "/dwd_surface.png";
$dwd_img_url = "chartcache/dwd_surface.png";

if (!file_exists($dwd_cache_file) || (time() - filemtime($dwd_cache_file) > 1800)) {
    $ctx = stream_context_create(["http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 6]]);
    $img_data = @file_get_contents($dwd_remote_url, false, $ctx);
    if ($img_data && strlen($img_data) > 10000) {
        @file_put_contents($dwd_cache_file, $img_data);
    }
}
if (!file_exists($dwd_cache_file) || filesize($dwd_cache_file) < 10000) {
    $dwd_img_url = $dwd_remote_url;
}

// 2. Met Office (UK)
$metoffice_page_url = "https://weather.metoffice.gov.uk/maps-and-charts/surface-pressure";
$metoffice_cache_file = $cache_dir . "/metoffice_surface.gif";
$metoffice_url_cache = $cache_dir . "/metoffice_url.txt";
$metoffice_img_url = "chartcache/metoffice_surface.gif";

if (!file_exists($metoffice_cache_file) || (time() - filemtime($metoffice_cache_file) > 1800)) {
    $ctx = stream_context_create(["http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 6]]);
    $page_html = @file_get_contents($metoffice_page_url, false, $ctx);
    if ($page_html && preg_match('#https://data\.consumer-digital\.api\.metoffice\.gov\.uk/v1/surface-pressure/colour/[^"\'\s]+/FSXX12T_00\.gif#', $page_html, $m)) {
        $mo_remote = $m[0];
        @file_put_contents($metoffice_url_cache, $mo_remote);
        $mo_data = @file_get_contents($mo_remote, false, $ctx);
        if ($mo_data && strlen($mo_data) > 5000) {
            @file_put_contents($metoffice_cache_file, $mo_data);
        }
    }
}

if (!file_exists($metoffice_cache_file) || filesize($metoffice_cache_file) < 5000) {
    $saved_url = file_exists($metoffice_url_cache) ? trim(@file_get_contents($metoffice_url_cache)) : "";
    $metoffice_img_url = !empty($saved_url) ? $saved_url : "https://data.consumer-digital.api.metoffice.gov.uk/v1/surface-pressure/colour/2026-09-08T1200/FSXX12T_00.gif";
}
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
    background: #fff;
}
.chart-caption {
    font-size: 11px;
    color: #8fa0b2;
    margin-top: 8px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.chart-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #01a4b4;
    text-decoration: none;
    background: rgba(1, 164, 180, 0.12);
    border: 1px solid rgba(1, 164, 180, 0.3);
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.chart-link-btn:hover {
    background: rgba(1, 164, 180, 0.25);
    color: #fff;
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
        <button type="button" class="tab-btn" onclick="showTab('dwd', this)">Carta DWD (Offenbach)</button>
        <button type="button" class="tab-btn" onclick="showTab('metoffice', this)">Met Office (Bracknell)</button>
    </div>
</div>

<!-- Tab 1: Interactive Windy Pressure & Isobars Map -->
<div id="view-windy" class="map-view active">
    <iframe class="map-frame" src="https://embed.windy.com/embed2.html?lat=42.0&amp;lon=2.0&amp;zoom=5&amp;level=surface&amp;overlay=pressure&amp;menu=&amp;message=&amp;marker=true&amp;calendar=now&amp;pressure=true&amp;type=map&amp;location=coordinates&amp;detail=true&amp;detailLat=41.817&amp;detailLon=1.895&amp;metricWind=km%2Fh&amp;metricTemp=%C2%B0C&amp;radarRange=-1" scrolling="no"></iframe>
</div>

<!-- Tab 2: DWD Surface Analysis -->
<div id="view-dwd" class="map-view">
    <div class="img-container">
        <a href="<?php echo htmlspecialchars($dwd_img_url); ?>" target="_blank" title="Clica per ampliar la carta DWD">
            <img src="<?php echo htmlspecialchars($dwd_img_url); ?>" alt="Carta Sinòptica DWD Isòbares en Superfície" referrerpolicy="no-referrer" loading="lazy">
        </a>
        <div class="chart-caption">
            <span>Carta oficial d'anàlisi de pressió en superfície (isòbares i fronts) del Servei Meteorològic Alemany (DWD).</span>
            <a href="<?php echo $dwd_page_url; ?>" target="_blank" class="chart-link-btn">
                <svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Web oficial DWD
            </a>
            <a href="<?php echo htmlspecialchars($dwd_img_url); ?>" target="_blank" class="chart-link-btn">
                🔍 Ampliar imatge original
            </a>
        </div>
    </div>
</div>

<!-- Tab 3: Met Office Bracknell Surface Analysis -->
<div id="view-metoffice" class="map-view">
    <div class="img-container">
        <a href="<?php echo htmlspecialchars($metoffice_img_url); ?>" target="_blank" title="Clica per ampliar la carta Met Office">
            <img src="<?php echo htmlspecialchars($metoffice_img_url); ?>" alt="Carta Sinòptica Met Office Bracknell" referrerpolicy="no-referrer" loading="lazy">
        </a>
        <div class="chart-caption">
            <span>Carta d'anàlisi sinòptica de superfície (fronts, borrasques i anticiclons) del Met Office del Regne Unit.</span>
            <a href="<?php echo $metoffice_page_url; ?>" target="_blank" class="chart-link-btn">
                <svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Web oficial Met Office
            </a>
            <a href="<?php echo htmlspecialchars($metoffice_img_url); ?>" target="_blank" class="chart-link-btn">
                🔍 Ampliar imatge original
            </a>
        </div>
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
