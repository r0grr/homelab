<?php
include_once("livedata.php");
include_once("common.php");
include_once("settings1.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");
date_default_timezone_set($TZ);

$temp_in = isset($weather["temp_indoor"]) ? floatval($weather["temp_indoor"]) : 22.0;
$hum_in  = isset($weather["humidity_indoor"]) ? intval($weather["humidity_indoor"]) : 50;
$feel_in = isset($weather["temp_indoor_feel"]) ? floatval($weather["temp_indoor_feel"]) : $temp_in;
$trend_in = isset($weather["temp_indoor_trend"]) ? floatval($weather["temp_indoor_trend"]) : 0.0;

// Calcular punt de rosada interior (Dew Point - formula de Magnus-Tetens)
$alpha = ((17.27 * $temp_in) / (237.7 + $temp_in)) + log($hum_in / 100.0);
$dew_in = ($hum_in > 0) ? round((237.7 * $alpha) / (17.27 - $alpha), 1) : 0;

// Diagnostic de confort
$comfort_title = "Confort Optim";
$comfort_color = "#8DFC2D";
$comfort_bg = "rgba(141, 252, 45, 0.15)";
$comfort_desc = "Condicions ideals per al descans, la feina i el benestar general a la llar segons el Reglament d'Instal-lacions Termiques (RITE).";

