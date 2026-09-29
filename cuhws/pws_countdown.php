<?php
include_once('livedata.php');
include_once('common.php');

// Funció astronòmica perpetual (Algorisme de Jean Meeus)
// Permet calcular equinoccis i solsticis amb precisió matemàtica per a qualsevol any futur > 2035.
function getMeeusSeasonEvent($year, $season_key) {
    $m = ($year - 2000) / 1000.0;
    switch($season_key) {
        case "spring":
            $jde = 2451623.80984 + 365242.37404 * $m + 0.05169 * pow($m, 2) - 0.00411 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera",
                "date_str" => date("j M Y", $ts) . " &bull; " . date("H:i", $ts) . " h",
                "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D",
                "timer_border" => "rgba(141,252,45,0.35)", "ts" => $ts
            ];
        case "summer":
            $jde = 2451716.56767 + 365241.62603 * $m + 0.00325 * pow($m, 2) + 0.00888 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "key" => "summer", "name" => "Estiu", "title" => "☀️ Inici Estiu",
                "date_str" => date("j M Y", $ts) . " &bull; " . date("H:i", $ts) . " h",
                "started" => "Estiu iniciat", "color" => "#ecb454", "timer_color" => "#f6d365",
                "timer_border" => "rgba(236,180,84,0.35)", "ts" => $ts
            ];
        case "autumn":
            $jde = 2451810.21715 + 365242.01767 * $m - 0.11575 * pow($m, 2) + 0.00337 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "key" => "autumn", "name" => "Tardor", "title" => "🍂 Inici Tardor",
                "date_str" => date("j M Y", $ts) . " &bull; " . date("H:i", $ts) . " h",
                "started" => "Tardor iniciada", "color" => "#ff8841", "timer_color" => "#2ecc71",
                "timer_border" => "rgba(46,204,113,0.35)", "ts" => $ts
            ];
        case "winter":
            $jde = 2451900.05952 + 365242.74049 * $m - 0.06223 * pow($m, 2) - 0.05235 * pow($m, 3);
            $ts = round(($jde - 2440587.5) * 86400);
            return [
                "key" => "winter", "name" => "Hivern", "title" => "❄️ Inici Hivern",
                "date_str" => date("j M Y", $ts) . " &bull; " . date("H:i", $ts) . " h",
                "started" => "Hivern iniciat", "color" => "#01a4b4", "timer_color" => "#57FAF9",
                "timer_border" => "rgba(87,250,249,0.35)", "ts" => $ts
            ];
    }
}

