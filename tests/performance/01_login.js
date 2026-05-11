/**
 * Performance Test 01 — Login
 *
 * Tests: POST /login
 * Simulates 1000 concurrent users logging in within 5 seconds.
 *
 * Run: k6 run tests/performance/01_login.js
 * Note: Set RATE_LIMITING_ENABLED=false in .env before running.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';
import { BASE_URL, getCsrfToken } from './utils.js';

// Custom metrics
const loginDuration = new Trend('login_duration');
const loginFailRate = new Rate('login_fail_rate');

export const options = {
    scenarios: {
        mass_login: {
            executor: 'ramping-vus',
            stages: [
                { duration: '5s',  target: 1000 }, // ramp up to 1000 users in 5s
                { duration: '10s', target: 1000 }, // hold at 1000 for 10s
                { duration: '5s',  target: 0    }, // ramp down
            ],
        },
    },
    thresholds: {
        http_req_duration: ['p(95)<5000'],  // 95% of requests under 5s (high load)
        login_duration:    ['avg<3000'],    // average login under 3s
        login_fail_rate:   ['rate<0.05'],   // less than 5% failures under stress
    },
};

// Users to test with (from DemoUsersSeeder — all have password: 'password')
const TEST_USERS = [
    { email: 'admin@nubl.com', password: 'password' },
    { email: 'donor@nubl.com', password: 'password' },
    { email: 'panda@nubl.com', password: 'password' },
    { email: 'recipient@nubl.com', password: 'password' },
    { email: 'community@nubl.com', password: 'password' },
];

export default function () {
    // Pick a random user each iteration
    const user = TEST_USERS[Math.floor(Math.random() * TEST_USERS.length)];

    // Step 1: Get login page + CSRF token
    const csrfToken = getCsrfToken('/login');
    if (!csrfToken) {
        loginFailRate.add(1);
        return;
    }

    // Step 2: Submit login
    const start = Date.now();
    const res = http.post(
        `${BASE_URL}/login`,
        {
            email: user.email,
            password: user.password,
            _token: csrfToken,
        },
        {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            redirects: 5,
        }
    );
    loginDuration.add(Date.now() - start);

    const passed = check(res, {
        'status is not 500': (r) => r.status !== 500,
        'redirected away from /login': (r) => !r.url.includes('/login'),
    });

    loginFailRate.add(!passed ? 1 : 0);

    sleep(1);
}
