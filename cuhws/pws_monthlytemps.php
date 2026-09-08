<?php
include_once('livedata.php');
include_once('common.php');
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
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: right; color: #4FFC37;">2026</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Gen</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">4.5&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">4.7&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Feb</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">6.7&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">8.7&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Mar</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">9.6&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">10.0&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Abr</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">12.9&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">15.2&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Mai</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">16.7&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">17.8&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Jun</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">20.3&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">24.1&deg;</td>
                    </tr>
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
                        <th style="padding: 1px 0 4px; font-weight: 600; text-align: right; color: #4FFC37;">2026</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Jul</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">24.1&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">27.2&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Ago</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">23.7&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">25.5&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Set</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">19.8&deg;</td>
                        <td style="color: #4FFC37; font-weight: 700; padding: 2.5px 0; text-align: right;">25.2&deg;</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Oct</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">15.3&deg;</td>
                        <td style="color: #718096; padding: 2.5px 0; text-align: right;">--</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Nov</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">8.7&deg;</td>
                        <td style="color: #718096; padding: 2.5px 0; text-align: right;">--</td>
                    </tr>
                    <tr>
                        <td style="color: #ff8841; font-weight: 600; padding: 2.5px 0; text-align: left;">Des</td>
                        <td style="color: #e2e8f0; padding: 2.5px 0; text-align: center;">4.9&deg;</td>
                        <td style="color: #718096; padding: 2.5px 0; text-align: right;">--</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="PWS_module_footer">
    <a href="historia.php" target="_blank"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Informes Climatològics (2006-2026)</a>
</div>
