<?php
include_once("settings.php");
include_once("common.php");
include_once("livedata.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");
date_default_timezone_set($TZ);

$now = time();
$current_year = intval(date("Y", $now));

// Catàleg anual dinàmic de pluges d'estels per a Catalunya
$raw_showers = [
    [
        "id" => "quadrantids",
        "name" => "Quadràntids",
        "start_m" => 12, "start_d" => 28,
        "peak_m" => 1, "peak_d" => 3, "peak_end_d" => 4,
        "end_m" => 1, "end_d" => 12,
        "zhr" => 120,
        "radiant" => "Boötes (El Bover)",
        "parent" => "Asteroide 2003 EH1",
        "color" => "#01a4b4"
    ],
    [
        "id" => "lyrids",
        "name" => "Lírids",
        "start_m" => 4, "start_d" => 16,
        "peak_m" => 4, "peak_d" => 22, "peak_end_d" => 23,
        "end_m" => 4, "end_d" => 25,
        "zhr" => 18,
        "radiant" => "Lira (Vega)",
        "parent" => "Cometa Thatcher (C/1861 G1)",
        "color" => "#9aba2f"
    ],
    [
        "id" => "eta_aquariids",
        "name" => "Eta Aquàrids",
        "start_m" => 4, "start_d" => 19,
        "peak_m" => 5, "peak_d" => 5, "peak_end_d" => 6,
        "end_m" => 5, "end_d" => 28,
        "zhr" => 50,
        "radiant" => "Aquari",
        "parent" => "Cometa 1P/Halley",
        "color" => "#01a4b4"
    ],
    [
        "id" => "delta_aquariids",
        "name" => "Delta Aquàrids del Sud",
        "start_m" => 7, "start_d" => 12,
        "peak_m" => 7, "peak_d" => 29, "peak_end_d" => 30,
        "end_m" => 8, "end_d" => 23,
        "zhr" => 25,
        "radiant" => "Aquari",
        "parent" => "Cometa 96P/Machholz",
        "color" => "#01a4b4"
    ],
    [
        "id" => "perseids",
        "name" => "Perseids (Llàgrimes de St. Llorenç)",
        "start_m" => 7, "start_d" => 17,
        "peak_m" => 8, "peak_d" => 12, "peak_end_d" => 13,
        "end_m" => 8, "end_d" => 24,
        "zhr" => 120,
        "radiant" => "Perseu",
        "parent" => "Cometa 109P/Swift-Tuttle",
        "color" => "#ff8841"
    ],
    [
        "id" => "draconids",
        "name" => "Dracònids",
        "start_m" => 10, "start_d" => 6,
        "peak_m" => 10, "peak_d" => 8, "peak_end_d" => 9,
        "end_m" => 10, "end_d" => 10,
        "zhr" => 10,
        "radiant" => "Dragó",
        "parent" => "Cometa 21P/Giacobini-Zinner",
        "color" => "#ecb454"
    ],
    [
        "id" => "orionids",
        "name" => "Oriònids",
        "start_m" => 10, "start_d" => 2,
        "peak_m" => 10, "peak_d" => 21, "peak_end_d" => 22,
        "end_m" => 11, "end_d" => 7,
        "zhr" => 25,
        "radiant" => "Orió",
        "parent" => "Cometa 1P/Halley",
        "color" => "#8DFC2D"
    ],
    [
        "id" => "taurids",
        "name" => "Tàurids del Sud i Nord",
        "start_m" => 9, "start_d" => 20,
        "peak_m" => 11, "peak_d" => 5, "peak_end_d" => 12,
        "end_m" => 12, "end_d" => 10,
        "zhr" => 10,
        "radiant" => "Taure (Boles de foc)",
        "parent" => "Cometa 2P/Encke",
        "color" => "#f37867"
    ],
    [
        "id" => "leonids",
        "name" => "Leònids",
        "start_m" => 11, "start_d" => 6,
        "peak_m" => 11, "peak_d" => 17, "peak_end_d" => 18,
        "end_m" => 11, "end_d" => 30,
        "zhr" => 15,
        "radiant" => "Lleó",
        "parent" => "Cometa 55P/Tempel-Tuttle",
        "color" => "#9aba2f"
    ],
    [
        "id" => "geminids",
        "name" => "Gemínids",
        "start_m" => 12, "start_d" => 4,
        "peak_m" => 12, "peak_d" => 13, "peak_end_d" => 14,
        "end_m" => 12, "end_d" => 20,
        "zhr" => 140,
        "radiant" => "Bessons (Castor/Pòl·lux)",
        "parent" => "Asteroide 3200 Phaethon",
        "color" => "#ecb454"
    ],
    [
        "id" => "ursids",
        "name" => "Úrsids",
        "start_m" => 12, "start_d" => 17,
        "peak_m" => 12, "peak_d" => 22, "peak_end_d" => 23,
        "end_m" => 12, "end_d" => 26,
        "zhr" => 10,
        "radiant" => "Óssa Menor",
        "parent" => "Cometa 8P/Tuttle",
        "color" => "#01a4b4"
    ]
];

