#!/usr/bin/env python3
"""
Membuat PROJECT_LATEST_BACKUP.zip dari folder proyek + memverifikasinya.
Pemakaian:  python3 tools/verifikasi/make_backup.py [folder_proyek] [berkas_zip_keluaran]
Dikecualikan: vendor/, node_modules/, .env, log, sesi, cache terkompilasi, __pycache__.
Diverifikasi: integritas ZIP, checksum semua berkas, tidak ada .env/vendor/log, (opsional) tidak ada nilai rahasia.
Opsional: set env RAHASIA_FILE=/jalur/ke/.env.lama agar nilai sensitifnya dipindai di dalam ZIP (nilai tidak ditampilkan).
"""
import os, sys, re, zipfile, hashlib
R = os.path.abspath(sys.argv[1] if len(sys.argv) > 1 else os.getcwd())
OUT = os.path.abspath(sys.argv[2] if len(sys.argv) > 2 else os.path.join(os.path.dirname(R), 'PROJECT_LATEST_BACKUP.zip'))
ROOT = os.path.basename(R)
SKIP = {'node_modules', 'vendor', '__pycache__', '.git', '.idea', '.vscode'}
EMPTY = ['storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/framework/testing',
         'storage/logs', 'bootstrap/cache', 'storage/app/public', 'storage/app/berkas']

def excluded(rel):
    p = rel.split('/')
    if p[0] in SKIP or '__pycache__' in p: return True
    if rel == '.env' or rel.endswith(('.log', '.pyc', '.DS_Store')): return True
    if rel.startswith('storage/logs/') and not rel.endswith('.gitignore'): return True
    if rel.startswith('storage/framework/') and not rel.endswith('.gitignore'): return True
    if rel.startswith('bootstrap/cache/') and not rel.endswith('.gitignore'): return True
    return False

files = []
for d, dirs, fs in os.walk(R):
    dirs[:] = [x for x in dirs if x not in SKIP and x != '__pycache__']
    for f in fs:
        rel = os.path.relpath(os.path.join(d, f), R).replace('\\', '/')
        if not excluded(rel): files.append(rel)
files.sort()
if os.path.exists(OUT): os.remove(OUT)
with zipfile.ZipFile(OUT, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
    for d in EMPTY: z.writestr(f'{ROOT}/{d}/', '')
    for rel in files: z.write(os.path.join(R, rel), f'{ROOT}/{rel}')

z = zipfile.ZipFile(OUT)
names = [n for n in z.namelist() if not n.endswith('/')]
sha = lambda b: hashlib.sha256(b).hexdigest()
ok = True
def lapor(label, cond, info=''):
    global ok; ok &= bool(cond); print(('OK    ' if cond else 'GAGAL ') + label + (f'  {info}' if info and not cond else ''))
lapor('integritas ZIP', z.testzip() is None)
lapor(f'jumlah berkas = {len(files)}', len(names) == len(files))
beda = [n for n in names if sha(z.read(n)) != sha(open(os.path.join(R, n.split('/', 1)[1]), 'rb').read())]
lapor('checksum semua berkas cocok', not beda, str(beda[:3]))
terlarang = [n for n in names if re.search(r'(^|/)(\.env|node_modules|vendor)(/|$)', n) or n.endswith('.log')]
lapor('tanpa .env / vendor / node_modules / log', not terlarang, str(terlarang[:3]))
rahasia_file = os.environ.get('RAHASIA_FILE')
if rahasia_file and os.path.exists(rahasia_file):
    nilai = []
    for line in open(rahasia_file, encoding='utf-8', errors='ignore'):
        line = line.strip()
        if '=' in line and not line.startswith('#'):
            k, v = line.split('=', 1); v = v.strip().strip('"\'')
            if re.search(r'(PASSWORD|KEY|SECRET|TOKEN)', k) and len(v) >= 6: nilai.append(v)
    bocor = [n for v in nilai for n in names if v.encode() in z.read(n)]
    lapor(f'tanpa kebocoran rahasia ({len(nilai)} nilai dipindai)', not bocor)
print(f'\n{OUT}  ({round(os.path.getsize(OUT)/1024)} KB, {len(files)} berkas)')
sys.exit(0 if ok else 1)
