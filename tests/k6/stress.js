// tests/k6/stress.js
// ---------------------------------------------------------------------------
// STRESS TEST — perf_test_plan.md §7 / §9.3
//
// Goal: find the BREAKING POINT (the VU level at which p95 first exceeds 2s
//       OR error rate first exceeds 5%).
// Profile: stepped ramp 50 → 100 → 200 → 300 → 500 VUs, 2 min per step.
// Thresholds: relaxed — we want the test to run all stages so post-run
//             analysis can identify the breaking step.
//
// Usage:
//   k6 run --out json=reports/stress.json tests/k6/stress.js
// ---------------------------------------------------------------------------

import { baseThresholds, stressOverrides } from './config/thresholds.js';

export { weightedJourney } from './lib/personas.js';

export const options = {
  scenarios: {
    breakpoint: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '2m', target: 50  },
        { duration: '2m', target: 100 },
        { duration: '2m', target: 200 },
        { duration: '2m', target: 300 },
        { duration: '2m', target: 500 },
        { duration: '1m', target: 0   },
      ],
      exec: 'weightedJourney',
      gracefulRampDown: '30s',
      gracefulStop: '30s',
    },
  },
  thresholds: {
    ...baseThresholds,
    ...stressOverrides,
  },
  noConnectionReuse: false,
  userAgent: 'k6-nubl-stress/1.0',
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

// Print stage-by-stage breakdown so we can pick the breaking point manually.
// (k6's JSON output also has per-second granularity if needed.)
export function handleSummary(data) {
  const lines = [
    '',
    '=== NUBL Stress Test — Final Summary ===',
    `Total requests: ${data.metrics.http_reqs?.values?.count ?? 'n/a'}`,
    `Overall failed: ${(data.metrics.http_req_failed?.values?.rate * 100).toFixed(2)} %`,
    `Read p95:       ${pct(data, 'http_req_duration{type:read}',  'p(95)')} ms`,
    `Read p99:       ${pct(data, 'http_req_duration{type:read}',  'p(99)')} ms`,
    `Write p95:      ${pct(data, 'http_req_duration{type:write}', 'p(95)')} ms`,
    `Write p99:      ${pct(data, 'http_req_duration{type:write}', 'p(99)')} ms`,
    'Use per-second JSON output to identify the stage where p95 first breached 2000 ms.',
    '',
  ];
  return {
    'stdout': lines.join('\n'),
    'reports/stress-summary.json': JSON.stringify(data, null, 2),
  };
}

function pct(data, metric, p) {
  const v = data.metrics?.[metric]?.values?.[p];
  return v === undefined ? 'n/a' : v.toFixed(0);
}
