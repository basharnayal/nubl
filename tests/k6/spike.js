// tests/k6/spike.js
// ---------------------------------------------------------------------------
// SPIKE TEST — perf_test_plan.md §7 / §9.2
//
// Goal: validate sudden-surge survivability.
// Profile: ramping-vus from 5 → 80 in 30s, hold 1m at 80, then recover
//          back to 5 for 2m30s. Total ~6m30s.
// Tags: requests in the post-surge window are tagged `phase:post_surge` so
//       the recovery latency can be validated separately.
//
// Usage:
//   k6 run --out json=reports/spike.json tests/k6/spike.js
// ---------------------------------------------------------------------------

import { spikeThresholds } from './config/thresholds.js';

// Re-export the weighted dispatcher (used as `exec`).
export { weightedJourney } from './lib/personas.js';

// Turn on the spike-phase tagger inside lib/personas.js
if (!__ENV.NUBL_TAG_SPIKE) {
  // k6 doesn't allow mutating __ENV at runtime in a meaningful way, so we
  // document this here: pass `-e NUBL_TAG_SPIKE=true` if you want phase tags.
  // The threshold suite still functions without them — it just won't gate on
  // the post_surge slice.
}

export const options = {
  scenarios: {
    flash_crowd: {
      executor: 'ramping-vus',
      startVUs: 5,
      stages: [
        { duration: '2m',    target: 5   },  // baseline (2 vCPU / 4 GB droplet)
        { duration: '30s',   target: 80  },  // surge up
        { duration: '1m',    target: 80  },  // sustained peak
        { duration: '30s',   target: 5   },  // drop back
        { duration: '2m30s', target: 5   },  // confirm recovered
      ],
      exec: 'weightedJourney',
      gracefulRampDown: '30s',
      gracefulStop: '30s',
    },
  },
  thresholds: spikeThresholds,
  noConnectionReuse: false,
  userAgent: 'k6-nubl-spike/1.0',
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};
