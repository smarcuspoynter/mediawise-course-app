# Decisions and status

Newest first.

| Date (2026) | Decision or change |
| --- | --- |
| Nov. 23 | Repository assembled for IT review: app, plugin v0.3.0, database design, embed code, Rise build scripts, docs. |
| Nov. 23 | Plugin v0.3.0 rebuilt around the current app: shortcode, page script, admin uploader per course, catalog of uploads. Tested on WordPress with SQLite. The Nov. 16 v0.2.0 plugin is retired. |
| Nov. 23 | Plugin and folder-plus-embed presented to IT as equal options. |
| Nov. 23 | Embed code on the WordPress dev site: auto-height frame, header-aware scrolling, page locked while a course is open, theme's empty space above the footer removed. App footer removed. |
| Nov. 22 | Demo hosted on GitHub Pages (team works on GitHub). Netlify dropped: its injected toolbar frame stopped Rise courses from starting. |
| Nov. 22 | Fix: saved resume data is tied to the Rise package version; fix: Rise launcher can start only once and is nudged if it stalls. |
| Nov. 22 | **Sign-in is name + email only. No password, no verification email.** Email link and Google/Microsoft sign-in dropped. |
| Nov. 22 | App shell rebuilt for the 10-course micro-series (core path + subject courses, record and certificate sidebar). |
| Nov. 18 | Every course is about 30 minutes, 7 lessons, built in the existing Rise course shells. Science is the pilot. Teacher videos dropped; courses open with a real-case challenge. Every claim links to a primary or highly reliable source. |
| Nov. 16 | Database: Supabase planned. Hosting: a page on Poynter's WordPress site, not a subdomain. |
| Nov. | A standalone app instead of LearnDash (no paywall, no LearnDash signup; Tin Canny tried earlier and dropped). |
| Oct. 27 | The single course became a micro-series: Start Here, Evaluating and Detecting AI Material, 7 subject courses, Take It to Your Classroom. |
| Oct. | Courses authored in Articulate Rise only (no Storyline), exported as SCORM 1.2 (not Rise's plain web export, which has no tracking). Partner LMSs get the same zips. |

## Open items

- IT picks the hosting route (plugin or folder) and the learner data option (Supabase, WordPress database or Google Sheets).
- Connect the app to the chosen database. The Supabase design is written and tested; the WordPress and Sheets versions aren't built.
- Decide how to handle someone typing another person's email (accept the risk, or require name and email to match). Today a returning email keeps its stored name.
- Final pretest and posttest items (3 sample items now).
- Export each Rise course as SCORM 1.2 with completion set to passing the course quiz.
- Build the other 9 courses in the Science pattern after the Science pilot is approved.
- Privacy notice on the course page, a retention rule for old records, and a way for teachers to ask for deletion.
- Test on district networks and Chromebooks during the pilot.
- If the grant needs a funder credit on the page, add it as page text (the app's footer was removed).
- Move this repository to a Poynter GitHub organization if IT prefers.
