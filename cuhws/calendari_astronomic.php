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

// Mesos en català
$mesos_cat = [
    1 => "Gener", 2 => "Febrer", 3 => "Març", 4 => "Abril", 5 => "Maig", 6 => "Juny",
    7 => "Juliol", 8 => "Agost", 9 => "Setembre", 10 => "Octubre", 11 => "Novembre", 12 => "Desembre"
];

// Algorisme astronòmic de Jean Meeus per calcular equinoccis i solsticis per a qualsevol any
function getMeeusSeasonDetail($year, $season) {
    global $mesos_cat;
    $m = ($year - 2000) / 1000;
    switch ($season) {
        case "spring":
            $jde = 2451623.80984 + 365242.37404 * $m + 0.05169 * pow($m, 2) - 0.00411 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "date" => date("j", $ts) . " " . $mesos_cat[intval(date("n", $ts))] . " " . $year,
                "time" => date("H:i", $ts) . " h",
                "name" => "Primavera (Equinocci)",
                "icon" => "🌱",
                "color" => "#8DFC2D",
                "ts" => $ts
            ];
        case "summer":
            $jde = 2451716.56767 + 365241.62603 * $m + 0.00325 * pow($m, 2) + 0.00888 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "date" => date("j", $ts) . " " . $mesos_cat[intval(date("n", $ts))] . " " . $year,
                "time" => date("H:i", $ts) . " h",
                "name" => "Estiu (Solstici)",
                "icon" => "☀️",
                "color" => "#ecb454",
                "ts" => $ts
            ];
        case "autumn":
            $jde = 2451810.21715 + 365242.01767 * $m - 0.11575 * pow($m, 2) + 0.00337 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "date" => date("j", $ts) . " " . $mesos_cat[intval(date("n", $ts))] . " " . $year,
                "time" => date("H:i", $ts) . " h",
                "name" => "Tardor (Equinocci)",
                "icon" => "🍂",
                "color" => "#ff8841",
                "ts" => $ts
            ];
        case "winter":
            $jde = 2451900.05952 + 365242.74049 * $m - 0.06223 * pow($m, 2) - 0.05235 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "date" => date("j", $ts) . " " . $mesos_cat[intval(date("n", $ts))] . " " . $year,
                "time" => date("H:i", $ts) . " h",
                "name" => "Hivern (Solstici)",
                "icon" => "❄️",
                "color" => "#01a4b4",
                "ts" => $ts
            ];
    }
}

