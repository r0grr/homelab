<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);
?>
<div class="PWS_module_title">
    <span>Estat Ambiental &bull; Salut Aire</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('H:i'); ?></span>
</div>
<div style="height: 152px; padding: 6px 10px; box-sizing: border-box;">
    <div style="display: flex; justify-content: space-between; align-items: stretch; height: 86px; gap: 6px; margin-bottom: 8px;">
        <div style="flex: 1; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; padding: 4px; font-size: 11px; line-height: 1.35; text-align: center;">
            <b style="color: #ff8841;">Estacions:</b><br>
            Sallent <span style="background:#00ccff; color:#fff; border-radius:3px; padding: 1px 4px; font-weight:700;">1</span><br>
            Manresa <span style="background:#0099cc; color:#fff; border-radius:3px; padding: 1px 4px; font-weight:700;">2</span><br>
            Bages <span style="background:#0099cc; color:#fff; border-radius:3px; padding: 1px 4px; font-weight:700;">2</span>
        </div>
        <div style="flex: 1; background: #0099cc; color: #fff; border-radius: 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,153,204,0.4);">
            <span style="font-size: 36px; font-weight: 800; line-height: 1;">2</span>
            <span style="font-size: 12.5px; font-weight: 700;">Risc Baix</span>
        </div>
        <div style="flex: 1.2; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; padding: 4px; font-size: 10.5px; line-height: 1.35; text-align: center;">
            <b style="color: #ff8841;">Recomanació:</b><br>
            Gaudeix de totes les activitats a l'aire lliure amb normalitat.
        </div>
    </div>

    <!-- Scale 1-11 -->
    <table style="width: 100%; border-collapse: collapse; text-align: center; font-size: 9.5px; font-weight: 700;">
        <tr style="height: 6px;">
            <td></td>
            <td style="color: #fff;">&#9660;</td>
            <td colspan="9"></td>
        </tr>
        <tr style="height: 15px; color: #fff;">
            <td style="background: #00ccff; border-radius: 3px 0 0 3px;">1</td>
            <td style="background: #0099cc;">2</td>
            <td style="background: #006699;">3</td>
            <td style="background: #ffff00; color: #000;">4</td>
            <td style="background: #ffcc00; color: #000;">5</td>
            <td style="background: #ff9933; color: #000;">6</td>
            <td style="background: #ff6666;">7</td>
            <td style="background: #ff0000;">8</td>
            <td style="background: #cc0000;">9</td>
            <td style="background: #990000;">10</td>
            <td style="background: #660000; border-radius: 0 3px 3px 0;">11+</td>
        </tr>
    </table>
</div>
<div class="PWS_module_footer">
    <a href="https://mediambient.gencat.cat" target="_blank"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Dades Ambientals Catalunya</a>
</div>
