<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);

$solar = isset($weather["solar"]) && floatval($weather["solar"]) > 0 ? intval($weather["solar"]) : 0;
$uv = isset($weather["uv"]) ? floatval($weather["uv"]) : 0.0;
$lux = isset($weather["lux"]) && intval($weather["lux"]) > 0 ? intval($weather["lux"]) : 0;

$uv_color = "#9aba2f";
if ($uv >= 8) $uv_color = "#d86858";
elseif ($uv >= 6) $uv_color = "#ff8841";
elseif ($uv >= 3) $uv_color = "#ecb454";
?>
<div class="PWS_module_title">
    <span>Solar &bull; Índex UV &bull; Lux</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div class="PWS_body" style="display: flex; flex-direction: column; justify-content: flex-start; height: 180px; padding: 8px 8px 6px 8px; box-sizing: border-box;">
    <!-- Top row: 3 data blocks aligned at the exact same height and baseline -->
    <div style="display: flex; justify-content: space-between; gap: 6px; width: 100%;">
        <!-- Block 1: Solar -->
        <div class="PWS_div_left" style="flex: 1; margin: 0; min-height: 48px; border-right: 3px solid #ecb454; border-left: 1px solid rgba(255,255,255,0.08); font-size: 13px; line-height: 1.3; padding: 4px 5px;">
            Radiació Solar<br><b style="font-size: 14.5px; color: #fff;"><?php echo $solar; ?> W/m&sup2;</b>
        </div>
        <!-- Block 2: UV -->
        <div class="PWS_div_left" style="flex: 1; margin: 0; min-height: 48px; border-right: 3px solid <?php echo $uv_color; ?>; border-left: 1px solid rgba(255,255,255,0.08); font-size: 13px; line-height: 1.3; padding: 4px 5px;">
            Ultraviolat<br><b style="font-size: 14.5px; color: #fff;"><?php echo number_format($uv, 1); ?> Índex</b>
        </div>
        <!-- Block 3: Lux -->
        <div class="PWS_div_right" style="flex: 1; margin: 0; min-height: 48px; border-left: 3px solid #01a4b4; border-right: 1px solid rgba(255,255,255,0.08); font-size: 13px; line-height: 1.3; padding: 4px 5px;">
            Lluminositat<br><b style="font-size: 14.5px; color: #fff;"><?php echo number_format($lux); ?> Lux</b>
        </div>
    </div>

    <!-- Bottom row: 3 matching visual graphics -->
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; height: 98px; margin-top: 6px;">
        <!-- Left graphic: Solar bars (6 blocks, wider & taller) -->
        <div style="flex: 1; text-align: center;">
            <svg width="48px" height="82px" viewBox="0 0 48 84">
                <rect x="8" y="2" width="32" height="80" rx="5" fill="rgba(255,255,255,0.06)" />
                <rect x="11" y="69" width="26" height="10" rx="2" fill="#9aba2f" />
                <rect x="11" y="56" width="26" height="10" rx="2" fill="<?php echo ($solar > 150 ? '#9aba2f' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="43" width="26" height="10" rx="2" fill="<?php echo ($solar > 350 ? '#ecb454' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="30" width="26" height="10" rx="2" fill="<?php echo ($solar > 550 ? '#ff8841' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="17" width="26" height="10" rx="2" fill="<?php echo ($solar > 750 ? '#f37867' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="4"  width="26" height="10" rx="2" fill="<?php echo ($solar > 950 ? '#d85240' : 'rgba(255,255,255,0.1)'); ?>" />
            </svg>
        </div>

        <!-- Middle graphic: UV triangle -->
        <div style="flex: 1; text-align: center; position: relative; width: 84px; height: 76px; margin: 0 auto;">
            <svg width="84" height="72" viewBox="0 0 100 80">
                <polygon points="50,5 95,75 5,75" fill="rgba(255,255,255,0.08)" stroke="#4a5568" stroke-width="2" />
                <polygon points="50,45 75,75 25,75" fill="<?php echo $uv_color; ?>" />
            </svg>
            <div style="position: absolute; top: 25px; left: 50%; transform: translateX(-50%); width: 32px; height: 32px; background: <?php echo $uv_color; ?>; border-radius: 50%; color: #fff; font-weight: 800; font-size: 18px; line-height: 32px; box-shadow: 0 2px 6px rgba(0,0,0,0.5); text-align: center;">
                <?php echo round($uv); ?>
            </div>
        </div>

        <!-- Right graphic: Lux bars (6 blocks, wider & taller) -->
        <div style="flex: 1; text-align: center;">
            <svg width="48px" height="82px" viewBox="0 0 48 84">
                <rect x="8" y="2" width="32" height="80" rx="5" fill="rgba(255,255,255,0.06)" />
                <rect x="11" y="69" width="26" height="10" rx="2" fill="#01a4b4" />
                <rect x="11" y="56" width="26" height="10" rx="2" fill="<?php echo ($lux > 10000 ? '#01a4b4' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="43" width="26" height="10" rx="2" fill="<?php echo ($lux > 25000 ? '#00bfa5' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="30" width="26" height="10" rx="2" fill="<?php echo ($lux > 45000 ? '#ecb454' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="17" width="26" height="10" rx="2" fill="<?php echo ($lux > 70000 ? '#ff8841' : 'rgba(255,255,255,0.1)'); ?>" />
                <rect x="11" y="4"  width="26" height="10" rx="2" fill="<?php echo ($lux > 90000 ? '#f37867' : 'rgba(255,255,255,0.1)'); ?>" />
            </svg>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="uvindexds.php" data-featherlight="iframe" data-featherlight-variant="featherlight-vertical" class="featherlight-vertical-link"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Guia UV Oficial</a>
</div>
