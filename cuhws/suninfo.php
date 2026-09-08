<?php
include_once("settings.php");
include_once("common.php");
include_once("livedata.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");
date_default_timezone_set($TZ);

$now = time();

// Càlcul d'efemèrides solars de Sallent
$sun_info = date_sun_info($now, $lat, $lon);
$sunrise_ts = $sun_info['sunrise'];
$sunset_ts  = $sun_info['sunset'];
$transit_ts = $sun_info['transit'];
$civil_start = $sun_info['civil_twilight_begin'];
$civil_end   = $sun_info['civil_twilight_end'];
$naut_start  = $sun_info['nautical_twilight_begin'];
$naut_end    = $sun_info['nautical_twilight_end'];
$astro_start = $sun_info['astronomical_twilight_begin'];
$astro_end   = $sun_info['astronomical_twilight_end'];

// Durada de la llum i foscor
$daylight_sec = max(0, $sunset_ts - $sunrise_ts);
$daylight_h = floor($daylight_sec / 3600);
$daylight_m = floor(($daylight_sec % 3600) / 60);

$darkness_sec = 86400 - $daylight_sec;
$darkness_h = floor($darkness_sec / 3600);
$darkness_m = floor(($darkness_sec % 3600) / 60);

$is_day = ($now >= $sunrise_ts && $now < $sunset_ts);

// Càlcul precís de coordenades solars (Elevació i Azimut)
function calc_solar_coords($timestamp, $latitude, $longitude) {
    $rad = M_PI / 180.0;
    $day_of_year = date("z", $timestamp);
    $hour = date("G", $timestamp) + (date("i", $timestamp)/60.0) + (date("s", $timestamp)/3600.0);
    $gamma = 2.0 * M_PI / 365.0 * ($day_of_year - 1 + ($hour - 12.0) / 24.0);
    
    $eqtime = 229.18 * (0.000075 + 0.001868 * cos($gamma) - 0.032077 * sin($gamma) - 0.014615 * cos(2 * $gamma) - 0.040849 * sin(2 * $gamma));
    $decl = 0.006918 - 0.399912 * cos($gamma) + 0.070257 * sin($gamma) - 0.006758 * cos(2 * $gamma) + 0.000907 * sin(2 * $gamma) - 0.002697 * cos(3 * $gamma) + 0.00148 * sin(3 * $gamma);
    
    $tz_offset = date("Z", $timestamp) / 60.0;
    $time_offset = $eqtime + 4.0 * $longitude - $tz_offset;
    $tst = $hour * 60.0 + $time_offset;
    $ha = ($tst / 4.0) - 180.0;
    $ha_rad = $ha * $rad;
    $lat_rad = $latitude * $rad;
    
    $cos_zenith = sin($lat_rad) * sin($decl) + cos($lat_rad) * cos($decl) * cos($ha_rad);
    $zenith = acos(max(-1.0, min(1.0, $cos_zenith)));
    $elevation = 90.0 - ($zenith / $rad);
    
    $sin_zenith = sin($zenith);
    if ($sin_zenith != 0) {
        $cos_az = (sin($decl) - cos($zenith) * sin($lat_rad)) / ($sin_zenith * cos($lat_rad));
        $azimuth = acos(max(-1.0, min(1.0, $cos_az))) / $rad;
        if ($ha > 0) {
            $azimuth = 360.0 - $azimuth;
        }
    } else {
        $azimuth = 180.0;
    }
    
    $dirs = ["Nord (N)", "Nord-Nord-Est (NNE)", "Nord-Est (NE)", "Est-Nord-Est (ENE)", "Est (E)", "Est-Sud-Est (ESE)", "Sud-Est (SE)", "Sud-Sud-Est (SSE)", "Sud (S)", "Sud-Sud-Oest (SSW)", "Sud-Oest (SW)", "Oest-Sud-Oest (WSW)", "Oest (W)", "Oest-Nord-Oest (WNW)", "Nord-Oest (NW)", "Nord-Nord-Oest (NNW)"];
    $cardinal = $dirs[round($azimuth / 22.5) % 16];
    
    return [
        "elevation" => round($elevation, 1),
        "azimuth"   => round($azimuth, 1),
        "cardinal"  => $cardinal,
        "declination" => round($decl / $rad, 2)
    ];
}

$solar_now  = calc_solar_coords($now, $lat, $lon);
$solar_noon = calc_solar_coords($transit_ts, $lat, $lon);

