// MediaWise Course App: sign-in Edge Function (Supabase, Deno).
//
// POST { "name": "Ana Teacher", "email": "ana@school.org" }
//   -> 200 { "token": "<JWT>", "learner": { "id": "...", "name": "Ana Teacher", "isNew": true } }
//
// Name + email only: no password, no verification email (decided Nov. 22, 2026).
// The token's "sub" is the learner's id and its role is "authenticated", so the row-level security
// policies in ../../schema.sql let it read and write only that learner's rows.
//
// Status: written, NOT yet deployed or run. Check before use:
//   - Newer Supabase projects sign tokens with asymmetric signing keys. If the project still accepts
//     its legacy JWT secret, set MWCA_JWT_SECRET to it (Project Settings > API). Otherwise, switch this
//     function to mint tokens the project accepts; the rest of the design is unchanged.
//   - Set ALLOWED_ORIGIN to the site that hosts the app, e.g. https://www.poynter.org
//
// Secrets: supabase secrets set MWCA_JWT_SECRET=... ALLOWED_ORIGIN=https://www.poynter.org
// Deploy:  supabase functions deploy sign-in --no-verify-jwt   (visitors have no token yet)

import { createClient } from "npm:@supabase/supabase-js@2";
import { SignJWT } from "npm:jose@5";

const ORIGIN = Deno.env.get("ALLOWED_ORIGIN") ?? "";
const TOKEN_DAYS = 30;

const cors = {
  "Access-Control-Allow-Origin": ORIGIN,
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Headers": "content-type, apikey, authorization",
  "Vary": "Origin",
};

const reply = (status: number, body: unknown) =>
  new Response(JSON.stringify(body), { status, headers: { ...cors, "content-type": "application/json" } });

Deno.serve(async (req) => {
  if (req.method === "OPTIONS") return new Response(null, { headers: cors });
  if (req.method !== "POST") return reply(405, { error: "POST only" });
  if (ORIGIN && req.headers.get("origin") !== ORIGIN) return reply(403, { error: "origin not allowed" });

  let name = "", email = "";
  try {
    const body = await req.json();
    name = String(body?.name ?? "").trim().slice(0, 200);
    email = String(body?.email ?? "").trim().toLowerCase().slice(0, 254);
  } catch {
    return reply(400, { error: "send JSON: { name, email }" });
  }
  if (!name) return reply(400, { error: "Please enter your name." });
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return reply(400, { error: "Please enter a valid email." });

  const admin = createClient(Deno.env.get("SUPABASE_URL")!, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!, {
    auth: { persistSession: false },
  });
  const { data, error } = await admin.rpc("find_or_create_learner", { p_email: email, p_name: name });
  if (error || !data?.[0]) return reply(500, { error: "Could not sign in. Please try again." });
  const learner = data[0] as { id: string; full_name: string; is_new: boolean };

  const secret = new TextEncoder().encode(Deno.env.get("MWCA_JWT_SECRET")!);
  const token = await new SignJWT({ role: "authenticated" })
    .setProtectedHeader({ alg: "HS256", typ: "JWT" })
    .setSubject(learner.id)
    .setAudience("authenticated")
    .setIssuedAt()
    .setExpirationTime(`${TOKEN_DAYS}d`)
    .sign(secret);

  return reply(200, { token, learner: { id: learner.id, name: learner.full_name, isNew: learner.is_new } });
});
