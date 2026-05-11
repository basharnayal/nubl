// tests/k6/soak.js
// ---------------------------------------------------------------------------
// ENDURANCE / SOAK TEST — perf_test_plan.md §7 / §9.4
//
// Goal: detect memory leaks, connection-pool drift, log-disk fill, queue
//       backlog over time.
// Profile: 2 hours sustained at 100 VUs equivalent (anchor 600 RPM), then
//          a drift comparison across the early vs late 25% of the run.
//
// Usage:
//   NUBL_TAG_SOAK=true k6 run --out json=reports/soak.json tests/k6/soak.js
// ---------------------------------------------------------------------------

import { buildArrivalScenarios } from './lib/workload.js';
import { soakThresholds } from './config/thresholds.js';
import { SOAK_ANCHOR_RPM } from './config/env.js';

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
  scenarios: buildArrivalScenarios(SOAK_ANCHOR_RPM, {
    duration: '2h',
    preAllocatedVUs: 80,
    maxVUs: 200,
  }),
  thresholds: soakThresholds,
  noConnectionReuse: false,
  userAgent: 'k6-nubl-soak/1.0',
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

export function handleSummary(data) {
  const lines = [
    '',
    '=== NUBL Soak Test — Summary ===',
    `Duration:  2h`,
    `Requests:  ${data.metrics.http_reqs?.values?.count ?? 'n/a'}`,
    `Failed:    ${(data.metrics.http_req_failed?.values?.rate * 100).toFixed(2)} %`,
    '— Drift comparison (early vs late 25% windows) —',
    `Read  p95 early: ${pct(data, 'http_req_duration{window:early,type:read}',  'p(95)')} ms`,
    `Read  p95 late:  ${pct(data, 'http_req_duration{window:late,type:read}',   'p(95)')} ms`,
    `Write p95 early: ${pct(data, 'http_req_duration{window:early,type:write}', 'p(95)')} ms`,
    `Write p95 late:  ${pct(data, 'http_req_duration{window:late,type:write}',  'p(95)')} ms`,
    '',
  ];
  return {
    'stdout': lines.join('\n'),
    'reports/soak-summary.json': JSON.stringify(data, null, 2),
  };
}

function pct(data, metric, p) {
  const v = data.metrics?.[metric]?.values?.[p];
  return v === undefined ? 'n/a' : v.toFixed(0);
}
