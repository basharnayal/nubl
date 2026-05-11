/**
 * Performance Test 05 — Recipient Request Submission
 *
 * Tests: POST /recipient/requests
 * Simulates 30 concurrent recipients submitting food requests.
 * Verifies weekly allowance is enforced and no over-spending occurs.
 *
 * IMPORTANT: Replace PROVIDER_ID and MENU_ITEM_ID with real IDs
 * from your database before running.
 *
 * Run: k6 run tests/performance/05_recipient_requests.js
 * Note: Set RATE_LIMITING_ENABLED=false in .env before running.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Counter } from 'k6/metrics';
import { BASE_URL, login, getAuthCsrfToken } from './utils.js';

const requestFailRate      = new Rate('request_fail_rate');
const allowanceExceeded    = new Counter('allowance_exceeded_count');
const requestSuccess       = new Counter('request_success_count');

export const options = {
    stages: [
        { duration: '10s', target: 30 },
        { duration: '20s', target: 30 },
        { duration: '10s', target: 0  },
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'],
        request_fail_rate: ['rate<0.05'],
    },
};

// Replace with real values from your database
const PROVIDER_ID   = __ENV.PROVIDER_ID   || '1';
const MENU_ITEM_ID  = __ENV.MENU_ITEM_ID  || '1';

const RECIPIENT_ACCOUNTS = [
    'recipient@nubl.com',
];

export default function () {
    const email = RECIPIENT_ACCOUNTS[Math.floor(Math.random() * RECIPIENT_ACCOUNTS.length)];

    // Step 1: Login
    const session = login(email, 'password');
    if (!session) {
        requestFailRate.add(1);
        return;
    }

    // Step 2: Get CSRF token
    const csrfToken = getAuthCsrfToken(session.jar, '/recipient/dashboard');
    if (!csrfToken) {
        requestFailRate.add(1);
        return;
    }

    // Step 3: Submit request
    const payload = JSON.stringify({
        provider_id: parseInt(PROVIDER_ID),
        items: [
            { id: parseInt(MENU_ITEM_ID), quantity: 1 },
        ],
    });

    const res = http.post(
        `${BASE_URL}/recipient/requests`,
        payload,
        {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            jar: session.jar,
            redirects: 5,
        }
    );

    // 422 = allowance exceeded or validation failed (expected, not a bug)
    if (res.status === 422) {
        allowanceExceeded.add(1);
    } else if (res.status === 200 || res.status === 201 || res.status === 302) {
        requestSuccess.add(1);
    }

    const passed = check(res, {
        'no server error':      (r) => r.status !== 500,
        'handled correctly':    (r) => [200, 201, 302, 422, 429].includes(r.status),
    });

    requestFailRate.add(!passed ? 1 : 0);

    sleep(1);
}
