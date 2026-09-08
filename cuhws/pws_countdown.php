<?php
include_once('livedata.php');
include_once('common.php');
?>
<div class="PWS_module_title">
    <span>Compte Enrere Estacions de l'Any</span>
</div>
<div style="height: 180px; padding: 18px 24px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-around; text-align: center;">
    <!-- Autumn -->
    <div style="flex: 1; padding: 0 16px; border-right: 1px solid rgba(255,255,255,0.1);">
        <div style="color: #ff8841; font-weight: 700; font-size: 14.5px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">🍂 Inici Tardor</div>
        <div style="font-size: 12.5px; color: #a0aec0; margin: 6px 0 10px; white-space: nowrap;">23 Set 2026 &bull; 02:05 h</div>
        <div id="pws_tardor_timer" style="color: #2ecc71; font-weight: 800; font-size: 18px; font-family: 'Courier New', monospace; background: rgba(0,0,0,0.35); padding: 7px 12px; border-radius: 6px; border: 1px solid rgba(46,204,113,0.35); display: inline-block; white-space: nowrap; letter-spacing: 0.5px;">
            --d --h --m
        </div>
    </div>

    <!-- Winter -->
    <div style="flex: 1; padding: 0 16px;">
        <div style="color: #01a4b4; font-weight: 700; font-size: 14.5px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">❄️ Inici Hivern</div>
        <div style="font-size: 12.5px; color: #a0aec0; margin: 6px 0 10px; white-space: nowrap;">21 Des 2026 &bull; 21:50 h</div>
        <div id="pws_hivern_timer" style="color: #57FAF9; font-weight: 800; font-size: 18px; font-family: 'Courier New', monospace; background: rgba(0,0,0,0.35); padding: 7px 12px; border-radius: 6px; border: 1px solid rgba(87,250,249,0.35); display: inline-block; white-space: nowrap; letter-spacing: 0.5px;">
            --d --h --m
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="mooninfo.php" data-featherlight="iframe"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Calendari Astronòmic Oficial</a>
</div>

<script>
(function() {
    function updateCountdowns() {
        var now = new Date().getTime();
        var tAutumn = new Date("2026-09-23T02:05:00+02:00").getTime();
        var tWinter = new Date("2026-12-21T21:50:00+01:00").getTime();

        var diffA = tAutumn - now;
        var elA = document.getElementById("pws_tardor_timer");
        if (diffA > 0) {
            var dA = Math.floor(diffA / (1000 * 60 * 60 * 24));
            var hA = Math.floor((diffA % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var mA = Math.floor((diffA % (1000 * 60 * 60)) / (1000 * 60));
            if (elA) elA.textContent = dA + "d " + (hA < 10 ? "0" : "") + hA + "h " + (mA < 10 ? "0" : "") + mA + "m";
        } else if (elA) {
            elA.textContent = "Tardor iniciada";
        }

        var diffW = tWinter - now;
        var elW = document.getElementById("pws_hivern_timer");
        if (diffW > 0) {
            var dW = Math.floor(diffW / (1000 * 60 * 60 * 24));
            var hW = Math.floor((diffW % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var mW = Math.floor((diffW % (1000 * 60 * 60)) / (1000 * 60));
            if (elW) elW.textContent = dW + "d " + (hW < 10 ? "0" : "") + hW + "h " + (mW < 10 ? "0" : "") + mW + "m";
        } else if (elW) {
            elW.textContent = "Hivern iniciat";
        }
    }
    updateCountdowns();
    if (!window.pwsCountdownTimer) {
        window.pwsCountdownTimer = setInterval(updateCountdowns, 60000);
    }
})();
</script>
