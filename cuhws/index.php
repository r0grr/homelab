<?php
header('Content-type: text/html; charset=utf-8');
if(!file_exists('settings1.php')) { copy('initial-settings1.php','settings1.php'); }
include('livedata.php');
include('settings1.php');
include('common.php');
date_default_timezone_set($TZ);

$seo_titles = [
    'cat' => 'El Temps a Sallent en directe | MeteoSallent - Estació Meteorològica Davis',
    'sp'  => 'El Tiempo en Sallent en directo | MeteoSallent - Estación Meteorológica Davis',
    'en'  => 'Live Weather in Sallent | MeteoSallent - Davis Weather Station',
    'fr'  => 'Météo en direct à Sallent | MeteoSallent - Station Météorologique Davis'
];
$seo_descriptions = [
    'cat' => 'Dades meteorològiques en temps real a Sallent (El Bages): temperatura, pluja, vent, pressió i humitat. Estació meteorològica Davis Vantage Pro2 Plus.',
    'sp'  => 'Datos meteorológicos en tiempo real en Sallent (El Bages): temperatura, lluvia, viento, presión y humedad. Estación meteorológica Davis Vantage Pro2 Plus.',
    'en'  => 'Real-time weather conditions in Sallent (Catalonia): live temperature, rainfall, wind speed, barometric pressure and humidity. Davis Vantage Pro2 Plus station.',
    'fr'  => 'Données météorologiques en direct à Sallent (Catalogne) : température, précipitations, vent, pression et humidité. Station météo Davis Vantage Pro2 Plus.'
];

$cur_lang = $selected_lang_code ?? 'cat';
$page_title = $seo_titles[$cur_lang] ?? $seo_titles['cat'];
$page_description = $seo_descriptions[$cur_lang] ?? $seo_descriptions['cat'];
$page_locale = ($cur_lang === 'sp') ? 'es_ES' : (($cur_lang === 'fr') ? 'fr_FR' : (($cur_lang === 'en') ? 'en_US' : 'ca_ES'));
?>
<!DOCTYPE html>
<html lang="<?php echo $language;?>">
<head>
  <meta charset="utf-8">
  <title><?php echo $page_title; ?></title>
  <meta name="description" content="<?php echo $page_description; ?>">
  <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, viewport-fit=cover">
  <link rel="canonical" href="https://www.tempscat.com/">

  <!-- Open Graph / Xarxes Socials -->
  <meta property="og:locale" content="<?php echo $page_locale; ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo $page_title; ?>">
  <meta property="og:description" content="<?php echo $page_description; ?>">
  <meta property="og:url" content="https://www.tempscat.com/">
  <meta property="og:site_name" content="TempsCat - MeteoSallent">
  <meta property="og:image" content="https://www.tempscat.com/img/meteosallent_logo.png">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?php echo $page_title; ?>">
  <meta name="twitter:description" content="<?php echo $page_description; ?>">
  <meta name="twitter:image" content="https://www.tempscat.com/img/meteosallent_logo.png">

  <!-- Dades estructurades JSON-LD / Schema.org -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Place",
    "name": "Estació Meteorològica Sallent (MeteoSallent)",
    "description": "Estació meteorològica en temps real a Sallent, El Bages",
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": 41.818,
      "longitude": 1.895
    },
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Sallent",
      "addressRegion": "Barcelona",
      "postalCode": "08650",
      "addressCountry": "ES"
    }
  }
  </script>

  <link rel="icon" type="image/png" href="img/favicon-32x32.png?v=3" sizes="32x32">
  <link rel="icon" type="image/png" href="img/favicon-196x196.png?v=3" sizes="196x196">
  <link rel="apple-touch-icon" href="img/apple-touch-icon.png?v=3">
  <link rel="shortcut icon" href="favicon.ico?v=3">
  <link href="css/main.<?php echo $theme;?>.css?version=<?php echo filemtime('css/main.'.$theme.'.css');?>" rel="stylesheet prefetch">
  <script src="js/jquery.js"></script>
</head>
<body>

