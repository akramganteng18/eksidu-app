"""
Renderer Blade-heuristik untuk PRATINJAU VISUAL (bukan pengganti PHP).
Ekspresi PHP tidak dievaluasi; diganti data contoh berdasarkan pola nama. Percabangan memilih
cabang pertama (atau cabang kosong untuk kondisi 'kosong'); perulangan digandakan 3x.
"""
import re, os, sys, itertools, json, html
from pathlib import Path

APP_ROOT = Path(__file__).resolve().parents[2]
ROOT = str(APP_ROOT / 'resources' / 'views')
STATUSES = ['pending', 'diproses', 'approved', 'selesai', 'rejected']
LABEL = {'pending':'Menunggu','diproses':'Diproses','approved':'Siap Diambil','selesai':'Selesai','rejected':'Ditolak'}
TONE = {'pending':'amber','diproses':'sky','approved':'green','selesai':'teal','rejected':'rose'}
TONES = ['teal','violet','coral','amber','sky','green']
cyc_status = itertools.cycle(STATUSES); cyc_tone = itertools.cycle(TONES)
OVERRIDES = {}   # nama-prop -> nilai tetap

# --- ikon asli dari icon.blade.php ---
_icon_src = open(f'{ROOT}/components/icon.blade.php', encoding='utf-8').read()
ICONS = dict(re.findall(r"'([a-z-]+)' => '([^']+)',", _icon_src))

def read(path):
    return open(path, encoding='utf-8').read()

def strip_comments(s):
    s = re.sub(r'\{\{--.*?--\}\}', '', s, flags=re.S)
    s = re.sub(r'@php\b.*?@endphp', '', s, flags=re.S)
    s = re.sub(r'@props\(\[.*?\]\)', '', s, flags=re.S)
    return s

def guess(expr, ctx=None):
    e = expr.strip()
    m = re.fullmatch(r"\$([A-Za-z_]+)", e)
    if ctx and m and m.group(1) in ctx: return str(ctx[m.group(1)])
    if re.fullmatch(r"'[^']*'|\"[^\"]*\"", e): return e[1:-1]
    tern = re.search(r"\?\s*'([^']*)'\s*:\s*'([^']*)'", e)
    if tern and not re.search(r'\bvalue\b', e): return tern.group(1) if tern.group(1) else tern.group(2)
    if 'batas_' in e: return '2048'
    low = e.lower()
    if 'route(' in low or 'url(' in low or 'asset(' in low or 'href' in low: return '#'
    if 'csrf' in low: return 'x'
    if 'tone' in low: return next(cyc_tone)
    if 'statuslabel' in low or 'status_label' in low: return LABEL[next(cyc_status)]
    if 'status' in low: return next(cyc_status)
    if 'kode_tiket' in low: return 'EKSIDU-2026-0000%02d' % (hash(e) % 90 + 10)
    if 'email' in low: return 'ahmad.fauzan@siswa.smkn5surakarta.sch.id'
    if 'initials' in low: return 'AF'
    if 'name' in low or 'nama' in low or 'judul' in low: return 'Ahmad Fauzan Nugroho'
    if any(k in low for k in ('format(', 'translatedformat', 'diffforhumans')): return '12 Okt 2026'
    if 'jenis' in low or 'layanan' in low: return 'Legalisir Ijazah'
    if any(k in low for k in ('count', 'total', 'sum', 'jumlah', 'value', 'lembar', 'page', 'urutan')): return '12'
    if 'message' in low or 'catatan' in low or 'keperluan' in low or 'deskripsi' in low or 'description' in low:
        return 'Contoh keterangan singkat yang menjelaskan isi bagian ini dengan jelas.'
    if 'kelas' in low: return 'XII RPL 1'
    if 'nis' in low: return '2223010001'
    if 'label' in low: return 'Label'
    return 'Contoh'

