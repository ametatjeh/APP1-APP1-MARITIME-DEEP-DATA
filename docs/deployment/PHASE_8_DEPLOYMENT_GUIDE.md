# MARITIME DEEP DATA — PHASE 8 DEPLOYMENT GUIDE
## Controlled NGO Infrastructure Deployment Runbook (App 1 Only)

---

### 1. OVERVIEW & SCOPE BOUNDARIES

**Maritime Deep Data** is a private, standalone intelligence and research platform designed exclusively for authorized non-governmental organization (NGO) investigators, maritime analysts, and research directors.

#### Strict Architectural Boundaries:
- **Scope**: APP 1 ONLY (Standalone Research & Investigation Platform).
- **No App 2 Integration**: Completely decoupled; no dependencies or communication channels with external tracking subsystems.
- **No Raw AIS/RF Collectors**: The platform does not ingest live high-frequency AIS radio telemetry or RF signals.
- **No Live Tracking Subsystem**: The platform is an analytical, forensic, and intelligence registry, not a tactical fleet tracker.
- **Multi-Country Architecture**: Multi-country jurisdiction schemas without hardcoded country business logic.
- **Controlled NGO Environment**: Deployed within a hardened, private, non-commercial infrastructure accessible strictly over TLS via authenticated sessions.

---

### 2. DEPLOYMENT TOPOLOGY & NETWORK TRUST BOUNDARIES

```
                             [ AUTHORIZED NGO CLIENTS ]
                           (Role-Based Access: 7 Roles)
                                       │
                                       │ HTTPS (TLS 1.3 / Port 443)
                                       ▼
                  ┌─────────────────────────────────────────┐
                  │          PERIMETER REVERSE PROXY        │
                  │             (Nginx Hardened)            │
                  └────────────────────┬────────────────────┘
                                       │
            ┌──────────────────────────┴──────────────────────────┐
            │ Path: /                                             │ Path: /api/v1, /up
            ▼                                                     ▼
┌──────────────────────────┐                            ┌──────────────────────────┐
│   VUE 3 STATIC ASSETS    │                            │    LARAVEL 13 REST API   │
│  (Vite Production Build) │                            │    (PHP-FPM 8.3 Pool)    │
│  Compiled SPA Artifacts  │                            │    Unix Domain Socket    │
└──────────────────────────┘                            └─────────────┬────────────┘
                                                                      │
                     ┌────────────────────────────────────────────────┼───────────────────────┐
                     │                                                │                       │
                     ▼                                                ▼                       ▼
      ┌──────────────────────────────┐                 ┌──────────────────────┐   ┌──────────────────────────┐
      │     POSTGRESQL 18 + POSTGIS  │                 │    REDIS 7 (IN-MEM)  │   │  PYTHON FASTAPI SERVICE  │
      │   - 70 Relational Tables     │                 │   - Session Store    │   │  - 8 Specialist Agents   │
      │   - PostGIS 3.6.2 Geometries │                 │   - Queue Broker     │   │  - RAG Vector Synthesis  │
      │   - Trigram & JSONB Indexes  │                 │   - Cache Layer      │   │  - Loopback 127.0.0.1:8100│
      │   - Port 5432 (Localhost)    │                 │   - Port 6379 (Local)│   │  - Token-Protected Auth  │
      └──────────────────────────────┘                 └──────────────────────┘   └──────────────────────────┘
                     │                                                │
                     │ (Local Disk / Private Storage)                 │
                     ▼                                                ▼
      ┌──────────────────────────────┐                 ┌──────────────────────┐
      │    PRIVATE EVIDENCE VAULT    │                 │ LARAVEL QUEUE WORKER │
      │  - storage/app/private_evid. │                 │  - Systemd Supervised│
      │  - Outside Web Root (750)    │                 │  - Async Import/Alert│
      │  - SHA-256 Hash Verification │                 │  - Redis Connection  │
      └──────────────────────────────┘                 └──────────────────────┘
```

#### Trust Boundary Rules:
1. **Perimeter (Public/VPN)**: Only TCP port 443 (HTTPS) and port 80 (HTTP 301 redirect) are reachable from authorized client networks.
2. **Loopback Only Services**: PostgreSQL (5432), Redis (6379), and FastAPI (8100) bind strictly to `127.0.0.1`. They are never exposed to external interfaces.
3. **Application Layer**: Laravel communicates with PostgreSQL via authenticated local sockets or loopback TCP, with Redis via unix socket or loopback, and with FastAPI via internal HTTP with `X-Internal-Token` HMAC validation.
4. **Evidence Vault**: Direct web access to `storage/app/private_evidence` is blocked at the Nginx web server layer (`return 403`). Evidence can only be accessed through Laravel's `EvidenceController` subject to `EvidencePolicy` verification.

---

### 3. SERVER & SYSTEM PREREQUISITES

