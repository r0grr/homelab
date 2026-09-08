<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);
?>
<div class="PWS_module_title">
    <span>Webcam en Directe &bull; Sallent</span>
</div>
<div style="height: 148px; position: relative; overflow: hidden; background: #000;">
    <a href="cam.php" data-featherlight="iframe">
        <img src="img/backgrounds/summer.jpg" alt="Webcam Sallent" style="width: 100%; height: 148px; object-fit: cover; opacity: 0.9;">
        <div style="position: absolute; bottom: 6px; left: 8px; background: rgba(0,0,0,0.65); padding: 2px 6px; border-radius: 3px; font-size: 9px; color: #fff; display: flex; align-items: center; gap: 4px;">
            <span style="color: #ff4757; font-size: 10px;">&bull; REC</span>
            <span><?php echo date('d-m-Y H:i'); ?></span>
        </div>
        <div style="position: absolute; top: 6px; right: 8px; background: rgba(0,0,0,0.65); padding: 2px 6px; border-radius: 3px; font-size: 9px; color: #00ff66; font-weight: 700;">
            LIVE
        </div>
    </a>
</div>
<div class="PWS_module_footer">
    <a href="cam.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Ampliar Webcam</a>
</div>
