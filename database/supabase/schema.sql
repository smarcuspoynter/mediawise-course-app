-- MediaWise Course App: learner data schema for Supabase (Postgres).
--
-- Status: written and tested against Postgres 16 with Supabase-style roles stubbed in
-- (see database/README.md). Not yet deployed to a Supabase project, and the app does not call it yet:
-- the current app keeps progress in each learner's browser until Poynter IT picks a data option.
--
-- What it stores: each teacher's name and email, course progress, pre/posttest answers and badges.
-- No passwords and no student data.
--
-- Sign-in model (decided Nov. 22, 2026): name + email, no password, no verification email.
-- Supabase Auth is not used. The sign-in Edge Function (functions/sign-in) calls
-- find_or_create_learner() with the service key and returns a short-lived token whose "sub" is the
-- learner's id, so auth.uid() works in the row-level security policies below.

create extension if not exists citext;

-- ---------------------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------------------

create table if not exists public.profiles (
  id            uuid primary key default gen_random_uuid(),
  email         citext not null unique check (char_length(email) between 3 and 254 and position('@' in email) > 1),
  full_name     text   not null check (char_length(full_name) between 1 and 200),
  created_at    timestamptz not null default now(),
  last_seen_at  timestamptz not null default now()
);

-- One row per learner per course. Mirrors the SCORM 1.2 data the app saves when a course commits.
create table if not exists public.progress (
  user_id          uuid not null references public.profiles(id) on delete cascade,
  course_id        text not null check (course_id in ('start','detect','science','social','ela','math','arts','health','cte','classroom')),
  status           text not null default 'not attempted'
                   check (status in ('not attempted','browsed','incomplete','completed','passed','failed')),
  pct              smallint not null default 0 check (pct between 0 and 100),
  pkg_version      text,          -- Rise coursePackageVersion the resume data belongs to
  suspend_data     text check (char_length(suspend_data) <= 65536),
  lesson_location  text check (char_length(lesson_location) <= 1000),
  total_time       text,          -- SCORM 1.2 HHHH:MM:SS
  updated_at       timestamptz not null default now(),
  primary key (user_id, course_id)
);

create table if not exists public.assessments (
  user_id     uuid not null references public.profiles(id) on delete cascade,
  kind        text not null check (kind in ('pre','post')),
  score       smallint not null check (score >= 0),
  total       smallint not null check (total > 0),
  confidence  smallint check (confidence between 1 and 5),
  answers     jsonb not null default '{}'::jsonb,
  created_at  timestamptz not null default now(),
  primary key (user_id, kind)
);

-- Written only by the trigger below, never directly by the app.
create table if not exists public.badges (
  user_id     uuid not null references public.profiles(id) on delete cascade,
  course_id   text not null,
  awarded_at  timestamptz not null default now(),
  primary key (user_id, course_id)
);

-- ---------------------------------------------------------------------------
-- Badge rule: a course that reports passed or completed earns its badge (once).
-- ---------------------------------------------------------------------------

create or replace function public.award_badge() returns trigger
language plpgsql security definer set search_path = public as $$
begin
  if new.status in ('passed', 'completed') then
    insert into public.badges (user_id, course_id) values (new.user_id, new.course_id)
    on conflict do nothing;
  end if;
  new.updated_at := now();
  return new;
end $$;

drop trigger if exists progress_award_badge on public.progress;
create trigger progress_award_badge
  before insert or update on public.progress
  for each row execute function public.award_badge();

-- ---------------------------------------------------------------------------
-- Row-level security: a learner can reach only their own rows.
-- ---------------------------------------------------------------------------

alter table public.profiles    enable row level security;
alter table public.progress    enable row level security;
alter table public.assessments enable row level security;
alter table public.badges      enable row level security;

drop policy if exists "own profile: read"   on public.profiles;
drop policy if exists "own profile: rename" on public.profiles;
create policy "own profile: read"   on public.profiles for select to authenticated using (id = auth.uid());
create policy "own profile: rename" on public.profiles for update to authenticated using (id = auth.uid()) with check (id = auth.uid());
revoke update on public.profiles from authenticated;
grant  update (full_name) on public.profiles to authenticated;   -- name only; email can't be changed by the learner

drop policy if exists "own progress" on public.progress;
create policy "own progress" on public.progress for all to authenticated using (user_id = auth.uid()) with check (user_id = auth.uid());

drop policy if exists "own assessments" on public.assessments;
create policy "own assessments" on public.assessments for all to authenticated using (user_id = auth.uid()) with check (user_id = auth.uid());

drop policy if exists "own badges: read" on public.badges;
create policy "own badges: read" on public.badges for select to authenticated using (user_id = auth.uid());

revoke all on public.profiles, public.progress, public.assessments, public.badges from anon;
grant select on public.profiles to authenticated;
grant select, insert, update on public.progress, public.assessments to authenticated;
grant select on public.badges to authenticated;

-- ---------------------------------------------------------------------------
-- Sign-in: find the learner by email or create them. Called only by the sign-in Edge Function
-- with the service key; learners and anonymous visitors can't call it.
-- An existing learner keeps their stored name (someone typing another person's email can't rename them).
-- ---------------------------------------------------------------------------

create or replace function public.find_or_create_learner(p_email text, p_name text)
returns table (id uuid, full_name text, is_new boolean)
language plpgsql security definer set search_path = public as $$
declare
  v_email citext := lower(trim(p_email));
  v_name  text   := left(trim(p_name), 200);
begin
  if v_email is null or position('@' in v_email) < 2 then
    raise exception 'invalid email';
  end if;
  if v_name is null or v_name = '' then
    raise exception 'name required';
  end if;
  return query
    insert into public.profiles as p (email, full_name) values (v_email, v_name)
    on conflict (email) do update set last_seen_at = now()
    returning p.id, p.full_name, (xmax = 0);
end $$;

revoke execute on function public.find_or_create_learner(text, text) from public, anon, authenticated;
grant  execute on function public.find_or_create_learner(text, text) to service_role;

-- ---------------------------------------------------------------------------
-- Staff report (for Poynter staff and grant reporting). Not visible to learners:
-- read it in the Supabase dashboard or with the service key, or export it as CSV.
-- ---------------------------------------------------------------------------

create or replace view public.staff_report as
select
  p.email,
  p.full_name,
  p.created_at                                   as signed_up,
  p.last_seen_at                                 as last_seen,
  pre.score                                      as pretest_score,
  post.score                                     as posttest_score,
  count(b.course_id)                             as badges,
  count(b.course_id) * 30                        as pd_minutes,
  string_agg(b.course_id, ', ' order by b.awarded_at) as courses_completed,
  count(*) filter (where b.course_id in ('start','detect','classroom')) = 3
    and count(*) filter (where b.course_id in ('science','social','ela','math','arts','health','cte')) >= 1
                                                 as core_path_done
from public.profiles p
left join public.badges b         on b.user_id = p.id
left join public.assessments pre  on pre.user_id = p.id and pre.kind = 'pre'
left join public.assessments post on post.user_id = p.id and post.kind = 'post'
group by p.id, p.email, p.full_name, p.created_at, p.last_seen_at, pre.score, post.score;

revoke all on public.staff_report from public, anon, authenticated;
grant select on public.staff_report to service_role;
