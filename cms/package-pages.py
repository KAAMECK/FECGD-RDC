"""Prépare une archive Pages des seuls fichiers publics du site."""
from pathlib import Path
import json, zipfile, re, html
from urllib.parse import unquote

root = Path(__file__).resolve().parent.parent
public = root / 'site/dist'
destination = root / 'output/fecgd-rdc-pages.zip'
allowed = {'.css', '.js', '.png', '.jpg', '.jpeg', '.webp', '.gif', '.svg', '.woff', '.woff2'}
pages = json.loads((public / 'generated-pages.json').read_text(encoding='utf-8'))
files = []
for item in pages:
    if not re.fullmatch(r'(?:[a-z0-9-]+/)?index\.html', item):
        raise ValueError('Chemin de page inattendu')
    page = public / item
    text = page.read_text(encoding='utf-8')
    if 'http://localhost' in text or 'name="fecgd_nonce"' in text:
        raise ValueError('Une donnée locale est présente dans la page')
    files.append(page)
files.append(public / 'styles.css')
# Copie uniquement les médias et scripts réellement référencés par les pages/CSS.
queue = list(files)
seen = set()
while queue:
    source = queue.pop()
    if source in seen or source.suffix not in {'.html', '.css', '.js'}:
        continue
    seen.add(source)
    text = html.unescape(source.read_text(encoding='utf-8'))
    for match in re.findall(r'assets/[^\s\"\'<> ,)]+', text):
        relative = unquote(match.split('?')[0])
        candidate = (public / relative).resolve()
        if not candidate.is_relative_to(public.resolve()) or candidate.suffix.lower() not in allowed or not candidate.is_file():
            raise ValueError('Média public introuvable : ' + relative)
        if candidate not in files:
            files.append(candidate)
            queue.append(candidate)
files = sorted(set(files))
for file in files:
    if file.stat().st_size > 25 * 1024 * 1024:
        raise ValueError('Fichier supérieur à la limite Pages : ' + file.name)
if len(files) + 1 > 1000:
    raise ValueError('Trop de fichiers pour le téléversement direct')
destination.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(destination, 'w', zipfile.ZIP_DEFLATED) as archive:
    for file in files:
        archive.write(file, file.relative_to(public).as_posix())
    archive.writestr('_headers', '/*\n  X-Robots-Tag: noindex, nofollow\n  X-Content-Type-Options: nosniff\n  Referrer-Policy: strict-origin-when-cross-origin\n')
print(f'{len(pages)} pages ; {len(files)+1} fichiers publics ; archive {destination.stat().st_size:,} octets.')
