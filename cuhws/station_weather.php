<?php
/**
 * station_weather.php
 * Sincronització de dades meteorològiques en temps real (Davis Vantage Pro2+)
 * per al panell de control solar (Solar Analytics Pro).
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Fitxer de telemetria en temps real generat per Cumulus MX
$gaugesFile = __DIR__ . '/cumulusdata/realtimegauges.txt';
if (!file_exists($gaugesFile)) {
    $gaugesFile = '/opt/servidor/cumulusmx/web/realtimegauges.txt';
}

$temp = 20.0;
$hum = 50;
$press = 1013.2;
$solarRad = 0.0;
$solarMax = 0.0;
$uv = 0.0;
$windSpeed = 0.0;
$windGust = 0.0;
$rainRate = 0.0;
$rainToday = 0.0;
$updatedTime = date('H:i');
$forecastText = "";

if (file_exists($gaugesFile)) {
    $content = file_get_contents($gaugesFile);
    $data = json_decode($content, true);
    if ($data) {
        $temp = isset($data['temp']) ? floatval($data['temp']) : $temp;
        $hum = isset($data['hum']) ? intval($data['hum']) : $hum;
        $press = isset($data['press']) ? floatval($data['press']) : $press;
        $solarRad = isset($data['SolarRad']) ? floatval($data['SolarRad']) : $solarRad;
        $solarMax = isset($data['CurrentSolarMax']) ? floatval($data['CurrentSolarMax']) : $solarMax;
        $uv = isset($data['UV']) ? floatval($data['UV']) : $uv;
        $windSpeed = isset($data['wspeed']) ? floatval($data['wspeed']) : $windSpeed;
        $windGust = isset($data['wgust']) ? floatval($data['wgust']) : $windGust;
        $rainRate = isset($data['rrate']) ? floatval($data['rrate']) : $rainRate;
        $rainToday = isset($data['rfall']) ? floatval($data['rfall']) : $rainToday;
        $updatedTime = isset($data['date']) ? $data['date'] : $updatedTime;
        $forecastText = isset($data['forecast']) ? html_entity_decode($data['forecast']) : "";
    }
}

// Coordenades de Sallent
$lat = 41.8260;
$lon = 1.8955;
date_default_timezone_set('Europe/Madrid');
$sunInfo = date_sun_info(time(), $lat, $lon);
$isDay = (time() >= $sunInfo['sunrise'] && time() < $sunInfo['sunset']);

// Càlcul de l'estat del cel basat en sensors físics Davis
$code = 0;
$desc = $isDay ? "Assolellat" : "Nit Serena";
$gradient = $isDay ? "linear-gradient(135deg, #ffde00, #ffb800)" : "linear-gradient(135deg, #4a5568, #1a202c)";
$shadow = $isDay ? "rgba(255,184,0,0.5)" : "rgba(26,32,44,0.5)";

if ($rainRate > 5.0) {
    $code = 65;
    $desc = "Pluja Forta";
    $gradient = "linear-gradient(135deg, #2b6cb0, #1a365d)";
    $shadow = "rgba(43,108,176,0.6)";
} elseif ($rainRate > 1.0) {
    $code = 63;
    $desc = "Pluja Moderada";
    $gradient = "linear-gradient(135deg, #4299e1, #2b6cb0)";
    $shadow = "rgba(66,153,225,0.5)";
} elseif ($rainRate > 0.0) {
    $code = 61;
    $desc = "Pluja Dèbil";
    $gradient = "linear-gradient(135deg, #63b3ed, #3182ce)";
    $shadow = "rgba(99,179,237,0.5)";
} elseif ($isDay) {
    if ($solarMax > 50) {
        $ratio = $solarRad / max(1.0, $solarMax);
        if ($ratio >= 0.78) {
            $code = 0;
            $desc = "Assolellat (Cel Serè)";
            $gradient = "linear-gradient(135deg, #ffde00, #ffb800)";
            $shadow = "rgba(255,184,0,0.5)";
        } elseif ($ratio >= 0.50) {
            $code = 1;
            $desc = "Pocs Núvols";
            $gradient = "linear-gradient(135deg, #ffde00, #a0aec0)";
            $shadow = "rgba(255,222,0,0.5)";
        } elseif ($ratio >= 0.22) {
            $code = 2;
            $desc = "Parcialment Ennuvolat";
            $gradient = "linear-gradient(135deg, #e2e8f0, #718096)";
            $shadow = "rgba(113,128,150,0.5)";
        } else {
            $code = 3;
            $desc = "Ennuvolat / Cobert";
            $gradient = "linear-gradient(135deg, #cbd5e0, #4a5568)";
            $shadow = "rgba(74,85,104,0.5)";
        }
    } else {
        if ($hum > 90) {
            $code = 45;
            $desc = "Boira Baixa";
            $gradient = "linear-gradient(135deg, #e2e8f0, #a0aec0)";
            $shadow = "rgba(160,174,192,0.5)";
        } else {
            $code = 0;
            $desc = "Sol Post / Naixent";
            $gradient = "linear-gradient(135deg, #ed8936, #dd6b20)";
            $shadow = "rgba(237,137,54,0.5)";
        }
    }
} else {
    // Nit
    if ($hum > 92) {
        $code = 45;
        $desc = "Boira Nocturna";
        $gradient = "linear-gradient(135deg, #4a5568, #2d3748)";
        $shadow = "rgba(74,85,104,0.5)";
    } elseif ($press < 1010 && $hum > 75) {
        $code = 2;
        $desc = "Nit Ennuvolada";
        $gradient = "linear-gradient(135deg, #718096, #2d3748)";
        $shadow = "rgba(113,128,150,0.5)";
    } else {
        $code = 0;
        $desc = "Nit Serena";
        $gradient = "linear-gradient(135deg, #4a5568, #1a202c)";
        $shadow = "rgba(26,32,44,0.5)";
    }
}

$solarPercent = ($solarMax > 0) ? round(($solarRad / $solarMax) * 100, 1) : 0.0;

echo json_encode([
    'success' => true,
    'station' => 'Davis Vantage Pro2 Plus (Sallent)',
    'temp' => $temp,
    'hum' => $hum,
    'press' => $press,
    'solar_rad' => $solarRad,
    'solar_max' => $solarMax,
    'solar_percent' => $solarPercent,
    'uv' => $uv,
    'wind_speed' => $windSpeed,
    'wind_gust' => $windGust,
    'rain_rate' => $rainRate,
    'rain_today' => $rainToday,
    'is_day' => $isDay,
    'code' => $code,
    'desc' => $desc,
    'gradient' => $gradient,
    'shadow' => $shadow,
    'updated' => $updatedTime,
    'forecast' => $forecastText
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
