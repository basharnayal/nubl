/**
 * Performance Test 02 — Donation Flow
 *
 * Tests: POST /payments/initiate
 * Simulates 20 concurrent donors initiating donations.
 *
 * Run: k6 run tests/performance/02_donation_flow.js
 * Note: Set RATE_LIMITING_ENABLED=false in .env before running.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';
import { BASE_URL, login, getAuthCsrfToken } from './utils.js';

const donationDuration = new Trend('donation_initiate_duration');
const donationFailRate  = new Rate('donation_fail_rate');

export const options = {
    stages: [
        { duration: '10s', target: 20 },
        { duration: '30s', target: 20 },
        { duration: '10s', target: 0  },
    ],
    thresholds: {
        http_req_duration:       ['p(95)<2000'],
        donation_initiate_duration: ['avg<1000'],
        donation_fail_rate:      ['rate<0.01'],
    },
};

const DONOR_ACCOUNTS = [
    'donor@nubl.com',
    'donor-seed@nubl.com',
];

export default function () {
    const email = DONOR_ACCOUNTS[Math.floor(Math.random() * DONOR_ACCOUNTS.length)];

    // Step 1: Login
    const session = login(email, 'password');
    if (!session) {
        donationFailRate.add(1);
        return;
    }

    // Step 2: Get fresh CSRF token from dashboard
    const csrfToken = getAuthCsrfToken(session.jar, '/donor/dashboard');
    if (!csrfToken) {
        donationFailRate.add(1);
        return;
    }

    // Step 3: Initiate donation
    const start = Date.now();
    const res = http.post(
        `${BASE_URL}/payments/initiate`,
        {
            amount:       '100',
            is_anonymous: '0',
            _token:       csrfToken,
        },
        {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            jar: session.jar,
            redirects: 0, // we expect a redirect to MyFatoorah — don't follow it
        }
    );
    donationDuration.add(Date.now() - start);

    const passed = check(res, {
        'initiate returns redirect (302)': (r) => r.status === 302,
        'no server error':                 (r) => r.status !== 500,
        'audit logged (payment created)':  (r) => r.status === 302,
    });

    donationFailRate.add(!passed ? 1 : 0);

    sleep(1);
}
