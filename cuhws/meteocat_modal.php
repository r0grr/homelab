<?php
header('Content-Type: text/html; charset=UTF-8');
$meteocat_url = "https://www.meteo.cat/prediccio/municipal/081918/";
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsió Meteocat - Sallent</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .header-bar {
            height: 42px;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            color: #e2e8f0;
            font-size: 13.5px;
        }
        .header-bar .title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #ffffff;
        }
        .header-bar a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            background: rgba(56, 189, 248, 0.12);
            border-radius: 5px;
            border: 1px solid rgba(56, 189, 248, 0.35);
            transition: background 0.2s;
        }
        .header-bar a:hover {
            background: rgba(56, 189, 248, 0.25);
        }
        iframe {
            width: 100%;
            height: calc(100% - 42px);
            border: none;
            background: #ffffff;
            display: block;
        }
    </style>
</head>
<body>
    <div class="header-bar">
        <div class="title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
            <span>Meteocat &bull; Predicció Municipal per a Sallent (081918)</span>
        </div>
        <a href="<?php echo $meteocat_url; ?>" target="_blank" rel="noopener noreferrer">↗ Obrir a meteo.cat</a>
    </div>
    <iframe src="<?php echo $meteocat_url; ?>" allowfullscreen></iframe>
</body>
</html>