| Component | Minimum Specification | Recommended Production |
|---|---|---|
| **Operating System** | Ubuntu 22.04 LTS / Debian 12 | Ubuntu 24.04 LTS Server |
| **CPU Architecture** | 4 Cores (x86_64) | 8 Cores (x86_64) |
| **RAM** | 8 GB | 16 GB - 32 GB |
| **Disk Space** | 50 GB NVMe SSD | 200 GB+ Enterprise NVMe (RAID 1/10) |
| **PHP** | PHP 8.3+ with `pgsql`, `pdo_pgsql`, `redis`, `mbstring`, `xml`, `bcmath`, `curl` | PHP 8.3 / 8.4 FPM |
| **Database** | PostgreSQL 18.x with PostGIS 3.6.x | PostgreSQL 18 with PostGIS 3.6 |
| **In-Memory Cache** | Redis 7.x | Redis 7.2 (AOF enabled) |
| **Python Environment**| Python 3.12 or 3.13 | Python 3.13 Virtualenv with FastAPI, Uvicorn, NumPy |
| **Web Server** | Nginx 1.24+ | Nginx 1.26+ with HTTP/2 and TLS 1.3 |

---

### 4. STEP-BY-STEP DEPLOYMENT RUNBOOK

#### Phase 1: Operating System & User Provisioning
```bash
# Update package repositories
sudo apt-get update && sudo apt-get upgrade -y

# Create dedicated service accounts with least privileges
sudo useradd -r -s /bin/false -d /var/www/maritime-deep-data mdd_app
sudo useradd -r -s /bin/false -d /var/www/maritime-deep-data/ai_service mdd-ai

# Create target directories
sudo mkdir -p /var/www/maritime-deep-data
sudo mkdir -p /var/log/maritime-deep-data
sudo mkdir -p /var/backups/maritime-deep-data
sudo chown -R www-data:www-data /var/log/maritime-deep-data
```

#### Phase 2: PostgreSQL & PostGIS Setup
```bash
# Install PostgreSQL 18 and PostGIS
sudo apt-get install -y postgresql-18 postgresql-18-postgis-3

# Configure Database and User
sudo -u postgres psql <<EOF
CREATE DATABASE maritime_deep_data;
CREATE USER mdd_db_user WITH ENCRYPTED PASSWORD 'SECURE_GENERATED_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE maritime_deep_data TO mdd_db_user;
\c maritime_deep_data
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS pgcrypto;
GRANT ALL ON SCHEMA public TO mdd_db_user;
EOF
```

#### Phase 3: Backend Deployment
```bash
# Clone or deploy release archive to /var/www/maritime-deep-data
cd /var/www/maritime-deep-data/backend

# Install production dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Copy and configure production environment
cp .env.production.example .env
php artisan key:generate

# Run production database migrations
php artisan migrate --force

# Cache routes, configuration, and views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Secure directory permissions
sudo chown -R www-data:www-data /var/www/maritime-deep-data/backend/storage
sudo chown -R www-data:www-data /var/www/maritime-deep-data/backend/bootstrap/cache
sudo chmod -R 775 /var/www/maritime-deep-data/backend/storage
sudo chmod -R 750 /var/www/maritime-deep-data/backend/storage/app/private_evidence
```

#### Phase 4: Frontend Production Compilation & Deployment
```bash
cd /var/www/maritime-deep-data/frontend
cp .env.production .env

# Install build dependencies and compile Vite SPA
npm ci
npm run build

# Verify compiled assets exist in frontend/dist
ls -lh dist/index.html
```

#### Phase 5: Python FastAPI AI Microservice Setup
```bash
cd /var/www/maritime-deep-data/ai_service
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt

# Create production environment
cp .env.production.example .env
# Set AI_SERVICE_TOKEN to match backend services.php
```

#### Phase 6: Systemd Units & Process Supervision
```bash
# Copy systemd units from scripts/deployment/
sudo cp ../scripts/deployment/mdd-laravel-queue.service /etc/systemd/system/
sudo cp ../scripts/deployment/mdd-laravel-scheduler.service /etc/systemd/system/
sudo cp ../scripts/deployment/mdd-laravel-scheduler.timer /etc/systemd/system/
sudo cp ../scripts/deployment/mdd-ai-service.service /etc/systemd/system/

# Reload systemd and start all services
sudo systemctl daemon-reload
sudo systemctl enable --now mdd-laravel-queue.service
sudo systemctl enable --now mdd-laravel-scheduler.timer
sudo systemctl enable --now mdd-ai-service.service

# Verify service health
sudo systemctl status mdd-laravel-queue.service
sudo systemctl status mdd-ai-service.service
```

#### Phase 7: Nginx Web Server Configuration
```bash
sudo cp ../scripts/deployment/nginx.conf /etc/nginx/sites-available/maritime-deep-data.conf
sudo ln -sf /etc/nginx/sites-available/maritime-deep-data.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### 5. POST-DEPLOYMENT SMOKE TESTS

1. **Infrastructure Health Check**:
   ```bash
   curl -s -k https://mdd-investigations.ngo.internal/api/v1/health | jq .
   ```
   *Expected Response*: `{"status": "operational", "checks": {"database": {"status": "healthy"}, "evidence_storage": {"status": "healthy"}}}`

2. **Security Headers Verification**:
   ```bash
   curl -I -k https://mdd-investigations.ngo.internal/api/v1/health
   ```
   *Verify*: Presence of `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy`.

3. **Evidence Vault Access Blocked**:
   ```bash
   curl -s -o /dev/null -w "%{http_code}" https://mdd-investigations.ngo.internal/storage/app/private_evidence/
   ```
   *Expected Response*: `403 Forbidden`.
