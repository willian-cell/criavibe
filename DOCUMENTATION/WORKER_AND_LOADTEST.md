# Worker and Load Testing

This document explains how to run the image worker, enqueue test jobs and run a basic k6 load test.

Prerequisites
- Redis running and accessible (set via `.env`)
- R2 credentials in `.env` if you want full integration
- PHP CLI with `phpredis` and `imagick` recommended

Run worker locally

```
php api/workers/image_worker.php
```

Enqueue a test job

```
php api/scripts/enqueue_test_job.php <galeria_id> <r2_path>
```

Run k6 test (requires k6 installed)

```
BASE_URL=http://localhost:8080 k6 run scripts/k6/upload_test.js
```

Supervisor/systemd
- Use `scripts/supervisor_image_worker.conf` for Supervisor.
- Use `scripts/systemd_image_worker.service` for systemd.
