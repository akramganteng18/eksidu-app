import sys
from playwright.sync_api import sync_playwright
# pemakaian: shot.py file.html out.png [lebar] [tinggi] [full]
f, out = sys.argv[1], sys.argv[2]
w = int(sys.argv[3]) if len(sys.argv) > 3 else 1366
h = int(sys.argv[4]) if len(sys.argv) > 4 else 860
full = (sys.argv[5] == 'full') if len(sys.argv) > 5 else False
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': w, 'height': h})
    errs = []
    pg.on('pageerror', lambda e: errs.append(str(e)))
    pg.on('console', lambda m: errs.append('console:' + m.text) if m.type == 'error' else None)
    pg.goto('file://' + f)
    pg.wait_for_timeout(900)
    pg.screenshot(path=out, full_page=full)
    print('errors:', errs[:5])
    b.close()
