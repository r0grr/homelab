<?php
include_once('livedata.php');
include_once('common.php');

$current_year = date('Y');
$historical_temps = [
    1 => '4.5&deg;',
    2 => '6.7&deg;',
    3 => '9.6&deg;',
    4 => '12.9&deg;',
    5 => '16.7&deg;',
    6 => '20.3&deg;',
    7 => '24.1&deg;',
    8 => '23.7&deg;',
    9 => '19.8&deg;',
    10 => '15.3&deg;',
    11 => '8.7&deg;',
    12 => '4.9&deg;'
];
$month_names = [
    1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Des'
];

$current_year_temps = [];
for ($m = 1; $m <= 12; $m++) {
    $current_year_temps[$m] = '--';
}

$report_file = __DIR__ . '/noaa_reports/' . $current_year . '.txt';
if (!file_exists($report_file)) {
    $report_file = __DIR__ . '/noaa_reports/noaayr.txt';
}

if (file_exists($report_file)) {
    $lines = file($report_file);
    foreach ($lines as $line) {
        if (preg_match('/^\s*([1-9]|1[0-2])\s+([\-\d\.]+)/', $line, $matches)) {
            $m_idx = intval($matches[1]);
            $current_year_temps[$m_idx] = number_format(floatval($matches[2]), 1, '.', '') . '&deg;';
        }
    }
}
?>
<div class="PWS_module_title">
    <span>Mitjanes Mensuals de Temperatura</span>
</div>
<div style="height: 180px; padding: 6px 10px; box-sizing: border-box; display: flex; align-items: center;">
    <div style="display: flex; justify-content: space-between; width: 100%; height: 166px;">
        <!-- First 6 months -->
        <div style="width: 48%; padding-right: 6px; border-right: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="color: #a0aec0; font-size: 11.5px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: left;">Mes</th>
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: center;">Hist.</th>
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: right; color: #4FFC37;"><?php echo $current_year; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($m = 1; $m <= 6; $m++): ?>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;"><?php echo $month_names[$m]; ?></td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;"><?php echo $historical_temps[$m]; ?></td>
                        <?php if ($current_year_temps[$m] !== '--'): ?>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;"><?php echo $current_year_temps[$m]; ?></td>
                        <?php else: ?>
                        <td style="color: #718096; padding: 2.5px 0; text-align: right;">--</td>
                        <?php endif; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <!-- Last 6 months -->
        <div style="width: 48%; padding-left: 6px; display: flex; align-items: center;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="color: #a0aec0; font-size: 11.5px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: left;">Mes</th>
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: center;">Hist.</th>
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: right; color: #4FFC37;"><?php echo $current_year; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($m = 7; $m <= 12; $m++): ?>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;"><?php echo $month_names[$m]; ?></td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;"><?php echo $historical_temps[$m]; ?></td>
                        <?php if ($current_year_temps[$m] !== '--'): ?>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;"><?php echo $current_year_temps[$m]; ?></td>
                        <?php else: ?>
                        <td style="color: #718096; padding: 2.5px 0; text-align: right;">--</td>
                        <?php endif; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="historia.php" target="_blank"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Informes Climatològics (2006-<?php echo $current_year; ?>)</a>
</div>

