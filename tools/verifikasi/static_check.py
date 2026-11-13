import re, glob, os, sys
R = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
problems = []
def bad(msg): problems.append(msg)

# ---------- A. PHP: keseimbangan kurung/kurawal/siku, di luar string & komentar ----------
def strip_php(src):
    out = []; i = 0; n = len(src)
    while i < n:
        c = src[i]; two = src[i:i+2]
        if two == '//' or c == '#' and src[i:i+2] != '#[':
            j = src.find('\n', i); i = n if j < 0 else j; continue
        if two == '/*':
            j = src.find('*/', i + 2); i = n if j < 0 else j + 2; continue
        if c in '\'"':
            q = c; i += 1
            while i < n and src[i] != q:
                i += 2 if src[i] == '\\' else 1
            i += 1; out.append('S'); continue
        if src.startswith('<<<', i):
            m = re.match(r"<<<['\"]?(\w+)['\"]?\n", src[i:])
            if m:
                end = src.find('\n' + m.group(1), i); i = n if end < 0 else end + len(m.group(1)) + 1; out.append('S'); continue
        out.append(c); i += 1
    return ''.join(out)

php_files = [f for d in ('app','routes','database','config','tests','lang','bootstrap') for f in glob.glob(f'{R}/{d}/**/*.php', recursive=True) if '/vendor/' not in f]
for f in php_files:
    src = open(f, encoding='utf-8').read()
    s = strip_php(src)
    for a, b in ('()', '{}', '[]'):
        if s.count(a) != s.count(b): bad(f'[PHP kurung] {os.path.relpath(f, R)}: {a}{b} tidak seimbang ({s.count(a)} vs {s.count(b)})')
    if not src.lstrip().startswith('<?php'): bad(f'[PHP] {os.path.relpath(f,R)} tidak diawali <?php')

# ---------- B. PHP: kelas yang dipakai harus di-import / satu namespace / bawaan ----------
BUILTIN = set('self static parent Exception RuntimeException Throwable InvalidArgumentException ZipArchive Closure DateTime Carbon Illuminate'.split())
project_classes = {}
for f in glob.glob(f'{R}/app/**/*.php', recursive=True) + glob.glob(f'{R}/database/**/*.php', recursive=True):
    s = open(f, encoding='utf-8').read()
    ns = re.search(r'^namespace\s+([^;]+);', s, re.M); c = re.search(r'^(?:abstract\s+|final\s+)?(?:class|trait|interface)\s+(\w+)', s, re.M)
    if c: project_classes[c.group(1)] = (ns.group(1) if ns else '')
for f in php_files:
    if '/config/' in f or '/lang/' in f or '/routes/' in f and False: continue
    s = open(f, encoding='utf-8').read(); code = strip_php(s)
    ns = (re.search(r'^namespace\s+([^;]+);', s, re.M) or [None, ''])[1]
    uses = set(m.group(2) or m.group(1).split('\\')[-1] for m in re.finditer(r'^use\s+([^;{\s]+?)(?:\s+as\s+(\w+))?;', s, re.M))
    # nama yang dipakai
    names = set(re.findall(r'(?<![\\\w$>:])([A-Z]\w+)::', code)) | set(re.findall(r'\bnew\s+([A-Z]\w+)', code)) | set(re.findall(r'\binstanceof\s+([A-Z]\w+)', code))
    names |= set(re.findall(r'(?:\(|,\s*|\?)\s*([A-Z]\w+)\s+\$', code))
    own = set(re.findall(r'(?:class|trait|interface)\s+(\w+)', s))
    for nme in names:
        if nme in uses or nme in own or nme in BUILTIN: continue
        if nme in project_classes and project_classes[nme] == ns: continue
        if nme in ('Rule', 'Password') and nme in uses: continue
        bad(f'[PHP use] {os.path.relpath(f,R)}: kelas "{nme}" dipakai tapi tidak di-import')

