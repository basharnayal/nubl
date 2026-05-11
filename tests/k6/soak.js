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

