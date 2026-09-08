<?php
include_once("livedata.php");
include_once("common.php");
include_once("settings1.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");

$wind_speed = isset($weather["wind_speed"]) ? floatval($weather["wind_speed"]) : 0.0;
$wind_gust  = isset($weather["wind_gust"]) ? floatval($weather["wind_gust"]) : 0.0;
$wind_dir   = isset($weather["wind_direction"]) ? intval($weather["wind_direction"]) : 0;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Previsió i Estat del Vent &bull; Windy</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #1a1d21;
    color: #cbd5e0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 12px;
    height: 100vh;
    overflow: hidden;
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
.wind-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 14px;
    background: rgba(30, 34, 40, 0.95);
    border-bottom: 1px solid rgba(80, 85, 95, 0.4);
    font-size: 11px;
}
.wind-stat {
    display: flex;
    align-items: center;
    gap: 8px;
}
.wind-badge {
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(0, 164, 180, 0.2);
    border: 1px solid #00A4B4;
    color: #fff;
    font-weight: 700;
}
.wind-badge-gust {
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(255, 136, 65, 0.2);
    border: 1px solid #ff8841;
    color: #ff8841;
    font-weight: 700;
}
iframe {
    width: 100% !important;
    height: calc(100vh - 66px) !important;
    border: 0;
    display: block;
}
</style>
</head>
<body>
<div class="weather34darkbrowser" url="Previsió i Camp de Vent en Temps Real &bull; Sallent &bull; Catalunya"></div>
<div class="wind-bar">
    <div class="wind-stat">
        <span>Estació Sallent:</span>
        <span class="wind-badge"><?php echo round($wind_speed); ?> km/h (<?php echo $wind_dir; ?>&deg;)</span>
        <span>Ràfega:</span>
        <span class="wind-badge-gust"><?php echo round($wind_gust); ?> km/h</span>
    </div>
    <div style="color: #a0aec0;">Model ECMWF / GFS &bull; Windy.com</div>
</div>
<iframe src="https://embed.windy.com/embed2.html?lat=41.817&amp;lon=1.895&amp;zoom=8&amp;level=surface&amp;overlay=wind&amp;menu=&amp;message=&amp;marker=true&amp;calendar=now&amp;pressure=true&amp;type=map&amp;location=coordinates&amp;detail=true&amp;detailLat=41.817&amp;detailLon=1.895&amp;metricWind=km%2Fh&amp;metricTemp=%C2%B0C&amp;radarRange=-1" scrolling="no"></iframe>
</body>
</html>
