<?php
include_once("livedata.php");
include_once("common.php");
include_once("settings1.php");
error_reporting(0);
header("Content-type: text/html; charset=UTF-8");

$uv = isset($weather["uv"]) ? floatval($weather["uv"]) : 0.0;
$solar = isset($weather["solar"]) ? intval($weather["solar"]) : 0;
$lux = isset($weather["lux"]) ? intval($weather["lux"]) : 0;

// Level categorization
if ($uv >= 11) {
    $uv_level = "Extrem";
    $uv_color = "#a475cb";
    $uv_bg = "rgba(164, 117, 203, 0.2)";
    $uv_desc = "Risc extrem de danys a la pell sense protecció. Evita sortir a l'exterior en hores solars centrals.";
} elseif ($uv >= 8) {
    $uv_level = "Molt Alt";
    $uv_color = "#f37867";
    $uv_bg = "rgba(243, 120, 103, 0.2)";
    $uv_desc = "Risc molt alt de cremades ràpides. Es requereix protecció màxima i romandre a l'ombra.";
} elseif ($uv >= 6) {
    $uv_level = "Alt";
    $uv_color = "#ff8841";
    $uv_bg = "rgba(255, 136, 65, 0.2)";
    $uv_desc = "Risc alt. Utilitza ulleres de sol, barret, crema solar SPF 30+ i busca l'ombra entre les 12h i 16h.";
} elseif ($uv >= 3) {
    $uv_level = "Moderat";
    $uv_color = "#ecb454";
    $uv_bg = "rgba(236, 180, 84, 0.2)";
    $uv_desc = "Risc moderat. Es recomana protecció solar a les hores centrals del dia.";
} else {
    $uv_level = "Baix";
    $uv_color = "#9aba2f";
    $uv_bg = "rgba(154, 186, 47, 0.2)";
    $uv_desc = "Risc mínim per a la majoria de persones. No cal protecció especial a l'aire lliure.";
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Guia Oficial d'Índex Ultraviolat (UV) i Radiació Solar</title>
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
.highlight-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 800;
    font-size: 11px;
}
.uv-hero {
    display: flex;
    align-items: center;
    gap: 14px;
}
.uv-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}
.uv-circle span {
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: -2px;
}
.uv-summary {
    flex: 1;
}
.uv-summary h3 {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 3px;
}
.uv-summary p {
    font-size: 11.5px;
    line-height: 1.35;
    color: #cbd5e0;
}
.metrics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 8px;
}
.metric-box {
    background: rgba(45, 50, 58, 0.6);
    border-radius: 6px;
    padding: 6px 8px;
    border-left: 3px solid #00A4B4;
}
.metric-box.solar {
    border-left-color: #ecb454;
}
.metric-box span {
    font-size: 10px;
    color: #a0aec0;
    display: block;
}
.metric-box b {
    font-size: 14px;
    color: #fff;
}
.scale-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    margin-top: 4px;
}
.scale-table th {
    text-align: left;
    padding: 4px 6px;
    color: #8fa0b2;
    border-bottom: 1px solid rgba(80, 85, 95, 0.4);
}
.scale-table td {
    padding: 5px 6px;
    border-bottom: 1px solid rgba(80, 85, 95, 0.2);
}
.scale-color-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
    vertical-align: middle;
}
.advice-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 11.5px;
    line-height: 1.35;
}
.advice-item:last-child {
    margin-bottom: 0;
}
.advice-icon {
    color: #00A4B4;
    flex-shrink: 0;
    margin-top: 1px;
}
.footer-note {
    font-size: 10px;
    color: #718096;
    text-align: center;
    margin-top: 4px;
}
</style>
</head>
<body>
<div class="weather34darkbrowser" url="Guia Oficial d'Índex Ultraviolat (UV) &bull; MeteoSallent"></div>

