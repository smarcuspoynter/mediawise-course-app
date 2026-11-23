# MediaWise AI Literacy course app

A free micro-series of AI literacy courses for teachers, from [MediaWise](https://www.poynter.org/mediawise/) at the
Poynter Institute, funded by Carnegie Corporation of New York. Teachers sign in with name and email, take short
Articulate Rise courses on a poynter.org page, earn a badge per course and PD hours, and get a certificate.

**Live demo:** https://smarcuspoynter.github.io/mediawise-course-app/ (every course plays a one-lesson sample for now)

## What's in this repository

| Folder | What it is | Status |
| --- | --- | --- |
| `index.html`, `brand/`, `lib/`, `courses/sample/` | The course app. One static HTML page, no build step. GitHub Pages serves it as the demo. | Working demo |
| `plugin/` | WordPress plugin v0.3.0: shortcode, page script, admin uploader for each course's Rise zip. Build the installable zip with `plugin/build.sh`. | Tested on WordPress 6.5 (local) |
| `embed/` | The no-plugin route: paste-in code for a Custom HTML block. This is what runs on the WordPress dev site now. | Live on the dev site |
| `database/` | Supabase design: tables, access rules, sign-in function, staff report | Schema tested; sign-in function written, not yet deployed |
| `rise-build/` | Spec and scripts that build course lessons in Articulate Rise | Used for the Science pilot |
| `docs/` | How it works, decisions, the course list | |

Start with [`docs/architecture.md`](docs/architecture.md).

## Decisions for IT

1. **Hosting:** the plugin or the folder + embed code. Both are documented in `docs/architecture.md`; both keep the app and courses on poynter.org, which SCORM tracking needs.
2. **Learner data:** Supabase (designed and tested, `database/`), the WordPress database or Google Sheets. **Until one is connected, progress is saved only in each teacher's browser.**

## Running it locally

Any static web server works:

```sh
python3 -m http.server 8000
# open http://localhost:8000/
```

Opening `index.html` straight from disk won't play courses, because browsers block SCORM's frame scripting on `file://`.

## Plugin

```sh
bash plugin/build.sh     # writes plugin/dist/mediawise-course-app-0.3.0.zip
```

The zip (about 6 MB, mostly the sample course) isn't committed. Install it in WP admin > Plugins > Add New > Upload, activate it, put `[mediawise_course]` on a full-width page,
and upload each Rise SCORM 1.2 zip under **MediaWise Courses**. Courses go in `wp-content/uploads/mediawise-courses/`
and survive plugin updates. See `plugin/mediawise-course-app/includes/` for the code.

## Folder route with real courses

Unzip each Rise export into `<folder>/courses/<id>/`, list them in `<folder>/courses/catalog.json`:

```json
{ "science": { "path": "science", "version": "<coursePackageVersion from scormdriver/indexAPI.html>" } }
```

and load the app as `index.html?courses=/<folder>/courses`.

## Third-party code

- [scorm-again](https://github.com/jcputney/scorm-again) (MIT), `lib/scorm12.min.js`
- `courses/sample/` is an Articulate Rise export; Rise's runtime files are Articulate's.

Questions: the MediaWise team at Poynter.
