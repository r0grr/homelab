<?php
// Function to get cached or fresh Generalitat de Catalunya Pla Alfa data for Sallent (El Bages)
function get_gencat_pla_alfa() {
    $cache_file = __DIR__ . '/gencat_pla_alfa.json';
    $cache_time = 900; // 15 minutes cache

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
        'header' => "User-Agent: MeteoSallent WeatherStation/1.0\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$level_names = [
    0 => 'Baix / Moderat',
    1 => 'Moderat',
    2 => 'Alt',
    3 => 'Molt Alt',
    4 => 'Extrem'
];

$level_colors = [
    0 => '#27ae60', // Verd
    1 => '#f39c12', // Groc fosc / Ambre
    2 => '#e67e22', // Taronja
    3 => '#e74c3c', // Vermell
    4 => '#8e1b1b'  // Granat fosc
];

$level_advisories = [
    0 => 'Sense restriccions especials en activitats',
    1 => 'Precaució en activitats al medi natural',
    2 => 'Regulació d\'activitats agràries i cremes',
    3 => 'Suspensió de cremes i feines agrícoles amb maquinària',
    4 => 'Restricció d\'accés a massissos i suspensió total'
];

$nivell_avui = 3; // default fallback
$has_dema = false;
$nivell_dema = null;
$nivell_dema_text = null;
$color_dema = null;
$hora_actualitzacio = date('H:i');
$data_str = date('d/m/Y');
$found_data = false;

// 1. Query Municipal Avui for Sallent
$url_avui = "https://services7.arcgis.com/ZCqVt1fRXwwK6GF4/arcgis/rest/services/Pla_Alfa_Municipal_Avui_FL_alternatiu_VW/FeatureServer/0/query?where=NOMMUNI=%27Sallent%27&outFields=*&f=json";
$res_avui = @file_get_contents($url_avui, false, $ctx);
if ($res_avui) {
    $data_avui = json_decode($res_avui, true);
    if (!empty($data_avui['features'][0]['attributes'])) {
        $attrs = $data_avui['features'][0]['attributes'];
        if (isset($attrs['PERIL_M']) && is_numeric($attrs['PERIL_M'])) {
            $nivell_avui = intval($attrs['PERIL_M']);
            $found_data = true;
        }
    }
}

// 2. Query Municipal Demà for Sallent
$url_dema = "https://services7.arcgis.com/ZCqVt1fRXwwK6GF4/arcgis/rest/services/pla_alfa_municipal_dema_FL_VW/FeatureServer/5/query?where=NOMMUNI=%27Sallent%27&outFields=*&f=json";
$res_dema = @file_get_contents($url_dema, false, $ctx);
if ($res_dema) {
    $data_dema = json_decode($res_dema, true);
    if (!empty($data_dema['features'][0]['attributes'])) {
        $attrs_dema = $data_dema['features'][0]['attributes'];
        if (isset($attrs_dema['PERIL_M']) && is_numeric($attrs_dema['PERIL_M'])) {
            $nivell_dema = intval($attrs_dema['PERIL_M']);
            $nivell_dema_text = $level_names[$nivell_dema] ?? 'Moderat';
            $color_dema = $level_colors[$nivell_dema] ?? '#e67e22';
            $has_dema = true;
        }
    }
}

// 3. Query Comarcal update time for Bages
$url_comarcal = "https://services7.arcgis.com/ZCqVt1fRXwwK6GF4/arcgis/rest/services/Pla_Alfa_Comarcal_Avui_FL_VW/FeatureServer/1/query?where=NOMCOMAR=%27Bages%27&outFields=*&returnGeometry=false&f=json";
$res_comarcal = @file_get_contents($url_comarcal, false, $ctx);
if ($res_comarcal) {
    $data_comarcal = json_decode($res_comarcal, true);
    if (!empty($data_comarcal['features'][0]['attributes']['HORA'])) {
        $hora_actualitzacio = trim($data_comarcal['features'][0]['attributes']['HORA']);
    }
    if (!empty($data_comarcal['features'][0]['attributes']['DATA'])) {
        $ts_com = $data_comarcal['features'][0]['attributes']['DATA'] / 1000;
        $data_str = date('d/m/Y', $ts_com);
    }
}

$output = [
    'updated_at' => time(),
    'data' => $data_str,
    'hora' => $hora_actualitzacio,
    'municipi' => 'Sallent',
    'comarca' => 'Bages',
    'nivell_avui' => $nivell_avui,
    'nivell_avui_text' => $level_names[$nivell_avui] ?? 'Moderat',
    'color_avui' => $level_colors[$nivell_avui] ?? '#e67e22',
    'has_dema' => $has_dema,
    'nivell_dema' => $nivell_dema,
    'nivell_dema_text' => $nivell_dema_text,
    'color_dema' => $color_dema,
    'advisory' => $level_advisories[$nivell_avui] ?? 'Consulteu les restriccions del Pla Alfa',
    'map_experience_url' => 'https://experience.arcgis.com/experience/2cf7ebbe492f401db826cb21eae9bfae',
    'gencat_url' => 'https://interior.gencat.cat/ca/arees_dactuacio/agents-rurals/pla-alfa/'
];

    if ($found_data || !file_exists($cache_file)) {
        @file_put_contents($cache_file, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return $output;
}

// If accessed directly via URL, output JSON with headers
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(get_gencat_pla_alfa(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