def render_expr_tags(s, ctx=None):
    s = re.sub(r'\{!!(.*?)!!\}', lambda m: '<svg viewBox="0 0 29 29" width="176" height="176"><rect width="29" height="29" fill="#fff"/><path d="M1 1h7v7H1zM21 1h7v7h-7zM1 21h7v7H1zM10 3h2v2h-2zM13 8h3v3h-3zM18 12h2v2h-2zM10 14h4v4h-4zM16 17h4v3h-4zM22 14h3v3h-3zM11 22h3v3h-3zM18 23h3v3h-3zM24 21h3v3h-3z" fill="#111"/></svg>' if 'QrCode' in m.group(1) else '', s, flags=re.S)
    s = re.sub(r'\{\{(.*?)\}\}', lambda m: html.escape(guess(m.group(1), ctx), quote=False), s, flags=re.S)
    return s

def match_end(s, start, open_re, close_re):
    depth = 0
    for m in re.finditer(f'({open_re})|({close_re})', s[start:]):
        if m.group(1): depth += 1
        else:
            depth -= 1
            if depth == 0: return start + m.end(), start + m.start()
    return None, None

def process_directives(s, sections, stacks):
    # @section ... @endsection / inline
    def sec(m):
        sections[m.group(1)] = m.group(2); return ''
    s = re.sub(r"@section\('([^']+)',\s*(?:'([^']*)'|\"([^\"]*)\")\)", lambda m: (sections.__setitem__(m.group(1), m.group(2) or m.group(3) or ''), '')[1], s)
    # push
    def push(m):
        stacks.setdefault(m.group(1), []).append(m.group(2)); return ''
    s = re.sub(r"@push\('([^']+)'\)(.*?)@endpush", push, s, flags=re.S)
    return s

def eval_cond_empty(cond):
    c = cond.lower()
    return any(k in c for k in ('isempty()', 'errors', '$errors', 'is_null', 'empty(', '! $', '!$', 'hasany', 'aktif', 'galat', 'ditolak', 'opsi'))

def cf(s):
    """control flow: pilih cabang & gandakan loop."""
    # @forelse
    while True:
        m = re.search(r'@forelse\s*\(', s)
        if not m: break
        end, _ = match_end(s, m.start(), r'@forelse\b', r'@endforelse\b')
        block = s[m.start():end]
        body = re.sub(r'^@forelse\s*\((?:[^()]|\([^()]*\))*\)', '', block, count=1)
        body = body[:-len('@endforelse')]
        main = body.split('@empty')[0]
        s = s[:m.start()] + (main * 3) + s[end:]
    for kw in ('foreach', 'for', 'while'):
        while True:
            m = re.search(rf'@{kw}\s*\(', s)
            if not m: break
            end, _ = match_end(s, m.start(), rf'@{kw}\b(?!each)' if kw == 'for' else rf'@{kw}\b', rf'@end{kw}\b')
            if end is None: break
            block = s[m.start():end]
            body = re.sub(rf'^@{kw}\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)', '', block, count=1)
            body = body[:-len(f'@end{kw}')]
            s = s[:m.start()] + (body * 3) + s[end:]
    # @switch
    while True:
        m = re.search(r'@switch\s*\(', s)
        if not m: break
        end, _ = match_end(s, m.start(), r'@switch\b', r'@endswitch\b')
        block = s[m.start():end]
        cm = re.search(r'@case\([^)]*\)(.*?)(?=@break|@case|@default|@endswitch)', block, flags=re.S)
        s = s[:m.start()] + (cm.group(1) if cm else '') + s[end:]
    # @if / @unless / @isset / @auth
    for _ in range(400):
        m = re.search(r'@(if|unless|isset|auth|guest|can)\b\s*(\(|)', s)
        if not m: break
        kw = m.group(1)
        # kondisi
        i = m.end()
        cond = ''
        if m.group(2) == '(':
            depth = 1; j = i
            while depth and j < len(s):
                depth += (s[j] == '(') - (s[j] == ')'); j += 1
            cond = s[i:j-1]; i = j
        end, _ = match_end(s, m.start(), rf'@{kw}\b', rf'@end{kw}\b')
        if end is None: s = s[:m.start()] + s[m.end():]; continue
        body = s[i:end - len(f'@end{kw}')]
        # pisahkan cabang pada level-0 @elseif/@else
        parts, cur, depth = [], '', 0
        for tok in re.split(r'(@if\b|@unless\b|@isset\b|@auth\b|@guest\b|@can\b|@endif\b|@endunless\b|@endisset\b|@endauth\b|@endguest\b|@endcan\b|@elseif\b\s*\((?:[^()]|\([^()]*\))*\)|@else\b)', body):
            if re.match(r'@(if|unless|isset|auth|guest|can)\b', tok): depth += 1; cur += tok
            elif re.match(r'@end(if|unless|isset|auth|guest|can)\b', tok): depth -= 1; cur += tok
            elif depth == 0 and (tok.startswith('@elseif') or tok == '@else'): parts.append(cur); cur = ''
            else: cur += tok
        parts.append(cur)
        if kw == 'unless': chosen = '' if not eval_cond_empty(cond) else parts[0]; chosen = parts[0] if not cond else parts[0]
        elif kw in ('guest',): chosen = ''
        else:
            neg = eval_cond_empty(cond) and len(parts) > 1
            chosen = parts[-1] if neg else parts[0]
            if eval_cond_empty(cond) and len(parts) == 1: chosen = parts[0] if kw != 'if' or not re.search(r'isEmpty|errors|\$errors', cond) else ''
        s = s[:m.start()] + chosen + s[end:]
    return s

