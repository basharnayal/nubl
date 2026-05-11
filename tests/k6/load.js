// tests/k6/load.js
// ---------------------------------------------------------------------------
// LOAD TEST — perf_test_plan.md §7 / §9.1
//
// Goal: validate SLOs (perf_test_plan.md §5) at expected peak.
// Profile: constant-arrival-rate, anchor = NUBL_LOAD_RPM (default 1000 RPM),
//          distributed across 11 personas by the workload weights.
//
// Usage:
//   k6 run \
//     -e NUBL_BASE_URL=https://staging.nubl.test \
//     -e NUBL_LOAD_RPM=1000 \
//     --out json=reports/load.json \
//     tests/k6/load.js
// ---------------------------------------------------------------------------

import { buildArrivalScenarios } from './lib/workload.js';
import { baseThresholds } from './config/thresholds.js';
import { LOAD_ANCHOR_RPM } from './config/env.js';

// Re-export every persona so k6 can resolve them by name via `exec`.
export {
  recipientBrowseFlow,
  donorDashboardFlow,
  guestDonationFlow,
  recipientRequestFlow,
  donorDonationFlow,
  providerRedemptionFlow,
  notificationsPollFlow,
  providerMenuCrudFlow,
  publicLandingFlow,
  authLoginFlow,
  adminActionsFlow,
} from './lib/personas.js';

export const options = {
  scenarios: buildArrivalScenarios(LOAD_ANCHOR_RPM, {
    duration: '15m',
    preAllocatedVUs: 100,
    maxVUs: 250,
  }),
  thresholds: baseThresholds,
  noConnectionReuse: false,
  insecureSkipTLSVerify: false,
  userAgent: 'k6-nubl-load/1.0 (perf-test; +https://github.com/nubl)',
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

export function handleSummary(data) {
  return {
    'stdout': textSummary(data),
    'reports/load-summary.json': JSON.stringify(data, null, 2),
  };
}

// Minimal inline text summary so we don't depend on the k6-summary jslib.
// Comment this out if you prefer the default k6 summary.
function textSummary(data) {
  const lines = [
    '',
    '=== NUBL Load Test — Summary ===',
    `Iterations: ${data.metrics.iterations?.values?.count ?? 'n/a'}`,
    `Requests:   ${data.metrics.http_reqs?.values?.count ?? 'n/a'}`,
    `Failed:     ${(data.metrics.http_req_failed?.values?.rate * 100).toFixed(2)} %`,
    `Read  p95:  ${pct(data, 'http_req_duration{type:read}',  'p(95)')} ms`,
    `Read  p99:  ${pct(data, 'http_req_duration{type:read}',  'p(99)')} ms`,
    `Write p95:  ${pct(data, 'http_req_duration{type:write}', 'p(95)')} ms`,
    `Write p99:  ${pct(data, 'http_req_duration{type:write}', 'p(99)')} ms`,
    '',
  ];
  return lines.join('\n');
}

function pct(data, metric, p) {
  const v = data.metrics?.[metric]?.values?.[p];
  return v === undefined ? 'n/a' : v.toFixed(0);
}