$mesos_cat = [1=>"Gen", 2=>"Feb", 3=>"Mar", 4=>"Abr", 5=>"Mai", 6=>"Jun", 7=>"Jul", 8=>"Ago", 9=>"Set", 10=>"Oct", 11=>"Nov", 12=>"Des"];

// Calcular timestamps exactes per a l'any actual
$processed_showers = [];
$active_shower = null;
$next_shower = null;
$min_diff_next = PHP_INT_MAX;

foreach ($raw_showers as $s) {
    // Tractament especial per a pluges que creuen cap d'any (com Quadràntids)
    $start_y = $current_year;
    $end_y = $current_year;
    $peak_y = $current_year;
    
    if ($s["id"] === "quadrantids") {
        // Si estem al desembre, el pic és el gener següent
        if (intval(date("n", $now)) == 12) {
            $end_y = $current_year + 1;
            $peak_y = $current_year + 1;
        } else {
            // Si estem a qualsevol altre mes, l'inici era el desembre anterior
            $start_y = $current_year - 1;
        }
    }
    
    $ts_start = mktime(0, 0, 0, $s["start_m"], $s["start_d"], $start_y);
    $ts_peak  = mktime(22, 0, 0, $s["peak_m"], $s["peak_d"], $peak_y);
    $ts_peak_end = mktime(6, 0, 0, $s["peak_m"], $s["peak_end_d"], $peak_y);
    $ts_end   = mktime(23, 59, 59, $s["end_m"], $s["end_d"], $end_y);
    
    // Si la pluja d'aquest any ja va acabar i no és desembre, programar la del proper any per al càlcul de pròxima
    if ($now > $ts_end) {
        $ts_start_next = mktime(0, 0, 0, $s["start_m"], $s["start_d"], $start_y + 1);
        $ts_peak_next  = mktime(22, 0, 0, $s["peak_m"], $s["peak_d"], $peak_y + 1);
        $ts_end_next   = mktime(23, 59, 59, $s["end_m"], $s["end_d"], $end_y + 1);
        $diff_next = $ts_peak_next - $now;
    } else {
        $diff_next = $ts_peak - $now;
    }
    
    $is_active = ($now >= $ts_start && $now <= $ts_end);
    $is_peak   = ($now >= $ts_peak - 86400 && $now <= $ts_peak_end + 86400);
    
    $item = [
        "name" => $s["name"],
        "period" => $s["start_d"] . " " . $mesos_cat[$s["start_m"]] . " - " . $s["end_d"] . " " . $mesos_cat[$s["end_m"]],
        "peak_str" => $s["peak_d"] . "-" . $s["peak_end_d"] . " " . $mesos_cat[$s["peak_m"]],
        "zhr" => $s["zhr"],
        "radiant" => $s["radiant"],
        "parent" => $s["parent"],
        "color" => $s["color"],
        "is_active" => $is_active,
        "is_peak" => $is_peak,
        "ts_peak" => $ts_peak,
        "diff_next" => $diff_next
    ];
    
    $processed_showers[] = $item;
    
    if ($is_active && !$active_shower) {
        $active_shower = $item;
    }
    
    if ($diff_next > 0 && $diff_next < $min_diff_next) {
        $min_diff_next = $diff_next;
        $next_shower = $item;
    }
}

// Pluja destacada (Hero)
$hero_shower = $active_shower ? $active_shower : $next_shower;
$days_to_peak = round($hero_shower["diff_next"] / 86400);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Guia de Pluges d'Estels i Meteors - MeteoSallent</title>
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
.hero-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.hero-title {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}
.hero-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    display: inline-block;
    margin-bottom: 6px;
}
.showers-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-top: 4px;
}
.showers-table th {
    text-align: left;
    color: #718096;
    padding: 6px 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-weight: 700;
}
.showers-table td {
    padding: 8px 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    vertical-align: middle;
}
.showers-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}
.status-pill {
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
}
.status-active {
    background: rgba(141, 252, 45, 0.15);
    color: #8DFC2D;
    border: 1px solid #8DFC2D;
}
.status-next {
    background: rgba(236, 180, 84, 0.15);
    color: #ecb454;
    border: 1px solid #ecb454;
}
.status-past {
    color: #718096;
}
.tip-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
@media (max-width: 560px) {
    .tip-grid { grid-template-columns: 1fr; }
    .showers-table th:nth-child(4), .showers-table td:nth-child(4) { display: none; }
}
.tip-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 6px;
    padding: 10px;
    font-size: 11px;
    line-height: 1.4;
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

<div class="weather34darkbrowser" url="Calendari de Pluges d'Estels • Sallent & Catalunya"></div>

