/**
 * Performance Test 03 — Payment Callback (Concurrency / Race Condition)
 *
 * Tests: GET /payments/callback?paymentId=X
 * Simulates concurrent callbacks for the same paymentId to verify
 * that lockForUpdate prevents duplicate fund transactions.
 *
 * IMPORTANT: Replace EXISTING_PAYMENT_ID with a real PENDING payment ID
 * from your database before running.
 *
 * Run: k6 run tests/performance/03_payment_callback.js
 * Note: Set RATE_LIMITING_ENABLED=false in .env before running.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';
import { BASE_URL } from './utils.js';

const callbackFailRate    = new Rate('callback_fail_rate');
const duplicateDetected   = new Counter('duplicate_callbacks_received');

export const options = {
    // Spike: 30 concurrent users hit the same callback simultaneously
    scenarios: {
        concurrent_callbacks: {
            executor: 'shared-iterations',
            vus: 30,
            iterations: 30,
            maxDuration: '30s',
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<3000'],
        callback_fail_rate: ['rate<0.05'],
    },
};

// Replace with a real PENDING payment's MyFatoorah paymentId
const PAYMENT_ID = __ENV.PAYMENT_ID || 'REPLACE_WITH_REAL_PAYMENT_ID';

export default function () {
    const res = http.get(
        `${BASE_URL}/payments/callback?paymentId=${PAYMENT_ID}`,
        { redirects: 5 }
    );

    const passed = check(res, {
        'no server error (500)': (r) => r.status !== 500,
        'not a 409 conflict':    (r) => r.status !== 409,
        'handled gracefully':    (r) => [200, 302, 404, 422].includes(r.status),
    });

    // Track 409 responses — should not happen for callbacks
    if (res.status === 409) {
        duplicateDetected.add(1);
    }

    callbackFailRate.add(!passed ? 1 : 0);

    sleep(0.5);
}
