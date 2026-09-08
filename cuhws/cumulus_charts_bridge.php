<?php
/**
 * cumulus_charts_bridge.php
 * Generates chartswudata txt files for CanvasJS charts in MeteoSallent (CU-HWS).
 * Supports:
 * - Daily charts (ddmmyyyy.txt) from RecentData table in cumulusmx.db
 * - Monthly / 7-day charts (mmyyyy.txt) from DayFileRec and RecentData
 * - Yearly charts (yyyy.txt)
 */

if (php_sapi_name() !== 'cli') {
    // Throttling: run at most once every 60 seconds if accessed via HTTP
    $lockFile = sys_get_temp_dir() . '/cuhws_charts_lock';
    if (file_exists($lockFile) && (time() - filemtime($lockFile) < 60) && empty($_GET['force'])) {
        return;
    }
    @touch($lockFile);
}

// Locate Cumulus data directory
$cumulusDir = null;
$possibleDirs = [
    '/var/www/html/cumulusmxdata',
    '/opt/servidor/cumulusmx/data',
    __DIR__ . '/cumulusmxdata'
];
foreach ($possibleDirs as $d) {
    if (is_dir($d) && file_exists($d . '/cumulusmx.db')) {
        $cumulusDir = $d;
        break;
    }
}

if (!$cumulusDir) {
    if (php_sapi_name() === 'cli') echo "Cumulus data directory not found\n";
    return;
}

$chartDir = __DIR__ . '/chartswudata';
if (!is_dir($chartDir)) {
    @mkdir($chartDir, 0775, true);
}

try {
    $db = new SQLite3($cumulusDir . '/cumulusmx.db', SQLITE3_OPEN_READONLY);
    $db->busyTimeout(3000);
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') echo "Cannot open cumulusmx.db: " . $e->getMessage() . "\n";
    return;
}

function degToCardinal($deg) {
    if ($deg === null || $deg === '') return 'N';
    $deg = (float)$deg;
    $cardinals = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    $idx = (int) round(($deg % 360) / 22.5) % 16;
    return $cardinals[$idx];
}

$todayYmd = date('Y-m-d');
$todayDmY = date('dmY');
$thisMonthYm = date('Y-m');
$thisMonthMY = date('mY');
$thisYearY = date('Y');

// =========================================================================
// 1. GENERATE DAILY FILE (ddmmyyyy.txt) FOR TODAY
// =========================================================================
$dailyQuery = "
    SELECT 
        Timestamp,
        OutsideTemp,
        DewPoint,
        Pressure,
        WindDir,
        WindSpeed,
        WindGust,
        Humidity,
        RainRate,
        RainToday,
        SolarRad
    FROM RecentData
    WHERE Timestamp >= '{$todayYmd} 00:00:00'
    ORDER BY Timestamp ASC
";

$res = $db->query($dailyQuery);
$dailyHeader = "Time,TemperatureC,DewpointC,PressurehPa,WindDirection,WindDirectionDegrees,WindSpeedKMH,WindSpeedGustKMH,Humidity,HourlyPrecipMM,Conditions,Clouds,dailyrainMM,SolarRadiationWatts/m^2,SoftwareType,DateUTC<br>";

// Row 0 empty, row 1 header
$dailyContent = "\n" . $dailyHeader . "\n";

$lastMinute = -1;
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $ts = $row['Timestamp'];
    $dt = strtotime($ts);
    $minute = (int)date('i', $dt);
    
    // Sample roughly every 5 minutes (or latest)
    if ($minute % 5 !== 0 && $lastMinute !== -1) {
        continue;
    }
    $lastMinute = $minute;

    $temp = isset($row['OutsideTemp']) ? sprintf("%.1f", $row['OutsideTemp']) : '0.0';
    $dew = isset($row['DewPoint']) ? sprintf("%.1f", $row['DewPoint']) : '0.0';
    $press = isset($row['Pressure']) ? sprintf("%.1f", $row['Pressure']) : '1013.2';
    $windDeg = isset($row['WindDir']) ? (int)$row['WindDir'] : 0;
    $windCard = degToCardinal($windDeg);
    $windSpd = isset($row['WindSpeed']) ? sprintf("%.1f", $row['WindSpeed']) : '0.0';
    $windGust = isset($row['WindGust']) ? sprintf("%.1f", $row['WindGust']) : '0.0';
    $hum = isset($row['Humidity']) ? (int)round($row['Humidity']) : 0;
    $rainRate = isset($row['RainRate']) ? sprintf("%.1f", $row['RainRate']) : '0.0';
    $rainToday = isset($row['RainToday']) ? sprintf("%.1f", $row['RainToday']) : '0.0';
    $solar = isset($row['SolarRad']) ? sprintf("%.1f", $row['SolarRad']) : '0.0';
    $utc = gmdate('Y-m-d H:i:s', $dt);

    $dailyContent .= "{$ts},{$temp},{$dew},{$press},{$windCard},{$windDeg},{$windSpd},{$windGust},{$hum},{$rainRate},,,{$rainToday},{$solar},CumulusMX,{$utc},\n";
}