<div class="container">
    <!-- Card 1: Actual UV reading at Sallent -->
    <div class="card" style="border-left: 4px solid <?php echo $uv_color; ?>;">
        <div class="card-title">
            <span>Mesura en temps real a Sallent</span>
            <span class="highlight-badge" style="background: <?php echo $uv_bg; ?>; color: <?php echo $uv_color; ?>;">
                <?php echo $uv_level; ?>
            </span>
        </div>
        <div class="uv-hero">
            <div class="uv-circle" style="background: <?php echo $uv_color; ?>;">
                <?php echo number_format($uv, 1); ?>
                <span>UV</span>
            </div>
            <div class="uv-summary">
                <h3>Índex UV <?php echo $uv_level; ?></h3>
                <p><?php echo $uv_desc; ?></p>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-box solar">
                <span>Radiació Solar Global</span>
                <b><?php echo $solar; ?> W/m²</b>
            </div>
            <div class="metric-box">
                <span>Il·luminació (Lux)</span>
                <b><?php echo number_format($lux); ?> Lux</b>
            </div>
        </div>
    </div>

    <!-- Card 2: Official WHO / Meteocat UV Scale -->
    <div class="card">
        <div class="card-title">Escala Oficial d'Índex UV (OMS / Meteocat)</div>
        <table class="scale-table">
            <thead>
                <tr>
                    <th>Índex</th>
                    <th>Nivell de perill</th>
                    <th>Protecció recomanada</th>
                </tr>
            </thead>
            <tbody>
                <tr style="<?php if ($uv < 3) echo 'background: rgba(154, 186, 47, 0.15); font-weight: bold;'; ?>">
                    <td><span class="scale-color-dot" style="background: #9aba2f;"></span>0 - 2</td>
                    <td style="color: #9aba2f;">Baix</td>
                    <td>Risc mínim. No cal protecció especial.</td>
                </tr>
                <tr style="<?php if ($uv >= 3 && $uv < 6) echo 'background: rgba(236, 180, 84, 0.15); font-weight: bold;'; ?>">
                    <td><span class="scale-color-dot" style="background: #ecb454;"></span>3 - 5</td>
                    <td style="color: #ecb454;">Moderat</td>
                    <td>Ulleres de sol, barret i crema solar a les hores centrals.</td>
                </tr>
                <tr style="<?php if ($uv >= 6 && $uv < 8) echo 'background: rgba(255, 136, 65, 0.15); font-weight: bold;'; ?>">
                    <td><span class="scale-color-dot" style="background: #ff8841;"></span>6 - 7</td>
                    <td style="color: #ff8841;">Alt</td>
                    <td>Crema SPF 30+, roba protectora, cercar ombra 12h-16h.</td>
                </tr>
                <tr style="<?php if ($uv >= 8 && $uv < 11) echo 'background: rgba(243, 120, 103, 0.15); font-weight: bold;'; ?>">
                    <td><span class="scale-color-dot" style="background: #f37867;"></span>8 - 10</td>
                    <td style="color: #f37867;">Molt Alt</td>
                    <td>Evitar exposició directa al migdia. Cremades en < 20 min.</td>
                </tr>
                <tr style="<?php if ($uv >= 11) echo 'background: rgba(164, 117, 203, 0.15); font-weight: bold;'; ?>">
                    <td><span class="scale-color-dot" style="background: #a475cb;"></span>11+</td>
                    <td style="color: #a475cb;">Extrem</td>
                    <td>Risc màxim. Cremades en minuts. Romandre a l'ombra o interiors.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Card 3: Practical Skin Protection Tips -->
    <div class="card">
        <div class="card-title">Consells de Protecció Dermatològica</div>
        <div class="advice-item">
            <svg class="advice-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <div><b>Horari crític:</b> La radiació màxima es produeix entre les 12:00h i les 16:30h oficials (hora solar astronòmica).</div>
        </div>
        <div class="advice-item">
            <svg class="advice-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <div><b>Protecció solar:</b> Aplica crema amb filtre d'ampli espectre (UVA/UVB) 20 minuts abans de l'exposició i renova-la cada 2 hores.</div>
        </div>
        <div class="advice-item">
            <svg class="advice-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div><b>Fototip clar (I i II):</b> Les persones amb pell o ulls clars han d'extremar la precaució fins i tot amb índexs moderats (3-5).</div>
        </div>
    </div>

    <div class="footer-note">
        Font: Organització Mundial de la Salut (OMS) i Servei Meteorològic de Catalunya (Meteocat).
    </div>
</div>
</body>
</html>
