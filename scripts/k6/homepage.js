/**
 * Smoke / light load against the app root.
 * Requires k6 on PATH: https://grafana.com/docs/k6/latest/set-up/install-k6/
 *
 *   npm run k6:home
 *   BASE_URL=http://127.0.0.1:8000 npm run k6:home
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