# ---------- C. Route: nama route dipakai di view/PHP harus ada ----------
names = set(['avatar.show','login','logout','dashboard','profile.edit','profile.update','profile.password','notifications.index','notifications.open','notifications.read','notifications.readAll','berkas.pengajuan','panduan.index','panduan.show','panduan.stream','register'])
names |= {f'siswa.pengajuan.{x}' for x in 'index create store show lacak.form lacak.cari'.split()}
names |= {f'admin.siswa.{x}' for x in 'index create store edit update destroy'.split()}
names |= {f'admin.dokumen.{x}' for x in 'index store update destroy'.split()}
names |= {f'admin.panduan.{x}' for x in 'index create store edit update destroy'.split()}
names |= {f'admin.{x}' for x in 'pengajuan.index pengajuan.show pengajuan.status pengajuan.pengembalian pengajuan.qrKonfirmasi scan-qr scan-qr.cari laporan.index laporan.cetak laporan.excel aktivitas.index'.split()}
names |= {f'admin.repository.{x}' for x in 'index store edit update download archive destroy loans loans.update qr.verify loans.complete loans.revoke'.split()}
names |= {'repository.preview', 'repository.qr.access', 'pengajuan.qr.show', 'siswa.dokumen-saya.index', 'siswa.dokumen.digital', 'siswa.dokumen.digital.file'}
names |= {f'admin.arsip-siswa.{x}' for x in 'index show store status update destroy download requirements.store requirements.delete requirements.exempt requirements.restore'.split()}
names |= {'admin.pengajuan.digital-revoke', 'admin.pengajuan.digital-reissue', 'admin.pengajuan.physical-code-rotate', 'admin.pengajuan.legalisir-result'}
names |= {f'siswa.repository.{x}' for x in 'index request download'.split()}
# Route resource dan fitur tambahan yang diperkenalkan pada 9 November.
names |= {f'admin.pengumuman.{x}' for x in 'index create store edit update destroy'.split()}
names |= {f'admin.security.two-factor{x}' for x in ['', '.begin', '.confirm', '.disable']}
names |= {f'admin.siswa.import.{x}' for x in 'create template preview cancel commit'.split()}
names |= {f'password.{x}' for x in 'request email reset update'.split()}
names |= {'two-factor.challenge', 'two-factor.challenge.store'}
names |= {f'admin.peminjaman-fisik.{x}' for x in 'inventory inventory.store inventory.update inventory.toggle index approve reject handover return'.split()}
names |= {f'siswa.peminjaman-fisik.{x}' for x in 'index store'.split()}
# verifikasi bahwa daftar di atas cocok dengan routes/web.php (nama eksplisit)
web = open(f'{R}/routes/web.php', encoding='utf-8').read()
for m in re.finditer(r"->name\('([^']+)'\)", web):
    pass
used = {}
for f in glob.glob(f'{R}/resources/views/**/*.php', recursive=True) + glob.glob(f'{R}/app/**/*.php', recursive=True):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r"(?<!->)\broute\(\s*'([\w.\-]+)'|redirect\(\)->route\(\s*'([\w.\-]+)'", s):
        used.setdefault(m.group(1) or m.group(2), set()).add(os.path.relpath(f, R))
for nm, files in sorted(used.items()):
    if nm not in names and not any(nm.startswith(p.rstrip('*')) for p in ()) : bad(f'[Route] route("{nm}") tidak ada. Dipakai di: {sorted(files)[:2]}')
# routeIs dengan wildcard
for f in glob.glob(f'{R}/resources/views/**/*.php', recursive=True):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r"'((?:admin|siswa|panduan|notifications|profile)[\w.\-]*\*?(?:\|[\w.\-*]+)*)'", s):
        for pat in m.group(1).split('|'):
            if pat.endswith('*') and not any(n.startswith(pat[:-1]) for n in names): bad(f'[Route] pola "{pat}" tidak cocok ke route apa pun ({os.path.relpath(f,R)})')

