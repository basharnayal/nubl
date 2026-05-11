// tests/k6/smoke.js
// ---------------------------------------------------------------------------
// SMOKE TEST — 1 VU × 1 min × all flows, sequential.
// Run this BEFORE every campaign (entry criterion E-05 in perf_test_plan.md).
// It validates auth, CSRF extraction, seed-user CSV, and basic endpoint
// availability — without applying any meaningful load.
//
// Usage:
//   k6 run -e NUBL_BASE_URL=https://staging.nubl.test tests/k6/smoke.js
// ---------------------------------------------------------------------------

import { sleep } from 'k6';
import {
  recipientBrowseFlow,
  donorDashboardFlow,
  guestDonationFlow,
  recipientRequestFlow,
  donorDonationFlow,
  providerRedemptionFlow,
  notificationsPollFlow,
  providerMenuCrudFlow,
  publicLandingFlow,
} from './lib/personas.js';

export const options = {
  vus: 1,
  iterations: 9, // one per flow below
  thresholds: {
    // Smoke is binary — anything failing is a defect.
    'http_req_failed':       ['rate==0'],
    'checks':                ['rate==1'],
    'http_429_unexpected':   ['count==0'],
  },
};

const flows = [
  publicLandingFlow,
  guestDonationFlow,
  donorDashboardFlow,
  donorDonationFlow,
  recipientBrowseFlow,
  recipientRequestFlow,
  providerMenuCrudFlow,
  providerRedemptionFlow,
  notificationsPollFlow,
];

export default function () {
  const i = (__ITER) % flows.length;
  flows[i]();
  sleep(1);
}