ATTR_RE = re.compile(r'''([:@\w.\-]+)(?:\s*=\s*("([^"]*)"|'([^']*)'|[^\s>]+))?''')

def parse_attrs(txt):
    attrs = []
    for m in ATTR_RE.finditer(txt):
        name = m.group(1)
        val = m.group(3) if m.group(3) is not None else (m.group(4) if m.group(4) is not None else (m.group(2) or True))
        attrs.append((name, val))
    return attrs

def find_tag_end(s, i):
    q = None
    while i < len(s):
        c = s[i]
        if q:
            if c == q: q = None
        elif c in '"\'': q = c
        elif c == '>': return i
        i += 1
    return -1

def render_components(s, sections, stacks, depth=0):
    out = ''; i = 0
    while True:
        m = re.search(r'<x-([a-z0-9.\-]+)', s[i:])
        if not m: out += s[i:]; break
        start = i + m.start(); out += s[i:start]
        tag = m.group(1)
        gt = find_tag_end(s, start)
        raw = s[start + 3 + len(tag): gt]
        selfclose = raw.rstrip().endswith('/')
        attrs_txt = raw.rstrip()[:-1] if selfclose else raw
        slot = ''
        if selfclose: nxt = gt + 1
        else:
            end, close_start = match_end(s, start, rf'<x-{re.escape(tag)}(?=[\s/>])(?:(?!/>)[^>])*?>' if False else rf'<x-{re.escape(tag)}\b(?:"[^"]*"|\'[^\']*\'|[^>"\'])*(?<!/)>', rf'</x-{re.escape(tag)}>')
            if end is None: nxt = gt + 1
            else:
                slot = s[gt + 1: close_start]; nxt = end
        out += render_one(tag, parse_attrs(attrs_txt), slot, sections, stacks, depth)
        i = nxt
    return out

DEFAULT_CLASS = {
    'stat-card': lambda d: f"stat tone-{d.get('tone','teal')}" + (' stat-lime' if d.get('lime') else ''),
}

def attr_value(name, val):
    if val is True: return True
    if name.startswith(':'):
        v = val.strip()
        if re.fullmatch(r"'[^']*'", v): return v[1:-1]
        if re.fullmatch(r'-?\d+', v): return v
        return guess(v)
    return val