$dailyTargetFile = "{$chartDir}/{$todayDmY}.txt";
@file_put_contents($dailyTargetFile, $dailyContent);

// Also generate yesterday if not exists
$yesterdayYmd = date('Y-m-d', strtotime('-1 day'));
$yesterdayDmY = date('dmY', strtotime('-1 day'));
$yesterdayTargetFile = "{$chartDir}/{$yesterdayDmY}.txt";
if (!file_exists($yesterdayTargetFile)) {
    $yRes = $db->query("
        SELECT 
            Timestamp, OutsideTemp, DewPoint, Pressure, WindDir, WindSpeed, WindGust, Humidity, RainRate, RainToday, SolarRad
        FROM RecentData
        WHERE Timestamp >= '{$yesterdayYmd} 00:00:00' AND Timestamp < '{$todayYmd} 00:00:00'
        ORDER BY Timestamp ASC
    ");
    $yContent = "\n" . $dailyHeader . "\n";
    $yHasRows = false;
    $lastMinY = -1;
    while ($row = $yRes->fetchArray(SQLITE3_ASSOC)) {
        $yHasRows = true;
        $ts = $row['Timestamp'];
        $dt = strtotime($ts);
        $minute = (int)date('i', $dt);
        if ($minute % 5 !== 0 && $lastMinY !== -1) continue;
        $lastMinY = $minute;

        $temp = isset($row['OutsideTemp']) ? sprintf("%.1f", $row['OutsideTemp']) : '0.0';
        $dew = isset($row['DewPoint']) ? sprintf("%.1f", $row['DewPoint']) : '0.0';
        $press = isset($row['Pressure']) ? sprintf("%.1f", $row['Pressure']) : '1013.2';
        $windDeg = isset($row['WindDir']) ? (int)$row['WindDir'] : 0;
        $windCard = degToCardinal($windDeg);
        $windSpd = isset($row['WindSpeed']) ? sprintf("%.1f", $row['WindSpeed']) : '0.0';
        $windGust = isset($row['WindGust']) ? sprintf("%.1f", $row['WindGust']) : '0.0';
        $hum = isset($row['Humidity']) ? (int)round($row['Humidity']) : 0;
        $rainRate = isset($row['RainRate']) ? sprintf("%.1f", $row['RainRate']) : '0.0';
        $rainToday = isset($row['RainToday']) ? sprintf("%.1f", $row['RainToday']) : '0.0';
        $solar = isset($row['SolarRad']) ? sprintf("%.1f", $row['SolarRad']) : '0.0';
        $utc = gmdate('Y-m-d H:i:s', $dt);

        $yContent .= "{$ts},{$temp},{$dew},{$press},{$windCard},{$windDeg},{$windSpd},{$windGust},{$hum},{$rainRate},,,{$rainToday},{$solar},CumulusMX,{$utc},\n";
    }
    if ($yHasRows) {
        @file_put_contents($yesterdayTargetFile, $yContent);
    }
}

// =========================================================================
// 2. GENERATE MONTHLY & 7-DAY FILE (mmyyyy.txt) AND YEARLY FILE (yyyy.txt)
// =========================================================================
$monthlyHeader = "Date,TemperatureHighC,TemperatureAvgC,TemperatureLowC,DewpointHighC,DewpointAvgC,DewpointLowC,HumidityHigh,HumidityAvg,HumidityLow,PressureMaxhPa,PressureMinhPa,WindSpeedMaxKMH,WindSpeedAvgKMH,GustSpeedMaxKMH,PrecipitationSumCM<br>";

// We collect all daily summaries for current year and month
$daysData = [];
$baseJson = $chartDir . '/history_' . $thisYearY . '_base.json';
if (file_exists($baseJson)) {
    $baseData = json_decode(file_get_contents($baseJson), true);
    if (is_array($baseData)) {
        $daysData = $baseData;
    }
}

$dfRes = $db->query("
    SELECT 
        strftime('%Y-%m-%d', Date) as day,
        HighTemp, AvgTemp, LowTemp,
        HighDewPoint, LowDewPoint,
        HighHumidity, LowHumidity,
        HighPress, LowPress,
        HighAvgWind, HighGust,
        TotalRain
    FROM DayFileRec
    WHERE Date >= '{$thisYearY}-01-01'
    ORDER BY Date ASC
");

while ($r = $dfRes->fetchArray(SQLITE3_ASSOC)) {
    $day = $r['day'];
    $lowTemp = (float)$r['LowTemp'];
    $lowPress = (float)$r['LowPress'];

    // If DayFileRec has uninitialized zero minimums, attempt to patch from RecentData
    if ($lowTemp <= 0.1 || $lowPress <= 100) {
        $patchAgg = $db->querySingle("
            SELECT 
                max(OutsideTemp) as highTemp, avg(OutsideTemp) as avgTemp, min(OutsideTemp) as lowTemp,
                max(DewPoint) as highDew, avg(DewPoint) as avgDew, min(DewPoint) as lowDew,
                max(Humidity) as highHum, avg(Humidity) as avgHum, min(Humidity) as lowHum,
                max(Pressure) as highPress, min(Pressure) as lowPress,
                max(WindSpeed) as highWind, avg(WindSpeed) as avgWind, max(WindGust) as highGust,
                max(RainToday) as rain
            FROM RecentData
            WHERE Timestamp >= '{$day} 00:00:00' AND Timestamp <= '{$day} 23:59:59'
        ", true);

        if ($patchAgg && $patchAgg['highTemp'] !== null) {
            $daysData[$day] = [
                'day' => $day,
                'highTemp' => sprintf("%.1f", $patchAgg['highTemp']),
                'avgTemp' => sprintf("%.1f", $patchAgg['avgTemp']),
                'lowTemp' => sprintf("%.1f", $patchAgg['lowTemp']),
                'highDew' => sprintf("%.1f", $patchAgg['highDew']),
                'avgDew' => sprintf("%.1f", $patchAgg['avgDew']),
                'lowDew' => sprintf("%.1f", $patchAgg['lowDew']),
                'highHum' => (int)round($patchAgg['highHum']),
                'avgHum' => (int)round($patchAgg['avgHum']),
                'lowHum' => (int)round($patchAgg['lowHum']),
                'highPress' => sprintf("%.1f", $patchAgg['highPress']),
                'lowPress' => sprintf("%.1f", $patchAgg['lowPress']),
                'highWind' => sprintf("%.1f", $patchAgg['highWind']),
                'avgWind' => sprintf("%.1f", $patchAgg['avgWind']),
                'highGust' => sprintf("%.1f", $patchAgg['highGust']),
                'rain' => sprintf("%.2f", $patchAgg['rain'])
            ];
            continue;
        }
    }

    $highTemp = sprintf("%.1f", $r['HighTemp']);
    $avgTemp = sprintf("%.1f", $r['AvgTemp']);
    $lowTemp = sprintf("%.1f", $r['LowTemp']);
    $highDew = sprintf("%.1f", $r['HighDewPoint'] ?? $r['AvgTemp']);
    $lowDew = sprintf("%.1f", $r['LowDewPoint'] ?? ($r['LowTemp'] - 3));
    $avgDew = sprintf("%.1f", ($highDew + $lowDew) / 2);
    $highHum = (int)($r['HighHumidity'] ?? 80);
    $lowHum = (int)($r['LowHumidity'] ?? 30);
    $avgHum = (int)round(($highHum + $lowHum) / 2);
    $highPress = sprintf("%.1f", $r['HighPress']);
    $lowPress = sprintf("%.1f", $r['LowPress']);
    $highWind = sprintf("%.1f", $r['HighAvgWind']);
    $avgWind = sprintf("%.1f", $highWind * 0.4);
    $highGust = sprintf("%.1f", $r['HighGust']);
    $totalRain = sprintf("%.2f", $r['TotalRain']);

    $daysData[$day] = [
        'day' => $day,
        'highTemp' => $highTemp, 'avgTemp' => $avgTemp, 'lowTemp' => $lowTemp,
        'highDew' => $highDew, 'avgDew' => $avgDew, 'lowDew' => $lowDew,
        'highHum' => $highHum, 'avgHum' => $avgHum, 'lowHum' => $lowHum,
        'highPress' => $highPress, 'lowPress' => $lowPress,
        'highWind' => $highWind, 'avgWind' => $avgWind, 'highGust' => $highGust,
        'rain' => $totalRain
    ];
}

// Check if today is already in DayFileRec; if not, aggregate from RecentData
if (!isset($daysData[$todayYmd])) {
    $todayAgg = $db->querySingle("
        SELECT 
            '{$todayYmd}' as day,
            max(OutsideTemp) as highTemp,
            avg(OutsideTemp) as avgTemp,
            min(OutsideTemp) as lowTemp,
            max(DewPoint) as highDew,
            avg(DewPoint) as avgDew,
            min(DewPoint) as lowDew,
            max(Humidity) as highHum,
            avg(Humidity) as avgHum,
            min(Humidity) as lowHum,
            max(Pressure) as highPress,
            min(Pressure) as lowPress,
            max(WindSpeed) as highWind,
            avg(WindSpeed) as avgWind,
            max(WindGust) as highGust,
            max(RainToday) as rain
        FROM RecentData
        WHERE Timestamp >= '{$todayYmd} 00:00:00'
    ", true);

    if ($todayAgg && $todayAgg['highTemp'] !== null) {
        $daysData[$todayYmd] = [
            'day' => $todayYmd,
            'highTemp' => sprintf("%.1f", $todayAgg['highTemp']),
            'avgTemp' => sprintf("%.1f", $todayAgg['avgTemp']),
            'lowTemp' => sprintf("%.1f", $todayAgg['lowTemp']),
            'highDew' => sprintf("%.1f", $todayAgg['highDew']),
            'avgDew' => sprintf("%.1f", $todayAgg['avgDew']),
            'lowDew' => sprintf("%.1f", $todayAgg['lowDew']),
            'highHum' => (int)round($todayAgg['highHum']),
            'avgHum' => (int)round($todayAgg['avgHum']),
            'lowHum' => (int)round($todayAgg['lowHum']),
            'highPress' => sprintf("%.1f", $todayAgg['highPress']),
            'lowPress' => sprintf("%.1f", $todayAgg['lowPress']),
            'highWind' => sprintf("%.1f", $todayAgg['highWind']),
            'avgWind' => sprintf("%.1f", $todayAgg['avgWind']),
            'highGust' => sprintf("%.1f", $todayAgg['highGust']),
            'rain' => sprintf("%.2f", $todayAgg['rain'])
        ];
    }
}

ksort($daysData);

// Build Monthly Content
$monthlyContent = "\n" . $monthlyHeader . "\n";
foreach ($daysData as $day => $d) {
    if (strpos($day, $thisMonthYm) === 0) {
        $dt = strtotime($day);
        $formattedDate = date('Y-n-j', $dt);
        $monthlyContent .= "{$formattedDate},{$d['highTemp']},{$d['avgTemp']},{$d['lowTemp']},{$d['highDew']},{$d['avgDew']},{$d['lowDew']},{$d['highHum']},{$d['avgHum']},{$d['lowHum']},{$d['highPress']},{$d['lowPress']},{$d['highWind']},{$d['avgWind']},{$d['highGust']},{$d['rain']}\n";
    }
}
$monthlyTargetFile = "{$chartDir}/{$thisMonthMY}.txt";
@file_put_contents($monthlyTargetFile, $monthlyContent);

// Build Yearly Content
$yearlyContent = "\n" . $monthlyHeader . "\n";
foreach ($daysData as $day => $d) {
    if (strpos($day, $thisYearY) === 0) {
        $dt = strtotime($day);
        $formattedDate = date('Y-n-j', $dt);
        $yearlyContent .= "{$formattedDate},{$d['highTemp']},{$d['avgTemp']},{$d['lowTemp']},{$d['highDew']},{$d['avgDew']},{$d['lowDew']},{$d['highHum']},{$d['avgHum']},{$d['lowHum']},{$d['highPress']},{$d['lowPress']},{$d['highWind']},{$d['avgWind']},{$d['highGust']},{$d['rain']}\n";
    }
}
$yearlyTargetFile = "{$chartDir}/{$thisYearY}.txt";
@file_put_contents($yearlyTargetFile, $yearlyContent);

// Build Weekly Content (rolling last 7 calendar days)
$weeklyKeys = array_keys($daysData);
sort($weeklyKeys);
$recentWeekKeys = array_slice($weeklyKeys, -7);
$weeklyContent = "\n" . $monthlyHeader . "\n";
foreach ($recentWeekKeys as $dKey) {
    $d = $daysData[$dKey];
    $dt = strtotime($dKey);
    $formattedDate = date('Y-n-j', $dt);
    $weeklyContent .= "{$formattedDate},{$d['highTemp']},{$d['avgTemp']},{$d['lowTemp']},{$d['highDew']},{$d['avgDew']},{$d['lowDew']},{$d['highHum']},{$d['avgHum']},{$d['lowHum']},{$d['highPress']},{$d['lowPress']},{$d['highWind']},{$d['avgWind']},{$d['highGust']},{$d['rain']}\n";
}
$weeklyTargetFile = "{$chartDir}/weekly.txt";
@file_put_contents($weeklyTargetFile, $weeklyContent);

if (php_sapi_name() === 'cli') {
    echo "Generated:\n  {$dailyTargetFile}\n  {$monthlyTargetFile}\n  {$yearlyTargetFile}\n  {$weeklyTargetFile}\n";
}
