<?php
include('settings.php');include('livedata.php');error_reporting(0); 
$result = date_sun_info(time(), $lat, $lon);
$suns2 =date('G.i', $result['sunset']);
$sunrs2 =date('G.i', $result['sunrise']);
$now =date('G.i');
// METAR fetch: NOAA Aviation Weather Center for LEBL (Josep Tarradellas Barcelona-El Prat)
$metar_file = __DIR__ . '/jsondata/metar34.txt';
$need_fetch = !file_exists($metar_file) || (time() - filemtime($metar_file) > 900) || (filesize($metar_file) < 200);

if ($need_fetch) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => "User-Agent: MeteoSallent-WeatherStation/2.0\r\n"
        ]
    ]);
    $raw_noaa = @file_get_contents('https://aviationweather.gov/api/data/metar?ids=LEBL&format=json', false, $ctx);
    if ($raw_noaa) {
        $noaa_arr = json_decode($raw_noaa, true);
        if (is_array($noaa_arr) && !empty($noaa_arr[0]['icaoId'])) {
            $n = $noaa_arr[0];
            $t = isset($n['temp']) ? floatval($n['temp']) : 20.0;
            $dp = isset($n['dewp']) ? floatval($n['dewp']) : 15.0;
            $press = isset($n['altim']) ? floatval($n['altim']) : 1013.25;
            $wdir = is_numeric($n['wdir'] ?? null) ? intval($n['wdir']) : 90;
            $wspd_kts = isset($n['wspd']) ? floatval($n['wspd']) : 0.0;
            $wspd_mph = $wspd_kts * 1.15078;
            $clouds = !empty($n['cover']) ? $n['cover'] : 'FEW';
            $cond_code = !empty($n['wxString']) ? $n['wxString'] : '';
            
            // Calculate relative humidity from temp & dewp
            $rh = 100 * exp((17.625 * $dp) / (243.04 + $dp)) / exp((17.625 * $t) / (243.04 + $t));
            $rh = max(0, min(100, round($rh)));

            $clean_data = [
                'data' => [[
                    'observed' => $n['reportTime'] ?? date('c'),
                    'raw_text' => $n['rawOb'] ?? '',
                    'icao' => 'LEBL',
                    'station' => ['name' => 'Aeroport Josep Tarradellas Barcelona-El Prat'],
                    'barometer' => [
                        'hg' => round($press * 0.02953, 2),
                        'mb' => round($press, 1)
                    ],
                    'conditions' => [['code' => $cond_code, 'text' => $cond_code]],
                    'clouds' => [['code' => $clouds, 'text' => $clouds]],
                    'dewpoint' => [
                        'celsius' => round($dp, 1),
                        'fahrenheit' => round($dp * 1.8 + 32, 1)
                    ],
                    'temperature' => [
                        'celsius' => round($t, 1),
                        'fahrenheit' => round($t * 1.8 + 32, 1)
                    ],
                    'humidity' => ['percent' => $rh],
                    'visibility' => [
                        'meters' => '10000',
                        'miles' => '6.2'
                    ],
                    'wind' => [
                        'degrees' => $wdir,
                        'speed_mph' => round($wspd_mph, 1),
                        'speed_kts' => round($wspd_kts, 1)
                    ],
                    'rain_in' => 0
                ]]
            ];
            @file_put_contents($metar_file, json_encode($clean_data, JSON_PRETTY_PRINT));
        }
    }
}

