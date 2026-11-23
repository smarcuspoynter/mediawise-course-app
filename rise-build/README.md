# Rise build scripts

How the 30-minute courses are written and pushed into Articulate Rise. The courses themselves live in Rise
(Poynter's Articulate account); these files are the source we write them from.

| File | What it is |
| --- | --- |
| `science_spec_v2.js` | The Science course as data: lessons 1–6, each a list of blocks, with every source linked. The current build. (Lesson 7, the course check, is a Rise quiz lesson.) |
| `rise_builder.js` | Turns spec blocks into Rise's own block format and saves them through the open Rise editor. |
| `science_visuals_make.py` | Draws the course's original graphics (opener, CER diagram, charts, chatbot mockup, toolkit card) and the labeled image placeholders, as 1600×900 PNGs. |

## The pattern every subject course follows (about 30 minutes)

1. **Welcome and your toolkit:** a real-case opener challenge (a graphic of 4 real claims plus a "where did this come from?" sort), objectives, recap flashcards, a warm-up check.
2. **Media literacy in the subject:** a process walkthrough (CER in Science), a case in tabs, a sorting activity, concept flashcards, a check.
3. **A classic non-AI case:** a prediction check, a process walkthrough, an accordion, a statement, a check.
4. **AI cases:** a timeline of 5 dated real cases, a "what would have caught it?" sort, accordion takeaways, 2 checks.
5. **Evaluate This lab:** an AI answer, then a branching scenario (wrong choices get feedback and a retry).
6. **Take it to your classroom:** a numbered toolkit, tabs for grades 6–12, K–5 and a 5-minute warm-up, takeaways, and an "All sources" accordion.
7. **Course check:** a 3-question Rise quiz, pass at 2 of 3, unlimited retries, answers shuffled.

All native Rise blocks, no Storyline. Every factual claim links to a primary or highly reliable source.

## How a build runs

1. Open the course in the Rise editor, signed in to Poynter's Articulate account.
2. In the browser console, load the block templates Rise uses (`window.__tpl`) and the editor's request headers
   (`window.__hdr`) from the open session, then load `rise_builder.js` and the spec.
3. For each lesson, `__RB.createBlocks(courseId, lessonId, blocks)` sends Rise's own editor actions
   (`rise/lessons/CREATE_BLOCKS`, and `CREATE_LESSON`, `DELETE_BLOCKS`, `UPDATE_LESSON` as needed) to
   `/api/rise-runtime/ducks/<action>`. The blocks are copies of Rise's default blocks with our content filled in.
4. Graphics: run `science_visuals_make.py` (needs Python, Playwright, and the Inter and Bitter fonts), then upload the
   PNGs through Rise's own upload steps or by hand in the editor.

The result is an ordinary Rise course that anyone on the team can edit by hand. The script relies on Rise's internal
editor actions, which Articulate can change without notice; if it breaks, courses can still be edited in Rise as usual.

## Spec format

Each block is an array: `[kind, ...content]`.

| Kind | Content |
| --- | --- |
| `p`, `note`, `statement_b`, `statement_d` | HTML |
| `sub` | heading text |
| `hp` | heading, HTML paragraph |
| `bullets`, `numbered` | list of HTML items |
| `tabs`, `accordion` | list of `[title, HTML]` |
| `flash` | list of `[front HTML, back HTML]` |
| `sorting` | pile names, list of `[item, pile index]` |
| `mc` | question HTML, list of `[answer, correct?]`, feedback HTML |
| `timeline` | list of `[date, title, HTML, image key]` |
| `process` | `{title, desc}`, list of `[step title, HTML]`, `{title, desc}` summary |
| `scenario` | character name, list of slides (`{type:"text", title, desc}` or `{type:"dialogue", desc, responses:[[text, correct?, feedback]]}`) |
| `img` | image key (a PNG from the visuals script), caption HTML. Placed by the image upload step, not by `rise_builder.js`. |

Only Science is built in this format so far (reviewed Nov. 18, 2026). The other 9 courses follow once the Science pilot is approved.
