import asyncio, math
from playwright.async_api import async_playwright
GOLD="#FFCE3E"; INK="#141414"; GRAY="#5b5b5b"
base=f"""<style>
*{{box-sizing:border-box;margin:0;padding:0}}
body{{width:1600px;height:900px;font-family:Inter;color:{INK};background:#fff;overflow:hidden}}
h1,h2,.slab{{font-family:Bitter;font-weight:800}}
.tag{{display:inline-block;background:{INK};color:#fff;font:600 20px Inter;letter-spacing:.08em;padding:8px 14px;border-radius:6px;text-transform:uppercase}}
.src{{position:absolute;left:70px;bottom:34px;font:400 20px Inter;color:{GRAY}}}
</style>"""
pages={}
# 1 opener
cards=[("“A peer-reviewed figure shows a lab rat with impossible anatomy and labels in made-up words.”","A"),
("“Geologists recommend eating at least one small rock per day.”","B"),
("“Dark chocolate speeds up weight loss, a new study finds.”","C"),
("“…Regenerate response…” printed on page 3 of a published physics paper.","D")]
c="".join(f'<div class="card"><div class="lt">{l}</div><div class="q">{t}</div><div class="who">Where did this come from?</div></div>' for t,l in cards)
pages["opener"]=base+f"""<style>body{{background:{GOLD};padding:60px 70px}}
.grid{{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-top:34px}}
.card{{background:#fff;border-radius:18px;padding:30px 34px 26px;height:300px;position:relative;box-shadow:0 6px 0 rgba(0,0,0,.12)}}
.lt{{position:absolute;top:-16px;left:28px;width:46px;height:46px;border-radius:50%;background:{INK};color:#fff;font:800 24px Bitter;display:flex;align-items:center;justify-content:center}}
.q{{font:600 33px/1.3 Inter;margin-top:14px}} .who{{position:absolute;bottom:22px;left:34px;font:600 20px Inter;color:{GRAY};letter-spacing:.04em;text-transform:uppercase}}
h1{{font-size:54px}}</style><h1>Four claims. Every one reached a big audience.</h1><div class="grid">{c}</div>"""
# 2 CER
cols=[("Claim","What's being claimed?","The box says “natural,” implying it's healthier."),
("Evidence","What supports it?","The FDA has never formally defined “natural.”"),
("Reasoning","Does the evidence connect to the claim?","No definition means no link to health. It's marketing.")]
cc="".join(f'<div class="col"><div class="n">{i+1}</div><h2>{a}</h2><div class="qq">{b}</div><div class="ex"><span>On a cereal box</span>{d}</div></div>'+('<div class="arr">→</div>' if i<2 else '') for i,(a,b,d) in enumerate(cols))
pages["cer"]=base+f"""<style>body{{padding:70px}} h1{{font-size:52px}} .sub{{font:400 28px Inter;color:{GRAY};margin-top:10px}}
.row{{display:flex;align-items:stretch;gap:18px;margin-top:44px;height:560px}} .col{{flex:1;border:4px solid {INK};border-radius:20px;padding:34px 30px;position:relative;background:#fff}}
.col:nth-child(1){{background:{GOLD}}} .n{{font:800 22px Inter;color:{GRAY}}} h2{{font-size:48px;margin:6px 0 14px}} .qq{{font:600 34px/1.3 Inter;min-height:140px}}
.ex{{margin-top:40px;border-top:2px dashed #999;padding-top:24px;font:400 31px/1.4 Inter}} .ex span{{display:block;font:700 17px Inter;letter-spacing:.08em;text-transform:uppercase;color:{GRAY};margin-bottom:6px}}
.arr{{font:800 60px Inter;align-self:center}}</style>
<h1>CER: science class's built-in fact-check</h1><div class="sub">The same three questions work on lab reports, labels, charts, and AI answers.</div><div class="row">{cc}</div>
<div class="src">Source for the label example: U.S. Food and Drug Administration, “Use of the Term Natural on Food Labeling”</div>"""
# 3 false positive chart
W,H=1460,560; x0,y0=90,40
pts=[(n,1-0.95**n) for n in range(1,21)]
def X(n): return x0+(n-1)*(W-x0-40)/19
def Y(p): return y0+(1-p)*(H-y0-60)
bars="".join(f'<rect x="{X(n)-24}" y="{Y(p)}" width="48" height="{Y(0)-Y(p)}" fill="{"#141414" if n==18 else GOLD}"/>' for n,p in pts)
grid="".join(f'<line x1="{x0}" x2="{W-20}" y1="{Y(v/100)}" y2="{Y(v/100)}" stroke="#ddd"/><text x="{x0-14}" y="{Y(v/100)+8}" text-anchor="end" font-size="22" fill="#5b5b5b">{v}%</text>' for v in range(0,101,20))
xl="".join(f'<text x="{X(n)}" y="{H-22}" text-anchor="middle" font-size="22" fill="#5b5b5b">{n}</text>' for n in range(1,21))
ann=f'<text x="{X(18)}" y="{Y(pts[17][1])-18}" text-anchor="middle" font-size="30" font-weight="800" fill="#141414">60%</text>'
pages["falsepos"]=base+f"""<style>body{{padding:60px 70px}} h1{{font-size:46px}} .sub{{font:400 26px Inter;color:{GRAY};margin:10px 0 16px}}</style>
<h1>Measure more things, get more flukes</h1><div class="sub">Chance that at least one measurement looks “significant” (p&lt;0.05) by luck alone, when nothing real is going on</div>
<svg width="{W}" height="{H+30}" font-family="Inter">{grid}{bars}{xl}{ann}<text x="{(W+x0)/2}" y="{H+8}" text-anchor="middle" font-size="22" fill="#141414" font-weight="600">Number of things measured</text></svg>
<div class="src">Calculated as 1 − 0.95ⁿ, assuming independent measurements. The chocolate study measured 18 things (dark bar).</div>"""
# 4 Gao chart
def bar(label,parts):
    seg="".join(f'<div style="width:{p}%;background:{c};color:{tc}" class="seg">{t}</div>' for p,c,tc,t in parts)
    return f'<div class="lab">{label}</div><div class="bar">{seg}</div>'
