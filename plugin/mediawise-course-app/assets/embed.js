/* MediaWise Course App, page side (loaded by the [mediawise_course] shortcode).
   Sizes the app's frame to the app so the page scrolls as one, keeps the app just below the sticky site header,
   locks page scrolling while a course is open, and removes the theme's empty space above the footer.
   Talks to the app with postMessage: app -> page {source:"mwca", type:"ready"|"height"|"top"|"player"},
   page -> app {source:"mwca-host", type:"viewport", top, vis, avail}.
   The same script is pasted into a Custom HTML block for the no-plugin setup (see embed/), which is why it
   avoids ampersands and less-than signs: WordPress rewrites those inside post content. */
(function(){
  function F(){ return document.getElementById('mwca-app'); }  // looked up each time: some themes rebuild the page after load
  function appOrigin(){ var f = F(); try { return f ? new URL(f.src, location.href).origin : ''; } catch(e){ return ''; } }
  function headerBottom(){  // bottom edge of the bars pinned to the top of the window (admin bar, sticky site header)
    var bars = [];
    document.querySelectorAll('#wpadminbar, header, nav, [class*="header"], [class*="sticky"]').forEach(function(el){
      var cs = getComputedStyle(el);
      if(['fixed', 'sticky'].indexOf(cs.position) === -1) return;
      var r = el.getBoundingClientRect();
      if(r.top > -2) if(250 > r.top) if(r.height > 0) if(300 > r.height) if(r.width > innerWidth / 2) bars.push(r);
    });
    bars.sort(function(a, b){ return a.top - b.top; });
    var b = 0;
    bars.forEach(function(r){ if(!(r.top > b + 2)) b = Math.max(b, r.bottom); });
    return b;
  }
  function tidyBelow(){  // remove the theme's empty space between the app and the site footer
    var f = F(), sec = f ? f.closest('section') : null;
    if(!sec) return;
    sec.style.marginBottom = '0px';
    var band = document.getElementById('sidebar-above-footer');  // Poynter theme's widget area above the footer
    if(band) if(!band.innerText.trim()) if(!band.querySelector('img, iframe, video, form')) band.style.display = 'none';
    for(var s = sec.nextElementSibling; s; s = s.nextElementSibling){
      if(!s.innerText.trim()) if(!s.querySelector('img, iframe, video, form')) s.style.display = 'none';
    }
  }
  function fullWidth(){  // stretch the app's wrapper to the window's full width, whatever the theme's content column does
    var f = F(), w = f ? f.parentElement : null;
    if(!w) return;
    ['max-width:none', 'position:relative', 'left:0', 'margin-right:0', 'margin-left:0'].forEach(function(rule){
      var kv = rule.split(':'); w.style.setProperty(kv[0], kv[1], 'important');
    });
    w.style.setProperty('width', document.documentElement.clientWidth + 'px', 'important');
    w.style.setProperty('margin-left', (-w.getBoundingClientRect().left) + 'px', 'important');
  }
  var locked = false;
  function align(){  // put the top of the app just under the sticky header
    var f = F();
    if(!f) return;
    var y = Math.max(0, Math.round(f.getBoundingClientRect().top + scrollY - headerBottom()));
    if(Math.abs(scrollY - y) > 2) scrollTo(0, y);
  }
  function lockPage(on){  // while a course is open, keep it lined up under the header and stop the page scrolling
    locked = !!on;
    document.documentElement.style.overflow = on ? 'hidden' : '';  // window only: locking body too would stop the site header sticking
    if(on){ align(); setTimeout(align, 150); setTimeout(align, 600); setTimeout(align, 1500); }
  }
  function send(){
    var f = F();
    if(!f || !f.contentWindow) return;
    var r = f.getBoundingClientRect(), hb = headerBottom();
    var vis = Math.min(innerHeight, r.bottom) - Math.max(hb, r.top);
    f.contentWindow.postMessage({ source: 'mwca-host', type: 'viewport',
      top: Math.round(Math.max(0, hb - r.top)), vis: Math.round(vis > 120 ? vis : innerHeight - hb),
      avail: Math.round(innerHeight - hb) }, appOrigin());
  }
  addEventListener('message', function(e){
    var f = F();
    if(!f || e.source !== f.contentWindow || e.origin !== appOrigin()) return;
    var d = e.data || {};
    if(d.source !== 'mwca') return;
    if(d.type === 'height'){ f.style.height = d.h + 'px'; tidyBelow(); fullWidth(); }
    if(d.type === 'ready'){ fullWidth(); }
    if(d.type === 'player'){ lockPage(d.open); }
    if(d.type === 'top'){
      var y = f.getBoundingClientRect().top + scrollY - headerBottom();
      if(d.force || scrollY > y) scrollTo(0, Math.max(0, y));
    }
    send();
  });
  addEventListener('scroll', function(){ if(locked) align(); send(); }, { passive: true });  // undo anything that moves the page while a course is open
  addEventListener('resize', function(){ fullWidth(); if(locked) align(); send(); });
  fullWidth();
})();
