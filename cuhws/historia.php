<?php
// Historial Climatològic Oficial • Sallent (2006 - 2026)
include_once('common.php');
date_default_timezone_set($TZ);

$reports_dir = __DIR__ . '/noaa_reports';

$available_years = range(2026, 2006);
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;
if (!in_array($selected_year, $available_years)) {
    $selected_year = 2026;
}

$selected_month = isset($_GET['month']) ? $_GET['month'] : 'all';

$months_cat = [
    'all' => 'Resum Anual',
    '01' => 'Gener',
    '02' => 'Febrer',
    '03' => 'Març',
    '04' => 'Abril',
    '05' => 'Maig',
    '06' => 'Juny',
    '07' => 'Juliol',
    '08' => 'Agost',
    '09' => 'Setembre',
    '10' => 'Octubre',
    '11' => 'Novembre',
    '12' => 'Desembre'
];

$report_content = "";
$report_title = "";

if ($selected_month === 'all') {
    $report_title = "Informe Climatològic Anual - Any $selected_year";
    $candidates = [
        "$reports_dir/$selected_year/$selected_year.txt",
        "$reports_dir/$selected_year.txt"
    ];
} else {
    $m_num = str_pad($selected_month, 2, '0', STR_PAD_LEFT);
    $m_name = $months_cat[$m_num] ?? $m_num;
    $report_title = "Informe Mensual - $m_name de $selected_year";
    $candidates = [
        "$reports_dir/$selected_year/{$selected_year}_{$m_num}.txt",
        "$reports_dir/{$selected_year}_{$m_num}.txt"
    ];
}

$found_file = null;
foreach ($candidates as $c) {
    if (file_exists($c)) {
        $found_file = $c;
        break;
    }
}

if ($found_file) {
    $raw = @file_get_contents($found_file);
    if (!mb_check_encoding($raw, 'UTF-8')) {
        $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
    }
    $report_content = $raw;
} else {
    $report_content = "Sense informe disponible per al període seleccionat ($selected_month/$selected_year).";
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Climatològic &bull; MeteoSallent (2006-2026)</title>
    <link rel="stylesheet" href="css/main.dark.css">
    <style>
        body {
            background: #111418;
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 16px;
            box-sizing: border-box;
        }
        .hist-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .hist-card {
            background: rgba(25, 30, 36, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .hist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .hist-header h1 {
            margin: 0;
            font-size: 20px;
            color: #01a4b4;
            font-weight: 700;
        }
        .btn-back {
            background: #01a4b4;
            color: #000;
            padding: 6px 14px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover {
            background: #48fb9e;
        }
        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .record-box {
            background: rgba(0,0,0,0.35);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }
        .record-box .title {
            font-size: 11.5px;
            color: #a0aec0;
            text-transform: uppercase;
            font-weight: 600;
        }
        .record-box .val {
            font-size: 20px;
            font-weight: 800;
            margin: 4px 0;
            font-family: 'Courier New', monospace;
        }
        .record-box .date {
            font-size: 11.5px;
            color: #718096;
        }
        .nav-selectors {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
            background: rgba(0,0,0,0.25);
            padding: 10px 14px;
            border-radius: 6px;
        }
        .nav-selectors label {
            font-size: 13px;
            color: #cbd5e0;
            font-weight: 600;
        }
        .nav-selectors select {
            background: #1a202c;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.15);
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .report-pre-box {
            background: #0d1117;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            padding: 14px 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        pre.report-content {
            margin: 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.45;
            color: #e2e8f0;
            white-space: pre;
        }
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background: #01a4b4;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="hist-container">
    <div class="hist-card">
        <div class="hist-header">
            <div>
                <h1>Arxiu Climatològic Històric &bull; Sallent (El Bages)</h1>
                <span style="font-size: 12.5px; color: #a0aec0;">Registre continu de 21 anys oficials (2006 - 2026) &bull; Davis Vantage Pro2 Plus</span>
            </div>
            <a href="index.php" class="btn-back">&larr; Tornar a la Web en Directe</a>
        </div>

        <!-- KPI Records Grid -->
        <div class="records-grid">
            <div class="record-box" style="border-top: 3px solid #ff8841;">
                <div class="title">Rècord Màxim Històric</div>
                <div class="val" style="color: #ff8841;">42.1&deg;C</div>
                <div class="date">28 Juny 2019 (15:39 h)</div>
            </div>
            <div class="record-box" style="border-top: 3px solid #01a4b4;">
                <div class="title">Rècord Mínim Històric</div>
                <div class="val" style="color: #57FAF9;">-10.4&deg;C</div>
                <div class="date">20 Desembre 2009 (08:41 h)</div>
            </div>
            <div class="record-box" style="border-top: 3px solid #2ecc71;">
                <div class="title">Pluja Màx. en un Dia</div>
                <div class="val" style="color: #2ecc71;">72.6 mm</div>
                <div class="date">29 Juny 2023</div>
            </div>
            <div class="record-box" style="border-top: 3px solid #e74c3c;">
                <div class="title">Ratxa Màx. de Vent</div>
                <div class="val" style="color: #e74c3c;">82.0 km/h</div>
                <div class="date">02 Març 2020 / 2008 / 2007</div>
            </div>
            <div class="record-box" style="border-top: 3px solid #9b59b6;">
                <div class="title">Any Més Plujós</div>
                <div class="val" style="color: #bb86fc;">952.4 mm</div>
                <div class="date">Any 2018 complet</div>
            </div>
            <div class="record-box" style="border-top: 3px solid #f1c40f;">
                <div class="title">Any Més Sec</div>
                <div class="val" style="color: #f1c40f;">284.4 mm</div>
                <div class="date">Any 2022 complet</div>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="historia.php" class="nav-selectors">
            <label for="year">Any:</label>
            <select name="year" id="year" onchange="this.form.submit()">
                <?php foreach ($available_years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y ? 'selected' : ''); ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="month" style="margin-left: 12px;">Mes:</label>
            <select name="month" id="month" onchange="this.form.submit()">
                <?php foreach ($months_cat as $k => $label): ?>
                    <option value="<?php echo $k; ?>" <?php echo ($selected_month == $k ? 'selected' : ''); ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <h3 style="color: #01a4b4; margin: 12px 0 8px 0; font-size: 15px;"><?php echo htmlspecialchars($report_title); ?></h3>
        
        <div class="report-pre-box">
            <pre class="report-content"><?php echo htmlspecialchars($report_content); ?></pre>
        </div>
    </div>
</div>
</body>
</html>
