#!/usr/bin/env bash
# ==============================================================================
# Script de despliegue GitOps / Docker Compose
# ==============================================================================
set -e

cd /opt/servidor

echo "🚀 Iniciando despliegue de Homelab..."

# Si existe repositorio Git y remote 'origin' configurado, sincronizar cambios
if [ -d ".git" ] && git remote get-url origin >/dev/null 2>&1; then
    echo "📦 Obteniendo últimas modificaciones de Git..."
    git fetch origin
    git pull --ff-only
fi

# Validar sintaxis de Compose
echo "🔍 Validando archivo docker-compose.yml..."
docker compose config > /dev/null

# Construir y levantar contenedores
echo "🔨 Construyendo imágenes y recreando contenedores..."
docker compose up -d --build --remove-orphans

# Limpiar imágenes obsoletas o huérfanas
echo "🧹 Limpiando imágenes antiguas sin usar..."
docker image prune -f

echo "✅ Despliegue completado con éxito."
docker compose ps
