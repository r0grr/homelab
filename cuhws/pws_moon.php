<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$moon_illum = 45;
$phase_name = "Quart Minvant";
if (class_exists('MoonPhase')) {
    $mp = new MoonPhase();
    $moon_illum = round($mp->illumination() * 100);
    $moon_age = $mp->age();
    if ($moon_age < 1.84) $phase_name = "Lluna Nova";
    elseif ($moon_age < 5.53) $phase_name = "Creixent Inicial";
    elseif ($moon_age < 9.22) $phase_name = "Quart Creixent";
    elseif ($moon_age < 12.91) $phase_name = "Creixent Gibosa";
    elseif ($moon_age < 16.61) $phase_name = "Lluna Plena";
    elseif ($moon_age < 20.30) $phase_name = "Minvant Gibosa";
    elseif ($moon_age < 23.99) $phase_name = "Quart Minvant";
    else $phase_name = "Minvant Inicial";
}
?>
<div class="PWS_module_title">
    <span>Fase Lunar</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #ff8841;">Sortida Lluna<br><b>23:30</b></div>
        <div class="PWS_div_left" style="border-right-color: #e8c400;">Lluna Plena<br><b>26 Set</b></div>
    </div>

    <!-- Middle Moon Disc -->
    <div class="PWS_middle">
        <div style="position: relative; width: 110px; height: 110px; margin: 8px auto 0; background-image: url('img/moon.png'); background-size: 110px 110px; background-repeat: no-repeat; border-radius: 50%;">
            <!-- Shadow overlay -->
            <svg width="110" height="110" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(20deg);">
                <path d="M 150 0 C 129.2 10 129.2 290 150 300 350 290 350 10 150 0" style="fill: rgba(0, 0, 0, 0.62);" />
            </svg>
            <div style="position: absolute; top: 28px; left: 0; width: 110px; text-align: center; color: #fff; text-shadow: 1px 1px 8px #000;">
                <b style="font-size: 20px; line-height: 1;"><?php echo $moon_illum; ?>%</b><br>
                <span style="font-size: 10px; font-weight: 600; text-transform: uppercase;">Il&middot;luminaci&oacute;</span>
                <hr style="margin: 2px 14px; border: 0; border-top: 1px solid rgba(255,255,255,0.4);">
                <b style="font-size: 11px;"><?php echo $phase_name; ?></b>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #ff8841;">Posta Lluna<br><b>16:41</b></div>
        <div class="PWS_div_right" style="border-left-color: #01a4b4;">Lluna Nova<br><b>10 Set</b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="mooninfo.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Calendari Lunar</a>
    <span style="color: #4a5568;"> | </span>
    <a href="meteorshowers.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Pluja d'Estels</a>
</div>
