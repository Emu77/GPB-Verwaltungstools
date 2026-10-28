#!/bin/bash
# Lädt die in Git geänderten Dateien per FTPS auf den Server (wie FileZilla).
# Aufruf:  ./deploy.sh            -> Dateien des letzten Commits
#          ./deploy.sh a31b633    -> alle Dateien seit diesem Commit
# Passwort wird von lftp abgefragt (nicht im Skript speichern!).

HOST="server2.sslsecurity.net"
USER="web32"
REMOTE="/html/gpb"

cd "$(dirname "$0")" || exit 1

VON="${1:-HEAD~1}"

# Nur hinzugefügte/geänderte Dateien; docs/ und Markdown brauchen nicht auf den Server
DATEIEN=$(git diff --name-only --diff-filter=ACMR "$VON" HEAD | grep -v -E '^docs/|\.md$|^deploy\.sh$')

if [ -z "$DATEIEN" ]; then
  echo "Keine Dateien zum Hochladen."
  exit 0
fi

echo "Hochladen nach $HOST:$REMOTE:"
echo "$DATEIEN" | sed 's/^/  /'
read -p "Weiter? [j/N] " antwort
[ "$antwort" = "j" ] || exit 0

# Gelöschte Dateien nur anzeigen, nicht automatisch auf dem Server löschen
GELOESCHT=$(git diff --name-only --diff-filter=D "$VON" HEAD)
[ -n "$GELOESCHT" ] && echo "Achtung, in Git gelöscht (auf dem Server ggf. von Hand löschen):" && echo "$GELOESCHT" | sed 's/^/  /'

BEFEHLE="set ssl:verify-certificate/C1:2F:10:CF:8F:97:90:01:30:89:82:FA:A0:97:94:A3:68:6E:D0:DA no; set ftp:ssl-force true; set ftp:ssl-protect-data true;"
for f in $DATEIEN; do
  ordner=$(dirname "$f")
  [ "$ordner" = "." ] && ziel="$REMOTE" || ziel="$REMOTE/$ordner"
  BEFEHLE="$BEFEHLE mkdir -p -f $ziel; put -O $ziel $f;"
done
BEFEHLE="$BEFEHLE bye"

lftp -u "$USER" -e "$BEFEHLE" "$HOST"
