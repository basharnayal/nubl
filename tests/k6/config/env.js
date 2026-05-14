// tests/k6/config/env.js
// ---------------------------------------------------------------------------
// Centralized environment + feature configuration for the k6 suite.
// Override any value at run time via -e VAR=value on the k6 CLI, e.g.
//   k6 run -e NUBL_BASE_URL=https://staging.nubl.test load.js
// ---------------------------------------------------------------------------

const env = (key, fallback) =>
  __ENV[key] !== undefined && __ENV[key] !== '' ? __ENV[key] : fallback;

export const BASE_URL = env('NUBL_BASE_URL', 'https://staging.nubl.test');

// MyFatoorah sandbox callback URL — controller responds 200/302 here.
// Append ?paymentId=... at call time.
export const PAYMENT_CALLBACK_PATH = '/payments/callback';

// Anchor request-per-minute for the Load test. Scaled per-scenario by the
// workload weights in lib/workload.js.
// Defaults tuned for a DigitalOcean 2 vCPU / 4 GB droplet.
export const LOAD_ANCHOR_RPM      = Number(env('NUBL_LOAD_RPM',      250));
export const ENDURANCE_ANCHOR_RPM = Number(env('NUBL_ENDURANCE_RPM', 150));

// Default sleep / think-time (seconds). Randomized inside helpers.
export const THINK_MIN = Number(env('NUBL_THINK_MIN', 1));
export const THINK_MAX = Number(env('NUBL_THINK_MAX', 4));

// Hard guardrails — refuse to run against prod-prod (vs prod-in-testing) by
// requiring an explicit acknowledgement env var.
export const ALLOW_PRODUCTION = env('NUBL_ALLOW_PRODUCTION', '') === 'YES_I_AM_SURE';

// Optional: skip the auth-protected scenarios entirely. Useful for quick public
// surface smoke tests.
export const PUBLIC_ONLY = env('NUBL_PUBLIC_ONLY', 'false') === 'true';

// Disable iteration sleeps for synthetic constant-arrival-rate testing.
// (Sleeps still happen inside flows for realism unless this is true.)
export const NO_SLEEP = env('NUBL_NO_SLEEP', 'false') === 'true';

// Path to seed-user CSV inside the k6 container / host. Relative to the
// repository root, which is where you should invoke k6 from.
export const USERS_CSV_PATH = env('NUBL_USERS_CSV', 'tests/k6/data/users.csv');

// Maximum donation amount used in donor + guest flows (SAR).
// Kept small to avoid blowing the recipient weekly allowance during endurance.
export const DONATION_MIN = Number(env('NUBL_DONATION_MIN', 1));
export const DONATION_MAX = Number(env('NUBL_DONATION_MAX', 25));
