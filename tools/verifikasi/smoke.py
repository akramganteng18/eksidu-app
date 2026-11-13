import sys, subprocess, json, os
from pathlib import Path
TOOLS_DIR = str(Path(__file__).resolve().parent)
sys.path.insert(0, TOOLS_DIR)
import blade_preview as bp
from playwright.sync_api import sync_playwright

VIEWS = ['auth.login','auth.register','dashboard.admin','dashboard.siswa',
 'siswa.pengajuan.create','siswa.pengajuan.index','siswa.pengajuan.lacak','siswa.pengajuan.show',
 'admin.pengajuan.index','admin.pengajuan.show','admin.pengajuan.scan','admin.pengajuan.qr-result',
 'admin.siswa.index','admin.siswa.create','admin.siswa.edit','admin.dokumen.index',
 'admin.laporan.index','admin.laporan.cetak','admin.aktivitas.index',
 'admin.panduan.index','admin.panduan.form','panduan.index','panduan.show',
 'profile.edit','notifications.index','errors.403','errors.404','errors.500']
os.makedirs('/tmp/preview/all',exist_ok=True)
JS_OVERFLOW = """() => {
  const vw = document.documentElement.clientWidth; const bad = [];
  if (document.documentElement.scrollWidth > vw + 1) {
    for (const el of document.querySelectorAll('body *')) {
      const r = el.getBoundingClientRect();
      if (r.right > vw + 1 && r.width > 0 && getComputedStyle(el).position !== 'fixed' && !el.closest('.table-wrap') && !el.closest('.sidebar') && !el.closest('svg')) bad.push(el.tagName + '.' + (el.className||'').toString().slice(0,50) + ' r=' + Math.round(r.right));
      if (bad.length > 4) break;
    }
  }
  return {sw: document.documentElement.scrollWidth, vw, bad};
}"""
res = {}
with sync_playwright() as p:
    b = p.chromium.launch()
    for v in VIEWS:
        for theme in ('light','dark'):
            f = f'/tmp/preview/all/{v}.{theme}.html'
            try: bp.page(v, theme, f)
            except Exception as e:
                res[(v,theme,'render')] = 'GAGAL RENDER: ' + repr(e)[:120]; continue
            for (w,h,tag) in ((1366,900,'desk'),(390,844,'mob')):
                pg = b.new_page(viewport={'width':w,'height':h})
                errs = []
                pg.on('pageerror', lambda e: errs.append(str(e)[:120]))
                pg.on('console', lambda m: errs.append('console:'+m.text[:120]) if m.type=='error' else None)
                pg.goto('file://'+f); pg.wait_for_timeout(350)
                o = pg.evaluate(JS_OVERFLOW)
                if errs or o['bad']:
                    res[(v,theme,tag)] = {'errs': errs[:3], 'overflow': o['bad'][:3], 'sw': o['sw'], 'vw': o['vw']}
                pg.close()
    b.close()
print('halaman diuji:', len(VIEWS)*2*2)
if not res: print('TIDAK ADA error JS / overflow horizontal')
for k,v in res.items(): print(k, v)
