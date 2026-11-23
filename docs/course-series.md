# The course series

Ten courses, about 30 minutes and 7 lessons each, authored in Articulate Rise and exported as SCORM 1.2. Each passed
course earns a badge and 30 minutes of PD time. The `id` is what the app, the plugin uploader and the database use.

| id | Course | Type | Status |
| --- | --- | --- | --- |
| `start` | Start Here: What media literacy and AI literacy are, and how they connect | Core | Planned |
| `detect` | Evaluating and Detecting AI Material | Core | Planned |
| `science` | Science: Studies, charts and chatbot citations | Subject | **Pilot.** Lessons 1–6 built from `rise-build/`; quiz (lesson 7) set in Rise |
| `social` | Social Studies: Primary sources, quotes and historical images | Subject | Planned |
| `ela` | English Language Arts: Authorship, quotes and AI-written text | Subject | Planned |
| `math` | Math: Statistics, charts and AI-generated numbers | Subject | Planned |
| `arts` | Arts: AI-generated images, music and who made it | Subject | Planned |
| `health` | Health & PE: Wellness claims, influencers and AI health advice | Subject | Planned |
| `cte` | Career & Technical Education: Specs, safety data and AI at work | Subject | Planned |
| `classroom` | Take It to Your Classroom: Lessons, routines and policies | Core | Planned |

**Certificate:** Start Here + Evaluating and Detecting + Take It to Your Classroom + at least one subject course,
plus the pretest and posttest.

Until a course's real Rise package is uploaded, the app plays the one-lesson sample in `courses/sample/`.

## Exporting a course from Rise

1. Rise > course > **Publish** > **LMS**.
2. Format **SCORM 1.2**. Tracking: **Quiz result** (passing the lesson 7 quiz), report status **Passed/Incomplete**.
3. Download the zip and upload it on the course's row under **WP admin > MediaWise Courses** (plugin route),
   or unzip it into `courses/<id>/` (folder route) and add it to `courses/catalog.json`.

The same zip goes to partner LMSs.
