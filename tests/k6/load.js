// tests/k6/load.js
// ---------------------------------------------------------------------------
// LOAD TEST — perf_test_plan.md §7 / §9.1
//
// Goal: validate SLOs (perf_test_plan.md §5) at expected peak.
// Profile: constant-arrival-rate, anchor = NUBL_LOAD_RPM (default 250 RPM),
//          distributed across 11 personas by the workload weights.
//          10-min gap between tests is handled by the run command.
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
    preAllocatedVUs: 30,
    maxVUs: 80,
  }),
  thresholds: baseThresholds,
  noConnectionReuse: false,
  insecureSkipTLSVerify: false,
  userAgent: 'k6-nubl-load/1.0 (perf-test; +https://github.com/nubl)',
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

