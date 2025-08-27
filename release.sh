#!/bin/bash

# ============================================
# SCRIPT PARA CREAR RELEASES AUTOMÁTICAMENTE
# Usage: ./release.sh 1.0.1 "Fixed bug with special characters"
# ============================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if version and message are provided
if [ $# -lt 2 ]; then
    echo -e "${RED}Error: Se requieren 2 argumentos${NC}"
    echo "Usage: $0 <version> <commit_message>"
    echo "Ejemplo: $0 1.0.1 'Fixed bug with special characters'"
    exit 1
fi

VERSION=$1
COMMIT_MSG=$2

echo -e "${YELLOW}=== CREANDO RELEASE v${VERSION} ===${NC}"

# Validate version format
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${RED}Error: Formato de versión inválido. Use X.Y.Z (ej: 1.0.1)${NC}"
    exit 1
fi

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo -e "${RED}Error: No estás en un repositorio Git${NC}"
    exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}Error: Tienes cambios sin commitear${NC}"
    echo "Ejecuta: git add . && git commit -m 'Prepare release'"
    exit 1
fi

# Update version in plugin file
echo -e "${YELLOW}Actualizando versión en el plugin...${NC}"
sed -i.bak "s/Version: .*/Version: ${VERSION}/" spam-comment-cleaner.php
sed -i.bak "s/define('SCC_PLUGIN_VERSION', '.*');/define('SCC_PLUGIN_VERSION', '${VERSION}');/" spam-comment-cleaner.php

# Remove backup file
rm spam-comment-cleaner.php.bak

# Check if version was updated
if ! grep -q "Version: ${VERSION}" spam-comment-cleaner.php; then
    echo -e "${RED}Error: No se pudo actualizar la versión en el plugin${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Versión actualizada en spam-comment-cleaner.php${NC}"

# Commit version update
git add spam-comment-cleaner.php
git commit -m "Bump version to ${VERSION}"

echo -e "${GREEN}✓ Commit de versión creado${NC}"

# Create and push tag
git tag -a "v${VERSION}" -m "${COMMIT_MSG}"
git push origin main
git push origin "v${VERSION}"

echo -e "${GREEN}✓ Tag v${VERSION} creado y pusheado${NC}"

echo -e "${YELLOW}=== RELEASE COMPLETADO ===${NC}"
echo -e "GitHub Actions creará automáticamente el release en:"
echo -e "https://github.com/donosor00/spam-comment-cleaner/releases"
echo -e ""
echo -e "${GREEN}¡Listo! En unos minutos estará disponible la actualización automática.${NC}"

# Wait a bit and check if release was created
echo -e "${YELLOW}Esperando a que GitHub Actions complete...${NC}"
sleep 30

echo -e "${YELLOW}Verificando release...${NC}"
RELEASE_URL="https://api.github.com/repos/donosor00/spam-comment-cleaner/releases/tags/v${VERSION}"
if curl -s "$RELEASE_URL" | grep -q "\"tag_name\": \"v${VERSION}\""; then
    echo -e "${GREEN}✓ Release creado exitosamente${NC}"
    echo -e "URL: https://github.com/donosor00/spam-comment-cleaner/releases/tag/v${VERSION}"
else
    echo -e "${YELLOW}⚠ Release aún no está listo. Revisa GitHub Actions.${NC}"
fi
