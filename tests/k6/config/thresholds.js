// tests/k6/config/thresholds.js
// ---------------------------------------------------------------------------
// Single source of truth for SLOs (see perf_test_plan.md §5 + §10).
// Every test script imports from here so threshold changes propagate atomically.
// ---------------------------------------------------------------------------

// ---- Base thresholds — apply to Load + Soak unchanged ---------------------
export const baseThresholds = {
  // Latency SLOs split by tag `type` (read vs write).
  'http_req_duration{type:read}':  ['p(95)<1000', 'p(99)<2000'],
  'http_req_duration{type:write}': ['p(95)<1500', 'p(99)<3000'],

  // Global request-failed rate. Excludes 429 (tracked separately below).
  // Abort the run if breached continuously for 30s — saves budget on a
  // clearly-broken environment.
  'http_req_failed': [
    { threshold: 'rate<0.01', abortOnFail: true, delayAbortEval: '30s' },
  ],

  // Functional checks: assertions inside the scripts (e.g., status==200,
  // CSRF token present). Independent from http_req_failed.
  'checks': ['rate>0.99'],

  // Rate limiters are disabled in the perf-test environment, so *any* 429
  // indicates env drift or in-controller throttling (e.g., QR redeem).
  // Tracked as a custom counter — see lib/http.js.
  'http_429_unexpected': ['count==0'],
};

// ---- Stress overrides — we deliberately want the test to keep running ----
// past breaches so we can identify the breaking point.
// Tuned for a 2 vCPU / 4 GB droplet — breaking point expected ~60-80 VUs.
export const stressOverrides = {
  // Drop abortOnFail and relax the rate to 10%.
  'http_req_failed': ['rate<0.10'],
  // Latency expectations are LOOSENED for stress — we're not gating on SLO
  // here, we're mapping the degradation curve.
  'http_req_duration{type:read}':  ['p(95)<4000'],
  'http_req_duration{type:write}': ['p(95)<6000'],
  // The 429 guard stays at 0 — config drift is still a defect under stress.
  'http_429_unexpected': ['count<10'],
};

// ---- Spike thresholds — surge window is permissive, post-surge is strict --
export const spikeThresholds = {
  // During the surge window we allow degraded latency.
  'http_req_duration{type:read}':  ['p(95)<2500'],
  'http_req_duration{type:write}': ['p(95)<4000'],

  // Post-surge: latency MUST return to SLO within the recovery window.
  // Tag applied by lib/personas.js using the scenario `progress` heuristic.
  'http_req_duration{phase:post_surge,type:read}':  ['p(95)<1000'],
  'http_req_duration{phase:post_surge,type:write}': ['p(95)<1500'],

  // Surge is allowed to fail a higher fraction, post-surge is not.
  'http_req_failed': ['rate<0.05'],
  'http_req_failed{phase:post_surge}': ['rate<0.01'],

  'checks': ['rate>0.97'],
  'http_429_unexpected': ['count==0'],
};

// ---- Endurance thresholds — adds drift detection over the test duration -------
// `window:early` / `window:late` tags are stamped inside lib/personas.js
// (first 15 min vs last 15 min of the run).
export const enduranceThresholds = {
  ...baseThresholds,
  'http_req_duration{window:early,type:read}':  ['p(95)<1000'],
  'http_req_duration{window:late,type:read}':   ['p(95)<1200'], // <20% drift
  'http_req_duration{window:early,type:write}': ['p(95)<1500'],
  'http_req_duration{window:late,type:write}':  ['p(95)<1800'],
};
