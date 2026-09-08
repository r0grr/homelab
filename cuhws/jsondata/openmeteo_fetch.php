<?php
// Open-Meteo forecast fetcher for MeteoSallent
// Caches forecast in DarkSky JSON format for CU-HWS dashboard compatibility

chdir(dirname(__FILE__));
include_once('../settings1.php');
include_once('../common.php');

$cache_file = "darksky-{$language}.txt";
$cache_time = 1800; // 30 minutes

if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time) && filesize($cache_file) > 500) {
    return;
}

$lat_coord = !empty($lat) ? $lat : 41.7925;
$lon_coord = !empty($lon) ? $lon : 1.8950;
$tz = !empty($TZ) ? urlencode($TZ) : "Europe%2FMadrid";

$url = "https://api.open-meteo.com/v1/forecast?latitude={$lat_coord}&longitude={$lon_coord}&daily=weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,windspeed_10m_max,winddirection_10m_dominant&timezone={$tz}";

$ctx = stream_context_create([
    'http' => [
        'timeout' => 5,
        'user_agent' => 'MeteoSallent-Weather34/1.0'
    ]
]);

$raw = @file_get_contents($url, false, $ctx);
if (!$raw) {
    return;
}

$data = json_decode($raw, true);
if (empty($data['daily']['time'])) {
    return;
}

$daily = $data['daily'];

$wmo_map = [
    0 => ['clear-day', 'Cel serè'],
    1 => ['clear-day', 'Serè'],
    2 => ['partly-cloudy-day', 'Parcialment ennuvolat'],
    3 => ['cloudy', 'Ennuvolat'],
    45 => ['fog', 'Boira'],
    48 => ['fog', 'Boira dipositada'],
    51 => ['rain', 'Plugim feble'],
    53 => ['rain', 'Plugim'],
    55 => ['rain', 'Plugim intens'],
    61 => ['rain', 'Pluja feble'],
    63 => ['rain', 'Pluja moderada'],
    65 => ['rain', 'Pluja forta'],
    71 => ['snow', 'Neu feble'],
    73 => ['snow', 'Neu moderada'],
    75 => ['snow', 'Neu forta'],
    80 => ['rain', 'Xàfecs febles'],
    81 => ['rain', 'Xàfecs'],
    82 => ['rain', 'Xàfecs violents'],
    95 => ['thunderstorm', 'Tempesta'],
    96 => ['thunderstorm', 'Tempesta amb pedra'],
    99 => ['thunderstorm', 'Tempesta forta']
];

$daily_data = [];
$num_days = min(4, count($daily['time']));
for ($i = 0; $i < $num_days; $i++) {
    $code = $daily['weathercode'][$i];
    $icon = isset($wmo_map[$code]) ? $wmo_map[$code][0] : 'partly-cloudy-day';
    $summary = isset($wmo_map[$code]) ? $wmo_map[$code][1] : 'Variable';
    $ts = strtotime($daily['time'][$i] . ' 12:00:00');
    
    $daily_data[] = [
        'time' => $ts,
        'summary' => $summary,
        'icon' => $icon,
        'temperatureMax' => round($daily['temperature_2m_max'][$i], 1),
        'temperatureMin' => round($daily['temperature_2m_min'][$i], 1),
        'windBearing' => round($daily['winddirection_10m_dominant'][$i]),
        'cloudCover' => ($code >= 3) ? 0.8 : (($code >= 1) ? 0.4 : 0.1),
        'humidity' => 0.55,
        'precipProbability' => round($daily['precipitation_probability_max'][$i] / 100, 2),
        'precipIntensityMax' => round($daily['precipitation_sum'][$i], 1),
        'precipAccumulation' => 0,
        'windSpeed' => round($daily['windspeed_10m_max'][$i], 0)
    ];
}

$ds = [
    'latitude' => (float)$lat_coord,
    'longitude' => (float)$lon_coord,
    'timezone' => !empty($TZ) ? $TZ : 'Europe/Madrid',
    'currently' => [
        'time' => time(),
        'summary' => $daily_data[0]['summary'],
        'icon' => $daily_data[0]['icon'],
        'temperature' => $daily_data[0]['temperatureMax'],
        'cloudCover' => $daily_data[0]['cloudCover']
    ],
    'hourly' => ['summary' => '', 'icon' => '', 'data' => []],
    'daily' => [
        'summary' => 'Previsió meteorològica de Sallent',
        'icon' => $daily_data[0]['icon'],
        'data' => $daily_data
    ]
];

$encoded = json_encode($ds);
file_put_contents("darksky-{$language}.txt", $encoded);
file_put_contents("darksky-ca.txt", $encoded);
file_put_contents("darksky-cat.txt", $encoded);
file_put_contents("darksky-en.txt", $encoded);
echo "Forecast updated successfully\n";
