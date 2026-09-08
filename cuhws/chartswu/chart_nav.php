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
