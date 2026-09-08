<?php
include_once("settings.php");
include_once("common.php");
include_once("livedata.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");
date_default_timezone_set($TZ);

// Càlculs astronòmics lunars en PHP natiu
function get_moon_data($timestamp) {
    // Mes sinòdic = 29.53058867 dies
    $synodic = 29.53058867;
    // Lluna nova de referència: 6 de gener de 2000 a les 18:14 UTC
    $known_new = 947182440;
    
    $diff = ($timestamp - $known_new) / 86400.0;
    $cycles = $diff / $synodic;
    $phase = $cycles - floor($cycles); // 0.0 a 1.0
    $age = $phase * $synodic;
    
    // Percentatge d'il·luminació (fórmula d'angle de fase)
    $illum = round((1.0 - cos($phase * 2.0 * M_PI)) / 2.0 * 100);
    
    if ($age < 1.84 || $age >= 27.69) {
        $name = "Lluna Nova";
        $icon = "🌑";
        $type = "new";
    } elseif ($age < 5.53) {
        $name = "Creixent Inicial";
        $icon = "🌒";
        $type = "waxing_crescent";
    } elseif ($age < 9.22) {
        $name = "Quart Creixent";
        $icon = "🌓";
        $type = "first_quarter";
    } elseif ($age < 12.91) {
        $name = "Creixent Gibosa";
        $icon = "🌔";
        $type = "waxing_gibbous";
    } elseif ($age < 16.61) {
        $name = "Lluna Plena";
        $icon = "🌕";
        $type = "full";
    } elseif ($age < 20.30) {
        $name = "Minvant Gibosa";
        $icon = "🌖";
        $type = "waning_gibbous";
    } elseif ($age < 23.99) {
        $name = "Quart Minvant";
        $icon = "🌗";
        $type = "last_quarter";
    } else {
        $name = "Minvant Inicial";
        $icon = "🌘";
        $type = "waning_crescent";
    }
    
    return [
        "phase" => $phase,
        "age" => $age,
        "illum" => $illum,
        "name" => $name,
        "icon" => $icon,
        "type" => $type
    ];
}

$now = time();
$current_moon = get_moon_data($now);

// Mes actual per al calendari
$mesos_cat = [1=>"Gener", 2=>"Febrer", 3=>"Març", 4=>"Abril", 5=>"Maig", 6=>"Juny", 7=>"Juliol", 8=>"Agost", 9=>"Setembre", 10=>"Octubre", 11=>"Novembre", 12=>"Desembre"];
$dies_cat = ["Dg", "Dl", "Dt", "Dc", "Dj", "Dv", "Ds"];

$year = intval(date("Y", $now));
$month = intval(date("n", $now));
$days_in_month = intval(date("t", $now));
$today_day = intval(date("j", $now));

// Properes 4 fases principals
function find_next_phases($start_ts) {
    $phases_to_find = [
        "first_quarter" => ["target" => 0.25, "name" => "Quart Creixent", "icon" => "🌓"],
        "full"          => ["target" => 0.50, "name" => "Lluna Plena",     "icon" => "🌕"],
        "last_quarter"  => ["target" => 0.75, "name" => "Quart Minvant",   "icon" => "🌗"],
        "new"           => ["target" => 1.00, "name" => "Lluna Nova",      "icon" => "🌑"]
    ];
    
    $synodic = 29.53058867 * 86400;
    $known_new = 947182440;
    
    $diff = $start_ts - $known_new;
    $cycles = $diff / $synodic;
    $base_cycle = floor($cycles);
    
    $results = [];
    foreach ($phases_to_find as $key => $p) {
        $target_cycle = $base_cycle + $p["target"];
        $target_ts = $known_new + ($target_cycle * $synodic);
        if ($target_ts < $start_ts) {
            $target_ts += $synodic;
        }
        $results[] = [
            "name" => $p["name"],
            "icon" => $p["icon"],
            "ts" => $target_ts
        ];
    }
    
    usort($results, function($a, $b) { return $a["ts"] - $b["ts"]; });
    return $results;
}

