<?php
// Function to get cached or fresh Weather.com AQI data for Sallent
function get_weather_aqi() {
    $cache_file = __DIR__ . '/weather_aqi.json';
    $cache_time = 1200; // 20 minutes cache

    // Serve cached data if still fresh
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        $cached = @json_decode(file_get_contents($cache_file), true);
        if (!empty($cached) && is_array($cached)) {
            return $cached;
        }
    }

$ctx = stream_context_create([
    'http' => [
        'timeout' => 4,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$api_key = '71f92ea9dd2f4790b92ea9dd2f779061';
$url = "https://api.weather.com/v3/wx/globalAirQuality?geocode=41.7925,1.8950&language=ca-AD&scale=EPA&format=json&apiKey={$api_key}";

$res = @file_get_contents($url, false, $ctx);
if ($res) {
    $data = json_decode($res, true);
    if (!empty($data['globalairquality'])) {
        $gaq = $data['globalairquality'];
        $aqi = intval($gaq['airQualityIndex'] ?? 50);
        $cat = $gaq['airQualityCategory'] ?? 'Bona';
        $color = '#27ae60';

        if ($aqi <= 50) {
            $color = '#00e400';
            $cat = 'Bona';
        } elseif ($aqi <= 100) {
            $color = '#f1c40f';
            $cat = 'Moderada';
        } elseif ($aqi <= 150) {
            $color = '#ff7e00';
            $cat = 'Perjudicial (G. Sensibles)';
        } elseif ($aqi <= 200) {
            $color = '#e74c3c';
            $cat = 'Insalubre';
        } else {
            $color = '#8f3f97';
            $cat = 'Molt Insalubre';
        }

        $pollutants = $gaq['pollutants'] ?? [];
        $pm25 = isset($pollutants['PM2.5']['amount']) ? round(floatval($pollutants['PM2.5']['amount']), 1) : 12.8;
        $pm10 = isset($pollutants['PM10']['amount']) ? round(floatval($pollutants['PM10']['amount']), 1) : 20.1;
        $o3   = isset($pollutants['O3']['amount']) ? round(floatval($pollutants['O3']['amount']), 1) : 128.3;
        $no2  = isset($pollutants['NO2']['amount']) ? round(floatval($pollutants['NO2']['amount']), 1) : 12.0;

        $msg = $gaq['messages']['General']['text'] ?? "Qualitat de l'aire acceptable per a la majoria de persones.";

        $output = [
            'updated_at' => time(),
            'hora' => date('H:i'),
            'data' => date('d/m/Y'),
            'aqi' => $aqi,
            'category' => $cat,
            'color' => $color,
            'primary_pollutant' => $gaq['primaryPollutant'] ?? 'PM2.5',
            'pm25' => $pm25,
            'pm10' => $pm10,
            'o3' => $o3,
            'no2' => $no2,
            'message' => $msg,
            'url' => 'https://weather.com/ca-AD/es/barcelona/city/sallent/air-quality'
        ];

        @file_put_contents($cache_file, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $output;
    }
}

    // Fallback to existing cache if API fails
    if (file_exists($cache_file)) {
        $cached = @json_decode(file_get_contents($cache_file), true);
        if (!empty($cached) && is_array($cached)) {
            return $cached;
        }
    }

    return [
        'updated_at' => time(),
        'hora' => date('H:i'),
        'data' => date('d/m/Y'),
        'aqi' => 61,
        'category' => 'Moderada',
        'color' => '#f1c40f',
        'primary_pollutant' => 'PM2.5',
        'pm25' => 12.8,
        'pm10' => 20.1,
        'o3' => 128.3,
        'no2' => 12.0,
        'message' => "La qualitat de l'aire és acceptable.",
        'url' => 'https://weather.com/ca-AD/es/barcelona/city/sallent/air-quality'
    ];
}

// If accessed directly via URL, output JSON with headers
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(get_weather_aqi(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
