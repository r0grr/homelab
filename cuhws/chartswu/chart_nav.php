<?php
/**
 * chart_nav.php
 * Navigation tabs for switching between Today (24h), Weekly (7d), Monthly (1m), and Yearly.
 */
function render_chart_nav($type, $activeSpan, $selectedYear = null) {
    $currentYear = intval(date('Y'));
    if ($selectedYear === null) {
        $selectedYear = (isset($_GET['year']) && is_numeric($_GET['year'])) ? intval($_GET['year']) : $currentYear;
    }
    
    $yearlyLink = "yearly{$type}.php" . ($selectedYear !== $currentYear ? "?year={$selectedYear}" : "");

    $spans = [
        'today' => ['label' => '24 Hores', 'file' => "today{$type}.php"],
        'weekly' => ['label' => '7 Dies', 'file' => "weekly{$type}.php"],
        'monthly' => ['label' => '1 Mes', 'file' => "monthly{$type}.php"],
        'yearly' => ['label' => '1 Any', 'file' => $yearlyLink],
    ];
    
    echo '<div class="chart-nav-bar">';
    foreach ($spans as $key => $s) {
        $isActive = ($key === $activeSpan);
        $cls = $isActive ? 'chart-nav-btn active' : 'chart-nav-btn';
        echo "<a href=\"{$s['file']}\" class=\"{$cls}\">{$s['label']}</a>";
    }
    if ($activeSpan === 'yearly') {
        echo '<div style="display:inline-flex; align-items:center; margin-left:8px; gap:5px;">';
        echo '<label for="chartYearSelect" style="font-size:11px; color:#a0aec0; font-weight:600;">Any:</label>';
        echo '<select id="chartYearSelect" onchange="location.href=\'yearly' . $type . '.php?year=\' + this.value" style="background:#2d3748; color:#fff; border:1px solid rgba(80,85,95,0.6); border-radius:4px; padding:2px 6px; font-size:11px; font-weight:600; cursor:pointer;">';
        for ($y = $currentYear; $y >= 2006; $y--) {
            $sel = ($y === $selectedYear) ? ' selected' : '';
            echo "<option value=\"{$y}\"{$sel}>{$y}</option>";
        }
        echo '</select>';
        echo '</div>';
    }
    echo '</div>';
}

function render_chart_scroller() {
    echo '
<div class="chart-scroller-wrapper">
    <button type="button" class="chart-scroll-btn scroll-left" id="btnScrollLeft" aria-label="Moure a l\'esquerra" title="Moure a l\'esquerra">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
    </button>
    <div class="chart-scroller-track-box">
        <input type="range" class="chart-scroller-range" id="chartScrollRange" min="0" max="100" value="0" aria-label="Desplaçar gràfic">
        <div class="chart-scroller-label">
            <span class="scroller-label-text">◄ Desplaça per navegar pel gràfic ►</span>
        </div>
    </div>
    <button type="button" class="chart-scroll-btn scroll-right" id="btnScrollRight" aria-label="Moure a la dreta" title="Moure a la dreta">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
    </button>
</div>
<script>
(function() {
    function initScroller() {
        var range = document.getElementById("chartScrollRange");
        var btnLeft = document.getElementById("btnScrollLeft");
        var btnRight = document.getElementById("btnScrollRight");
        var wrapper = document.getElementById("chartScrollWrapper");
        if (!wrapper) {
            var c = document.getElementById("chartContainer");
            if (c && c.parentElement) {
                wrapper = c.parentElement;
                wrapper.id = "chartScrollWrapper";
                wrapper.classList.add("chart-scroll-wrapper");
            }
        }
        if (!range || !wrapper) return;

        function getMaxScroll() {
            var maxW = wrapper.scrollWidth - wrapper.clientWidth;
            if (maxW <= 0) {
                try {
                    if (window.parent && window.parent !== window) {
                        var pContent = window.parent.document.querySelector(".featherlight-iframe .featherlight-content");
                        if (pContent) {
                            return Math.max(0, pContent.scrollWidth - pContent.clientWidth);
                        }
                    }
                } catch(e) {}
            }
            return Math.max(0, maxW);
        }

        function setScrollPos(px) {
            wrapper.scrollLeft = px;
            try {
                if (window.parent && window.parent !== window) {
                    var pContent = window.parent.document.querySelector(".featherlight-iframe .featherlight-content");
                    if (pContent) {
                        var maxW = wrapper.scrollWidth - wrapper.clientWidth;
                        var maxP = pContent.scrollWidth - pContent.clientWidth;
                        if (maxP > 0) {
                            var ratio = maxW > 0 ? (px / maxW) : 0;
                            pContent.scrollLeft = ratio * maxP;
                        }
                    }
                }
            } catch(e) {}
        }

        function updateRangeFromScroll() {
            var max = getMaxScroll();
            if (max > 0) {
                var cur = wrapper.scrollLeft;
                range.value = Math.round((cur / max) * 100);
            } else {
                range.value = 0;
            }
        }

        range.addEventListener("input", function() {
            var max = getMaxScroll();
            var target = (parseFloat(this.value) / 100) * max;
            setScrollPos(target);
        });

        if (btnLeft) {
            btnLeft.addEventListener("click", function() {
                var step = Math.max(120, wrapper.clientWidth * 0.35);
                var target = Math.max(0, wrapper.scrollLeft - step);
                wrapper.scrollTo({ left: target, behavior: "smooth" });
                setScrollPos(target);
                setTimeout(updateRangeFromScroll, 250);
            });
        }

        if (btnRight) {
            btnRight.addEventListener("click", function() {
                var max = getMaxScroll();
                var step = Math.max(120, wrapper.clientWidth * 0.35);
                var target = Math.min(max, wrapper.scrollLeft + step);
                wrapper.scrollTo({ left: target, behavior: "smooth" });
                setScrollPos(target);
                setTimeout(updateRangeFromScroll, 250);
            });
        }

        wrapper.addEventListener("scroll", updateRangeFromScroll, { passive: true });
        window.addEventListener("resize", updateRangeFromScroll);
        
        setTimeout(updateRangeFromScroll, 300);
        setTimeout(updateRangeFromScroll, 800);
        setTimeout(updateRangeFromScroll, 1500);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initScroller);
    } else {
        initScroller();
    }
})();
</script>';
}