// Efemèrides oficials dels canvis d'estació a Catalunya (IGN / OAN)
// Horaris oficials en CET (UTC+1) a l'hivern/primavera i CEST (UTC+2) a l'estiu/tardor.
$seasons_timeline = [
    // 2026
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2026 &bull; 15:46 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2026-03-20 15:46:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2026 &bull; 09:24 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2026-06-21 09:24:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "23 Set 2026 &bull; 02:05 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2026-09-23 02:05:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2026 &bull; 21:50 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2026-12-21 21:50:00 CET")],
    // 2027
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2027 &bull; 21:25 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2027-03-20 21:25:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2027 &bull; 15:11 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2027-06-21 15:11:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "23 Set 2027 &bull; 08:02 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2027-09-23 08:02:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "22 Des 2027 &bull; 03:42 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2027-12-22 03:42:00 CET")],
    // 2028
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2028 &bull; 03:17 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2028-03-20 03:17:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "20 Jun 2028 &bull; 21:02 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2028-06-20 21:02:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "22 Set 2028 &bull; 13:45 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2028-09-22 13:45:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2028 &bull; 09:20 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2028-12-21 09:20:00 CET")],
    // 2029
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2029 &bull; 09:02 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2029-03-20 09:02:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2029 &bull; 02:48 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2029-06-21 02:48:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "22 Set 2029 &bull; 19:38 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2029-09-22 19:38:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2029 &bull; 15:14 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2029-12-21 15:14:00 CET")],
    // 2030
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2030 &bull; 14:52 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2030-03-20 14:52:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2030 &bull; 08:31 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2030-06-21 08:31:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "23 Set 2030 &bull; 01:27 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2030-09-23 01:27:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2030 &bull; 21:09 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2030-12-21 21:09:00 CET")],
    // 2031
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2031 &bull; 20:41 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2031-03-20 20:41:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2031 &bull; 14:28 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2031-06-21 14:28:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "23 Set 2031 &bull; 07:15 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2031-09-23 07:15:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "22 Des 2031 &bull; 02:55 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2031-12-22 02:55:00 CET")],
    // 2032
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2032 &bull; 02:21 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2032-03-20 02:21:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "20 Jun 2032 &bull; 20:08 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2032-06-20 20:08:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "22 Set 2032 &bull; 13:10 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2032-09-22 13:10:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2032 &bull; 08:56 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2032-12-21 08:56:00 CET")],
    // 2033
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2033 &bull; 08:22 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2033-03-20 08:22:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2033 &bull; 02:01 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2033-06-21 02:01:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "22 Set 2033 &bull; 18:52 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2033-09-22 18:52:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2033 &bull; 14:45 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2033-12-21 14:45:00 CET")],
    // 2034
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2034 &bull; 14:17 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2034-03-20 14:17:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2034 &bull; 07:44 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2034-06-21 07:44:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "23 Set 2034 &bull; 00:40 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2034-09-23 00:40:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "21 Des 2034 &bull; 20:34 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2034-12-21 20:34:00 CET")],
    // 2035
    ["key" => "spring", "name" => "Primavera", "title" => "🌱 Inici Primavera", "date_str" => "20 Mar 2035 &bull; 20:02 h", "started" => "Primavera iniciada", "color" => "#8DFC2D", "timer_color" => "#8DFC2D", "timer_border" => "rgba(141,252,45,0.35)", "ts" => strtotime("2035-03-20 20:02:00 CET")],
    ["key" => "summer", "name" => "Estiu",     "title" => "☀️ Inici Estiu",     "date_str" => "21 Jun 2035 &bull; 13:33 h", "started" => "Estiu iniciat",      "color" => "#ecb454", "timer_color" => "#f6d365", "timer_border" => "rgba(236,180,84,0.35)", "ts" => strtotime("2035-06-21 13:33:00 CEST")],
    ["key" => "autumn", "name" => "Tardor",    "title" => "🍂 Inici Tardor",    "date_str" => "23 Set 2035 &bull; 06:38 h", "started" => "Tardor iniciada",    "color" => "#ff8841", "timer_color" => "#2ecc71", "timer_border" => "rgba(46,204,113,0.35)", "ts" => strtotime("2035-09-23 06:38:00 CEST")],
    ["key" => "winter", "name" => "Hivern",    "title" => "❄️ Inici Hivern",    "date_str" => "22 Des 2035 &bull; 02:30 h", "started" => "Hivern iniciat",     "color" => "#01a4b4", "timer_color" => "#57FAF9", "timer_border" => "rgba(87,250,249,0.35)", "ts" => strtotime("2035-12-22 02:30:00 CET")]
];

$now = time();
$grace_period_sec = 3 * 86400; // 3 dies en segons

// Generador perpetu: Si el temps actual o futur immediat s'apropa o supera el 2035,
// calculem automàticament els anys necessaris mitjançant l'algorisme de Jean Meeus.
$last_ts = end($seasons_timeline)["ts"];
if ($now + (2 * 365 * 86400) > $last_ts) {
    $start_year = intval(date("Y", $last_ts)) + 1;
    $target_year = intval(date("Y", $now)) + 2;
    for ($y = $start_year; $y <= $target_year; $y++) {
        foreach (["spring", "summer", "autumn", "winter"] as $s_key) {
            $seasons_timeline[] = getMeeusSeasonEvent($y, $s_key);
        }
    }
}

