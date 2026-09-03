#!/usr/bin/env bash
# ==============================================================================
# Script de Respaldo Diario - Homelab a Almacenamiento SMB/Router
# ==============================================================================
set -euo pipefail

# Cargar variables de entorno si existen
if [ -f "/opt/servidor/.env" ]; then
    set -a
    source /opt/servidor/.env
    set +a
fi

BACKUP_DIR="${BACKUP_TARGET_DIR:-/mnt/backup_router}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
DATE=$(date +"%Y%m%d_%H%M%S")
DEST="$BACKUP_DIR/snapshot_$DATE"

echo "💾 [$(date)] Iniciando copia de seguridad en $BACKUP_DIR..."

# Comprobar que el directorio o punto de montaje de red existe y es escribible
if [ ! -d "$BACKUP_DIR" ] || [ ! -w "$BACKUP_DIR" ]; then
    echo "⚠️ ERROR: El destino de respaldo '$BACKUP_DIR' no existe o no tiene permisos de escritura."
    echo "Verifica que el disco SMB del router esté montado en /etc/fstab."
    exit 1
fi

mkdir -p "$DEST"

echo "📂 Copiando configuraciones y datos persistentes..."
# 1. Configuración de Home Assistant (excluyendo bases de datos temporales pesadas si fuera necesario)
rsync -a --delete \
    --exclude="home-assistant_v2.db*" \
    --exclude="*.log" \
    /opt/servidor/homeassistant/config/ "$DEST/homeassistant_config/"

# 2. Datos persistentes de Cumulus MX
if [ -d "/opt/servidor/cumulusmx/data" ]; then
    rsync -a --delete /opt/servidor/cumulusmx/data/ "$DEST/cumulusmx_data/"
fi

# 3. Estado de Mosquitto MQTT
if [ -d "/opt/servidor/mosquitto/data" ]; then
    rsync -a --delete /opt/servidor/mosquitto/data/ "$DEST/mosquitto_data/"
fi

# 4. Archivo .env con permisos restringidos
if [ -f "/opt/servidor/.env" ]; then
    cp /opt/servidor/.env "$DEST/.env"
    chmod 600 "$DEST/.env"
fi

echo "✅ Snapshot completado en: $DEST"

# Rotación de copias: eliminar las más antiguas que el periodo de retención
echo "🧹 Limpiando snapshots con más de $RETENTION_DAYS días..."
find "$BACKUP_DIR" -maxdepth 1 -type d -name "snapshot_*" -mtime +"$RETENTION_DAYS" -exec rm -rf {} + 2>/dev/null || true

echo "🎉 Proceso de backup finalizado con éxito a las $(date)."