// Dades de l'estació Davis Vantage Pro2
$solar_rad = isset($weather["solar"]) ? floatval($weather["solar"]) : 0;
$solar_uv  = isset($weather["uv"]) ? floatval($weather["uv"]) : 0.0;
$solar_lux = isset($weather["lux"]) ? intval($weather["lux"]) : 0;
$solar_max = isset($weather["maxsolar"]) ? floatval($weather["maxsolar"]) : 0;
$sunshine_h = isset($weather["sunshine"]) ? floatval($weather["sunshine"]) : 0.0;

// Recomanació UV OMS
if ($solar_uv >= 11) {
    $uv_label = "Extrem"; $uv_color = "#b5179e"; $uv_desc = "Protecció extrema necessària: evita l'exposició solar!";
} elseif ($solar_uv >= 8) {
    $uv_label = "Molt Alt"; $uv_color = "#ef233c"; $uv_desc = "Risc molt alt: barret, ulleres, crema FPS 50+ i cerca l'ombra.";
} elseif ($solar_uv >= 6) {
    $uv_label = "Alt"; $uv_color = "#f77f00"; $uv_desc = "Risc alt: imprescindible crema solar, protecció ocular i samarreta.";
} elseif ($solar_uv >= 3) {
    $uv_label = "Moderat"; $uv_color = "#fcbf49"; $uv_desc = "Risc moderat: es recomana protecció a les hores centrals.";
} else {
    $uv_label = "Baix"; $uv_color = "#48cae4"; $uv_desc = "Risc baix: no cal protecció especial per a activitats a l'aire lliure.";
}

// Percentatge de progrés del dia
if ($now < $sunrise_ts) {
    $day_progress = 0;
    $state_desc = "Nit &bull; Previ a la sortida del Sol";
} elseif ($now > $sunset_ts) {
    $day_progress = 100;
    $state_desc = "Nit &bull; Sol post";
} else {
    $day_progress = round((($now - $sunrise_ts) / $daylight_sec) * 100);
    $state_desc = "Dia en curs &bull; Sol radiant sobre l'horitzó";
}