$next_phases = find_next_phases($now);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Calendari Lunar Oficial - MeteoSallent</title>
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
.hero-phase {
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: 16px;
    flex-wrap: wrap;
    padding: 6px 0;
}
.hero-disc {
    position: relative;
    width: 104px;
    height: 104px;
    border-radius: 50%;
    background: url("img/moon.png") center/cover no-repeat;
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.hero-info {
    flex: 1;
    min-width: 200px;
}
.hero-name {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}
.hero-sub {
    font-size: 12px;
    color: #cbd5e0;
    line-height: 1.5;
}
.badge-gold {
    display: inline-block;
    background: rgba(236, 180, 84, 0.18);
    color: #ecb454;
    border: 1px solid #ecb454;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 11px;
    margin-top: 6px;
}
.phases-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}
@media (max-width: 580px) {
    .phases-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.phase-card {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 10px 8px;
    text-align: center;
}
.phase-icon {
    font-size: 26px;
    line-height: 1;
    margin-bottom: 4px;
}
.phase-name {
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 2px;
}
.phase-date {
    font-size: 10px;
    color: #a0aec0;
}
.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
    margin-top: 8px;
}
.cal-head {
    text-align: center;
    font-weight: 700;
    font-size: 10px;
    color: #718096;
    padding-bottom: 4px;
    text-transform: uppercase;
}
.cal-cell {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 6px;
    padding: 6px 4px;
    text-align: center;
    min-height: 52px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
}
.cal-cell.today {
    border-color: #01a4b4;
    background: rgba(1, 164, 180, 0.15);
    box-shadow: 0 0 10px rgba(1, 164, 180, 0.35);
}
.cal-cell.empty {
    background: transparent;
    border: 0;
}
.cal-day-num {
    font-size: 10px;
    font-weight: 700;
    color: #a0aec0;
}
.cal-cell.today .cal-day-num {
    color: #01a4b4;
}
.cal-day-icon {
    font-size: 16px;
    line-height: 1;
}
.cal-day-illum {
    font-size: 9px;
    color: #718096;
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

<div class="weather34darkbrowser" url="Calendari Lunar Oficial • Sallent (Catalunya)"></div>

<div class="container">

    <!-- Targeta Lluna Actual -->
    <div class="card" style="border-left: 4px solid #ecb454;">
        <div class="card-title">
            <span>Fase Lunar en Temps Real</span>
            <span style="color: #ecb454; font-weight: 700; font-size: 11px;">Sallent (41.82&deg;N, 1.90&deg;E)</span>
        </div>
        <div class="hero-phase">
            <div class="hero-disc">
                <span style="position: absolute; bottom: -8px; background: rgba(0,0,0,0.75); border: 1px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 1px 8px; font-size: 10px; font-weight: 800; color: #fff;">
                    <?php echo $current_moon["illum"]; ?>%
                </span>
            </div>
            <div class="hero-info">
                <div class="hero-name"><?php echo $current_moon["name"] . " " . $current_moon["icon"]; ?></div>
                <div class="hero-sub">
                    &bull; Il·luminació del disc lunar: <b><?php echo $current_moon["illum"]; ?>%</b><br>
                    &bull; Edat de la lluna: <b><?php echo number_format($current_moon["age"], 1); ?> dies</b> des de la darrera Lluna Nova<br>
                    &bull; Durada del cicle sinòdic: <b>29 dies, 12 hores i 44 minuts</b>
                </div>
                <div class="badge-gold">
                    Visibilitat d'observació: <?php echo ($current_moon["illum"] > 70 ? "Brillant (ideal per observar cràters amb filtres)" : "Òptima per cel profund i estels"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Properes 4 fases principals -->
    <div class="card">
        <div class="card-title">
            <span>Properes Fases Principals</span>
            <span style="font-size: 10px; color: #718096;">Hora oficial de Catalunya (CET/CEST)</span>
        </div>
        <div class="phases-grid">
            <?php foreach ($next_phases as $np): ?>
            <div class="phase-card">
                <div class="phase-icon"><?php echo $np["icon"]; ?></div>
                <div class="phase-name"><?php echo $np["name"]; ?></div>
                <div class="phase-date">
                    <b><?php echo date("j", $np["ts"]) . " " . $mesos_cat[intval(date("n", $np["ts"]))]; ?></b><br>
                    <?php echo date("H:i", $np["ts"]); ?> h
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Calendari del Mes Actual -->
    <div class="card">
        <div class="card-title">
            <span>Calendari Lunar de <?php echo $mesos_cat[$month] . " " . $year; ?></span>
            <span style="font-size: 10px; color: #01a4b4;">&#9632; Avui ressaltat</span>
        </div>
        
        <div class="cal-grid">
            <?php 
            $weekday_headers = ["Dl", "Dt", "Dc", "Dj", "Dv", "Ds", "Dg"];
            foreach ($weekday_headers as $wh) {
                echo '<div class="cal-head">' . $wh . '</div>';
            }
            
            $first_day_weekday = intval(date("N", mktime(12, 0, 0, $month, 1, $year)));
            for ($pad = 1; $pad < $first_day_weekday; $pad++) {
                echo '<div class="cal-cell empty"></div>';
            }
            
            for ($d = 1; $d <= $days_in_month; $d++) {
                $day_ts = mktime(12, 0, 0, $month, $d, $year);
                $d_moon = get_moon_data($day_ts);
                $is_today = ($d == $today_day) ? " today" : "";
                
                echo '<div class="cal-cell' . $is_today . '">';
                echo '<span class="cal-day-num">' . $d . '</span>';
                echo '<span class="cal-day-icon" title="' . htmlspecialchars($d_moon["name"]) . '">' . $d_moon["icon"] . '</span>';
                echo '<span class="cal-day-illum">' . $d_moon["illum"] . '%</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="footer">
        Algorisme astronòmic natiu en temps real &bull; Observació astronòmica a Sallent &bull; MeteoSallent
    </div>

</div>

</body>
</html>
