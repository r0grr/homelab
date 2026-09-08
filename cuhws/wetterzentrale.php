<?php
header('Content-Type: text/html; charset=UTF-8');
$ens_url = "https://wetterzentrale.de/es/ens_image.php?geoid=34523&var=201&model=gfs&member=ENS&bw=1";
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ensembles GFS - Sallent (Wetterzentrale)</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body, html {
            margin: 0;
            padding: 0;
            background: #0f172a;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            text-align: center;
            height: 100%;
            overflow-y: auto;
        }
        .header {
            padding: 10px 16px;
            background: #1e293b;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header .title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #ffffff;
        }
        .header a {
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
        .header a:hover {
            background: rgba(56, 189, 248, 0.25);
        }
        .content {
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .img-card {
            background: #ffffff;
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
            max-width: 900px;
            width: 100%;
        }
        img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 4px;
        }
        .footer-note {
            margin-top: 14px;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
            <span>Ensembles GFS (850 hPa & Precip.) &bull; Sallent</span>
        </div>
        <a href="<?php echo $ens_url; ?>" target="_blank" rel="noopener noreferrer">↗ Imatge a Wetterzentrale</a>
    </div>
    <div class="content">
        <div class="img-card">
            <img src="<?php echo $ens_url; ?>" alt="Diagrama Ensembles GFS Sallent" />
        </div>
        <div class="footer-note">
            <b>Font de dades:</b> Wetterzentrale.de &bull; Model GFS Ensemble (GEFS 0.5&deg;) &bull; Coordenades de Sallent (geoid: 34523).<br>
            El gràfic mostra l'evolució prevista dels diferents membres del model a 850 hPa (temperatura) i precipitació acumulada.
        </div>
    </div>
</body>
</html>
