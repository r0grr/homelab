#!/usr/bin/env bash
# ==============================================================================
# INFORME SETMANAL DE MANTENIMENT I SEGURETAT
# Revisa actualitzacions de Debian (APT), alertes de seguretat (CVEs)
# i noves versions d'imatges Docker (sense descarregar-les via buildx/registry).
# Envia l'informe al topic d'Actualitzacions de Telegram.
# ==============================================================================

set -uo pipefail

# Protecció de concurrència (evitar execucions solapades)
LOCK_FILE="/tmp/informe_manteniment.lock"
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
  echo "L'informe de manteniment ja s'està executant en un altre procés. Sortint."
  exit 0
fi

BASE_DIR="/opt/servidor"
ENV_FILE="${BASE_DIR}/.env"

if [ -f "$ENV_FILE" ]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
fi

BOT_TOKEN="${TELEGRAM_BOT_TOKEN:-}"
CHAT_ID="${TELEGRAM_UPDATES_CHAT_ID:-${TELEGRAM_CHAT_ID:-}}"
TOPIC_ID="${TELEGRAM_UPDATES_TOPIC_ID:-97}"

if [ -z "$BOT_TOKEN" ] || [ -z "$CHAT_ID" ]; then
  echo "Error: TELEGRAM_BOT_TOKEN o CHAT_ID no configurats." >&2
  exit 1
fi

# 1. Format de la data en català
dies=("Diumenge" "Dilluns" "Dimarts" "Dimecres" "Dijous" "Divendres" "Dissabte")
mesos=("" "gener" "febrer" "març" "abril" "maig" "juny" "juliol" "agost" "setembre" "octubre" "novembre" "desembre")
dia_setmana=${dies[$(date +%w)]}
dia_mes=$(date +%-d)
mes_num=$(date +%-m)
mes_nom=${mesos[$mes_num]}
any=$(date +%Y)
hora=$(date +%H:%M)
DATA_STR="${dia_setmana}, ${dia_mes} de ${mes_nom} de ${any} - ${hora}"

# 2. Comprovació d'APT i Seguretat (Debian)
echo "Actualitzant llistes de paquets APT..."
if command -v sudo >/dev/null 2>&1 && [ "$(id -u)" -ne 0 ]; then
  sudo apt-get update -qq >/dev/null 2>&1 || true
else
  apt-get update -qq >/dev/null 2>&1 || true
fi

UPGRADE_SIM=$(apt-get -s dist-upgrade 2>/dev/null || true)
NUM_TOTAL=$(grep -c "^Inst " <<< "$UPGRADE_SIM" || true)

# Filtrar actualitzacions procedents de repositoris de seguretat (CVEs)
SEC_LINES=$(grep "^Inst " <<< "$UPGRADE_SIM" | grep -iE "security|deb[0-9]+u" || true)
SEC_PKGS=()
if [ -n "$SEC_LINES" ]; then
  while read -r line; do
    [ -z "$line" ] && continue
    pkg=$(awk '{print $2}' <<< "$line")
    SEC_PKGS+=("$pkg")
  done < <(echo "$SEC_LINES" | sort -u)
fi
NUM_SEC=${#SEC_PKGS[@]}

# Reinici requerit?
if [ -f /var/run/reboot-required ]; then
  REBOOT_STR="⚠️ Sí"
else
  REBOOT_STR="No"
fi

# Llista formatada de paquets de seguretat
SEC_PKGS_STR=""
if [ "$NUM_SEC" -gt 0 ]; then
  for pkg in "${SEC_PKGS[@]}"; do
    SEC_PKGS_STR="${SEC_PKGS_STR}  • <code>${pkg}</code>\n"
  done
fi

# 3. Comprovació de Contenidors Docker
echo "Comprovant estat i noves versions d'imatges Docker..."
TOTAL_RUNNING=$(docker ps -q 2>/dev/null | wc -l)
UNHEALTHY=$(docker ps --filter "health=unhealthy" -q 2>/dev/null | wc -l)

if [ "$UNHEALTHY" -eq 0 ]; then
  HEALTH_STR="Tots sans"
else
  HEALTH_STR="⚠️ ${UNHEALTHY} amb problemes"
fi

DOCKER_UPDATES=()
while IFS=$'\t' read -r name image; do
  [ -z "$name" ] && continue
  # Ignorar imatges compilades localment
  if [[ "$image" =~ ^servidor- ]]; then
    continue
  fi

  local_digests=$(docker inspect "$image" --format '{{json .RepoDigests}}' 2>/dev/null || echo "")
  remote_digest=$(timeout 15 docker buildx imagetools inspect "$image" 2>/dev/null | grep "^Digest:" | awk '{print $2}' || echo "")

  if [ -n "$remote_digest" ] && [[ "$local_digests" != *"$remote_digest"* ]]; then
    DOCKER_UPDATES+=("${name} (${image})")
  fi
done < <(docker ps --format '{{.Names}}\t{{.Image}}' 2>/dev/null)

DOCKER_UPDATES_STR=""
if [ ${#DOCKER_UPDATES[@]} -gt 0 ]; then
  for item in "${DOCKER_UPDATES[@]}"; do
    DOCKER_UPDATES_STR="${DOCKER_UPDATES_STR}  • <code>${item}</code>\n"
  done
else
  DOCKER_UPDATES_STR="  • <i>Totes les imatges estan al dia</i>\n"
fi

# 4. Construcció del missatge HTML per a Telegram
MESSAGE="🛡️ <b>INFORME SETMANAL DE MANTENIMENT - SERVIDOR</b>
📅 <i>${DATA_STR}</i>

📦 <b>Paquets Debian (APT):</b>
• Actualitzacions pendents: <b>${NUM_TOTAL}</b>
• 🚨 Actualitzacions de Seguretat (CVE): <b>${NUM_SEC}</b>
${SEC_PKGS_STR}• ⚠️ Cal reiniciar el sistema: <b>${REBOOT_STR}</b>

🐳 <b>Estat de Docker:</b>
• Contenidors actius: <b>${TOTAL_RUNNING} / ${TOTAL_RUNNING}</b> (${HEALTH_STR})
• Imatges amb nova versió disponible:
${DOCKER_UPDATES_STR}"

# 5. Enviament a Telegram
echo "Enviant informe a Telegram (Topic: ${TOPIC_ID})..."
PAYLOAD=$(jq -n \
  --arg chat_id "$CHAT_ID" \
  --arg message_thread_id "$TOPIC_ID" \
  --arg text "$(echo -e "$MESSAGE")" \
  '{
    chat_id: $chat_id,
    message_thread_id: ($message_thread_id | tonumber),
    text: $text,
    parse_mode: "HTML",
    disable_web_page_preview: true
  }')

curl -s --max-time 20 -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD" >/dev/null

echo "Informe enviat correctament!"
