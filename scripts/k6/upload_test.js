import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  vus: 100,
  duration: '1m',
};

const BASE = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
  // Simula chamada para preparar uploads
  const payload = { galeria_id: 1, files: [{ name: 't.jpg', type: 'image/jpeg', size: 12345 }] };
  let res = http.post(`${BASE}/api/fotos/direct_prepare.php`, JSON.stringify(payload), { headers: { 'Content-Type': 'application/json' } });
  check(res, { 'prepare ok': (r) => r.status === 200 });

  const body = res.json() || {};
  if (body.uploads && body.uploads.length) {
    // Em um teste real aqui faríamos PUTs para upload_url. Para carga do servidor, apenas confirmamos
    const toConfirm = body.uploads.map(u => ({ r2_path: u.r2_path, original_name: u.original_name, size: u.size }));
    const conf = http.post(`${BASE}/api/fotos/direct_confirm.php`, JSON.stringify({ galeria_id: 1, items: toConfirm }), { headers: { 'Content-Type': 'application/json' } });
    check(conf, { 'confirm ok': (r) => r.status === 200 });
  }

  sleep(1);
}
