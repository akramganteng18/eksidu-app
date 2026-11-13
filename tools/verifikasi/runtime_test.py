from playwright.sync_api import sync_playwright
URL = 'file:///tmp/preview/rt_siswa.html'
URL2 = 'file:///tmp/preview/rt_create.html'
ok = []; fail = []
def check(name, cond, info=''):
    (ok if cond else fail).append(name); print(('PASS ' if cond else 'FAIL ') + name + (f'  [{info}]' if info and not cond else ''))

with sync_playwright() as p:
    b = p.chromium.launch()

    # --- Tema: tanpa pilihan + OS gelap -> gelap
    ctx = b.new_context(color_scheme='dark', viewport={'width': 1366, 'height': 800}); pg = ctx.new_page(); pg.goto(URL); pg.wait_for_timeout(300)
    check('Tema: tanpa pilihan, OS gelap -> data-theme=dark', pg.evaluate("document.documentElement.dataset.theme") == 'dark')
    # --- toggle -> terang, tersimpan, bertahan setelah reload meski OS gelap
    pg.click("button[aria-label='Ganti tema']"); pg.wait_for_timeout(200)
    check('Tema: klik toggle -> light', pg.evaluate("document.documentElement.dataset.theme") == 'light')
    check('Tema: pilihan tersimpan di localStorage', pg.evaluate("localStorage.getItem('eksidu-theme')") == 'light')
    pg.reload(); pg.wait_for_timeout(300)
    check('Tema: pilihan manual (light) menang atas OS gelap setelah reload', pg.evaluate("document.documentElement.dataset.theme") == 'light')
    # --- OS berubah saat sudah memilih manual -> tidak berubah
    pg.emulate_media(color_scheme='light'); pg.emulate_media(color_scheme='dark'); pg.wait_for_timeout(200)
    check('Tema: perubahan OS tidak menimpa pilihan manual', pg.evaluate("document.documentElement.dataset.theme") == 'light')
    ctx.close()

    # --- Tema: tanpa pilihan, OS terang & OS berubah -> mengikuti OS
    ctx = b.new_context(color_scheme='light', viewport={'width': 1366, 'height': 800}); pg = ctx.new_page(); pg.goto(URL); pg.wait_for_timeout(300)
    check('Tema: tanpa pilihan, OS terang -> light', pg.evaluate("document.documentElement.dataset.theme") == 'light')
    pg.emulate_media(color_scheme='dark'); pg.wait_for_timeout(900)
    check('Tema: tanpa pilihan manual, mengikuti perubahan OS -> dark', pg.evaluate("document.documentElement.dataset.theme") == 'dark')
    # warna benar-benar berubah
    bg = pg.evaluate("getComputedStyle(document.body).backgroundColor")
    check('Tema: warna latar body gelap (charcoal)', bg == 'rgb(14, 16, 19)', bg)
    ctx.close()

    # --- Konfirmasi dialog
    ctx = b.new_context(viewport={'width': 1366, 'height': 800}); pg = ctx.new_page(); pg.goto(URL); pg.wait_for_timeout(300)
    pg.evaluate("window.__sub = 0; document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { e.preventDefault(); window.__sub++; }))")
    btn = pg.locator("form[data-confirm] button[type=submit]").first
    btn.click(); pg.wait_for_timeout(250)
    vis = pg.locator("#confirm-title").is_visible()
    check('Konfirmasi: klik hapus membuka dialog', vis)
    check('Konfirmasi: form BELUM terkirim sebelum konfirmasi', pg.evaluate("window.__sub") == 0)
    pg.get_by_role('button', name='Batal').first.click(); pg.wait_for_timeout(250)
    check('Konfirmasi: Batal menutup dialog & tidak mengirim', (not pg.locator("#confirm-title").is_visible()) and pg.evaluate("window.__sub") == 0)
    btn.click(); pg.wait_for_timeout(250)
    pg.keyboard.press('Escape'); pg.wait_for_timeout(250)
    check('Konfirmasi: Escape menutup dialog', not pg.locator("#confirm-title").is_visible())
    btn.click(); pg.wait_for_timeout(250)
    pg.locator(".modal button.btn-danger, .modal button.btn-primary").first.click(); pg.wait_for_timeout(300)
    check('Konfirmasi: tombol lanjut mengirim form tepat 1x', pg.evaluate("window.__sub") == 1, str(pg.evaluate("window.__sub")))

    # --- Toast
    pg.evaluate("window.dispatchEvent(new CustomEvent('eksidu:toast', {detail:{tone:'green', title:'Berhasil', message:'Data tersimpan.'}}))"); pg.wait_for_timeout(300)
    check('Toast: muncul dengan pesan', pg.locator("#toast-root .toast").count() >= 1 and 'Data tersimpan.' in pg.locator("#toast-root").inner_text())
    ctx.close()

    # --- Sidebar seluler
    ctx = b.new_context(viewport={'width': 390, 'height': 844}); pg = ctx.new_page(); pg.goto(URL); pg.wait_for_timeout(300)
    check('Sidebar seluler: tersembunyi awalnya', pg.evaluate("getComputedStyle(document.querySelector('.sidebar')).visibility") == 'hidden')
    pg.click("button[aria-label='Buka menu']"); pg.wait_for_timeout(450)
    check('Sidebar seluler: terbuka setelah klik menu', pg.evaluate("getComputedStyle(document.querySelector('.sidebar')).visibility") == 'visible' and pg.evaluate("document.querySelector('.sidebar').dataset.open") == 'true')
    pg.keyboard.press('Escape'); pg.wait_for_timeout(450)
    check('Sidebar seluler: Escape menutup', pg.evaluate("getComputedStyle(document.querySelector('.sidebar')).visibility") == 'hidden')
    ctx.close()

    # --- Validasi ukuran berkas klien
    ctx = b.new_context(viewport={'width': 1366, 'height': 900}); pg = ctx.new_page(); pg.goto(URL2); pg.wait_for_timeout(300)
    pg.set_input_files('#file_pendukung', '/tmp/preview/besar.pdf'); pg.wait_for_timeout(250)
    msg = pg.locator("p.field-error[x-text='galat']").inner_text()
    check('Berkas: file >2MB ditolak di klien dengan pesan', 'melebihi' in msg, msg)
    check('Berkas: input dikosongkan setelah ditolak', pg.evaluate("document.getElementById('file_pendukung').files.length") == 0)
    ctx.close()

    # --- Reduced motion
    ctx = b.new_context(reduced_motion='reduce', viewport={'width': 1366, 'height': 800}); pg = ctx.new_page(); pg.goto(URL); pg.wait_for_timeout(300)
    d = pg.evaluate("getComputedStyle(document.querySelector('.page-enter')).animationDuration")
    check('Reduced-motion: animasi halaman dinonaktifkan', d in ('0.001s', '1e-06s', '0s') or float(d.replace('s','')) < 0.01, d)
    ctx.close()
    b.close()

print(f'\n{len(ok)} lulus, {len(fail)} gagal')