// Càlcul compte enrere per a l'Equinocci de Tardor (~22 de setembre de 2026 a les 20:05 UTC)
$autumn_equinox_ts = gmmktime(20, 5, 0, 9, 22, 2026);
$equinox_diff_sec = $autumn_equinox_ts - $now;
$equinox_days = max(0, floor($equinox_diff_sec / 86400));
$equinox_hours = max(0, floor(($equinox_diff_sec % 86400) / 3600));
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Astronomia i Dades Solars - MeteoSallent</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #181b1f;
    color: #cbd5e0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 12px;
    padding: 0 0 20px 0;
    overflow-x: hidden;
    overflow-y: auto;
}
.weather34darkbrowser {
    position: relative;
    width: 100%;
    height: 32px;
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
    max-width: 680px;
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
.hero-sun {
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: 16px;
    flex-wrap: wrap;
    padding: 4px 0;
}
.hero-disc {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #fff275 0%, #ff8c00 70%, #d9381e 100%);
    box-shadow: 0 0 25px rgba(255, 165, 0, 0.65), 0 0 50px rgba(255, 140, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
}
.hero-info {
    flex: 1;
    min-width: 220px;
}
.hero-name {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.hero-sub {
    font-size: 12px;
    color: #cbd5e0;
    line-height: 1.5;
}
.kpi-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-top: 10px;
}
.kpi-box {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 6px;
    padding: 8px 10px;
    text-align: center;
}
.kpi-label {
    font-size: 10.5px;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 3px;
}
.kpi-val {
    font-size: 16px;
    font-weight: 800;
    color: #f1f5f9;
}
.kpi-sub {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
}
.badge-solar {
    display: inline-block;
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid #f59e0b;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 11px;
    margin-top: 6px;
}
.progress-bar-bg {
    width: 100%;
    height: 9px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    overflow: hidden;
    margin: 8px 0 4px;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #ef4444);
    border-radius: 6px;
    transition: width 0.4s ease;
}
.timeline-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.timeline-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-left: 3px solid #f59e0b;
    border-radius: 5px;
    padding: 8px 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.timeline-name {
    font-size: 11.5px;
    font-weight: 600;
    color: #e2e8f0;
}
.timeline-sub {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 1px;
}
.timeline-time {
    font-size: 14px;
    font-weight: 800;
    color: #fbbf24;
    text-align: right;
}
.sdo-container {
    text-align: center;
    position: relative;
    padding-top: 6px;
}
.sdo-tabs {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}
.sdo-tab-btn {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #cbd5e0;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sdo-tab-btn.active, .sdo-tab-btn:hover {
    background: #f59e0b;
    color: #000;
    border-color: #f59e0b;
}
.sdo-img-wrapper {
    position: relative;
    display: inline-block;
    max-width: 100%;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
}
.sdo-img-wrapper img {
    display: block;
    width: 100%;
    max-width: 440px;
    height: auto;
    margin: 0 auto;
}
.sdo-caption {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 8px;
    line-height: 1.4;
}
.footer {
    text-align: center;
    font-size: 11px;
    color: #64748b;
    margin-top: 6px;
}
@media (max-width: 580px) {
    .kpi-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    .timeline-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="weather34darkbrowser" url="Observatori Solar de Sallent &bull; Efemèrides i Astronomia"></div>

<div class="container">

    <!-- Card 1: Resum i Radiometria Solar de l'Estació -->
    <div class="card">
        <div class="card-title">
            <span>Estat Solar i Telemetria Davis Vantage Pro2</span>
            <span style="font-size: 11px; color: #fbbf24;"><?php echo date('d/m/Y H:i'); ?></span>
        </div>
        
        <div class="hero-sun">
            <div class="hero-disc">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                    <circle cx="12" cy="12" r="5"></circle>
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                </svg>
            </div>
            
            <div class="hero-info">
                <div class="hero-name">
                    Sol a Sallent
                    <span style="font-size: 12px; font-weight: normal; color: #a0aec0;">(El Bages, 336m)</span>
                </div>
                <div class="hero-sub">
                    <b>Estat:</b> <?php echo $state_desc; ?><br>
                    <b>Alba / Posta:</b> <?php echo date('H:i', $sunrise_ts); ?> / <?php echo date('H:i', $sunset_ts); ?> (<?php echo "$daylight_h h $daylight_m m de llum"; ?>)<br>
                    <b>Índex UV:</b> <span style="font-weight: 700; color: <?php echo $uv_color; ?>;"><?php echo $solar_uv; ?> - <?php echo $uv_label; ?></span> (<?php echo $uv_desc; ?>)
                </div>
                <div>
                    <span class="badge-solar">&#9728; Sensor Davis VP2 actiu</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 14px;">
            <div style="display: flex; justify-content: space-between; font-size: 10.5px; color: #94a3b8; font-weight: 600;">
                <span>Sortida: <?php echo date('H:i', $sunrise_ts); ?></span>
                <span>Progrés diürn: <?php echo $day_progress; ?>%</span>
                <span>Posta: <?php echo date('H:i', $sunset_ts); ?></span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $day_progress; ?>%;"></div>
            </div>
        </div>

        <div class="kpi-grid-4">
            <div class="kpi-box">
                <div class="kpi-label">Radiació Solar</div>
                <div class="kpi-val" style="color: #fbbf24;"><?php echo round($solar_rad); ?> <span style="font-size: 11px;">W/m²</span></div>
                <div class="kpi-sub">Sensor Davis VP2+</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Índex UV</div>
                <div class="kpi-val" style="color: <?php echo $uv_color; ?>;"><?php echo number_format($solar_uv, 1); ?></div>
                <div class="kpi-sub"><?php echo $uv_label; ?></div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Il·luminació</div>
                <div class="kpi-val" style="color: #38bdf8;"><?php echo number_format($solar_lux); ?> <span style="font-size: 11px;">Lux</span></div>
                <div class="kpi-sub">Llum ambiental</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Insolació</div>
                <div class="kpi-val" style="color: #4ade80;"><?php echo number_format($sunshine_h, 1); ?> <span style="font-size: 11px;">h</span></div>
                <div class="kpi-sub">Sol efectiu avui</div>
            </div>
        </div>
    </div>

    <!-- Card 2: Coordenades i Posició Solar en Temps Real -->
    <div class="card">
        <div class="card-title">
            <span>Posició Astronòmica Solar (Sallent &bull; Lat 41.817&deg; Lon 1.895&deg;)</span>
            <span style="font-size: 10px; color: #38bdf8;">Càlcul natiu en viu</span>
        </div>

        <div class="kpi-grid-4">
            <div class="kpi-box">
                <div class="kpi-label">Azimut</div>
                <div class="kpi-val" style="color: #38bdf8;"><?php echo $solar_now['azimuth']; ?>&deg;</div>
                <div class="kpi-sub"><?php echo $solar_now['cardinal']; ?></div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Altitud / Elevació</div>
                <div class="kpi-val" style="color: <?php echo $solar_now['elevation'] > 0 ? '#fbbf24' : '#64748b'; ?>;">
                    <?php echo ($solar_now['elevation'] > 0 ? '+' : '') . $solar_now['elevation']; ?>&deg;
                </div>
                <div class="kpi-sub"><?php echo $solar_now['elevation'] > 0 ? "Sobre l'horitzó" : "Sota l'horitzó"; ?></div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Migdia Solar (Zenit)</div>
                <div class="kpi-val" style="color: #fb923c;"><?php echo date('H:i', $transit_ts); ?></div>
                <div class="kpi-sub">Màx: +<?php echo $solar_noon['elevation']; ?>&deg; (Sud)</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-label">Declinació Solar</div>
                <div class="kpi-val" style="color: #e2e8f0;"><?php echo ($solar_now['declination'] > 0 ? '+' : '') . $solar_now['declination']; ?>&deg;</div>
                <div class="kpi-sub">Angle axial terrestre</div>
            </div>
        </div>
    </div>

    <!-- Card 3: Efemèrides de Crepuscles i Llum Diürna -->
    <div class="card">
        <div class="card-title">
            <span>Cronograma Crepuscular d'Avui (Sallent)</span>
            <span style="font-size: 10px; color: #a0aec0;">Horari Oficial CEST</span>
        </div>

        <div class="timeline-grid">
            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Alba Astronòmica</div>
                    <div class="timeline-sub">Primer indici de llum celeste (Sol a -18&deg;)</div>
                </div>
                <div class="timeline-time"><?php echo date('H:i', $astro_start); ?></div>
            </div>

            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Alba Nàutica</div>
                    <div class="timeline-sub">Línia de l'horitzó perceptible (Sol a -12&deg;)</div>
                </div>
                <div class="timeline-time"><?php echo date('H:i', $naut_start); ?></div>
            </div>

            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Crepuscle Civil Matinal</div>
                    <div class="timeline-sub">Visibilitat a l'exterior sense enllumenat</div>
                </div>
                <div class="timeline-time"><?php echo date('H:i', $civil_start); ?></div>
            </div>

            <div class="timeline-item" style="border-left-color: #eab308; background: rgba(234, 179, 8, 0.08);">
                <div>
                    <div class="timeline-name" style="color: #fde047;">Sortida del Sol</div>
                    <div class="timeline-sub">El limbe solar creua l'horitzó est</div>
                </div>
                <div class="timeline-time" style="color: #fde047;"><?php echo date('H:i', $sunrise_ts); ?></div>
            </div>

            <div class="timeline-item" style="border-left-color: #f97316; background: rgba(249, 115, 22, 0.08);">
                <div>
                    <div class="timeline-name" style="color: #fdba74;">Migdia Solar (Trànsit)</div>
                    <div class="timeline-sub">Sol en el punt més alt cap al Sud</div>
                </div>
                <div class="timeline-time" style="color: #fdba74;"><?php echo date('H:i', $transit_ts); ?></div>
            </div>

            <div class="timeline-item" style="border-left-color: #eab308; background: rgba(234, 179, 8, 0.08);">
                <div>
                    <div class="timeline-name" style="color: #fde047;">Posta del Sol</div>
                    <div class="timeline-sub">El Sol desapareix sota l'horitzó oest</div>
                </div>
                <div class="timeline-time" style="color: #fde047;"><?php echo date('H:i', $sunset_ts); ?></div>
            </div>

            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Crepuscle Civil Vespertí</div>
                    <div class="timeline-sub">Encesa d'enllumenat públic urbà</div>
                </div>
                <div class="timeline-time"><?php echo date('H:i', $civil_end); ?></div>
            </div>

            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Crepuscle Nàutic</div>
                    <div class="timeline-sub">Aparició dels principals estels de navegació</div>
                </div>
                <div class="timeline-time"><?php echo date('H:i', $naut_end); ?></div>
            </div>

            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Fi Crepuscle Astronòmic</div>
                    <div class="timeline-sub">Inici de la nit profunda astronòmica</div>
                </div>
                <div class="timeline-time"><?php echo date('H:i', $astro_end); ?></div>
            </div>

            <div class="timeline-item">
                <div>
                    <div class="timeline-name">Hores de Foscor</div>
                    <div class="timeline-sub">Total període nocturn</div>
                </div>
                <div class="timeline-time" style="color: #94a3b8;"><?php echo "$darkness_h h $darkness_m m"; ?></div>
            </div>
        </div>
    </div>

    <!-- Card 4: Imatge en Directe del Disc Solar - NASA SDO -->
    <div class="card">
        <div class="card-title">
            <span>Telescopi Espacial SDO de la NASA &bull; Disc Solar en Directe</span>
            <span style="font-size: 10px; color: #f59e0b;">NASA Solar Dynamics Observatory</span>
        </div>

        <div class="sdo-container">
            <div class="sdo-tabs">
                <button type="button" class="sdo-tab-btn active" onclick="switchSdo('hmi', this)">Fotosfera Visible (Taques Solars)</button>
                <button type="button" class="sdo-tab-btn" onclick="switchSdo('193', this)">Corona Ultraviolada (193 &Aring;)</button>
                <button type="button" class="sdo-tab-btn" onclick="switchSdo('304', this)">Cromosfera i Erupcions (304 &Aring;)</button>
            </div>

            <div class="sdo-img-wrapper">
                <img id="sdoImage" src="https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_HMIIC.jpg" alt="Disc Solar NASA SDO" onerror="this.src='img/sun_placeholder.png';">
            </div>

            <div class="sdo-caption" id="sdoCaption">
                <b>Fotosfera en Llum Visible (HMI Intensitygram Continuum):</b> Imatge actual de la superfície solar capturada pel satèl·lit SDO. Permet observar directament les taques solars (regions més fredes i magnèticament intenses) i fàcules actives en temps real.
            </div>
        </div>
    </div>

    <!-- Card 5: Temps Espacial i Cicle Solar 25 -->
    <div class="card">
        <div class="card-title">
            <span>Activitat Solar i Cicle 25</span>
            <span style="font-size: 10px; color: #a855f7;">Màxim Solar 2024 - 2026</span>
        </div>

        <div style="font-size: 11.5px; line-height: 1.6; color: #cbd5e1;">
            <p style="margin-bottom: 8px;">
                El Sol segueix un cicle d'activitat magnètica d'aproximadament 11 anys. Actualment ens trobem en el <b>Cicle Solar 25</b>, en plena fase de <b>Màxim Solar</b>. Això es tradueix en un nombre elevat de taques solars, ejeccions de massa coronal (CME) i tempestes geomagnètiques capaços de generar espectaculars aurores boreals a latituds inusuals, inclosa Catalunya.
            </p>
            <div style="background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 6px; padding: 10px 12px; margin-top: 6px;">
                <b style="color: #c084fc;">🍂 Proper Canvi d'Estació: Equinocci de Tardor 2026</b><br>
                Tindrà lloc el <b>22 de setembre de 2026</b> a les <b>22:05 h CEST</b> (20:05 UTC).
                <span style="color: #fbbf24; font-weight: 700;">Resten només <?php echo $equinox_days; ?> dies i <?php echo $equinox_hours; ?> hores!</span>
                En aquest moment precís, el Sol creua l'equador celeste cap a l'hemisferi sud, donant lloc a un dia i una nit de pràcticament idèntica durada arreu del planeta Terra.
            </div>
        </div>
    </div>

    <div class="footer">
        Observatori Meteorològic i Astronòmic de Sallent (El Bages) &bull; Dades Davis Vantage Pro2 Plus &bull; NASA SDO
    </div>

</div>

<script>
var sdoData = {
    'hmi': {
        'url': 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_HMIIC.jpg',
        'caption': '<b>Fotosfera en Llum Visible (HMI Intensitygram Continuum):</b> Imatge actual de la superfície solar capturada pel satèl·lit SDO. Permet observar directament les taques solars (regions més fredes i magnèticament intenses) i fàcules actives en temps real.'
    },
    '193': {
        'url': 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_0193.jpg',
        'caption': '<b>Corona Ultraviolada Extrema (AIA 193 &Aring;):</b> Revela l\'atmosfera solar a 1.250.000 Kelvin. Les zones fosques són forats coronals des d\'on emana vent solar ràpid cap a la Terra, i les regions brillants són arcs magnètics actius.'
    },
    '304': {
        'url': 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_0304.jpg',
        'caption': '<b>Cromosfera i Erupcions (AIA 304 &Aring;):</b> Mostra plasma d\'heli ionitzat a 50.000 Kelvin. Ideal per detectar grans protuberàncies, filaments foscos i ejeccions violentes a la vora del disc solar.'
    }
};

function switchSdo(type, btn) {
    var img = document.getElementById('sdoImage');
    var cap = document.getElementById('sdoCaption');
    if (!img || !cap || !sdoData[type]) return;
    
    var buttons = document.querySelectorAll('.sdo-tab-btn');
    buttons.forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
    
    img.src = sdoData[type].url + '?t=' + new Date().getTime();
    cap.innerHTML = sdoData[type].caption;
}
</script>

</body>
</html>
