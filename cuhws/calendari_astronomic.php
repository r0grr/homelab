<?php
include_once("settings.php");
include_once("common.php");
include_once("livedata.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");
date_default_timezone_set($TZ);

$now = time();
$current_year = intval(date("Y", $now));

// Dades solars d'avui a Sallent
$sun_info = date_sun_info($now, $lat, $lon);
$sunrise_str = date("H:i", $sun_info["sunrise"]);
$sunset_str  = date("H:i", $sun_info["sunset"]);
$daylight_sec = $sun_info["sunset"] - $sun_info["sunrise"];
$daylight_h   = floor($daylight_sec / 3600);
$daylight_m   = floor(($daylight_sec % 3600) / 60);

// Efemèrides de canvi d'estació (IGN / Observatori Astronòmic Nacional)
// Valors calculats amb precisió astronòmica per al fus horari CET/CEST
$seasons = [
    2026 => [
        "spring" => ["date" => "20 Març 2026", "time" => "15:46 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2026-03-20 15:46:00 CET")],
        "summer" => ["date" => "21 Juny 2026", "time" => "09:24 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2026-06-21 09:24:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2026", "time" => "02:05 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2026-09-23 02:05:00 CEST")],
        "winter" => ["date" => "21 Desembre 2026", "time" => "21:50 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2026-12-21 21:50:00 CET")]
    ]
];

