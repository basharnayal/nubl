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