<!-- TOP NAVIGATION NAVBAR -->
<header class="top-navbar">
  <div class="top-navbar-row">
    <div class="top-navbar-left">
      <a class="nav-link-btn" onclick="document.getElementById('openweather34sidebarMenu').checked = !document.getElementById('openweather34sidebarMenu').checked;" title="Menú" style="cursor: pointer;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        <span>Menú</span>
      </a>
      <a class="nav-link-btn" href="index.php" title="Inici">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <span>Inici</span>
      </a>
      <a class="nav-link-btn" href="https://sqv.tempscat.com" target="_blank" title="Estació Meteorològica Sant Quirze del Vallès">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        <span>Sant Quirze</span>
      </a>
    </div>

    <div class="navbar-brand-block">
      <div class="navbar-brand">
        <img src="img/meteosallent_logo.png" class="meteosallent-logo" width="34" height="30" alt="MeteoSallent - Estació Meteorològica de Sallent (El Bages)">
        <h1 class="navbar-brand-title" style="margin: 0; font-size: inherit; font-weight: inherit; display: inline;">MeteoSallent</h1>
      </div>
      <div class="navbar-brand-sub desktop-only">
        Davis Vantage Pro2 Plus (UV-Solar) &bull; 336m. snm. &bull; E08650 SALLENT (El Bages) CAT
      </div>
    </div>

    <div class="top-navbar-right">
      <a href="https://www.meteoclimatic.net/perfil/ESCAT0800000008650B" target="_blank" class="meteoclimatic-seal-badge" title="Meteoclimatic Sallent E08650">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
        <span>Estació amb segell Meteoclimatic de qualitat destacada</span>
      </a>
    </div>
  </div>

  <div class="top-navbar-mobile-sub mobile-only">
    <div class="mobile-station-line">
      Davis Vantage Pro2 Plus (UV-Solar) - 336m. snm. - E08650 SALLENT (El Bages) CAT
    </div>
    <a href="https://www.meteoclimatic.net/perfil/ESCAT0800000008650B" target="_blank" class="mobile-meteoclimatic-line" title="Meteoclimatic Sallent E08650">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
      <span>Estació amb segell Meteoclimatic de qualitat destacada</span>
    </a>
  </div>
</header>

<!-- ROW 1: TOP KPI STRIP (5 compact boxes - Collapsible on mobile/tablet) -->
<div class="top-summary-wrapper">
  <button type="button" class="top-summary-toggle-btn" id="topSummaryToggle" onclick="toggleTopSummary();" aria-expanded="false">
    <span class="top-summary-btn-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
      <span>Indicadors ràpids en directe</span>
      <span class="summary-badge">6 blocs</span>
    </span>
    <span class="top-summary-chevron">&#9662;</span>
  </button>
  <div class="top-summary-content" id="top_summary_content">
    <div class="PWS_weather_container">
      <div class="PWS_weather_item_s"><div id="pws_topclock"><?php include('pws_topclock.php'); ?></div></div>
      <div class="PWS_weather_item_s"><div id="pws_toptemp"><?php include('pws_toptemp.php'); ?></div></div>
      <div class="PWS_weather_item_s"><div id="pws_topextra"><?php include('pws_topextra.php'); ?></div></div>
      <div class="PWS_weather_item_s"><div id="pws_topwind"><?php include('pws_topwind.php'); ?></div></div>
      <div class="PWS_weather_item_s"><div id="pws_toprain"><?php include('pws_toprain.php'); ?></div></div>
      <div class="PWS_weather_item_s"><div id="pws_topticker"><?php include('pws_topticker.php'); ?></div></div>
    </div>
  </div>
</div>

