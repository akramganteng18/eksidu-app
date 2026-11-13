import sys
from playwright.sync_api import sync_playwright
from PIL import Image
views = sys.argv[3].split(','); theme = sys.argv[1]; out = sys.argv[2]
w = int(sys.argv[4]) if len(sys.argv) > 4 else 390
h = int(sys.argv[5]) if len(sys.argv) > 5 else 1500
tmp = []
with sync_playwright() as p:
    b = p.chromium.launch()
    for i, v in enumerate(views):
        pg = b.new_page(viewport={'width': w, 'height': 844})
        pg.goto(f'file:///tmp/preview/all/{v}.{theme}.html'); pg.wait_for_timeout(500)
        f = f'/tmp/preview/_s{i}.png'; pg.screenshot(path=f, full_page=True); tmp.append(f); pg.close()
    b.close()
ims = [Image.open(f).convert('RGB') for f in tmp]
ims = [im.crop((0, 0, im.width, min(im.height, h))) for im in ims]
W = sum(im.width for im in ims) + 12 * (len(ims) - 1); H = max(im.height for im in ims)
sheet = Image.new('RGB', (W, H), (128, 128, 128)); x = 0
for im in ims: sheet.paste(im, (x, 0)); x += im.width + 12
sheet.save(out); print(sheet.size)