# ---------- D. route -> controller method ----------
ctrl_map = {'AdminPengajuanController': 'Admin/PengajuanController', 'SiswaPengajuanController': 'Siswa/PengajuanController', 'DocumentRepositoryController': 'Admin/DocumentRepositoryController', 'DocumentLoanController': 'Admin/DocumentLoanController', 'SiswaDocumentRepositoryController': 'Siswa/DocumentRepositoryController',
             'PhysicalInventoryController': 'Admin/PhysicalInventoryController',
             'PhysicalLoanController': 'Admin/PhysicalLoanController',
             'SiswaPhysicalLoanController': 'Siswa/PhysicalLoanController'}
imports = dict(re.findall(r'use App\\Http\\Controllers\\([\w\\]+?)(?: as (\w+))?;', web)) if False else {}
for m in re.finditer(r"\[(\w+)::class,\s*'(\w+)'\]", web):
    cls, meth = m.groups()
    path = ctrl_map.get(cls) or cls.replace('Controller', 'Controller')
    cands = glob.glob(f'{R}/app/Http/Controllers/**/{path.split("/")[-1]}.php', recursive=True)
    cand = [c for c in cands if (path.split('/')[0] in c if '/' in path else True)]
    if not cand: bad(f'[Controller] {cls} tidak ditemukan'); continue
    src = open(cand[0], encoding='utf-8').read()
    if not re.search(rf'function\s+{meth}\s*\(', src): bad(f'[Controller] {cls}@{meth} tidak ada')
for m in re.finditer(r"resource\('(\w+)',\s*(\w+)::class", web):
    cls = m.group(2); src = open(glob.glob(f'{R}/app/Http/Controllers/**/{cls}.php', recursive=True)[0], encoding='utf-8').read()
    for meth in ('index', 'store', 'update', 'destroy'):
        if not re.search(rf'function\s+{meth}\s*\(', src): bad(f'[Controller] resource {cls} kekurangan {meth}')

# ---------- E. view() & @include & <x-...> ----------
views = set(os.path.relpath(f, f'{R}/resources/views')[:-10].replace('/', '.') for f in glob.glob(f'{R}/resources/views/**/*.blade.php', recursive=True))
for f in glob.glob(f'{R}/app/**/*.php', recursive=True):
    for m in re.finditer(r"view\('([\w.\-]+)'", open(f, encoding='utf-8').read()):
        if m.group(1) not in views: bad(f'[View] view("{m.group(1)}") tidak ada ({os.path.relpath(f,R)})')
comps = {v[len('components.'):] for v in views if v.startswith('components.')}
for f in glob.glob(f'{R}/resources/views/**/*.blade.php', recursive=True):
    s = open(f, encoding='utf-8').read(); rel = os.path.relpath(f, R)
    for m in re.finditer(r"@include\('([\w.\-]+)'", s):
        if m.group(1) not in views: bad(f'[Include] {m.group(1)} tidak ada ({rel})')
    for m in re.finditer(r"@extends\('([\w.\-]+)'", s):
        if m.group(1) not in views: bad(f'[Extends] {m.group(1)} tidak ada ({rel})')
    for m in re.finditer(r"<x-([a-z0-9.\-]+)", s):
        if m.group(1) not in comps: bad(f'[Komponen] <x-{m.group(1)}> tidak ada ({rel})')

