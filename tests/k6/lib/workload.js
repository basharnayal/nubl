// tests/k6/lib/workload.js
// ---------------------------------------------------------------------------
// Translates the User-Behavior Distribution (perf_test_plan.md §6) into k6
// scenario definitions. Two builders:
//
//   buildArrivalScenarios(anchorRPM, durationCfg) → constant-arrival-rate
//     Used for Load + Soak. Throughput is enforced; VUs are spawned as needed.
//
//   distribution → flat array used by the weighted dispatcher in personas.js
//     (Spike + Stress use a single ramping-vus scenario that dispatches
//     iterations by weighted random.)
// ---------------------------------------------------------------------------

export const distribution = [
  { name: 'recipient_browse',   weight: 0.28, fn: 'recipientBrowseFlow',     type: 'read'  },
  { name: 'donor_dashboard',    weight: 0.18, fn: 'donorDashboardFlow',      type: 'read'  },
  { name: 'guest_donation',     weight: 0.15, fn: 'guestDonationFlow',       type: 'write' },
  { name: 'recipient_request',  weight: 0.10, fn: 'recipientRequestFlow',    type: 'write' },
  { name: 'donor_initiate',     weight: 0.08, fn: 'donorDonationFlow',       type: 'write' },
  { name: 'provider_redeem',    weight: 0.07, fn: 'providerRedemptionFlow',  type: 'write' },
  { name: 'notifications_poll', weight: 0.06, fn: 'notificationsPollFlow',   type: 'read'  },
  { name: 'provider_menu',      weight: 0.03, fn: 'providerMenuCrudFlow',    type: 'write' },
  { name: 'public_landing',     weight: 0.03, fn: 'publicLandingFlow',       type: 'read'  },
  { name: 'auth_login',         weight: 0.01, fn: 'authLoginFlow',           type: 'write' },
  { name: 'admin_actions',      weight: 0.01, fn: 'adminActionsFlow',        type: 'read'  },
];

// Sanity check: weights sum to 1.0 (within a tiny epsilon).
const _sum = distribution.reduce((s, x) => s + x.weight, 0);
if (Math.abs(_sum - 1.0) > 0.001) {
  throw new Error(`workload distribution weights sum to ${_sum}, expected 1.0`);
}

/**
 * Build per-persona constant-arrival-rate scenarios. Each scenario's rate is
 * proportional to its weight against the anchor RPM.
 *
 * @param {number} anchorRPM - total target requests per minute across all scenarios
 * @param {object} cfg - { duration, preAllocatedVUs, maxVUs, startTime }
 * @returns {object} k6 `scenarios` config object
 */
export function buildArrivalScenarios(anchorRPM, cfg) {
  const scenarios = {};
  for (const item of distribution) {
    scenarios[item.name] = {
      executor: 'constant-arrival-rate',
      rate: Math.max(1, Math.round(anchorRPM * item.weight)),
      timeUnit: '1m',
      duration: cfg.duration,
      preAllocatedVUs: cfg.preAllocatedVUs,
      maxVUs: cfg.maxVUs,
      exec: item.fn,
      startTime: cfg.startTime || '0s',
      tags: { persona: item.name, type: item.type },
      gracefulStop: '30s',
    };
  }
  return scenarios;
}

// ---- Weighted dispatcher state -------------------------------------------
// Pre-computed cumulative weights for O(log n) selection.
const _cum = (() => {
  const out = [];
  let acc = 0;
  for (const item of distribution) {
    acc += item.weight;
    out.push({ name: item.name, fn: item.fn, type: item.type, ceil: acc });
  }
  return out;
})();

/**
 * Pick one persona at random, weighted by §6.1. Returns the entry.
 */
export function pickWeightedPersona() {
  const r = Math.random();
  for (const entry of _cum) {
    if (r <= entry.ceil) return entry;
  }
  return _cum[_cum.length - 1];
}