def render_one(tag, attrs, slot, sections, stacks, depth):
    name = tag.replace('.', '/')
    # kasus khusus
    d = {}
    extra = []
    for n, v in attrs:
        key = n.lstrip(':')
        val = attr_value(n, v)
        d[key] = val
        if key not in ('x-bind:required',): extra.append((key, val))
    slot_html = process_slot(slot, sections, stacks, depth)
    if tag == 'icon':
        paths = ICONS.get(d.get('name', 'document'), ICONS['document']).split('|')
        size = d.get('size', 'h-5 w-5'); cls = d.get('class', '')
        stroke = d.get('stroke', '1.8')
        return f'<svg class="{size} shrink-0 {cls}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{stroke}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ''.join(f'<path d="{p}"/>' for p in paths) + '</svg>'
    if tag == 'status-badge':
        st = d.get('status') if d.get('status') in STATUSES else next(cyc_status)
        return f'<span class="badge tone-{TONE[st]} {d.get("class","")}">{LABEL[st]}</span>'
    if tag == 'avatar':
        return f'<span class="avatar {d.get("size","h-9 w-9 text-sm")}"><span>AF</span></span>'
    if tag == 'badge':
        return f'<span class="badge tone-{d.get("tone","slate")} {d.get("class","")}">{slot_html}</span>'
    if tag == 'theme-toggle':
        return '<button type="button" x-data="themeToggle" @click="toggle()" class="btn btn-ghost btn-icon" aria-label="Ganti tema"><span x-show="theme !== \'dark\'"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="%s"/></svg></span><span x-show="theme === \'dark\'" x-cloak><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="%s"/></svg></span></button>' % (ICONS['moon'], ICONS['sun'])
    if name in CHARTS:
        return CHARTS[name]
    # komponen umum: muat berkas
    path = f'{ROOT}/components/{name}.blade.php'
    if not os.path.exists(path): return f'<!-- komponen {tag} tidak ditemukan -->'
    src = read(path)
    props_decl = re.search(r'@props\(\[(.*?)\]\)', src, flags=re.S)
    prop_names = re.findall(r"'([a-zA-Z_]+)'\s*(?:=>|,|\])", props_decl.group(1)) if props_decl else []
    prop_names += re.findall(r'"([a-zA-Z_]+)"\s*(?:=>|,|\])', props_decl.group(1)) if props_decl else []
    body = strip_comments(src)
    ctx = {k: v for k, v in d.items() if k in prop_names or k in ('title', 'label', 'name', 'subtitle', 'eyebrow', 'text', 'value')}
    ctx['slot'] = slot_html
    # $attributes (only / except / merge) dengan kelas bawaan dari merge([...])
    cls = d.get('class', '')
    other = ' '.join(f'{k}="{html.escape(str(v))}"' for k, v in extra if k not in prop_names and k not in ('class',) and not k.startswith('x-bind'))
    def merged_default(expr):
        m = re.search(r"'class'\s*=>\s*(.*)\]\)?\s*$", expr, flags=re.S)
        e = m.group(1) if m else expr
        lits = re.findall(r"'([^']*)'|\"([^\"]*)\"", e)
        lits = [(a or b) for a, b in lits]
        def interp(t): return re.sub(r"\{\$(\w+)\}", lambda mm: str(d.get(mm.group(1), 'slate')), t)
        if not lits:
            fb = DEFAULT_CLASS.get(tag)
            return fb(d) if fb else ''
        vars_ = re.findall(r"\$(\w+)", e)
        truthy = any(str(d.get(v, '')).lower() in ('1', 'true') or d.get(v) is True for v in vars_ if v not in ('size',))
        base = interp(lits[0]); extra_l = [interp(t) for t in lits[1:]] if truthy else []
        if 'size' in vars_ and 'size' in d: base = d['size'] + ' ' + base
        return ' '.join([base] + extra_l).strip()
    def attr_sub(m):
        chain = m.group(1)
        if chain.startswith("->only('class')"): return f'class="{cls}"'
        mm = re.search(r"merge\((\[.*\])\)", chain, flags=re.S)
        dflt = merged_default(mm.group(1)) if mm else ''
        return f'class="{(dflt + " " + cls).strip()}" {other}'
    body = re.sub(r"\{\{\s*\$attributes(.*?)\}\}", attr_sub, body, flags=re.S)
    body = body.replace('{{ $slot }}', slot_html)
    return render_str(body, sections, stacks, depth + 1, ctx)

def process_slot(slot, sections, stacks, depth):
    return render_str(slot, sections, stacks, depth + 1, {}) if slot.strip() else ''

