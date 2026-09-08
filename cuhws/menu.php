<?php 
include_once('settings1.php');
include_once('common.php');
include_once('shared.php'); ?>
<!-- begin modern menu.php -->
<input type="checkbox" class="openweather34sidebarMenu" id="openweather34sidebarMenu" style="display: none;">
<label for="openweather34sidebarMenu" id="weather34sidebarOverlay"></label>
<div id="weather34sidebarMenu" style="z-index: 99999;">
    <div style="padding: 20px 16px 10px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 700; color: #01a4b4; font-size: 15px;">MeteoSallent Menú</span>
        <label for="openweather34sidebarMenu" style="cursor: pointer; color: #a0aec0; font-size: 18px; font-weight: 700;">&times;</label>
    </div>
    <ul class="weather34sidebarMenuInner">
        <li><a href="#">PREFERÈNCIES</a></li>
        <li><a href="index.php" title="Inici"><?php echo $weather34homeicon; ?> Inici</a></li>  
        <li><a href="historia.php" title="Historial Climatològic"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><path d="M14 9 L3 9 3 29 23 29 23 18 M18 4 L28 4 28 14 M28 4 L14 18"></path></svg> Historial Climatològic (2006-2026)</a></li>  

        <li><a href="#">XARXA TEMPSCAT</a></li>
        <li><a href="https://sqv.tempscat.com" target="_blank" title="Estació Meteorològica Sant Quirze del Vallès"><?php echo $info;?> Sant Quirze del Vallès</a></li>

        <li><a href="#">XARXES METEOROLÒGIQUES</a></li>
        <li><a href="https://meteoclimatic.net" target="_blank"><?php echo $info;?> Xarxa Meteoclimatic</a></li>
        <li><a href="https://meteo.cat" target="_blank"><?php echo $info;?> Servei Meteorològic de Catalunya</a></li>
        <li><a href="https://www.icgc.cat/terratremols" target="_blank"><?php echo $info;?> Sismologia ICGC Catalunya</a></li>

        <?php if($languages=="yes"): ?>
        <li><a href="#"><?php echo $arrow34icon; ?> IDIOMES</a></li>
        <li style="display: flex; gap: 8px; padding: 6px 12px; flex-wrap: wrap;">
            <a href="index.php?lang=cat" title="Català"><img src="img/flags/cat.svg" width="22" height="16" alt="Català"></a>
            <a href="index.php?lang=sp" title="Castellano"><img src="img/flags/sp.svg" width="22" height="16" alt="Español"></a>
            <a href="index.php?lang=en" title="English"><img src="img/flags/en.svg" width="22" height="16" alt="English"></a>
            <a href="index.php?lang=fr" title="Français"><img src="img/flags/fr.svg" width="22" height="16" alt="Français"></a>
        </li>
        <?php endif; ?>
    </ul>
</div>
<!-- end menu.php -->