$json_string = file_exists($metar_file) ? file_get_contents($metar_file) : "";
$parsed_json = json_decode($json_string);
if (isset($parsed_json->{'data'}[0])) {
    $d0 = $parsed_json->{'data'}[0];
    $metar34time       = isset($d0->{'observed'}) ? $d0->{'observed'} : '';
    $metar34raw        = isset($d0->{'raw_text'}) ? $d0->{'raw_text'} : '';
    $metar34stationid  = isset($d0->{'icao'}) ? $d0->{'icao'} : '';	
    $metar34stationname= isset($d0->{'station'}->{'name'}) ? $d0->{'station'}->{'name'} : '';
    $metar34pressurehg = isset($d0->{'barometer'}->{'hg'}) ? (float)$d0->{'barometer'}->{'hg'} : 0;	
    $metar34pressuremb = isset($d0->{'barometer'}->{'mb'}) ? (float)$d0->{'barometer'}->{'mb'} : 0;
    $metar34conditions = isset($d0->{'conditions'}[0]->{'code'}) ? $d0->{'conditions'}[0]->{'code'} : '';
    $metar34conditionstext = isset($d0->{'conditions'}[0]->{'text'}) ? $d0->{'conditions'}[0]->{'text'} : '';
    $metar34clouds     = isset($d0->{'clouds'}[0]->{'code'}) ? $d0->{'clouds'}[0]->{'code'} : '';
    $metar34cloudstext = isset($d0->{'clouds'}[0]->{'text'}) ? $d0->{'clouds'}[0]->{'text'} : '';
    $metar34dewpointc  = isset($d0->{'dewpoint'}->{'celsius'}) ? (float)$d0->{'dewpoint'}->{'celsius'} : 0;
    $metar34dewpointf  = isset($d0->{'dewpoint'}->{'fahrenheit'}) ? (float)$d0->{'dewpoint'}->{'fahrenheit'} : 0;
    $metar34temperaturec = isset($d0->{'temperature'}->{'celsius'}) ? (float)$d0->{'temperature'}->{'celsius'} : 0;
    $metar34temperaturef = isset($d0->{'temperature'}->{'fahrenheit'}) ? (float)$d0->{'temperature'}->{'fahrenheit'} : 0;
    $metar34humidity   = isset($d0->{'humidity'}->{'percent'}) ? (float)$d0->{'humidity'}->{'percent'} : 0;
    $metar34visibility = isset($d0->{'visibility'}->{'meters'}) ? (float)str_replace(',', '', $d0->{'visibility'}->{'meters'}) : 10000;
    $metar34visibilitymiles = isset($d0->{'visibility'}->{'miles'}) ? (float)$d0->{'visibility'}->{'miles'} : 6;
    $metar34windir     = isset($d0->{'wind'}->{'degrees'}) ? (float)$d0->{'wind'}->{'degrees'} : 0;
    $metar34windspeedmph = isset($d0->{'wind'}->{'speed_mph'}) ? (float)$d0->{'wind'}->{'speed_mph'} : 0;
    $metar34windspeedkmh = number_format($metar34windspeedmph*1.60934,0);
    $metar34windspeedkts = isset($d0->{'wind'}->{'speed_kts'}) ? (float)$d0->{'wind'}->{'speed_kts'} : 0;
    $metar34raininches = isset($d0->{'rain_in'}) ? (float)$d0->{'rain_in'} : 0;
    $metar34rainmm     = number_format($metar34raininches*25.4,2);
    $metar34vismiles   = number_format($metar34visibility*0.000621371,1);
    $metar34viskm      = number_format($metar34visibility*0.001,1);
} else {
    $metar34time = '';
    $metar34raw = '';
    $metar34stationid = '';
    $metar34stationname = '';
    $metar34pressurehg = 0;
    $metar34pressuremb = 0;
    $metar34conditions = '';
    $metar34conditionstext = '';
    $metar34clouds = '';
    $metar34cloudstext = '';
    $metar34dewpointc = 0;
    $metar34dewpointf = 0;
    $metar34temperaturec = 0;
    $metar34temperaturef = 0;
    $metar34humidity = 0;
    $metar34visibility = 10000;
    $metar34visibilitymiles = 6;
    $metar34windir = 0;
    $metar34windspeedmph = 0;
    $metar34windspeedkmh = '0';
    $metar34windspeedkts = 0;
    $metar34raininches = 0;
    $metar34rainmm = '0.00';
    $metar34vismiles = '6.2';
    $metar34viskm = '10.0';
    $sky_icon = 'clear.svg';
    $sky_desc = 'Cel serè';
}
// start the weather34 icon output and descriptions
if($metar34conditions =='-SHRA'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Light Rain <br>Showers';
}
//rain 
else if($metar34conditions =='SHRA'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Light Rain <br>Showers';
}
//rain heavy
else if($metar34conditions =='+SHRA'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Heavy Rain <br>Showers';
}
//rain light
else if($metar34conditions=='-RA'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Light Rain <br>Showers';
}
//rain moderate
else if($metar34conditions=='+RA'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Moderate Rain <br>Showers';
}
//rain
else if($metar34conditions=='RA'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Light Rain <br>Showers';
}
//rain squalls
else if($metar34conditions=='SQ'){
if ($now >$suns2 ){$sky_icon='rain.svg';} 
else if ($now <$sunrs2 ){$sky_icon='rain.svg';} 
else $sky_icon='rain.svg'; 
$sky_desc='Rain Squall<br>Showers';
}
//snow light
else if($metar34conditions=='-SN'){
if ($now >$suns2 ){$sky_icon='snow.svg';} 
else if ($now <$sunrs2 ){$sky_icon='snow.svg';} 
else $sky_icon='snow.svg'; 
$sky_desc='Light Snow <br>Showers';
}
//snow moderate
else if($metar34conditions=='+SN'){
if ($now >$suns2 ){$sky_icon='snow.svg';} 
else if ($now <$sunrs2 ){$sky_icon='snow.svg';} 
else $sky_icon='snow.svg'; 
$sky_desc='Moderate Snow <br>Showers';
}
//snow
else if($metar34conditions=='SN'){
if ($now >$suns2 ){$sky_icon='snow.svg';} 
else if ($now <$sunrs2 ){$sky_icon='snow.svg';} 
else $sky_icon='snow.svg'; 
$sky_desc='Snow Showers <br>';
}
//snow grains
else if($metar34conditions=='SG'){
if ($now >$suns2 ){$sky_icon='snow.svg';} 
else if ($now <$sunrs2 ){$sky_icon='snow.svg';} 
else $sky_icon='snow.svg'; 
$sky_desc='Snow Grains <br>';
}
//snow grains
else if($metar34conditions=='SNINCR'){
if ($now >$suns2 ){$sky_icon='snow.svg';} 
else if ($now <$sunrs2 ){$sky_icon='snow.svg';} 
else $sky_icon='snow.svg'; 
$sky_desc='Snow Showers <br>';
}
//sleet
else if($metar34conditions=='IP'){
if ($now >$suns2 ){$sky_icon='sleet.svg';} 
else if ($now <$sunrs2 ){$sky_icon='sleet.svg';} 
else $sky_icon='sleet.svg'; 
$sky_desc='Sleet Showers';
}
//Haze
else if($metar34conditions=='HZ'){
if ($now >$suns2 ){$sky_icon='nt_haze.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_haze.svg';} 
else $sky_icon='haze.svg'; 
$sky_desc='Hazy <br>Conditions';
}
//Batches Fog
else if($metar34conditions=='BCFG'){
if ($now >$suns2 ){$sky_icon='nt_fog.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_fog.svg';} 
else $sky_icon='fog.svg'; 
$sky_desc='Foggy <br>Conditions';
}
//Fog
else if($metar34conditions=='FG'){
if ($now >$suns2 ){$sky_icon='nt_fog.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_fog.svg';} 
else $sky_icon='fog.svg'; 
$sky_desc='Foggy <br>Conditions';
}
//Fog-NIGHT
else if($metar34conditions=='NFG'){
if ($now >$suns2 ){$sky_icon='nt_fog.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_fog.svg';} 
else $sky_icon='fog.svg'; 
$sky_desc='Foggy <br>Conditions';
}
//Mist-Night
else if($metar34conditions=='BR'){
if ($now >$suns2 ){$sky_icon='nt_fog.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_fog.svg';} 
else $sky_icon='fog.svg'; 
$sky_desc='Misty <br>Conditions';
}
//Mist
else if($metar34conditions=='NBR'){
if ($now >$suns2 ){$sky_icon='nt_fog.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_fog.svg';} 
else $sky_icon='fog.svg'; 
$sky_desc='Misty <br>Conditions';
}
//Hail
else if($metar34conditions=='GR'){
if ($now >$suns2 ){$sky_icon='hail.svg';} 
else if ($now <$sunrs2 ){$sky_icon='hail.svg';} 
else $sky_icon='hail.svg'; 
$sky_desc='Hail and Rain <br>Conditions';
}
//Hail GS
else if($metar34conditions=='GS'){
if ($now >$suns2 ){$sky_icon='hail.svg';} 
else if ($now <$sunrs2 ){$sky_icon='hail.svg';} 
else $sky_icon='hail.svg'; 
$sky_desc='Hail <br>Conditions';
}
//ICE CYSTALS
else if($metar34conditions=='IC'){
if ($now >$suns2 ){$sky_icon='hail.svg';} 
else if ($now <$sunrs2 ){$sky_icon='hail.svg';} 
else $sky_icon='hail.svg'; 
$sky_desc='Ice Crystals';
}
//ICE PELLETS
else if($metar34conditions=='PL'){
if ($now >$suns2 ){$sky_icon='hail.svg';} 
else if ($now <$sunrs2 ){$sky_icon='hail.svg';} 
else $sky_icon='hail.svg'; 
$sky_desc='Ice Pellets <br>';
}
//Thunderstorms
else if($metar34conditions=='TS'){
if ($now >$suns2 ){$sky_icon='tstorm.svg';} 
else if ($now <$sunrs2 ){$sky_icon='tstorm.svg';} 
else $sky_icon='tstorm.svg'; 
$sky_desc='Thunderstorm <br>Conditions';
}
//Thunderstorms
else if($metar34conditions=='-TS'){
if ($now >$suns2 ){$sky_icon='tstorm.svg';} 
else if ($now <$sunrs2 ){$sky_icon='tstorm.svg';} 
else $sky_icon='tstorm.svg'; 
$sky_desc='Thunderstorm <br>Conditions';
}
//Thunderstorms
else if($metar34conditions=='+TS'){
if ($now >$suns2 ){$sky_icon='tstorm.svg';} 
else if ($now <$sunrs2 ){$sky_icon='tstorm.svg';} 
else $sky_icon='tstorm.svg'; 
$sky_desc='Heavy <br>Thunderstorms';
}
//Thunderstorms
else if($metar34conditions=='TSRA'){
if ($now >$suns2 ){$sky_icon='tstorm.svg';} 
else if ($now <$sunrs2 ){$sky_icon='tstorm.svg';} 
else $sky_icon='tstorm.svg'; 
$sky_desc='Thunderstorm <br>Conditions';
}
//Scattered Thunderstorms
else if($metar34conditions=='SCTTSRA'){
if ($now >$suns2 ){$sky_icon='tstorm.svg';} 
else if ($now <$sunrs2 ){$sky_icon='tstorm.svg';} 
else $sky_icon='tstorm.svg'; 
$sky_desc='Scattered <br>Thunderstorms';
}
//Scattered Thunderstorms
else if($metar34conditions=='NTSRA'){
if ($now >$suns2 ){$sky_icon='tstorm.svg';} 
else if ($now <$sunrs2 ){$sky_icon='tstorm.svg';} 
else $sky_icon='tstorm.svg'; 
$sky_desc='Scattered <br>Thunderstorms';
}
//Dust
else if($metar34conditions=='DS'){
if ($now >$suns2 ){$sky_icon='dust.svg';} 
else if ($now <$sunrs2 ){$sky_icon='dust.svg';} 
else $sky_icon='dust.svg'; 
$sky_desc='Dust Storm <br>Conditions';
}
//Widespread Dust
else if($metar34conditions=='DU'){
if ($now >$suns2 ){$sky_icon='dust.svg';} 
else if ($now <$sunrs2 ){$sky_icon='dust.svg';} 
else $sky_icon='dust.svg'; 
$sky_desc='Widespread Dust <br>Conditions';
}
//Dust-Sand Whirls
else if($metar34conditions=='PO'){
if ($now >$suns2 ){$sky_icon='dust.svg';} 
else if ($now <$sunrs2 ){$sky_icon='dust.svg';} 
else $sky_icon='dust.svg'; 
$sky_desc='Dust-Sand Whirls <br>Conditions';
}
//Sand
else if($metar34conditions=='SA'){
if ($now >$suns2 ){$sky_icon='dust.svg';} 
else if ($now <$sunrs2 ){$sky_icon='dust.svg';} 
else $sky_icon='dust.svg'; 
$sky_desc='Dust-Sand <br>Conditions';
}
//Sandstorm
else if($metar34conditions=='SS'){
if ($now >$suns2 ){$sky_icon='dust.svg';} 
else if ($now <$sunrs2 ){$sky_icon='dust.svg';} 
else $sky_icon='dust.svg'; 
$sky_desc='Sandstorm <br>Conditions';
}
//Volcanic Ash
else if($metar34conditions=='VA'){
if ($now >$suns2 ){$sky_icon='volcanoe.svg';} 
else if ($now <$sunrs2 ){$sky_icon='volcanoe.svg';} 
else $sky_icon='volcanoe.svg'; 
$sky_desc='Volcanic Ash <br>Conditions';
}

