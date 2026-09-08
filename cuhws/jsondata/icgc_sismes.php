<?php
// ICGC Sismocat Fetcher & Cacher for MeteoSallent
function get_icgc_sismes() {
    $cache_file = __DIR__ . '/icgc_sismes.json';
    $cache_time = 600; // 10 min

    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        $raw = file_get_contents($cache_file);
        $data = json_decode($raw, true);
        if (!empty($data) && is_array($data)) {
            return $data;
        }
    }

    $url = "https://sismocat.icgc.cat/sisweb2/sisweb_api_rss_external.php?consulta=2init&lang=ca";
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'MeteoSallent PWS Dashboard (https://meteosallent.cat)'
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $xml_raw = @file_get_contents($url, false, $ctx);
    if (!$xml_raw) {
        if (file_exists($cache_file)) return json_decode(file_get_contents($cache_file), true);
        return array();
    }

    $xml = @simplexml_load_string($xml_raw);
    if (!$xml || !isset($xml->channel->item)) {
        if (file_exists($cache_file)) return json_decode(file_get_contents($cache_file), true);
        return array();
    }

    $items = array();
    foreach ($xml->channel->item as $item) {
        $desc = (string)$item->description;
        $title = (string)$item->title;
        $pubDate = (string)$item->pubDate;
        $link = (string)$item->link;

        $region = "Catalunya";
        $mag = "1.0";

        if (preg_match('/Regi[oó]:\s*(.*?)\s*Magnitud:\s*([-\d\.]+)/u', $desc, $m)) {
            $region = trim($m[1]);
            $mag = trim($m[2]);
        }

        $ts = strtotime($pubDate);
        $date_fmt = $ts ? date('d/m H:i', $ts) : $pubDate;

        $items[] = array(
            'title' => $title,
            'region' => $region,
            'magnitude' => $mag,
            'date' => $date_fmt,
            'timestamp' => $ts,
            'link' => $link
        );
    }

    if (!empty($items)) {
        file_put_contents($cache_file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return $items;
}
