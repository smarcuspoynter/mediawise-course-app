# Embedding the app in a WordPress page without the plugin (Nov. 23, 2026)

**Installed and working on the dev page** https://poydev.wpengine.com/mediawise/programs/reboot-media-and-ai-literacy-for-educators/ (classic editor, Code tab; page ID 1169985). 

What it does (with the app's "embedded mode", live on GitHub Pages and the claude.ai prototype):
- Full-width frame, even inside the theme's narrow content column.
- The frame grows and shrinks to the app's height, so the page scrolls as one (no scrollbar inside the frame).
- On every screen change, if you've scrolled into the app, the page scrolls so the app starts just below the WordPress admin bar and Poynter's sticky header. The course player is lined up under the header and fills the rest of the window.
- **While a course is open, the page itself is locked** (no page scrolling), so reaching the end of a Rise lesson doesn't drag the site footer into view. Leaving the course unlocks it. (App sends {type:"player", open}.) The lock is self-correcting: if anything moves the page while a course is open (browser scroll restoration on reload, late layout changes), it snaps the course back under the header. Only the window (`html`) is locked; locking `body` too broke the sticky Poynter header. First version (Nov. 23, 12:15 am) locked before aligning and left the course stuck below the banner; fixed the same night.
- **No empty space between the app and the site footer:** the script removes the bottom margin of the app's section, hides empty theme sections after it, and hides the theme's `#sidebar-above-footer` widget band when it's empty (it is on this page). Gap went from ~190 px plus a 96 px empty green band to 0.
- Pop-ups, toasts and the presenter "Demo" button are placed in the visible part of the frame.
- App footer (Poynter logo + Carnegie line) removed at Sean's request, everywhere. If the grant needs a funder credit, add it as normal page text.

## WordPress gotchas (handled in the code below)
1. **WordPress rewrites some characters in post content**, even inside `<script>`: `&&` became `&#038;&#038;`, which broke the script. The script is written with no `&` or `<` characters. Keep it that way when editing.
2. **The Poynter theme (elumine-child) rebuilds the page content after load**, so a script that grabbed the iframe once ended up watching a detached copy. The script looks the iframe up by id on every message.
3. GitHub Pages lets browsers cache the app for up to 10 minutes, so app updates can take that long to show.

Verified on poydev: header detected at 90 px (admin bar + Poynter bar); course player 715 px tall in an 805 px window, top exactly under the header; frame resizes to each screen (812 px sign-in, ~2,080 px home); app ends directly at the dark site footer.

How the two talk: the app posts {source:"mwca", type:"ready"|"height"|"top"|"player"} to the page; the page script posts {source:"mwca-host", type:"viewport", top, vis, avail} back, only to https://smarcuspoynter.github.io. If the app moves to another host, change `src` and `APP` in the code.

The code itself is in [`embed-code.html`](embed-code.html). Paste the whole file into a **Custom HTML** block.

The plugin does the same thing: its shortcode loads this script as a real file (`plugin/mediawise-course-app/assets/embed.js`), with one addition: it measures the page to make the app full width, because WordPress block themes override the CSS full-width trick used here. If you use this embed code on a block theme and the app stays narrow, use the plugin's version of the script.
