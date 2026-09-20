<?php
include_once('livedata.php');
include_once('common.php');
date_default_timezone_set($TZ);
?>
<div class="PWS_module_title">
    <span>Càmera Web &bull; Sallent</span>
    <span class="PWS_ol_time"><svg viewBox="0 0 32 32" width="7" height="7" fill="currentColor"><circle cx="16" cy="16" r="14"></circle></svg> <?php echo date('d/m H:i'); ?></span>
</div>
<div style="height: 180px; padding: 12px 14px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; align-items: center; text-align: center;">
    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #718096;">
        <span style="font-weight: 700; color: #94a3b8;">Davis VP2 Plus Sallent</span>
        <span style="color: #f59e0b; font-weight: 600; display: flex; align-items: center; gap: 4px;">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; box-shadow: 0 0 6px #f59e0b;"></span>
            En preparació
        </span>
    </div>

    <!-- Camera lens aperture icon graphic -->
    <div style="position: relative; width: 64px; height: 64px; margin: 4px auto;">
        <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="32" cy="32" r="30" stroke="rgba(255,255,255,0.12)" stroke-width="1.5" fill="rgba(15, 23, 42, 0.75)"/>
            <circle cx="32" cy="32" r="22" stroke="rgba(56, 189, 248, 0.4)" stroke-width="1.5" fill="rgba(2, 132, 199, 0.1)"/>
            <!-- Camera aperture / lens elements -->
            <path d="M32 14 L42 28 L36 38 L22 34 Z" stroke="rgba(56, 189, 248, 0.6)" stroke-width="1.2" fill="none"/>
            <path d="M48 26 L46 42 L32 46 L28 32 Z" stroke="rgba(56, 189, 248, 0.5)" stroke-width="1.2" fill="none"/>
            <path d="M42 48 L26 48 L18 36 L28 26 Z" stroke="rgba(56, 189, 248, 0.5)" stroke-width="1.2" fill="none"/>
            <circle cx="32" cy="32" r="8" fill="#0284c7" opacity="0.75"/>
            <circle cx="30" cy="30" r="2.5" fill="#ffffff" opacity="0.9"/>
        </svg>
    </div>

    <!-- Badge Pròximament Disponible -->
    <div style="margin: 2px 0;">
        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; font-weight: 800; font-size: 12.5px; padding: 4px 14px; border-radius: 6px; letter-spacing: 0.5px; text-transform: uppercase;">
            Pròximament disponible
        </div>
    </div>

    <div style="font-size: 11.5px; color: #94a3b8; line-height: 1.35;">
        Instal·lació de càmera meteorològica HD en curs orientada a Sallent
    </div>
</div>
<div class="PWS_module_footer">
    <span style="color: #64748b;"><svg viewBox="0 0 32 32" width="12" height="10" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="10%"><circle cx="16" cy="16" r="12"></circle></svg> Canal de vídeo en desplegament tècnic</span>
</div>
