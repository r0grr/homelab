<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

// Mesos en català per a les dates
$cat_months = [
    1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Des'
];

$moon_illum = 0;
$phase_name = 'Lluna Nova';
$moon_shadow_svg = '';

// Les 8 fases lunars (hemisferi nord)
$lunar_phases = [
    0 => [
        'name' => 'Lluna Nova',
        'svg'  => '<circle cx="50" cy="50" r="50" fill="rgba(10, 15, 20, 0.85)" />'
    ],
    1 => [
        'name' => 'Creixent Inicial',
        'svg'  => '<path d="M 50 0 A 50 50 0 0 0 50 100 A 25 50 0 0 0 50 0 Z" fill="rgba(10, 15, 20, 0.82)" />'
    ],
    2 => [
        'name' => 'Quart Creixent',
        'svg'  => '<path d="M 50 0 A 50 50 0 0 0 50 100 L 50 0 Z" fill="rgba(10, 15, 20, 0.82)" />'
    ],
    3 => [
        'name' => 'Creixent Gibosa',
        'svg'  => '<path d="M 50 0 A 50 50 0 0 0 50 100 A 25 50 0 0 1 50 0 Z" fill="rgba(10, 15, 20, 0.82)" />'
    ],
    4 => [
        'name' => 'Lluna Plena',
        'svg'  => '' // Il·luminació total, sense ombra
    ],
    5 => [
        'name' => 'Minvant Gibosa',
        'svg'  => '<path d="M 50 0 A 50 50 0 0 1 50 100 A 25 50 0 0 0 50 0 Z" fill="rgba(10, 15, 20, 0.82)" />'
    ],
    6 => [
        'name' => 'Quart Minvant',
        'svg'  => '<path d="M 50 0 A 50 50 0 0 1 50 100 L 50 0 Z" fill="rgba(10, 15, 20, 0.82)" />'
    ],
    7 => [
        'name' => 'Minvant Inicial',
        'svg'  => '<path d="M 50 0 A 50 50 0 0 1 50 100 A 25 50 0 0 1 50 0 Z" fill="rgba(10, 15, 20, 0.82)" />'
    ]
];

$now = time();
$next_full_str = '--';
$next_new_str = '--';

if (class_exists('MoonPhase')) {
    $mp = new MoonPhase();
    $moon_illum = round($mp->illumination() * 100);
    $moon_phase_val = $mp->phase();
    
    // Càlcul de l'índex de fase (0 a 7)
    $phase_idx = (int)floor(fmod($moon_phase_val + 0.0625, 1.0) * 8);
    if (isset($lunar_phases[$phase_idx])) {
        $phase_name = $lunar_phases[$phase_idx]['name'];
        $moon_shadow_svg = $lunar_phases[$phase_idx]['svg'];
    }

    // Propera Lluna Plena i Lluna Nova
    $next_full_ts = ($mp->full_moon() > $now) ? $mp->full_moon() : $mp->next_full_moon();
    if ($next_full_ts) {
        $m_num = (int)date('n', $next_full_ts);
        $next_full_str = date('j', $next_full_ts) . ' ' . ($cat_months[$m_num] ?? date('M', $next_full_ts));
    }

    $next_new_ts = ($mp->new_moon() > $now) ? $mp->new_moon() : $mp->next_new_moon();
    if ($next_new_ts) {
        $m_num = (int)date('n', $next_new_ts);
        $next_new_str = date('j', $next_new_ts) . ' ' . ($cat_months[$m_num] ?? date('M', $next_new_ts));
    }
}

// Hores de Sortida i Posta de la Lluna
$moon_rise_str = '--:--';
$moon_set_str = '--:--';
if (isset($Moon) && is_object($Moon)) {
    if (!empty($Moon->moonrise)) {
        $moon_rise_str = date('H:i', $Moon->moonrise);
    }
    if (!empty($Moon->moonset)) {
        $moon_set_str = date('H:i', $Moon->moonset);
    }
}
?>
<div class="PWS_module_title">
    <span>Fase Lunar</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body">
    <!-- Left values -->
    <div class="PWS_left">
        <div class="PWS_div_left" style="border-right-color: #ff8841;">Sortida Lluna<br><b><?php echo $moon_rise_str; ?></b></div>
        <div class="PWS_div_left" style="border-right-color: #e8c400;">Lluna Plena<br><b><?php echo $next_full_str; ?></b></div>
    </div>

    <!-- Middle Moon Disc -->
    <div class="PWS_middle">
        <div style="position: relative; width: 110px; height: 110px; margin: 8px auto 0; background-image: url('img/moon.png'); background-size: 110px 110px; background-repeat: no-repeat; border-radius: 50%; overflow: hidden;">
            <!-- Dynamic 8-phase shadow overlay -->
            <?php if (!empty($moon_shadow_svg)): ?>
            <svg width="110" height="110" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="position: absolute; top: 0; left: 0; border-radius: 50%; pointer-events: none;">
                <?php echo $moon_shadow_svg; ?>
            </svg>
            <?php endif; ?>
            <div style="position: absolute; top: 28px; left: 0; width: 110px; text-align: center; color: #fff; text-shadow: 1px 1px 8px #000; z-index: 2; pointer-events: none;">
                <b style="font-size: 20px; line-height: 1;"><?php echo $moon_illum; ?>%</b><br>
                <span style="font-size: 10px; font-weight: 600; text-transform: uppercase;">Il&middot;luminaci&oacute;</span>
                <hr style="margin: 2px 14px; border: 0; border-top: 1px solid rgba(255,255,255,0.4);">
                <b style="font-size: 11px;"><?php echo $phase_name; ?></b>
            </div>
        </div>
    </div>

    <!-- Right values -->
    <div class="PWS_right">
        <div class="PWS_div_right" style="border-left-color: #ff8841;">Posta Lluna<br><b><?php echo $moon_set_str; ?></b></div>
        <div class="PWS_div_right" style="border-left-color: #01a4b4;">Lluna Nova<br><b><?php echo $next_new_str; ?></b></div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="mooninfo.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Calendari Lunar</a>
    <span style="color: #4a5568;"> | </span>
    <a href="meteorshowers.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Pluja d'Estels</a>
</div>