if ($temp_in < 18) {
    $comfort_title = "Ambient Fred";
    $comfort_color = "#01a4b4";
    $comfort_bg = "rgba(1, 164, 180, 0.2)";
    $comfort_desc = "La temperatura es baixa. Es recomana activar la calefaccio per evitar refredaments i risc de condensacio a parets i finestres.";
} elseif ($temp_in > 26) {
    $comfort_title = "Ambient Calorós";
    $comfort_color = "#f37867";
    $comfort_bg = "rgba(243, 120, 103, 0.2)";
    $comfort_desc = "La temperatura es elevada. Es recomana abaixar persianes a les hores de sol i aprofitar la ventilacio nocturna per refrescar.";
} elseif ($hum_in < 35) {
    $comfort_title = "Ambient Molt Sec";
    $comfort_color = "#ecb454";
    $comfort_bg = "rgba(236, 180, 84, 0.2)";
    $comfort_desc = "La humitat relativa es baixa (< 35%). Pot causar sequedat ocular, irritacio a la gola i augment de pols en suspensio.";
} elseif ($hum_in > 65) {
    $comfort_title = "Ambient Molt Humit";
    $comfort_color = "#ff8841";
    $comfort_bg = "rgba(255, 136, 65, 0.2)";
    $comfort_desc = "Humitat elevada (> 65%). Afavoreix la proliferacio d'acars i floridura. Es recomana ventilar energeticament o utilitzar un deshumidificador.";
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Guia de Confort i Climatitzacio Interior - MeteoSallent</title>
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
    max-width: 440px;
    margin: 0 auto;
    padding: 10px 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.card {
    background: rgba(30, 34, 40, 0.95);
    border: 1px solid rgba(80, 85, 95, 0.5);
    border-radius: 8px;
    padding: 12px 14px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}
.card-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #a0aec0;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 11px;
    display: inline-block;
}
.metric-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 6px;
}
.metric-box {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 8px 10px;
    text-align: center;
}
.metric-val {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    margin-top: 2px;
}
.metric-lbl {
    font-size: 10px;
    text-transform: uppercase;
    color: #718096;
    letter-spacing: 0.5px;
}
.scale-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    font-size: 11px;
}
.scale-table th {
    text-align: left;
    color: #718096;
    padding: 4px 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.scale-table td {
    padding: 5px 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 11px;
    line-height: 1.4;
}
.tip-item:last-child {
    margin-bottom: 0;
}
.tip-icon {
    font-size: 14px;
    flex-shrink: 0;
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

<div class="weather34darkbrowser" url="Guia Oficial de Confort Interior (RITE / Generalitat)"></div>

<div class="container">

    <!-- Estat actual -->
    <div class="card" style="border-left: 4px solid <?php echo $comfort_color; ?>;">
        <div class="card-title">
            <span>Estat Interior Actual</span>
            <span class="status-badge" style="background: <?php echo $comfort_bg; ?>; color: <?php echo $comfort_color; ?>; border: 1px solid <?php echo $comfort_color; ?>;">
                <?php echo $comfort_title; ?>
            </span>
        </div>
        <p style="font-size: 11px; line-height: 1.4; color: #e2e8f0; margin-bottom: 10px;">
            <?php echo $comfort_desc; ?>
        </p>

        <div class="metric-grid">
            <div class="metric-box">
                <div class="metric-lbl">Temperatura</div>
                <div class="metric-val"><?php echo number_format($temp_in, 1); ?>&deg;C</div>
                <div style="font-size: 10px; color: #a0aec0; margin-top: 2px;">
                    Tendencia: <?php echo ($trend_in > 0 ? "+".number_format($trend_in, 1)."&deg;C" : number_format($trend_in, 1)."&deg;C"); ?>
                </div>
            </div>
            <div class="metric-box">
                <div class="metric-lbl">Humitat Relativa</div>
                <div class="metric-val"><?php echo $hum_in; ?>%</div>
                <div style="font-size: 10px; color: #a0aec0; margin-top: 2px;">
                    <?php echo ($hum_in >= 40 && $hum_in <= 60 ? "Franja Optima" : ($hum_in < 40 ? "Franja Baixa" : "Franja Alta")); ?>
                </div>
            </div>
            <div class="metric-box">
                <div class="metric-lbl">Sensacio Termica</div>
                <div class="metric-val" style="color: #8DFC2D;"><?php echo number_format($feel_in, 1); ?>&deg;C</div>
                <div style="font-size: 10px; color: #a0aec0; margin-top: 2px;">Index de xafogor</div>
            </div>
            <div class="metric-box">
                <div class="metric-lbl">Punt de Rosada</div>
                <div class="metric-val" style="color: #01a4b4;"><?php echo number_format($dew_in, 1); ?>&deg;C</div>
                <div style="font-size: 10px; color: #a0aec0; margin-top: 2px;">Risc condensacio</div>
            </div>
        </div>
    </div>

    <!-- Rangs oficials de temperatura (RITE) -->
    <div class="card">
        <div class="card-title">
            <span>Temperatura de Confort (RITE)</span>
        </div>
        <table class="scale-table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Classificacio</th>
                    <th>Efectes / Recomanacio</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="color: #01a4b4; font-weight: 700;">&lt; 18 &deg;C</td>
                    <td style="color: #01a4b4;">Fred</td>
                    <td>Risc de condensacio a parets i refredats.</td>
                </tr>
                <tr>
                    <td style="color: #8DFC2D; font-weight: 700;">21 - 23 &deg;C</td>
                    <td style="color: #8DFC2D;">Optim Hivern</td>
                    <td>Confort termic estandard per a habitatges.</td>
                </tr>
                <tr>
                    <td style="color: #ecb454; font-weight: 700;">23 - 25 &deg;C</td>
                    <td style="color: #ecb454;">Optim Estiu</td>
                    <td>Temperatura recomanada d'aire condicionat.</td>
                </tr>
                <tr>
                    <td style="color: #f37867; font-weight: 700;">&gt; 26 &deg;C</td>
                    <td style="color: #f37867;">Caloros</td>
                    <td>Somnolencia, perdua de concentracio i fatiga.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Rangs oficials d'humitat (OMS) -->
    <div class="card">
        <div class="card-title">
            <span>Humitat Relativa Recomanada</span>
        </div>
        <table class="scale-table">
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Nivell</th>
                    <th>Impacte a la Salut</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="color: #ecb454; font-weight: 700;">&lt; 35%</td>
                    <td style="color: #ecb454;">Molt Sec</td>
                    <td>Irritacio de pell, mucoses i vies respiratories.</td>
                </tr>
                <tr>
                    <td style="color: #8DFC2D; font-weight: 700;">40% - 60%</td>
                    <td style="color: #8DFC2D;">Optim</td>
                    <td>Franja saludable ideal per a la vida quotidiana.</td>
                </tr>
                <tr>
                    <td style="color: #ff8841; font-weight: 700;">60% - 70%</td>
                    <td style="color: #ff8841;">Moderat</td>
                    <td>Sensacio de xafogor a l'estiu o fred humit a l'hivern.</td>
                </tr>
                <tr>
                    <td style="color: #f37867; font-weight: 700;">&gt; 70%</td>
                    <td style="color: #f37867;">Excessiu</td>
                    <td>Risc de fongs, floridura i allergies per acars.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Consells d'eficiencia i salut -->
    <div class="card">
        <div class="card-title">
            <span>Consells Practics de Climatitzacio</span>
        </div>
        <div class="tip-item">
            <span class="tip-icon">&#129703;</span>
            <div><b>Ventilacio creuada:</b> Obre finestres oposades durant 10-15 minuts cada mati per renovar l'aire sense refredar ni escalfar l'estructura.</div>
        </div>
        <div class="tip-item">
            <span class="tip-icon">&#9728;</span>
            <div><b>Aprofitament solar:</b> A l'hivern, apuja persianes a les hores centrals de sol; a l'estiu, abaixa-les abans que hi toqui el sol directe.</div>
        </div>
        <div class="tip-item">
            <span class="tip-icon">&#128167;</span>
            <div><b>Control d'humitat:</b> Si la humitat supera el 65%, ventila immediatament despres de cuinar o dutxar-te per evitar floridures.</div>
        </div>
    </div>

    <div class="footer">
        Dades transmeses per la consola Davis Vantage Pro2 &bull; MeteoSallent
    </div>

</div>

</body>
</html>
