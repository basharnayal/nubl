/**
 * Performance Test 04 — QR Redemption (Race Condition)
 *
 * Tests: POST /provider/redeem
 * Simulates two providers scanning the same QR simultaneously.
 * Only one should succeed (200), the second must get 409.
 *
 * IMPORTANT: Replace TOKEN_HASH with a real PENDING redemption token hash
 * from your order_redemptions table before running.
 *
 * Run: k6 run tests/performance/04_qr_redemption.js
 * Note: Set RATE_LIMITING_ENABLED=false in .env before running.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate } from 'k6/metrics';
import { BASE_URL, login, getAuthCsrfToken } from './utils.js';

const successCount    = new Counter('redemption_success_count');
const conflictCount   = new Counter('redemption_conflict_count');
const errorCount      = new Counter('redemption_error_count');
const redemptionFail  = new Rate('redemption_fail_rate');

export const options = {
    // 2 VUs hit simultaneously — mimics two providers scanning same QR
    scenarios: {
        concurrent_redeem: {
            executor: 'shared-iterations',
            vus: 2,
            iterations: 2,
            maxDuration: '30s',
        },
    },
    thresholds: {
        http_req_duration:    ['p(95)<3000'],
        redemption_fail_rate: ['rate<0.05'],
    },
};

// Replace with a real provider account and a PENDING token hash
const PROVIDER_EMAIL = __ENV.PROVIDER_EMAIL || 'community@nubl.com';
const TOKEN_HASH     = __ENV.TOKEN_HASH     || 'REPLACE_WITH_REAL_TOKEN_HASH';

export default function () {
    // Step 1: Login as provider
    const session = login(PROVIDER_EMAIL, 'password');
    if (!session) {
        redemptionFail.add(1);
        errorCount.add(1);
        return;
    }

    // Step 2: Get CSRF token
    const csrfToken = getAuthCsrfToken(session.jar, '/provider/dashboard');
    if (!csrfToken) {
        redemptionFail.add(1);
        errorCount.add(1);
        return;
    }

    // Step 3: Attempt redemption
    const res = http.post(
        `${BASE_URL}/provider/redeem`,
        {
            token: TOKEN_HASH,
            _token: csrfToken,
        },
        {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            jar: session.jar,
            redirects: 0,
        }
    );

    if (res.status === 200) {
        successCount.add(1);
    } else if (res.status === 409) {
        conflictCount.add(1); // Expected for the second concurrent scan
    } else {
        errorCount.add(1);
    }

    const passed = check(res, {
        'response is 200 or 409': (r) => [200, 409].includes(r.status),
        'no server error':        (r) => r.status !== 500,
    });

    redemptionFail.add(!passed ? 1 : 0);

    sleep(0.5);
}

export function handleSummary(data) {
    const success  = data.metrics.redemption_success_count?.values?.count || 0;
    const conflict = data.metrics.redemption_conflict_count?.values?.count || 0;

    console.log('\n=== QR Redemption Race Condition Results ===');
    console.log(`Successful redemptions : ${success}  (expected: 1)`);
    console.log(`Conflict responses     : ${conflict} (expected: 1)`);
    console.log(success === 1 && conflict === 1
        ? '✅ PASS — lockForUpdate prevented duplicate redemption'
        : '❌ FAIL — duplicate redemption may have occurred');

    return {};
}