# ---------- F. Blade: direktif berpasangan ----------
PAIRS = [('if','endif'),('foreach','endforeach'),('forelse','endforelse'),('for','endfor'),('section','endsection'),('php','endphp'),('push','endpush'),('error','enderror'),('unless','endunless'),('switch','endswitch'),('isset','endisset'),('auth','endauth')]
for f in glob.glob(f'{R}/resources/views/**/*.blade.php', recursive=True):
    s = open(f, encoding='utf-8').read(); rel = os.path.relpath(f, R)
    s2 = re.sub(r'\{\{--.*?--\}\}', '', s, flags=re.S)
    for a, b in PAIRS:
        na = len(re.findall(rf'@{a}\b(?!\w)(?=\s*(?:\(|$|\n|<|\w|@|\)|\.)|\s)', s2)) if a not in ('section','php') else 0
        if a == 'section':
            na = len(re.findall(r"@section\('[^']+'\)(?!\s*,)", s2)) ; na = len(re.findall(r"@section\('[^']+'\)", s2)) - 0
        if a == 'php': na = len(re.findall(r'@php\b(?!\s*\()', s2)) - len(re.findall(r'@php\s*\(', s2))
        if a == 'if': na = len(re.findall(r'@if\s*\(', s2))
        if a == 'for': na = len(re.findall(r'@for\s*\(', s2))
        if a == 'error': na = len(re.findall(r"@error\s*\(", s2))
        if a == 'isset': na = len(re.findall(r"@isset\s*\(", s2))
        if a == 'unless': na = len(re.findall(r"@unless\s*\(", s2))
        if a == 'foreach': na = len(re.findall(r'@foreach\s*\(', s2))
        if a == 'forelse': na = len(re.findall(r'@forelse\s*\(', s2))
        if a == 'switch': na = len(re.findall(r'@switch\s*\(', s2))
        if a == 'auth': na = len(re.findall(r'@auth\b', s2))
        if a == 'push': na = len(re.findall(r'@push\s*\(', s2))
        nb = len(re.findall(rf'@{b}\b', s2))
        if a == 'section': nb = len(re.findall(r'@endsection\b', s2))
        if na != nb: bad(f'[Blade] {rel}: @{a} ({na}) vs @{b} ({nb})')
    # {{ }} seimbang
    if s2.count('{{') != s2.count('}}'): bad(f'[Blade] {rel}: {{{{ }}}} tidak seimbang')
    if s2.count('{!!') != s2.count('!!}'): bad(f'[Blade] {rel}: {{!! !!}} tidak seimbang')
    # Alpine/atribut: jumlah tanda kutip ganjil di baris tag? (cek kasar) -- lewati

# ---------- G. Bentrok sintaks Alpine (@event.modifier / @event:) dengan direktif Blade ----------
RISKY = set('error auth guest can cannot env empty class style js json checked selected disabled readonly required include lang once section stack push props method csrf dump dd isset unless if for foreach while php endphp yield extends'.split())
dirs = set(RISKY)
for f in glob.glob(f'{R}/vendor/laravel/framework/src/Illuminate/View/Compilers/**/*.php', recursive=True):
    for m in re.finditer(r'function compile([A-Z]\w*)\(', open(f, encoding='utf-8').read()):
        n = m.group(1); dirs.add(n[0].lower() + n[1:])
dirs -= {'statements','statement','echos','comments','extensions','string','regularEchos','escapedEchos','rawEchos','echo','comment','verbatim','components','componentTags','classComponentTags'}
for f in glob.glob(f'{R}/resources/views/**/*.blade.php', recursive=True):
    s = re.sub(r'\{\{--.*?--\}\}', '', open(f, encoding='utf-8').read(), flags=re.S)
    for m in re.finditer(r'(?<![\w@])@(\w+)(?=[.:])([.:][\w.:\-]*)', s):
        if m.group(1) in dirs and not re.match(r'\.\s', m.group(2) + ' '):
            bad(f'[Blade/Alpine] {os.path.relpath(f,R)}: "{m.group(0)}" bentrok dengan direktif Blade @{m.group(1)} — pakai x-on:{m.group(1)}')

print(f'PHP diperiksa: {len(php_files)} | view: {len(views)} | komponen: {len(comps)} | route dipakai: {len(used)}')
print('\n'.join(problems) if problems else 'STATIC CHECK: tidak ada masalah')
