<?php
include_once('livedata.php');
include_once('common.php');
include_once('jsondata/icgc_sismes.php');
date_default_timezone_set($TZ);

$sismes = get_icgc_sismes();
$top_sismes = array_slice($sismes, 0, 3);
?>
<div class="PWS_module_title">
    <span>Xarxa Sísmica de Catalunya &bull; ICGC Sismocat</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> En directe</span>
</div>
<div style="height: 180px; padding: 10px 14px; box-sizing: border-box; font-size: 12.5px; overflow-y: auto;">
    <?php if (!empty($top_sismes)): ?>
        <?php foreach ($top_sismes as $i => $s): ?>
            <?php
            $m = floatval($s['magnitude']);
            $badge_bg = "#9999F8";
            if ($m >= 2.5) $badge_bg = "#ff8841";
            elseif ($m >= 1.5) $badge_bg = "#FDDC19";
            ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; <?php echo ($i < count($top_sismes)-1 ? 'border-bottom: 1px solid rgba(255,255,255,0.06);' : ''); ?>">
                <div style="width: 44px; height: 38px; background: <?php echo $badge_bg; ?>; color: #000; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: 15.5px; font-weight: 800; flex-shrink: 0;">
                    <?php echo $s['magnitude']; ?>
                </div>
                <div style="flex: 1; text-align: left; margin-left: 12px; line-height: 1.4;">
                    <b style="color: #fff; font-size: 13px;"><?php echo $s['region']; ?></b><br>
                    <span style="color: #a0aec0; font-size: 11px;"><?php echo $s['date']; ?> &bull; Sisme instrumentat</span>
                </div>
                <div style="text-align: right;">
                    <a href="<?php echo $s['link']; ?>" target="_blank" style="color: #01a4b4; text-decoration: none; font-size: 11.5px; font-weight: 600; padding: 3px 9px; background: rgba(1,164,180,0.15); border-radius: 4px;">
                        Detalls &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; color: #a0aec0; padding-top: 40px;">
            Sense sismes recents a Catalunya
        </div>
    <?php endif; ?>
</div>
<div class="PWS_module_footer">
    <a href="https://www.icgc.cat/terratremols" target="_blank"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Institut Cartogràfic i Geològic de Catalunya (ICGC)</a>
</div>
