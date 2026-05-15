# Deploy e gerenciamento do Worker de Imagens

Requisitos:
- PHP CLI (>=7.4)
- Extensão `redis` (phpredis) ou adaptar `api/lib/Queue.php`
- `imagick` recomendado (ImageMagick)
- Redis em execução

Execução manual:

```
php api/workers/image_worker.php
```

Supervisor (exemplo): copie `scripts/supervisor_image_worker.conf` para `/etc/supervisor/conf.d/` e recarregue:

```
sudo cp scripts/supervisor_image_worker.conf /etc/supervisor/conf.d/criavibe_image_worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start criaVibe_image_worker
```

Systemd (exemplo): copie `scripts/systemd_image_worker.service` para `/etc/systemd/system/` e habilite:

```
sudo cp scripts/systemd_image_worker.service /etc/systemd/system/criavibe_image_worker.service
sudo systemctl daemon-reload
sudo systemctl enable --now criaVibe_image_worker.service
```

Railway / Docker deploy:
- O repositório agora inclui um `Procfile` com processos `web` e `worker`.
- Em Railway, crie dois serviços a partir do mesmo repo: um para `web` e outro para `worker`.
- Para testes locais, use `docker-compose.yml`:

```
docker-compose up --build
```

Logs:
- Supervisor logs configurados em `/var/log/supervisor/`
- Systemd logs via `journalctl -u criaVibe_image_worker`
