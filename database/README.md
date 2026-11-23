# Learner data

The app is built to save each teacher's progress to a database once Poynter IT picks one of three options
(see the main README). **Today the app keeps progress in each learner's own browser**, which is fine for
the demo but not for launch: progress doesn't follow a teacher to another device, and staff can't run reports.

This folder holds the design for the Supabase option, the one built for 100,000 teachers.

| File | What it is | Status |
| --- | --- | --- |
| `supabase/schema.sql` | Tables, badge rule, row-level security, sign-in lookup, staff report | Written and tested in Postgres 16 (results below). Not deployed. |
| `supabase/functions/sign-in/index.ts` | Takes a name and email, returns a token that unlocks only that teacher's rows | Written, not yet run. See the notes at the top of the file. |

## Tables

| Table | One row per | Holds |
| --- | --- | --- |
| `profiles` | teacher | email (unique), name, sign-up and last-seen dates |
| `progress` | teacher × course | SCORM status, percent, Rise package version, resume data, time spent |
| `assessments` | teacher × pre/post | score, total, confidence (1–5), answers |
| `badges` | teacher × course | when the badge was earned (written only by the database) |
| `staff_report` (view) | teacher | email, name, pre/post scores, badges, PD minutes, courses done, core path done |

## How sign-in and access work

1. A teacher types a name and email in the app.
2. The app sends them to the `sign-in` function, which finds or creates the teacher with the service key
   (`find_or_create_learner`) and returns a token for that teacher.
3. With that token the app can read and write **only that teacher's rows**. Row-level security enforces it in the
   database, not in the app.
4. Badges come from a database rule: when a course's status becomes passed or completed, the badge is added.
   Learners can't add badges themselves.
5. Staff read `staff_report` in the Supabase dashboard (or export it as CSV). Learners can't read it.

Typing someone else's email signs you in as them. That's the trade-off of no password and no verification,
noted as an open decision for IT. One guard is built in: a returning email keeps its stored name, so it can't be renamed.

## Test results (Nov. 23, 2026, Postgres 16 with Supabase-style roles stubbed in)

- New sign-ins create learners; a repeat sign-in with a different name keeps the stored name
- Anonymous visitors can't call the sign-in lookup
- Passing a course awards its badge automatically
- A learner can't write another learner's progress, give themselves a badge, change their email, or read the staff report
- A second learner sees none of the first learner's rows
- The staff report shows scores, badges, PD minutes (30 per badge) and whether the core path is done

## If IT picks a different option

- **WordPress database:** the same four tables become plugin tables, and the sign-in step becomes a WordPress
  REST endpoint in the plugin. Not built yet.
- **Google Sheets:** one sheet per table, written through a Google Apps Script web app. Fine for a pilot, not for
  100,000 teachers. Not built yet.