<!-- ROW 2: TELEMETRIA PRINCIPAL EN DIRECTE (1. Temperatura, 2. Humitat, 3. Precipitació, 4. Vent) -->
<div class="PWS_weather_container">
  <div class="PWS_weather_item"><div id="pws_temperature"><?php include('pws_temperature.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_humidity"><?php include('pws_humidity.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_rainfall"><?php include('pws_rainfall.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_wind"><?php include('pws_wind.php'); ?></div></div>
</div>

<!-- ROW 3: PRESSIÓ, RADIACIÓ SOLAR I ASTRONOMIA (5. Baròmetre, 6. Solar UV, 7. Posició Solar, 8. Fase Lunar) -->
<div class="PWS_weather_container">
  <div class="PWS_weather_item"><div id="pws_barometer"><?php include('pws_barometer.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_solaruv"><?php include('pws_solaruv.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_sun"><?php include('pws_sun.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_moon"><?php include('pws_moon.php'); ?></div></div>
</div>

<!-- ROW 4: ESTAT DEL CEL, PREVISIÓ, RISC FORESTAL I QUALITAT AIRE (9. Estat del Cel, 10. Previsió, 11. Pla Alfa, 12. Qualitat Aire) -->
<div class="PWS_weather_container">
  <div class="PWS_weather_item"><div id="pws_currentsky"><?php include('pws_currentsky.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_forecast"><?php include('pws_forecast.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_fwi"><?php include('pws_fwi.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_aqi"><?php include('pws_aqi.php'); ?></div></div>
</div>

<!-- ROW 5: CLIMA INTERIOR, CLIMATOLOGIA, RÈCORDS I CÀMERA (13. Interior, 14. Mitjanes, 15. Rècords, 16. Càmera Web) -->
<div class="PWS_weather_container">
  <div class="PWS_weather_item"><div id="pws_indoor"><?php include('pws_indoor.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_monthlytemps"><?php include('pws_monthlytemps.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_records"><?php include('pws_records.php'); ?></div></div>
  <div class="PWS_weather_item"><div id="pws_webcam"><?php include('pws_webcam.php'); ?></div></div>
</div>

<!-- ROW 6: TARGETES AMPLES (17. Compte enrere & 18. Xarxa Sísmica ICGC) -->
<div class="PWS_weather_container">
  <div class="PWS_weather_item_w"><div id="pws_countdown"><?php include('pws_countdown.php'); ?></div></div>
  <div class="PWS_weather_item_w"><div id="pws_eqlist"><?php include('pws_eqlist.php'); ?></div></div>
</div>

<!-- FOOTER -->
<div class="PWS_weather_container pws_footer_container">
  <div class="pws_footer_card">
    <div class="pws_footer_logos">
      <a href="https://www.davisinstruments.com/" target="_blank" title="Davis Instruments">
        <img src="img/designedfor.svg" width="95" alt="Davis Instruments">
      </a>
      <a href="https://cumuluswiki.org/a/Software" target="_blank" title="Cumulus MX">
        <img src="img/cumulusmx.png" height="24" alt="Cumulus MX">
      </a>
      <a href="https://www.meteoclimatic.net/perfil/ESCAT0800000008650B" target="_blank" class="meteoclimatic-footer-badge" title="Meteoclimatic E08650">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
        <span>Meteoclimatic E08650</span>
      </a>
    </div>

    <div class="pws_footer_info">
      <div class="pws_footer_station">
        <b>Estació Meteorològica Davis Vantage Pro2 Plus (UV-Solar)</b> &bull; Sallent (El Bages, Catalunya)
      </div>
      <div class="pws_footer_meta">
        Altitud: <b>336m. snm.</b> &bull; Codi: <b>E08650 SALLENT (El Bages) CAT</b> &bull; Xarxa Tempscat (<a href="https://sqv.tempscat.com" target="_blank" style="color: #01a4b4; text-decoration: none; font-weight: 600;">Sant Quirze</a>) &bull; Actualització contínua en directe
      </div>
      <div class="pws_footer_seal">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="#fbbf24" style="vertical-align: -1px; margin-right: 4px;"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
        <span>Estació amb segell Meteoclimatic de qualitat destacada</span>
      </div>
      <div class="pws_footer_disclaimer">
        Dades no oficials. No utilitzar per a protecció civil o alertes d'emergència.
      </div>
    </div>

    <div class="pws_footer_lang">
      <a href="index.php?lang=cat" title="Catalunya / Català" class="pws_flag_btn">
        <img src="img/flags/cat.svg" width="26" height="18" alt="Catalunya">
        <span>Català</span>
      </a>
    </div>
  </div>
</div>

<?php include('updater.php'); ?>
<script>
function toggleTopSummary() {
  var $c = $('#top_summary_content');
  var $btn = $('#topSummaryToggle');
  if ($c.length) {
    $c.stop(true, true).slideToggle(220, function() {
      var isVis = $(this).is(':visible');
      $btn.toggleClass('is-active', isVis);
      $btn.attr('aria-expanded', isVis ? 'true' : 'false');
    });
  } else {
    var el = document.getElementById('top_summary_content');
    if (el) {
      var isHidden = (el.style.display === 'none' || getComputedStyle(el).display === 'none');
      el.style.display = isHidden ? 'block' : 'none';
      $btn.toggleClass('is-active', isHidden);
      $btn.attr('aria-expanded', isHidden ? 'true' : 'false');
    }
  }
}

$(document).on('keydown', function(e) {
  if (e.key === 'Escape') {
    var chk = document.getElementById('openweather34sidebarMenu');
    if (chk) chk.checked = false;
  }
});
</script>
<?php include('menu.php'); ?>

</body>
</html>