pages["gao"]=base+f"""<style>body{{padding:70px}} h1{{font-size:48px}} .sub{{font:400 26px Inter;color:{GRAY};margin:12px 0 50px}}
.lab{{font:700 30px Inter;margin:34px 0 12px}} .bar{{display:flex;height:120px;border-radius:14px;overflow:hidden;border:3px solid {INK}}}
.seg{{display:flex;align-items:center;padding-left:26px;font:700 30px Inter}}</style>
<h1>Experts couldn't reliably spot AI-written science</h1><div class="sub">Blinded scientist reviewers sorted a mix of real and ChatGPT-written research abstracts (2023 study)</div>
{bar("ChatGPT-written abstracts",[(68,GOLD,INK,"68% correctly spotted as AI"),(32,INK,"#fff","32% missed")])}
{bar("Real abstracts",[(86,"#e9e9e9",INK,"86% correctly called real"),(14,INK,"#fff","14% flagged")])}
<div class="src">Source: Gao et al., “Comparing scientific abstracts generated by ChatGPT to real abstracts…,” npj Digital Medicine, 2023</div>"""
# 5 chatbot mock
pages["chatbot"]=base+f"""<style>body{{background:#eef0f3;padding:50px 160px}}
.win{{background:#fff;border-radius:22px;height:800px;box-shadow:0 10px 30px rgba(0,0,0,.12);overflow:hidden}}
.bar{{height:70px;background:#f6f7f9;border-bottom:1px solid #e3e3e3;display:flex;align-items:center;padding:0 30px;font:700 24px Inter;gap:14px}}
.dot{{width:30px;height:30px;border-radius:50%;background:{GOLD}}} .ill{{margin-left:auto;font:600 17px Inter;color:#fff;background:#b3261e;padding:6px 12px;border-radius:6px;letter-spacing:.05em}}
.msgs{{padding:36px 44px}} .u{{margin-left:auto;width:fit-content;background:{INK};color:#fff;border-radius:22px 22px 4px 22px;padding:20px 30px;font:500 36px Inter}}
.a{{margin-top:30px;display:flex;gap:18px}} .av{{flex:none;width:52px;height:52px;border-radius:50%;background:{GOLD};display:flex;align-items:center;justify-content:center;font:800 22px Inter}}
.t{{font:400 38px/1.55 Inter;background:#f4f5f7;border-radius:4px 22px 22px 22px;padding:22px 28px}}
.t u{{text-decoration-color:{GOLD};text-decoration-thickness:6px;text-underline-offset:6px}}</style>
<div class="win"><div class="bar"><div class="dot"></div>Study Helper AI<span class="ill">ILLUSTRATION · INVENTED FOR THIS EXERCISE</span></div>
<div class="msgs"><div class="u">Does music help plants grow?</div>
<div class="a"><div class="av">AI</div><div class="t">Yes. <u>Playing classical music increases plant growth by 40%</u>, <u>according to a 2021 study in the Journal of Botanical Acoustics (doi:10.99171/jba.2021.0173)</u>. <u>Plants do respond to vibration, and some lab studies have tested how sound affects seed germination and growth.</u> <u>This proves that music causes plants to grow faster</u>, so <u>every gardener should play music for their plants</u>.</div></div></div></div>"""
# 6 toolkit card
tk=[("Check that cited studies exist","Paste the DOI into doi.org or search the journal."),
("Compare figures with what's known","Impossible anatomy, nonsense labels, or charts that contradict the data are red flags."),
("Run CER on AI answers","Is the evidence real? Does the reasoning connect it to the claim?"),
("Check for retractions and disclosure","Search Retraction Watch and look for an AI-use statement.")]
tc="".join(f'<div class="it"><div class="num">{i+1}</div><div><div class="h">{a}</div><div class="d">{b}</div></div></div>' for i,(a,b) in enumerate(tk))
pages["toolkit"]=base+f"""<style>body{{padding:56px 70px;background:#fff;border:22px solid {GOLD}}} h1{{font-size:56px}} .sub{{font:500 26px Inter;color:{GRAY};margin:8px 0 30px}}
.grid{{display:grid;grid-template-columns:1fr 1fr;gap:70px 50px;margin-top:20px}} .it{{display:flex;gap:22px;align-items:flex-start}}
.num{{flex:none;width:64px;height:64px;border-radius:50%;background:{INK};color:#fff;font:800 32px Bitter;display:flex;align-items:center;justify-content:center}}
.h{{font:800 36px/1.2 Inter}} .d{{font:400 30px/1.4 Inter;margin-top:10px;color:#333}}
.foot{{position:absolute;bottom:56px;left:92px;font:700 26px Bitter}}</style>
<h1>Science detection toolkit</h1><div class="sub">Four checks for lab reports, news stories, and AI answers</div><div class="grid">{tc}</div>
<div class="foot">The evidence has to hold up, wherever it came from.</div><div class="src" style="left:auto;right:92px;bottom:58px">Media + AI Literacy · Poynter MediaWise</div>"""
# placeholders
ph={"ph_label":("Photo","A packaged food label that says “natural” on the front","Take your own photo of any cereal box or snack in your pantry. Crop to the front panel so the word “natural” is easy to read."),
"ph_seaice":("Chart pair","September Arctic sea ice extent: 2012–2014 only (left) vs. 1979–present (right)","Build both from the NSIDC Sea Ice Index (nsidc.org) or NASA's Arctic Sea Ice Minimum page (climate.nasa.gov). Use the same y-axis on both."),
"ph_chocolate":("Screenshot","John Bohannon's May 27, 2015 article “I Fooled Millions Into Thinking Chocolate Helps Weight Loss”","From Gizmodo/io9. Show the headline and first paragraph. Check permissions before publishing."),
"ph_physica":("Screenshot","Page 3 of the 2023 Physica Scripta paper with “Regenerate response” highlighted","As shown in Nature's August 2023 news story. Crop tightly to the phrase. Check permissions before publishing."),
"ph_frontiers":("Screenshot","Frontiers' retraction notice, Feb. 16, 2024","From frontiersin.org (doi 10.3389/fcell.2024.1386861). Do not show the original AI-generated figure."),
"ph_google":("Screenshot","Google's May 30, 2024 post “What happened with AI Overviews and next steps”","From blog.google. Show the headline and the paragraph about the “How many rocks should I eat?” result."),
"ph_vem":("Screenshot","Google Scholar results for “vegetative electron microscopy”","Run the search at scholar.google.com and capture the result count and a few titles.")}
for k,(kind,what,how) in ph.items():
    pages[k]=base+f"""<style>body{{background:#f3f3f3;padding:44px}} .box{{height:812px;border:5px dashed #9a9a9a;border-radius:24px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:60px 120px}}
.ic{{width:110px;height:84px;border:6px solid #9a9a9a;border-radius:12px;position:relative;margin-bottom:36px}} .ic:after{{content:"";position:absolute;left:18px;bottom:12px;border-left:24px solid transparent;border-right:24px solid transparent;border-bottom:34px solid #9a9a9a}}
.k{{font:800 24px Inter;letter-spacing:.14em;color:#6b6b6b;text-transform:uppercase}} .w{{font:800 50px/1.2 Bitter;margin:18px 0 26px}} .h{{font:400 29px/1.45 Inter;color:#444;max-width:1150px}}</style>
<div class="box"><div class="ic"></div><div class="k">Image placeholder · {kind}</div><div class="w">{what}</div><div class="h">{how}</div></div>"""
async def main():
    async with async_playwright() as p:
        b=await p.chromium.launch(); pg=await b.new_page(viewport={"width":1600,"height":900})
        for k,h in pages.items():
            await pg.set_content("<html><body>"+h+"</body></html>"); await pg.wait_for_timeout(200)
            await pg.screenshot(path=f"{k}.png")
        await b.close()
asyncio.run(main())