def render_str(s, sections, stacks, depth=0, ctx=None):
    ctx = ctx or {}
    if depth > 12: return ''
    s = strip_comments(s)
    s = re.sub(r"\{\{\s*\$slot\s*\}\}", ctx.get('slot', ''), s)
    s = re.sub(r"@(csrf|method\([^)]*\)|vite\([^)]*\)|stack\('[^']*'\)|stack\(\"[^\"]*\"\))", lambda m: ''.join(stacks.get(re.findall(r"'([^']+)'", m.group(1))[0], [])) if m.group(1).startswith('stack') else '', s)
    s = re.sub(r"@(selected|checked|required|disabled)\([^)]*\)", '', s)
    s = re.sub(r"@class\(\[.*?\]\)", '', s, flags=re.S)
    s = re.sub(r"@error\('[^']*'\).*?@enderror", '', s, flags=re.S)
    s = re.sub(r"@js\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)", "''", s)
    s = re.sub(r"@include\('([^']+)'[^)]*\)", lambda m: render_str(read(f"{ROOT}/{m.group(1).replace('.','/')}.blade.php"), sections, stacks, depth + 1, ctx), s)
    s = cf(s)
    s = render_components(s, sections, stacks, depth)
    # kelas dinamis: tone-{{..}} dll
    s = re.sub(r'\{\{[^}]*\}\}', lambda m: html.escape(guess(m.group(0)[2:-2], ctx), quote=False), s)
    s = re.sub(r'\{!!.*?!!\}', lambda m: render_expr_tags(m.group(0)), s, flags=re.S)
    return s

def render_view(view, sections=None, stacks=None):
    sections = sections if sections is not None else {}
    stacks = stacks if stacks is not None else {}
    src = read(f"{ROOT}/{view.replace('.','/')}.blade.php")
    src = strip_comments(src)
    ext = re.search(r"@extends\('([^']+)'\)", src)
    if ext:
        src = re.sub(r"@extends\('[^']+'\)", '', src)
        # kumpulkan section blok
        for m in re.finditer(r"@section\('([^']+)'\)(.*?)@(?:endsection|show)", src, flags=re.S):
            sections[m.group(1)] = render_str(m.group(2), sections, stacks)
        for m in re.finditer(r"@section\('([^']+)',\s*'([^']*)'\)", src):
            sections[m.group(1)] = m.group(2)
        for m in re.finditer(r"@push\('([^']+)'\)(.*?)@endpush", src, flags=re.S):
            stacks.setdefault(m.group(1), []).append(m.group(2))
        layout = read(f"{ROOT}/{ext.group(1).replace('.','/')}.blade.php")
        layout = strip_comments(layout)
        layout = re.sub(r"@yield\('([^']+)'(?:,\s*'([^']*)')?\)", lambda m: sections.get(m.group(1), m.group(2) or ''), layout)
        layout = re.sub(r"@yield\(\"content\"\)", sections.get('content', ''), layout)
        return render_str(layout, sections, stacks)
    return render_str(src, sections, stacks)

CHARTS = {}
def _chart_svgs():
    bars = ''
    vals = [3, 5, 4, 8, 6, 9]; lab = ['Jun','Jul','Agu','Sep','Okt','Nov']
    for i, v in enumerate(vals):
        h = 130 * v / 12; x = 40 + i * 62; y = 154 - h
        last = ' is-last' if i == len(vals) - 1 else ''
        bars += f'<g class="chart-bar{last}"><rect x="{x}" y="{y}" width="34" height="{h}" rx="7"/></g><text x="{x+17}" y="{y-6}" text-anchor="middle" class="chart-value">{v}</text><text x="{x+17}" y="192" text-anchor="middle" class="chart-text">{lab[i]}</text>'
    grid = ''.join(f'<line x1="30" x2="420" y1="{154 - 130*i/4}" y2="{154 - 130*i/4}" class="chart-grid"/><text x="22" y="{158 - 130*i/4}" text-anchor="end" class="chart-text">{3*i}</text>' for i in range(5))
    CHARTS['chart/bars'] = f'<svg viewBox="0 0 420 200" class="h-auto w-full">{grid}{bars}</svg>'
    segs = [(45, 25), (30, 70), (25, 40)]
    c = ''
    for i, (p, off) in enumerate(segs): c += f'<circle class="seg seg-{i+1}" cx="18" cy="18" r="15.9155" fill="none" stroke-width="4.2" stroke-dasharray="{p-1.2} {100-p+1.2}" stroke-dashoffset="{off}"/>'
    CHARTS['chart/donut'] = ('<div class="flex flex-col items-center gap-5 sm:flex-row"><div class="relative h-40 w-40 shrink-0"><svg viewBox="0 0 36 36" class="h-full w-full"><circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="4.2" style="stroke: rgb(var(--c-surface-3))"/>' + c + '</svg><div class="absolute inset-0 flex flex-col items-center justify-center"><span class="font-display text-3xl font-extrabold leading-none">24</span><span class="mt-1 text-[11px] font-bold uppercase tracking-wide text-muted">Total</span></div></div><ul class="w-full flex-1 space-y-2.5 text-sm">'
        + ''.join(f'<li class="flex items-center justify-between"><span class="flex items-center gap-2"><span class="legend-dot seg-{i+1}"></span><span class="text-ink-2">{n}</span></span><span class="font-bold">{v}</span></li>' for i, (n, v) in enumerate([('Peminjaman Rapor', 11), ('Legalisir', 7), ('Pengambilan Ijazah', 6)])) + '</ul></div>')