<div class="container">

    <!-- Hero Card: Pluja activa o propera -->
    <div class="card" style="border-left: 4px solid <?php echo $hero_shower["color"]; ?>;">
        <div class="card-title">
            <span><?php echo $hero_shower["is_active"] ? "Pluja d'Estels Activa Ara" : "Propera Pluja d'Estels Destacada"; ?></span>
            <span style="color: <?php echo $hero_shower["color"]; ?>; font-weight: 700;">Visibilitat des de Sallent</span>
        </div>
        <div class="hero-box">
            <div>
                <?php if ($hero_shower["is_peak"]): ?>
                    <div class="hero-badge" style="background: rgba(243, 120, 103, 0.2); color: #f37867; border: 1px solid #f37867;">🔥 PIC DE MÀXIMA ACTIVITAT ARA!</div>
                <?php elseif ($hero_shower["is_active"]): ?>
                    <div class="hero-badge" style="background: rgba(141, 252, 45, 0.15); color: #8DFC2D; border: 1px solid #8DFC2D;">✨ EN PERÍODE D'ACTIVITAT</div>
                <?php else: ?>
                    <div class="hero-badge" style="background: rgba(1, 164, 180, 0.15); color: #01a4b4; border: 1px solid #01a4b4;">⏳ Resten aprox. <?php echo $days_to_peak; ?> dies per al pic</div>
                <?php endif; ?>
                
                <div class="hero-title"><?php echo $hero_shower["name"]; ?></div>
                <div style="font-size: 12px; color: #cbd5e0; line-height: 1.5;">
                    &bull; Nit de màxima activitat (Pic): <b style="color: #fff;"><?php echo $hero_shower["peak_str"]; ?></b><br>
                    &bull; Període d'activitat: <b><?php echo $hero_shower["period"]; ?></b><br>
                    &bull; Radiant astronòmic: <b><?php echo $hero_shower["radiant"]; ?></b> (Origen: <?php echo $hero_shower["parent"]; ?>)
                </div>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 10px 14px; text-align: center; min-width: 120px;">
                <div style="font-size: 10px; text-transform: uppercase; color: #718096; font-weight: 700;">Taxa Zenital (ZHR)</div>
                <div style="font-size: 26px; font-weight: 800; color: <?php echo $hero_shower["color"]; ?>; margin-top: 2px;">
                    <?php echo $hero_shower["zhr"]; ?>
                </div>
                <div style="font-size: 10px; color: #a0aec0;">meteors / hora</div>
            </div>
        </div>
    </div>

    <!-- Taula Anual de Pluges d'Estels -->
    <div class="card">
        <div class="card-title">
            <span>Calendari Oficial de Meteors de l'Any (IMO)</span>
            <span style="font-size: 10px; color: #718096;">International Meteor Organization</span>
        </div>
        <table class="showers-table">
            <thead>
                <tr>
                    <th>Pluja d'Estels</th>
                    <th>Període</th>
                    <th>Pic (Màxim)</th>
                    <th>Radiant</th>
                    <th style="text-align: right;">ZHR</th>
                    <th style="text-align: right;">Estat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($processed_showers as $ps): ?>
                <tr>
                    <td>
                        <b style="color: <?php echo $ps["color"]; ?>;"><?php echo $ps["name"]; ?></b>
                    </td>
                    <td><?php echo $ps["period"]; ?></td>
                    <td><b><?php echo $ps["peak_str"]; ?></b></td>
                    <td style="color: #a0aec0;"><?php echo $ps["radiant"]; ?></td>
                    <td style="text-align: right; font-weight: 700; color: #fff;"><?php echo $ps["zhr"]; ?>/h</td>
                    <td style="text-align: right;">
                        <?php if ($ps["is_peak"]): ?>
                            <span class="status-pill" style="background: #f37867; color: #fff;">PIC ARA</span>
                        <?php elseif ($ps["is_active"]): ?>
                            <span class="status-pill status-active">ACTIVA</span>
                        <?php elseif ($ps["diff_next"] > 0 && $ps["diff_next"] == $min_diff_next): ?>
                            <span class="status-pill status-next">PROPERA</span>
                        <?php else: ?>
                            <span class="status-pill status-past">&bull;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Consells d'Observació a Catalunya -->
    <div class="card">
        <div class="card-title">
            <span>Guia d'Observació Astronòmica a Sallent i el Bages</span>
        </div>
        <div class="tip-grid">
            <div class="tip-card">
                <b>🔭 Sense instruments òptics:</b> Els meteors creuen grans extensions de volta celest a velocitats de 20 a 70 km/s. Utilitzar prismàtics o telescopis en redueix el camp visual. El millor mètode és a ull nu des d'una gandula.
            </div>
            <div class="tip-card">
                <b>🌌 Allunya't de la llum urbana:</b> A Sallent, busca zones lliures de fanals (direcció Cabrianes, camí d'Avinyó o miradors enlairats). Com més fosc el cel, més meteors dèbils es poden percebre.
            </div>
            <div class="tip-card">
                <b>👁️ Adaptació a la foscor:</b> L'ull humà necessita entre 20 i 30 minuts per dilatar la pupil·la i activar la rodopsina retiniana. Evita consultar la pantalla del telèfon mòbil mentre observis.
            </div>
            <div class="tip-card">
                <b>⏰ Millor hora d'observació:</b> A partir de la mitjanit i durant les hores de matinada abans de l'alba, ja que el punt d'observació a la Terra viatja de cara cap al flux de partícules meteòriques.
            </div>
        </div>
    </div>

    <div class="footer">
        Dades contrastades segons la International Meteor Organization (IMO) &bull; MeteoSallent
    </div>

</div>

</body>
</html>
