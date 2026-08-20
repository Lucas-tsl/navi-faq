#!/usr/bin/env bash
# Assemble et vérifie le zip de soumission WordPress.org pour Navi FAQ —
# même logique que deploy-local.sh (rsync + .distignore), avec en plus une
# passe de vérifications propres à une soumission publique (Text Domain,
# absence de fichiers de dev, structure du zip).
#
# Usage : ./scripts/build-wporg-release.sh [--copy-to DOSSIER] [--skip-plugin-check]
#   --copy-to DOSSIER     Copie le zip fini dans ce dossier une fois vérifié
#                         (supprime les anciens navi-faq-*.zip qui s'y trouvent).
#   --skip-plugin-check   Ignore l'étape wp plugin check (nécessite le
#                         conteneur navi_wp_cli de la stack docker-compose
#                         du plugin compagnon navi-wordpress, voir
#                         deploy-local.sh) — utile si cette stack n'est pas
#                         lancée.
#
# Produit : build/navi-faq-<version>.zip (dossier build/ nettoyé avant
# chaque exécution, jamais versionné — voir .gitignore).

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$REPO_ROOT"

COPY_TO=""
RUN_PLUGIN_CHECK=1
while [ $# -gt 0 ]; do
    case "$1" in
        --copy-to)
            COPY_TO="${2:-}"
            shift 2
            ;;
        --skip-plugin-check)
            RUN_PLUGIN_CHECK=0
            shift
            ;;
        *)
            echo "Argument inconnu : $1" >&2
            exit 1
            ;;
    esac
done

VERSION=$(grep -m1 "^ \* Version:" navi-faq.php | sed 's/.*Version: *//' | tr -d '[:space:]')
if [ -z "$VERSION" ]; then
    echo "Erreur : impossible de lire la version depuis navi-faq.php" >&2
    exit 1
fi

TEXT_DOMAIN=$(grep -m1 "^ \* Text Domain:" navi-faq.php | sed 's/.*Text Domain: *//' | tr -d '[:space:]')
if [ "$TEXT_DOMAIN" != "navi-faq" ]; then
    echo "Erreur : Text Domain (navi-faq.php) vaut '$TEXT_DOMAIN', attendu 'navi-faq'." >&2
    exit 1
fi

echo "==> Version détectée : $VERSION"

rm -rf build
mkdir -p "build/navi-faq"
rsync -a --exclude-from=".distignore" ./ "build/navi-faq/"

echo "==> Vérification : aucun fichier de dev dans le paquet assemblé"
LEAKED=$(find build/navi-faq -maxdepth 1 \( -name "node_modules" -o -name ".git" -o -name ".claude" -o -name "composer.json" -o -name "package.json" -o -name "phpcs.xml.dist" -o -name "scripts" -o -name "README.md" -o -name "tests" \))
if [ -n "$LEAKED" ]; then
    echo "Erreur : fichiers de dev présents dans le paquet, .distignore incomplet ou obsolète :" >&2
    echo "$LEAKED" >&2
    exit 1
fi

ZIP_PATH="build/navi-faq-${VERSION}.zip"
echo "==> Construction de $ZIP_PATH"
(
    cd build
    if command -v zip >/dev/null 2>&1; then
        zip -rq "navi-faq-${VERSION}.zip" navi-faq
    else
        # zip absent (courant sur certaines images minimales) : repli sur
        # le module zipfile de Python, présent partout où PHP/WP tournent déjà.
        python3 - "$VERSION" <<'PYEOF'
import os
import sys
import zipfile

version = sys.argv[1]
zip_path = f"navi-faq-{version}.zip"
src_dir = "navi-faq"

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(src_dir):
        for f in files:
            full = os.path.join(root, f)
            zf.write(full, os.path.relpath(full, "."))
PYEOF
    fi
)

echo "==> Vérification de la structure du zip"
python3 - "$ZIP_PATH" "$VERSION" <<'PYEOF'
import sys
import zipfile

zip_path, version = sys.argv[1], sys.argv[2]
zf = zipfile.ZipFile(zip_path)
names = zf.namelist()

top = sorted(set(n.split('/')[0] for n in names if n.strip()))
assert top == ['navi-faq'], f"Dossier racine du zip incorrect : {top} (attendu ['navi-faq'])"

data = zf.read('navi-faq/navi-faq.php').decode('utf-8')
assert f"Version: {version}" in data, "Version incohérente entre navi-faq.php et le nom du zip"
assert "Text Domain: navi-faq" in data, "Text Domain incorrect dans le zip"

print(f"OK : {len(names)} fichiers, dossier racine 'navi-faq/', Text Domain aligné, version {version}.")
PYEOF

if [ "$RUN_PLUGIN_CHECK" -eq 1 ]; then
    if docker exec navi_wp_cli true 2>/dev/null; then
        echo "==> Déploiement dans l'instance de dev locale pour wp plugin check"
        docker exec navi_wp_web rm -rf /var/www/html/wp-content/plugins/navi-faq
        docker cp "build/navi-faq/." navi_wp_web:/var/www/html/wp-content/plugins/navi-faq
        docker exec navi_wp_web chown -R www-data:www-data /var/www/html/wp-content/plugins/navi-faq
        docker exec navi_wp_cli wp plugin activate navi-faq --path=/var/www/html >/dev/null 2>&1 || true
        echo "==> wp plugin check navi-faq"
        docker exec navi_wp_cli wp plugin check navi-faq --path=/var/www/html
    else
        echo "==> Conteneur navi_wp_cli inaccessible, wp plugin check ignoré (lancer la stack docker-compose de navi-wordpress pour l'exécuter)."
    fi
fi

if [ -n "$COPY_TO" ]; then
    echo "==> Copie vers $COPY_TO"
    mkdir -p "$COPY_TO"
    rm -f "$COPY_TO"/navi-faq-*.zip
    cp "$ZIP_PATH" "$COPY_TO/"
    echo "OK : $COPY_TO/navi-faq-${VERSION}.zip"
fi

echo "==> Terminé : $ZIP_PATH"
