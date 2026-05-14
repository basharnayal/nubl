// tests/k6/endurance.js
// ---------------------------------------------------------------------------
// ENDURANCE TEST — perf_test_plan.md §7 / §9.4
//
// Goal: detect memory leaks, connection-pool drift, log-disk fill, queue
//       backlog over time.
// Profile: 1 hour sustained at 100 VUs equivalent (anchor 150 RPM), then
//          a drift comparison across the early vs late 25% of the run.
//          10-min gap between tests is handled by the run command.
//
// Usage:
//   NUBL_TAG_ENDURANCE=true k6 run tests/k6/endurance.js
// ---------------------------------------------------------------------------

import { buildArrivalScenarios } from './lib/workload.js';
import { enduranceThresholds } from './config/thresholds.js';
import { ENDURANCE_ANCHOR_RPM } from './config/env.js';

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
  scenarios: buildArrivalScenarios(ENDURANCE_ANCHOR_RPM, {
    duration: '1h',
    preAllocatedVUs: 20,
    maxVUs: 50,
  }),
  thresholds: enduranceThresholds,
  noConnectionReuse: false,
  userAgent: 'k6-nubl-endurance/1.0',
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};