_chart_svgs()

def js_bundle():
    parts = []
    for f in ('theme.js', 'ui.js'):
        t = read(str(APP_ROOT / 'resources' / 'js' / f))
        t = re.sub(r'^\s*import .*?;\s*$', '', t, flags=re.M)
        t = t.replace('export default function', 'function').replace('export function', 'function').replace('export const', 'const')
        if f == 'theme.js': t = t.replace('reduceMotion', 'reduceMotionTema')
        parts.append(t)
    alpine_path = APP_ROOT / 'node_modules/alpinejs/dist/cdn.min.js'
    if not alpine_path.is_file():
        raise FileNotFoundError(f'Alpine.js tidak ditemukan: {alpine_path}. Jalankan npm install terlebih dahulu.')
    alpine = read(str(alpine_path))
    boot = """
document.addEventListener('alpine:init', () => {
  Alpine.data('themeToggle', themeToggle); Alpine.data('toasts', toasts); Alpine.data('confirmDialog', confirmDialog); Alpine.data('fileField', fileField);
});
initLoading(); document.addEventListener('DOMContentLoaded', initCountUp);
"""
    return '(function(){' + '\n'.join(parts) + boot + '})();\n' + alpine

def page(view, theme, out, extra_head='', real_init=False, built=False):
    body_html = render_view(view)
    css_path = Path(os.environ.get('EKSIDU_PREVIEW_CSS', '/tmp/eksidu.css'))
    if not css_path.is_file():
        raise FileNotFoundError(f'CSS pratinjau tidak ditemukan: {css_path}. Buat dengan Tailwind CLI atau set EKSIDU_PREVIEW_CSS.')
    css = read(str(css_path))
    # buang tag <script> init tema dari render (kita sisipkan manual) agar tema deterministik
    body_html = re.sub(r'<link rel="icon"[^>]*>', '', body_html)
    if not real_init:
        body_html = re.sub(r'<script>\s*\(function \(\) \{\s*var tema.*?</script>', '', body_html, flags=re.S)
    pre = '' if real_init else f"<script>try{{localStorage.setItem('eksidu-theme','{theme}')}}catch(e){{}}document.documentElement.setAttribute('data-theme','{theme}');</script>"
    if built:
        import json as _j
        man = _j.load(open(APP_ROOT / 'public/build/manifest.json'))
        tags = f'<link rel="stylesheet" href="/{"build/" + man["resources/css/app.css"]["file"]}"><script type="module" src="/{"build/" + man["resources/js/app.js"]["file"]}"></script>'
        body_html = body_html.replace('<head>', '<head>' + pre + tags + extra_head, 1)
    else:
        body_html = body_html.replace('<head>', '<head>' + pre + f'<style>{css}</style>' + extra_head, 1)
        body_html = body_html.replace('</body>', f'<script>{JS}</script></body>')
    open(out, 'w', encoding='utf-8').write(body_html)

JS = js_bundle()

if __name__ == '__main__':
    view, theme, out = sys.argv[1:4]
    page(view, theme, out)
    print('ok', out)
