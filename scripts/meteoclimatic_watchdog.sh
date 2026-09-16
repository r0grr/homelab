#!/usr/bin/env bash
# Watchdog for Meteoclimatic DATA2 Feed Generator
set -euo pipefail

TARGET_FILE="/opt/servidor/cuhws/meteoclimatic.htm"
SERVICE_NAME="meteoclimatic-updater.service"
MAX_AGE_SEC=600

# 1. Check systemd service status
if ! systemctl is-active --quiet "$SERVICE_NAME"; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WATCHDOG] $SERVICE_NAME is not active! Restarting..."
    systemctl restart "$SERVICE_NAME"
    exit 0
fi

# 2. Check file existence & freshness
NOW=$(date +%s)
if [ -f "$TARGET_FILE" ]; then
    FILE_MTIME=$(stat -c %Y "$TARGET_FILE" 2>/dev/null || echo 0)
    AGE=$(( NOW - FILE_MTIME ))
    
    if [ "$AGE" -gt "$MAX_AGE_SEC" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WATCHDOG] $TARGET_FILE is stale ($AGE seconds old > $MAX_AGE_SEC s)! Restarting service..."
        systemctl restart "$SERVICE_NAME"
        exit 0
    fi

    # 3. Check payload integrity
    if ! grep -q "^\*VER=DATA2" "$TARGET_FILE" || ! grep -q "^\*EOT\*" "$TARGET_FILE"; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WATCHDOG] $TARGET_FILE payload corrupted! Regenerating..."
        /usr/bin/python3 -c "import sys; sys.path.insert(0, '/opt/servidor/scripts'); import meteoclimatic_updater; meteoclimatic_updater.generate_meteoclimatic()"
        exit 0
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WATCHDOG] $TARGET_FILE missing! Triggering immediate generation..."
    systemctl restart "$SERVICE_NAME"
    exit 0
fi
