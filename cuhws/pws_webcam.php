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

    <!-- Camera icon graphic: outdoor surveillance weathercam -->
    <div style="position: relative; width: 68px; height: 68px; margin: 2px auto;">
        <svg width="68" height="68" viewBox="0 0 68 68" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="34" cy="34" r="32" fill="rgba(15, 23, 42, 0.85)" stroke="rgba(56, 189, 248, 0.3)" stroke-width="1.5" />
            <circle cx="34" cy="34" r="26" stroke="rgba(255, 255, 255, 0.08)" stroke-width="1" stroke-dasharray="3 3" />
            <!-- Mount bracket -->
            <path d="M17 45 L25 39 L28 41 L20 47 Z" fill="#475569" stroke="#64748b" stroke-width="1"/>
            <rect x="15" y="44" width="4" height="10" rx="1" fill="#334155"/>
            <!-- Camera housing -->
            <rect x="23" y="23" width="26" height="18" rx="4" fill="#1e293b" stroke="#38bdf8" stroke-width="1.6"/>
            <!-- Visor / Sun shield -->
            <path d="M21 21 L51 21 L47 24 L24 24 Z" fill="#38bdf8" opacity="0.85"/>
            <path d="M21 21 L52 21" stroke="#38bdf8" stroke-width="2" stroke-linecap="round"/>
            <!-- Lens and optic -->
            <ellipse cx="48.5" cy="32" rx="3.5" ry="8" fill="#0f172a" stroke="#38bdf8" stroke-width="1.5"/>
            <ellipse cx="48.5" cy="32" rx="2" ry="5" fill="#0284c7"/>
            <circle cx="48" cy="30" r="1" fill="#ffffff" opacity="0.9"/>
            <!-- LED status indicators -->
            <circle cx="27" cy="27" r="1.5" fill="#10b981"/>
            <circle cx="44" cy="26" r="0.8" fill="#38bdf8" opacity="0.8"/>
            <circle cx="44" cy="38" r="0.8" fill="#38bdf8" opacity="0.8"/>
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
