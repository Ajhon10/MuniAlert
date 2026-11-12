import { test } from 'node:test';
import assert from 'node:assert/strict';
import { securityPolicy } from '../../hazard-client/security-policy.mjs';

test('CSP rejects insecure and injected custom sources and never permits dynamic script evaluation', () => {
  for (const origin of ['http://evil.example', 'https://u:p@evil.example', '*', 'https://good.example; script-src *', 'https://good.example/path']) {
    assert.throws(() => securityPolicy({ VITE_CSP_EXTRA_ORIGINS: origin }));
  }
  const policy = securityPolicy({ VITE_API_BASE_URL: 'https://api.example.test', VITE_CSP_EXTRA_ORIGINS: 'https://map.example.test' });
  const scripts = policy.meta.split(';').find(item => item.trim().startsWith('script-src'));
  assert.equal(scripts.trim(), "script-src 'self'");
  assert(!policy.meta.includes('unsafe-eval'));
  assert(policy.header.includes("frame-ancestors 'none'"));
  assert(policy.meta.includes('https://api.example.test'));
  assert(policy.meta.includes('https://map.example.test'));
});