//+FC
else if($metar34conditions=='+FC'){
if ($now >$suns2 ){$sky_icon='nsvrtsa.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nsvrtsa.svg';} 
else $sky_icon='nsvrtsat.svg'; 
$sky_desc='Tornado <br> Water Sprout';
}
//2nd part clouds
//clear
else if ($metar34clouds=='SKC') {
if ($now >$suns2 ){$sky_icon='nt_clear.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_clear.svg';} 
else $sky_icon='clear.svg'; 
$sky_desc='Clear <br>Conditions';
}
//clear
else if($metar34clouds=='CLR'){
if ($now >$suns2 ){$sky_icon='nt_clear.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_clear.svg';} 
else $sky_icon='clear.svg'; 
$sky_desc='Clear <br>Conditions';
}
//clear
else if($metar34clouds=='CAVOK'){
if ($now >$suns2 ){$sky_icon='nt_clear.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_clear.svg';} 
else $sky_icon='clear.svg'; 
$sky_desc='Clear <br>Conditions';
}
//few
else if($metar34clouds=='FEW'){
if ($now >$suns2 ){$sky_icon='partlycloudy.svg';} 
else if ($now <$sunrs2 ){$sky_icon='partlycloudy.svg';} 
else $sky_icon='partlysunny.svg'; 
$sky_desc='Partly Cloudy <br>Conditions';
}
//scattered clouds
else if($metar34clouds=='SCT'){
if ($now >$suns2 ){$sky_icon='nt_scatteredclouds.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_scatteredclouds.svg';} 
else $sky_icon='scatteredclouds.svg'; 	
$sky_desc='Mostly Scattered <br>Clouds';
}
//mostly cloudy
else if($metar34clouds=='BKN'){		
if ($now >$suns2 ){$sky_icon='nt_mostlycloudy.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_mostlycloudy.svg';} 
else $sky_icon='mostlycloudy.svg'; 	
$sky_desc='Mostly Cloudy <br>Conditions';
}
//overcast
else if($metar34clouds=='OVC'){
if ($now >$suns2 ){$sky_icon='nt_overcast.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_overcast.svg';} 
else $sky_icon='overcast.svg'; 
$sky_desc='Overcast <br>Conditions';
}
//overcast
else if($metar34clouds=='OVX'){
if ($now >$suns2 ){$sky_icon='nt_overcast.svg';} 
else if ($now <$sunrs2 ){$sky_icon='nt_overcast.svg';} 
else $sky_icon='overcast.svg'; 
$sky_desc='Overcast Conditions';
}
//offline
else{
	$sky_icon='offline.svg';
	$sky_desc='Data Offline';
};
//end weather34 metar aviation script API	 

?>