// Efemèrides de canvi d'estació oficials (IGN / Observatori Astronòmic Nacional) 2026-2035
$seasons_ign = [
    2026 => [
        "spring" => ["date" => "20 Març 2026", "time" => "15:46 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2026-03-20 15:46:00 CET")],
        "summer" => ["date" => "21 Juny 2026", "time" => "09:24 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2026-06-21 09:24:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2026", "time" => "02:05 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2026-09-23 02:05:00 CEST")],
        "winter" => ["date" => "21 Desembre 2026", "time" => "21:50 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2026-12-21 21:50:00 CET")]
    ],
    2027 => [
        "spring" => ["date" => "20 Març 2027", "time" => "21:25 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2027-03-20 21:25:00 CET")],
        "summer" => ["date" => "21 Juny 2027", "time" => "15:11 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2027-06-21 15:11:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2027", "time" => "08:02 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2027-09-23 08:02:00 CEST")],
        "winter" => ["date" => "22 Desembre 2027", "time" => "03:42 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2027-12-22 03:42:00 CET")]
    ],
    2028 => [
        "spring" => ["date" => "20 Març 2028", "time" => "03:17 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2028-03-20 03:17:00 CET")],
        "summer" => ["date" => "20 Juny 2028", "time" => "21:02 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2028-06-20 21:02:00 CEST")],
        "autumn" => ["date" => "22 Setembre 2028", "time" => "13:45 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2028-09-22 13:45:00 CEST")],
        "winter" => ["date" => "21 Desembre 2028", "time" => "09:20 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2028-12-21 09:20:00 CET")]
    ],
    2029 => [
        "spring" => ["date" => "20 Març 2029", "time" => "09:02 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2029-03-20 09:02:00 CET")],
        "summer" => ["date" => "21 Juny 2029", "time" => "02:48 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2029-06-21 02:48:00 CEST")],
        "autumn" => ["date" => "22 Setembre 2029", "time" => "19:38 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2029-09-22 19:38:00 CEST")],
        "winter" => ["date" => "21 Desembre 2029", "time" => "15:14 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2029-12-21 15:14:00 CET")]
    ],
    2030 => [
        "spring" => ["date" => "20 Març 2030", "time" => "14:52 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2030-03-20 14:52:00 CET")],
        "summer" => ["date" => "21 Juny 2030", "time" => "08:31 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2030-06-21 08:31:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2030", "time" => "01:27 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2030-09-23 01:27:00 CEST")],
        "winter" => ["date" => "21 Desembre 2030", "time" => "21:09 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2030-12-21 21:09:00 CET")]
    ],
    2031 => [
        "spring" => ["date" => "20 Març 2031", "time" => "20:41 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2031-03-20 20:41:00 CET")],
        "summer" => ["date" => "21 Juny 2031", "time" => "14:28 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2031-06-21 14:28:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2031", "time" => "07:15 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2031-09-23 07:15:00 CEST")],
        "winter" => ["date" => "22 Desembre 2031", "time" => "02:55 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2031-12-22 02:55:00 CET")]
    ],
    2032 => [
        "spring" => ["date" => "20 Març 2032", "time" => "02:21 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2032-03-20 02:21:00 CET")],
        "summer" => ["date" => "20 Juny 2032", "time" => "20:08 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2032-06-20 20:08:00 CEST")],
        "autumn" => ["date" => "22 Setembre 2032", "time" => "13:10 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2032-09-22 13:10:00 CEST")],
        "winter" => ["date" => "21 Desembre 2032", "time" => "08:56 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2032-12-21 08:56:00 CET")]
    ],
    2033 => [
        "spring" => ["date" => "20 Març 2033", "time" => "08:22 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2033-03-20 08:22:00 CET")],
        "summer" => ["date" => "21 Juny 2033", "time" => "02:01 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2033-06-21 02:01:00 CEST")],
        "autumn" => ["date" => "22 Setembre 2033", "time" => "18:52 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2033-09-22 18:52:00 CEST")],
        "winter" => ["date" => "21 Desembre 2033", "time" => "14:45 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2033-12-21 14:45:00 CET")]
    ],
    2034 => [
        "spring" => ["date" => "20 Març 2034", "time" => "14:17 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2034-03-20 14:17:00 CET")],
        "summer" => ["date" => "21 Juny 2034", "time" => "07:44 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2034-06-21 07:44:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2034", "time" => "00:40 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2034-09-23 00:40:00 CEST")],
        "winter" => ["date" => "21 Desembre 2034", "time" => "20:34 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2034-12-21 20:34:00 CET")]
    ],
    2035 => [
        "spring" => ["date" => "20 Març 2035", "time" => "20:02 h", "name" => "Primavera (Equinocci)", "icon" => "🌱", "color" => "#8DFC2D", "ts" => strtotime("2035-03-20 20:02:00 CET")],
        "summer" => ["date" => "21 Juny 2035", "time" => "13:33 h", "name" => "Estiu (Solstici)", "icon" => "☀️", "color" => "#ecb454", "ts" => strtotime("2035-06-21 13:33:00 CEST")],
        "autumn" => ["date" => "23 Setembre 2035", "time" => "06:38 h", "name" => "Tardor (Equinocci)", "icon" => "🍂", "color" => "#ff8841", "ts" => strtotime("2035-09-23 06:38:00 CEST")],
        "winter" => ["date" => "22 Desembre 2035", "time" => "02:30 h", "name" => "Hivern (Solstici)", "icon" => "❄️", "color" => "#01a4b4", "ts" => strtotime("2035-12-22 02:30:00 CET")]
    ]
];

if (isset($seasons_ign[$current_year])) {
    $cur_seasons = $seasons_ign[$current_year];
} else {
    $cur_seasons = [
        "spring" => getMeeusSeasonDetail($current_year, "spring"),
        "summer" => getMeeusSeasonDetail($current_year, "summer"),
        "autumn" => getMeeusSeasonDetail($current_year, "autumn"),
        "winter" => getMeeusSeasonDetail($current_year, "winter")
    ];
}

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
            <span>Gran Esdeveniment Astronòmic: Eclipsi Total de Sol (12 d'Agost de 2026)</span>
        </div>
        <p style="font-size: 11px; line-height: 1.5; color: #e2e8f0;">
            <?php if ($now < strtotime("2026-08-12 21:00:00 CEST")): ?>
            El <b>12 d'agost de 2026</b> tindrà lloc un esdeveniment històric: el primer eclipsi total de Sol visible des de la península Ibèrica des de fa més d'un segle. La franja de totalitat creuarà el nord de la península (Galícia, Cantàbric, Castella i Lleó, Aragó i part de Catalunya / Illes Balears) a última hora de la tarda abans de la posta solar.
            <?php else: ?>
            El <b>12 d'agost de 2026</b> va tenir lloc un esdeveniment històric: el primer eclipsi total de Sol visible des de la península Ibèrica en més d'un segle. La franja de totalitat va creuar el nord de la península (Galícia, Cantàbric, Castella i Lleó, Aragó i part de Catalunya / Illes Balears) a última hora de la tarda abans de la posta solar.
            <?php endif; ?>
        </p>
    </div>

    <div class="footer">
        Dades contrastades segons l'Observatorio Astronómico Nacional (IGN) &bull; MeteoSallent
    </div>

</div>

</body>
</html>
