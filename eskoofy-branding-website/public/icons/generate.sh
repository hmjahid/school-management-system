#!/usr/bin/env bash
# Regenerate the PWA/app icons with ImageMagick.
# Requires: ImageMagick `magick`/`convert` and a bold sans-serif TTF font.
#
#   ./generate.sh  [font.ttf]
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

MAGICK="$(command -v magick || command -v convert)"
FONT="${1:-/usr/share/fonts/dejavu-sans-fonts/DejaVuSansCondensed-Bold.ttf}"

# "any" icons: slate-900 rounded square, blue-600 circle, white bold "E".
for S in 512 192 180; do
  "$MAGICK" -size "${S}x${S}" xc:none \
    -fill '#0f172a' -draw "roundrectangle 0,0,$((S-1)),$((S-1)),$((S*18/100)),$((S*18/100))" \
    -fill '#2563eb' -draw "circle $((S/2)),$((S/2)) $((S/2)),$((S*30/100))" \
    -font "$FONT" -pointsize "$((S*55/100))" -fill white -gravity center \
    -annotate "+0+$((S*2/100))" 'E' "icon-${S}.png"
done
mv icon-180.png apple-touch-icon.png

# Maskable icon: full-bleed background, content kept inside the 80% safe zone.
"$MAGICK" -size 512x512 xc:'#0f172a' \
  -fill '#2563eb' -draw 'circle 256,256 256,205' \
  -font "$FONT" -pointsize 250 -fill white -gravity center \
  -annotate '+0+18' 'E' maskable-512.png

echo "icons regenerated:"
ls -1 ./*.png apple-touch-icon.png