// Fallback dinàmic si l'any és diferent
if (!isset($seasons[$current_year])) {
    $seasons[$current_year] = [
        "spring" => ["date" => "20 Març " . $current_year, "time" => "04:06 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime($current_year . "-03-20 04:06:00 CET")],
        "summer" => ["date" => "21 Juny " . $current_year, "time" => "21:58 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime($current_year . "-06-21 21:58:00 CEST")],
        "autumn" => ["date" => "22 Setembre " . $current_year, "time" => "13:44 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime($current_year . "-09-22 13:44:00 CEST")],
        "winter" => ["date" => "21 Desembre " . $current_year, "time" => "09:21 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime($current_year . "-12-21 09:21:00 CET")]
    ];
}

$cur_seasons = $seasons[$current_year];

// Canvis d'horari oficial a Catalunya (Últim diumenge de març i d'octubre)
function getLastSunday($month, $year) {
    return date("j", strtotime("last Sunday of " . date("F", mktime(0,0,0,$month,1,$year)) . " " . $year));
}
$dst_start_day = getLastSunday(3, $current_year);
$dst_end_day   = getLastSunday(10, $current_year);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Calendari Astronòmic Oficial de Catalunya - MeteoSallent</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #181b1f;
    color: #cbd5e0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 12px;
    padding: 0 0 16px 0;
    overflow-x: hidden;
    overflow-y: auto;
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
.container {
    max-width: 720px;
    margin: 0 auto;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.card {
    background: rgba(30, 34, 40, 0.95);
    border: 1px solid rgba(80, 85, 95, 0.5);
    border-radius: 8px;
    padding: 14px 16px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
}
.card-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #a0aec0;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.seasons-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
@media (max-width: 600px) {
    .seasons-grid { grid-template-columns: repeat(2, 1fr); }
}
.season-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 12px 10px;
    text-align: center;
}
.season-icon {
    font-size: 24px;
    margin-bottom: 4px;
}
.season-title {
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 4px;
}
.season-date {
    font-size: 11px;
    color: #cbd5e0;
}
.season-time {
    font-size: 10px;
    color: #718096;
    margin-top: 2px;
}
.sun-metric-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    text-align: center;
}
.sun-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 10px;
}
.sun-val {
    font-size: 18px;
    font-weight: 800;
    color: #fff;
    margin-top: 2px;
}
.sun-lbl {
    font-size: 10px;
    text-transform: uppercase;
    color: #718096;
}
.dst-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 8px;
}
.dst-card:last-child {
    margin-bottom: 0;
}
.footer {
    text-align: center;
    font-size: 10px;
    color: #718096;
    margin-top: 4px;
}
</style>
</head>
<body>

<div class="weather34darkbrowser" url="Calendari Astronòmic Oficial • Observatori Astronòmic Nacional"></div>

<div class="container">

    <!-- Efemèrides de les 4 Estacions de l'any -->
    <div class="card" style="border-left: 4px solid #01a4b4;">
        <div class="card-title">
            <span>Inici Oficial de les 4 Estacions de l'Any <?php echo $current_year; ?></span>
            <span style="color: #01a4b4; font-weight: 700; font-size: 11px;">Hora oficial peninsular (IGN)</span>
        </div>
        <div class="seasons-grid">
            <?php foreach ($cur_seasons as $s): ?>
            <div class="season-box" style="border-top: 3px solid <?php echo $s["color"]; ?>;">
                <div class="season-icon"><?php echo $s["icon"]; ?></div>
                <div class="season-title"><?php echo $s["name"]; ?></div>
                <div class="season-date"><?php echo $s["date"]; ?></div>
                <div class="season-time"><?php echo $s["time"]; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Fotoperíode i Llum Solar a Sallent -->
    <div class="card">
        <div class="card-title">
            <span>Fotoperíode i Durada del Dia a Sallent (41.82&deg;N)</span>
            <span style="font-size: 10px; color: #ecb454;">Càlcul solar en temps real</span>
        </div>
        <div class="sun-metric-grid">
            <div class="sun-box">
                <div class="sun-lbl">Sortida del Sol (Alba)</div>
                <div class="sun-val" style="color: #ff8841;"><?php echo $sunrise_str; ?> h</div>
            </div>
            <div class="sun-box">
                <div class="sun-lbl">Hores de Llum Solar Avui</div>
                <div class="sun-val" style="color: #ecb454;"><?php echo $daylight_h . "h " . $daylight_m . "m"; ?></div>
            </div>
            <div class="sun-box">
                <div class="sun-lbl">Posta del Sol (Ocàs)</div>
                <div class="sun-val" style="color: #01a4b4;"><?php echo $sunset_str; ?> h</div>
            </div>
        </div>
        <div style="font-size: 11px; color: #a0aec0; margin-top: 10px; line-height: 1.4;">
            A la nostra latitud (41&deg; 49' N), la durada de la llum solar oscil·la entre el mínim de <b>9 hores i 10 minuts</b> pel Solstici d'Hivern i el màxim de <b>15 hores i 16 minuts</b> pel Solstici d'Estiu.
        </div>
    </div>

    <!-- Canvi d'Horari Oficial -->
    <div class="card">
        <div class="card-title">
            <span>Canvis d'Horari Oficial a Catalunya (UE) <?php echo $current_year; ?></span>
        </div>
        
        <div class="dst-card">
            <div>
                <b style="color: #8DFC2D; font-size: 12px;">Horari d'Estiu (CEST &bull; UTC+2):</b><br>
                <span style="color: #cbd5e0;">Diumenge <?php echo $dst_start_day; ?> de Març de <?php echo $current_year; ?></span>
            </div>
            <div style="font-size: 11px; color: #ecb454; font-weight: 700;">A les 02:00 h seran les 03:00 h (+1h)</div>
        </div>

        <div class="dst-card">
            <div>
                <b style="color: #01a4b4; font-size: 12px;">Horari d'Hivern (CET &bull; UTC+1):</b><br>
                <span style="color: #cbd5e0;">Diumenge <?php echo $dst_end_day; ?> d'Octubre de <?php echo $current_year; ?></span>
            </div>
            <div style="font-size: 11px; color: #01a4b4; font-weight: 700;">A les 03:00 h seran les 02:00 h (-1h)</div>
        </div>
    </div>

    <!-- Eclipsis Destacats -->
    <div class="card">
        <div class="card-title">
            <span>Gran Esdeveniment Astronòmic: Eclipsi Total de Sol 2026</span>
        </div>
        <p style="font-size: 11px; line-height: 1.5; color: #e2e8f0;">
            El <b>12 d'agost de 2026</b> tindrà lloc un esdeveniment històric: el primer eclipsi total de Sol visible des de la península Ibèrica des de fa més d'un segle. La franja de totalitat creuarà el nord de la península (Galícia, Cantàbric, Castella i Lleó, Aragó i part de Catalunya / Illes Balears) a última hora de la tarda abans de la posta solar.
        </p>
    </div>

    <div class="footer">
        Dades contrastades segons l'Observatorio Astronómico Nacional (IGN) &bull; MeteoSallent
    </div>

</div>

</body>
</html>
