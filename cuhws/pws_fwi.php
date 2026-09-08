<?php
// Mòdul oficial del Pla Alfa de la Generalitat de Catalunya per a Sallent (El Bages)
include_once(__DIR__ . '/jsondata/gencat_pla_alfa.php');
$pla_data = get_gencat_pla_alfa();

$nivell_avui = isset($pla_data['nivell_avui']) ? intval($pla_data['nivell_avui']) : 3;
$nivell_avui_text = $pla_data['nivell_avui_text'] ?? 'Molt Alt';
$color_avui = $pla_data['color_avui'] ?? '#e74c3c';

$has_dema = !empty($pla_data['has_dema']) && isset($pla_data['nivell_dema']);
$nivell_dema = $has_dema ? intval($pla_data['nivell_dema']) : null;
$nivell_dema_text = $has_dema ? ($pla_data['nivell_dema_text'] ?? '') : '';
$color_dema = $has_dema ? ($pla_data['color_dema'] ?? '#e74c3c') : '';

$hora = $pla_data['hora'] ?? '9:30';
$data_pla = $pla_data['data'] ?? date('d/m/Y');
$advisory = $pla_data['advisory'] ?? 'Suspensió de cremes i feines agrícoles amb maquinària';

$level_colors = [
    0 => '#27ae60',
    1 => '#f39c12',
    2 => '#e67e22',
    3 => '#e74c3c',
    4 => '#8e1b1b'
];
?>
<div class="PWS_module_title">
    <span>Pla Alfa &bull; Agents Rurals</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo $hora; ?> h</span>
</div>
<div style="height: 180px; padding: 10px 12px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; text-align: center;">
    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; color: #a0aec0;">
        <span style="font-weight: 700; color: #cbd5e0;">Sallent &bull; Bages</span>
        <span style="font-size: 12px; color: #718096;"><?php echo $data_pla; ?></span>
    </div>

    <!-- Active level badge -->
    <div style="margin: 3px 0;">
        <div style="background: <?php echo $color_avui; ?>; color: #fff; font-weight: 800; font-size: 15px; padding: 6px 16px; border-radius: 6px; letter-spacing: 0.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.4); display: inline-block;">
            NIVELL <?php echo $nivell_avui; ?> &bull; <?php echo mb_strtoupper($nivell_avui_text); ?>
        </div>
    </div>

    <!-- 5-level visual scale bar -->
    <div style="display: flex; gap: 8px; justify-content: center; margin: 4px 0; padding: 0 4px;">
        <?php for ($i = 0; $i <= 4; $i++): 
            $isActive = ($i == $nivell_avui);
            $c = $level_colors[$i];
        ?>
            <div style="flex: 1; height: 22px; line-height: 22px; padding: 0 8px; border-radius: 3px; background: <?php echo $c; ?>; opacity: <?php echo ($isActive ? '1' : '0.28'); ?>; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: #fff; box-sizing: border-box; <?php echo ($isActive ? 'border: 2px solid #ffffff; box-shadow: 0 0 8px '.$c.'; transform: scale(1.05);' : ''); ?>">
                <?php echo $i; ?>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Tomorrow forecast -->
    <div style="background: rgba(255, 255, 255, 0.05); border-radius: 4px; padding: 6px 10px; font-size: 13px; display: flex; justify-content: space-between; align-items: center;">
        <span style="color: #a0aec0;">Previsió demà:</span>
        <?php if ($has_dema): ?>
            <span style="font-weight: 700; color: <?php echo $color_dema; ?>;">Nivell <?php echo $nivell_dema; ?> (<?php echo $nivell_dema_text; ?>)</span>
        <?php else: ?>
            <span style="font-weight: 600; color: #94a3b8; font-style: italic;">No actualitzat</span>
        <?php endif; ?>
    </div>

    <!-- Advisory / Restrictions text -->
    <div style="font-size: 12.5px; color: #e2e8f0; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($advisory); ?>">
        &#9888;&#65039; <?php echo $advisory; ?>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="https://experience.arcgis.com/experience/2cf7ebbe492f401db826cb21eae9bfae" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Mapa interactiu</a>
    <span style="color: #4a5568;"> | </span>
    <a href="https://interior.gencat.cat/ca/arees_dactuacio/agents-rurals/pla-alfa/" target="_blank"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Agents Rurals</a>
</div>