// Trobar l'estació activa per al bloc esquerre (la primera que no hagi superat els 3 dies de celebració)
$left_idx = 0;
foreach ($seasons_timeline as $idx => $ev) {
    if (($ev["ts"] + $grace_period_sec) > $now) {
        $left_idx = $idx;
        break;
    }
}
$right_idx = $left_idx + 1;
if (!isset($seasons_timeline[$right_idx])) {
    $right_idx = $left_idx;
}

$ev_left  = $seasons_timeline[$left_idx];
$ev_right = $seasons_timeline[$right_idx];

// Càlcul inicial en PHP per al renderitzat servidor
$diff_left = $ev_left["ts"] - $now;
if ($diff_left <= 0) {
    $left_timer_text = $ev_left["started"];
} else {
    $dl = floor($diff_left / 86400);
    $hl = floor(($diff_left % 86400) / 3600);
    $ml = floor(($diff_left % 3600) / 60);
    $left_timer_text = sprintf("%dd %02dh %02dm", $dl, $hl, $ml);
}

$diff_right = $ev_right["ts"] - $now;
if ($diff_right <= 0) {
    $right_timer_text = $ev_right["started"];
} else {
    $dr = floor($diff_right / 86400);
    $hr = floor(($diff_right % 86400) / 3600);
    $mr = floor(($diff_right % 3600) / 60);
    $right_timer_text = sprintf("%dd %02dh %02dm", $dr, $hr, $mr);
}

// Passem només un conjunt optimitzat de les properes estacions a JS
$js_seasons = array_slice($seasons_timeline, max(0, $left_idx - 1), 8);
?>
<div class="PWS_module_title">
    <span>Compte Enrere Estacions de l'Any</span>
</div>
<div style="height: 180px; padding: 14px 10px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; text-align: center; gap: 8px;">
    <!-- Slot 1 (Esquerra) -->
    <div id="pws_slot_left" style="flex: 1; min-width: 0; padding: 0 6px; border-right: 1px solid rgba(255,255,255,0.1);">
        <div id="pws_left_title" style="color: <?php echo $ev_left['color']; ?>; font-weight: 700; font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $ev_left['title']; ?></div>
        <div id="pws_left_date" style="font-size: 12px; color: #a0aec0; margin: 5px 0 8px; white-space: nowrap;"><?php echo $ev_left['date_str']; ?></div>
        <div id="pws_left_timer" style="color: <?php echo $ev_left['timer_color']; ?>; font-weight: 800; font-size: 16px; font-family: 'Courier New', Courier, monospace; background: rgba(0,0,0,0.35); padding: 6px 8px; border-radius: 6px; border: 1px solid <?php echo $ev_left['timer_border']; ?>; display: inline-block; white-space: nowrap; letter-spacing: 0.3px; max-width: 100%; box-sizing: border-box;">
            <?php echo $left_timer_text; ?>
        </div>
    </div>

    <!-- Slot 2 (Dreta) -->
    <div id="pws_slot_right" style="flex: 1; min-width: 0; padding: 0 6px;">
        <div id="pws_right_title" style="color: <?php echo $ev_right['color']; ?>; font-weight: 700; font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $ev_right['title']; ?></div>
        <div id="pws_right_date" style="font-size: 12px; color: #a0aec0; margin: 5px 0 8px; white-space: nowrap;"><?php echo $ev_right['date_str']; ?></div>
        <div id="pws_right_timer" style="color: <?php echo $ev_right['timer_color']; ?>; font-weight: 800; font-size: 16px; font-family: 'Courier New', Courier, monospace; background: rgba(0,0,0,0.35); padding: 6px 8px; border-radius: 6px; border: 1px solid <?php echo $ev_right['timer_border']; ?>; display: inline-block; white-space: nowrap; letter-spacing: 0.3px; max-width: 100%; box-sizing: border-box;">
            <?php echo $right_timer_text; ?>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="calendari_astronomic.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Calendari Astronòmic Oficial</a>
