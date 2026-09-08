<?php
include_once('livedata.php');
include_once('common.php');
include_once('jsondata/icgc_sismes.php');

$sismes = get_icgc_sismes();
$latest = !empty($sismes) ? $sismes[0] : null;

$mag = $latest ? $latest['magnitude'] : "1.5";
$region = $latest ? $latest['region'] : "Alts Pirineus";
$date_str = $latest ? $latest['date'] : date('d/m H:i');
$link = $latest ? $latest['link'] : "https://www.icgc.cat/terratremols";
?>
<div class="PWS_module_title" style="padding-top: 2px;">
    <span>Sismes Catalunya &bull; ICGC</span>
</div>
<div style="padding: 6px 8px; display: flex; align-items: center; justify-content: space-between; height: 80px; box-sizing: border-box;">
    <a href="<?php echo $link; ?>" target="_blank" style="text-decoration: none; flex-shrink: 0;">
        <div style="width: 54px; height: 52px; background-color: #9999F8; color: #000; border-radius: 5px; text-align: center; padding-top: 6px; box-sizing: border-box;">
            <span style="font-size: 16px; font-weight: 800;"><?php echo $mag; ?></span><br>
            <svg viewBox="0 0 32 32" width="12" height="12" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="4">
                <path d="M4 16 L11 16 14 29 18 3 21 16 28 16" />
            </svg>
        </div>
    </a>
    <div style="font-size: 11px; line-height: 1.3; text-align: left; margin-left: 8px; overflow: hidden; flex: 1;">
        <b style="color: #f37867;">Xarxa Sísmica ICGC</b><br>
        <span style="color: #e2e8f0; font-weight: 600;"><?php echo $region; ?></span><br>
        <span style="color: #a0aec0; font-size: 10.5px;"><?php echo $date_str; ?> &bull; Sismocat</span><br>
        <a href="https://www.icgc.cat/terratremols" target="_blank" style="color: #01a4b4; text-decoration: none; font-size: 10px;">icgc.cat/terratremols &rarr;</a>
    </div>
</div>
