/**
 * Smoke / light load against the app root.
 *
 *   npm run k6:home
 * Uses native `k6` if installed, otherwise Docker (`grafana/k6`). On Linux,
 * Docker uses host networking so http://localhost:8080 targets your machine.
 *
 *   BASE_URL=http://127.0.0.1:8000 npm run k6:home
 * Install k6 only: https://grafana.com/docs/k6/latest/set-up/install-k6/
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = (__ENV.BASE_URL || 'http://localhost:8080').replace(/\/$/, '');

export const options = {
  vus: 5,
  duration: '30s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<3000'],
  },
};

export default function () {
  const res = http.get(`${BASE_URL}/`);
  check(res, {
    'status is 200': (r) => r.status === 200,
    'body not empty': (r) => (r.body && r.body.length > 0) || false,
  });
  sleep(1);
}
