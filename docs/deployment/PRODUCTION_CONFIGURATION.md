# MARITIME DEEP DATA — PRODUCTION CONFIGURATION
## Complete Environment & Configuration Matrix

---

### 1. ENVIRONMENT ISOLATION PHILOSOPHY

Maritime Deep Data enforces strict separation between environments to guarantee data privacy, chain of custody integrity, and secret containment.

| Environment | Hostname Convention | Database | AI Microservice | Debug Mode | Storage Vault |
|---|---|---|---|---|---|
| **Local Dev** | `localhost:8000` / `localhost:5173` | Local PostgreSQL | Local Uvicorn (Hot Reload) | `APP_DEBUG=true` | Local test vault |
| **Staging** | `mdd-staging.ngo.internal` | Dedicated Staging PostGIS | Dedicated Staging Uvicorn | `APP_DEBUG=false` | Isolated Staging Vault |
| **Production** | `mdd-investigations.ngo.internal` | Hardened Production PostGIS | Supervised Production Uvicorn | `APP_DEBUG=false` | Encrypted Private Vault |

---

### 2. BACKEND PRODUCTION ENVIRONMENT SPECIFICATION

| Variable | Recommended Production Value | Security / Operational Rationale |
|---|---|---|
| `APP_NAME` | `"Maritime Deep Data"` | Display identity for email notifications and logs. |
| `APP_ENV` | `production` | Enables strict error sanitization and performance optimizations. |
| `APP_KEY` | `base64:GENERATE_VIA_ARTISAN` | 256-bit AES cipher key for cookie encryption and PII vault encryption. |
| `APP_DEBUG` | `false` | **CRITICAL**: Never expose stack traces or environment variables in error pages. |
| `APP_URL` | `https://mdd-investigations.ngo.internal` | Canonical HTTPS URL used for email generation and CORS verification. |
| `LOG_CHANNEL` | `daily` | Automatically rotates operational logs daily to prevent disk exhaustion. |
| `LOG_LEVEL` | `info` | Omits debug spam while capturing all investigative audit events. |
| `LOG_MAX_FILES` | `30` | 30-day log retention compliance for NGO internal audits. |
| `DB_CONNECTION` | `pgsql` | Connects to PostgreSQL 18 with PostGIS extension. |
| `DB_HOST` | `127.0.0.1` | Binds strictly to local interface; rejects external internet connections. |
| `DB_PORT` | `5432` | Standard PostgreSQL port. |
| `DB_DATABASE` | `maritime_deep_data` | Core investigative database holding 70 relational tables. |
| `DB_USERNAME` | `mdd_db_user` | Dedicated unprivileged database role (non-superuser). |
| `DB_PASSWORD` | `<STRONG_SECRET>` | Cryptographically generated password (minimum 32 characters). |
| `SESSION_DRIVER` | `redis` | High-performance in-memory session storage with instant revocation capability. |
| `SESSION_ENCRYPT` | `true` | Encrypts all session payload data at rest within Redis. |
| `SESSION_SECURE_COOKIE` | `true` | Flags cookies with `Secure`, ensuring browser transmits only over HTTPS. |
| `SESSION_HTTP_ONLY` | `true` | Flags cookies with `HttpOnly`, preventing XSS JavaScript exfiltration. |
| `SESSION_SAME_SITE` | `strict` | Mitigates Cross-Site Request Forgery (CSRF). |
| `CACHE_STORE` | `redis` | In-memory cache for API rate limiting, watchlists, and lookups. |
| `QUEUE_CONNECTION` | `redis` | Asynchronous job execution for data imports, alert notifications, and indexing. |
| `FILESYSTEM_DISK` | `private` | **CRITICAL**: Routes all file writes to `storage/app/private_evidence` (no public symlink). |
| `AI_SERVICE_URL` | `http://127.0.0.1:8100` | Loopback communication endpoint for Python FastAPI multi-agent microservice. |
| `AI_SERVICE_TOKEN` | `<STRONG_SECRET>` | Shared internal HMAC secret required in `X-Internal-Token` header. |
| `SANCTUM_STATEFUL_DOMAINS`| `mdd-investigations.ngo.internal` | Whitelist for stateful SPA authentication. |

---

### 3. FRONTEND VITE PRODUCTION SPECIFICATION

The frontend single-page application (Vue 3 + Vite) embeds build-time variables. Under no circumstances should backend API keys, database credentials, or secret tokens be placed in frontend configuration.

| Variable | Production Value | Description |
|---|---|---|
| `VITE_API_BASE_URL` | `/api/v1` | Relative API path; routed through Nginx reverse proxy to prevent CORS preflight latency. |
| `VITE_APP_NAME` | `"Maritime Deep Data"` | UI title and brand header. |
| `VITE_ENVIRONMENT` | `production` | Enables production telemetry and disables devtools overlays. |

---

### 4. PYTHON FASTAPI AI MICROSERVICE SPECIFICATION

| Variable | Production Value | Description |
|---|---|---|
| `AI_HOST` | `127.0.0.1` | Strict loopback binding; service will not respond on public IP interfaces. |
| `AI_PORT` | `8100` | Microservice port. |
| `AI_WORKERS` | `2` | Uvicorn worker process concurrency for asynchronous query orchestration. |
| `AI_SERVICE_TOKEN` | `<STRONG_SECRET>` | Shared authentication secret; requests lacking this token in `X-Internal-Token` receive `401 Unauthorized`. |
| `AI_CORS_ORIGINS` | `http://localhost:8000,http://127.0.0.1:8000` | Strict origin whitelist restricted to Laravel backend. |

---

### 5. SECRET MANAGEMENT & KEY ROTATION RUNBOOK

1. **Storage of Production Secrets**:
   - Production secrets must never be committed to Git.
   - Store offline master credentials in an encrypted password manager (e.g. KeepassXC / Bitwarden Vault) managed by the designated NGO Infrastructure Lead and Research Director.

2. **APP_KEY Rotation Procedure**:
   - `APP_KEY` encrypts user session cookies and sensitive encrypted fields (such as crew passport/national IDs in `persons` table).
   - Rotating `APP_KEY` requires re-encrypting existing database ciphertexts before activating the new key.
   - For routine deployment, `APP_KEY` is generated once at initial deployment and preserved across software releases.

3. **AI_SERVICE_TOKEN Rotation Procedure**:
   ```bash
   # Generate new 64-character token
   NEW_TOKEN=$(openssl rand -hex 32)

   # Update ai_service/.env and restart Uvicorn
   sed -i "s/AI_SERVICE_TOKEN=.*/AI_SERVICE_TOKEN=${NEW_TOKEN}/" /var/www/maritime-deep-data/ai_service/.env
   sudo systemctl restart mdd-ai-service.service

   # Update backend/.env and clear config cache
   sed -i "s/AI_SERVICE_TOKEN=.*/AI_SERVICE_TOKEN=${NEW_TOKEN}/" /var/www/maritime-deep-data/backend/.env
   cd /var/www/maritime-deep-data/backend && php artisan config:cache
   ```
