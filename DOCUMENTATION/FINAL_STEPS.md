# Final Steps to Run and Test the Scaled Upload Flow

1. Configure `.env` (copy `.env.example`)

```
cp .env.example .env
# edit .env with DB, R2 and Redis credentials
```

2. Run DB migrations (via web or CLI)

```
php api/db_migrations.php
```

3. Start Redis (system-specific). Ensure `REDIS_HOST`/`REDIS_PORT` in `.env`.

4. Start worker (manual)

```
php api/workers/image_worker.php
```

Or use Supervisor/systemd configurations in `scripts/`.

Railway / Docker deploy
- The repository includes a `Procfile` with `web` and `worker` processes.
- On Railway, create two services from the same repo: one service for `web`, another for `worker`.
- Locally, run both services with:

```
docker-compose up --build
```

5. Run a small k6 smoke test locally

```
BASE_URL=http://localhost:8080 k6 run scripts/k6/upload_test.js
```

6. To enable force-direct uploads, set `FORCE_DIRECT_UPLOAD=1` in `.env` and reload.

Notes:
- For full scale (1M photos) use partitioning, strong DB sizing, multiple workers / autoscaling, and CDN in front of R2.
- Monitor Redis, DB connections, PHP-FPM workers and network bandwidth.
