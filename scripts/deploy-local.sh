#!/usr/bin/env bash
# Déploie Navi FAQ vers une instance WordPress/WooCommerce de dev locale
# existante — ce dépôt n'a pas son propre docker-compose.yml : il partage
# la stack du plugin compagnon Saito Navi (navi-wordpress/docker-compose.yml,
# conteneurs navi_wp_web/navi_wp_cli), pour tester les deux plugins sur le
# même site sans faire tourner deux instances WordPress en parallèle.
#
# Assemble d'abord un dossier propre via rsync + .distignore (même contenu
# que ce qui serait réellement distribué), puis le copie dans le conteneur.
#
# Usage : ./scripts/deploy-local.sh [--no-verify]
#   NAVI_FAQ_DEPLOY_CONTAINER : nom du conteneur web (défaut navi_wp_web)
#   NAVI_FAQ_DEPLOY_BASE_URL  : URL utilisée pour la vérification finale
#                                (défaut http://localhost:8082)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"

CONTAINER="${NAVI_FAQ_DEPLOY_CONTAINER:-navi_wp_web}"
BASE_URL="${NAVI_FAQ_DEPLOY_BASE_URL:-http://localhost:8082}"
PLUGIN_DEST="/var/www/html/wp-content/plugins/navi-faq"

VERIFY=1
for arg in "$@"; do
    case "$arg" in
        --no-verify) VERIFY=0 ;;
        *)
            echo "Argument inconnu : $arg" >&2
            exit 1
            ;;
    esac
done

if ! docker exec "$CONTAINER" true 2>/dev/null; then
    echo "Erreur : conteneur '$CONTAINER' inaccessible (docker exec a échoué) — lancer la stack docker-compose de navi-wordpress." >&2
    exit 1
fi

BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

echo "==> Assemblage du plugin (rsync + .distignore) dans $BUILD_DIR"
rsync -a --exclude-from="$REPO_ROOT/.distignore" "$REPO_ROOT/" "$BUILD_DIR/navi-faq/"

echo "==> Copie vers $CONTAINER:$PLUGIN_DEST"
docker exec "$CONTAINER" mkdir -p "$PLUGIN_DEST"
docker exec "$CONTAINER" sh -c "rm -rf $PLUGIN_DEST/*"
docker cp "$BUILD_DIR/navi-faq/." "$CONTAINER:$PLUGIN_DEST"

echo "==> chown www-data:www-data sur $PLUGIN_DEST"
docker exec "$CONTAINER" chown -R www-data:www-data "$PLUGIN_DEST"

if [ "$VERIFY" -eq 1 ]; then
    echo "==> Vérification HTTP ($BASE_URL)"
    status=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL" || echo "000")
    if [ "$status" != "200" ] && [ "$status" != "302" ]; then
        echo "Attention : $BASE_URL a répondu $status — vérifier manuellement avant de continuer" >&2
        exit 1
    fi
    echo "OK : $BASE_URL répond $status"
fi

echo "==> Déploiement terminé"