</div>

<script>
(function() {
    var seasons = <?php echo json_encode(array_values(array_map(function($s) {
        return [
            "title"        => $s["title"],
            "date_str"     => $s["date_str"],
            "started"      => $s["started"],
            "color"        => $s["color"],
            "timer_color"  => $s["timer_color"],
            "timer_border" => $s["timer_border"],
            "time_ms"      => $s["ts"] * 1000
        ];
    }, $js_seasons))); ?>;

    var GRACE_PERIOD_MS = 3 * 24 * 60 * 60 * 1000; // 3 dies en mil·lisegons

    function updateCountdowns() {
        var now = new Date().getTime();

        // Trobar l'estació activa per a l'esquerra (primera amb inici + 3 dies > now)
        var leftIdx = 0;
        for (var i = 0; i < seasons.length; i++) {
            if ((seasons[i].time_ms + GRACE_PERIOD_MS) > now) {
                leftIdx = i;
                break;
            }
        }
        var rightIdx = Math.min(leftIdx + 1, seasons.length - 1);

        var evLeft = seasons[leftIdx];
        var evRight = seasons[rightIdx];

        // Actualitzar Slot Esquerre
        var elLeftTitle = document.getElementById("pws_left_title");
        var elLeftDate  = document.getElementById("pws_left_date");
        var elLeftTimer = document.getElementById("pws_left_timer");

        if (elLeftTitle && elLeftDate && elLeftTimer) {
            elLeftTitle.textContent = evLeft.title;
            elLeftTitle.style.color = evLeft.color;
            elLeftDate.innerHTML    = evLeft.date_str;

            var diffL = evLeft.time_ms - now;
            if (diffL <= 0) {
                // Estació iniciada fa menys de 3 dies
                elLeftTimer.textContent = evLeft.started;
                elLeftTimer.style.color = evLeft.timer_color;
                elLeftTimer.style.borderColor = evLeft.timer_border;
            } else {
                var dL = Math.floor(diffL / (1000 * 60 * 60 * 24));
                var hL = Math.floor((diffL % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var mL = Math.floor((diffL % (1000 * 60 * 60)) / (1000 * 60));
                elLeftTimer.textContent = dL + "d " + (hL < 10 ? "0" : "") + hL + "h " + (mL < 10 ? "0" : "") + mL + "m";
                elLeftTimer.style.color = evLeft.timer_color;
                elLeftTimer.style.borderColor = evLeft.timer_border;
            }
        }

        // Actualitzar Slot Dret
        var elRightTitle = document.getElementById("pws_right_title");
        var elRightDate  = document.getElementById("pws_right_date");
        var elRightTimer = document.getElementById("pws_right_timer");

        if (elRightTitle && elRightDate && elRightTimer) {
            elRightTitle.textContent = evRight.title;
            elRightTitle.style.color = evRight.color;
            elRightDate.innerHTML    = evRight.date_str;

            var diffR = evRight.time_ms - now;
            if (diffR <= 0) {
                elRightTimer.textContent = evRight.started;
                elRightTimer.style.color = evRight.timer_color;
                elRightTimer.style.borderColor = evRight.timer_border;
            } else {
                var dR = Math.floor(diffR / (1000 * 60 * 60 * 24));
                var hR = Math.floor((diffR % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var mR = Math.floor((diffR % (1000 * 60 * 60)) / (1000 * 60));
                elRightTimer.textContent = dR + "d " + (hR < 10 ? "0" : "") + hR + "h " + (mR < 10 ? "0" : "") + mR + "m";
                elRightTimer.style.color = evRight.timer_color;
                elRightTimer.style.borderColor = evRight.timer_border;
            }
        }
    }

    updateCountdowns();
    if (!window.pwsCountdownTimer) {
        window.pwsCountdownTimer = setInterval(updateCountdowns, 60000);
    }
})();
</script>
