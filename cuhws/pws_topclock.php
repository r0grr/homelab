<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$now = time();
$cat_weekdays = array('Dg', 'Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds');
$cat_months = array('Gen', 'Feb', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Des');

$wday = $cat_weekdays[date('w', $now)];
$day = date('j', $now);
$month = $cat_months[date('n', $now) - 1];
$year = date('Y', $now);
$timeStr = date('H:i', $now);
?>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span id="pws_live_time" style="font-family: 'Courier New', Courier, monospace; font-weight: bold; font-size: 14px; color: #F38D39;"><?php echo $timeStr; ?></span>
    <span style="font-size: 11px; color: #e2e8f0; margin-left: 6px; font-weight: 500;"><?php echo "$wday $day $month $year"; ?></span>
</div>
<div style="font-size: 13px; color: #cbd5e0; padding: 13px 8px; line-height: 1.5; text-align: center;">
    <b>Sallent (El Bages)</b><br>
    <?php
    $cur_temp = floatval($weather["temp"]);
    if ($cur_temp >= 30) {
        echo '<span style="color: #ff8841;">Ambient molt càlid i estiuenc.</span>';
    } elseif ($cur_temp >= 20) {
        echo '<span style="color: #9aba2f;">Temperatura suau i agradable.</span>';
    } elseif ($cur_temp >= 10) {
        echo '<span style="color: #01a4b4;">Ambient fresc.</span>';
    } else {
        echo '<span style="color: #57FAF9;">Ambient fred.</span>';
    }
    ?><br>
    <span style="color: #a0aec0; font-size: 11.5px;">Cel serè &bull; 0% risc pluja &bull; Vent <?php echo $weather["wind_speed"]; ?> km/h</span>
</div>
<script>
if (!window.pwsClockTimer) {
    window.pwsClockTimer = setInterval(function() {
        var el = document.getElementById('pws_live_time');
        if (el) {
            var d = new Date();
            var h = String(d.getHours()).padStart(2, '0');
            var m = String(d.getMinutes()).padStart(2, '0');
            el.textContent = h + ':' + m;
        }
    }, 15000);
}
</script>
