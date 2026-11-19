# MARITIME DEEP DATA — MONITORING AND OBSERVABILITY
## Health Endpoints, Telemetry Collection, Structured Logging & Alerting Policies

---

### 1. HEALTH CHECK ARCHITECTURE

Maritime Deep Data provides dedicated operational health check endpoints designed for synthetic uptime probes, container orchestrators, and perimeter monitoring systems.

#### 1. Primary Backend Health Endpoint
- **URL**: `GET /api/v1/health`
- **Access**: Public / Monitoring probe (Internal network)
- **Response Format**: JSON (HTTP 200 for healthy, HTTP 503 for unhealthy)
- **Subsystem Checks**:
  1. **Database & PostGIS**: Tests connection, measures latency in ms, queries `PostGIS_Lib_Version()`.
  2. **Cache / Redis**: Performs ephemeral read/write key test, measures cache roundtrip latency.
  3. **Private Evidence Storage**: Checks read/write accessibility of `storage/app/private_evidence`.
  4. **AI Microservice**: Probes `http://127.0.0.1:8100/health`. If offline or timeout, reports `status: offline` with `fallback_active: true` without failing the overall application.

```json
{
  "status": "operational",
  "application": "Maritime Deep Data (App 1 Standalone)",
  "environment": "production",
  "timestamp": "2026-11-19T19:20:00+07:00",
  "duration_ms": 1.25,
  "checks": {
    "database": {
      "status": "healthy",
      "driver": "pgsql",
      "postgis_version": "3.6.2",
      "latency_ms": 0.45
    },
    "cache": {
      "status": "healthy",
      "driver": "redis",
      "latency_ms": 0.12
    },
    "evidence_storage": {
      "status": "healthy",
      "disk": "private",
      "type": "Private Object Storage (No Public Access)"
    },
    "ai_service": {
      "status": "healthy",
      "url": "http://127.0.0.1:8100",
      "details": {
        "status": "healthy",
        "service": "Maritime Deep Data AI Service"
      }
    }
  }
}
```

#### 2. AI Microservice Health Endpoint
- **URL**: `GET http://127.0.0.1:8100/health`
- **Output**: Multi-agent availability status, framework version, token protection status.

---

### 2. LOGGING ARCHITECTURE & RETENTION

1. **Structured Log Format**:
   - Backend logs are routed to `storage/logs/laravel-YYYY-MM-DD.log`.
   - Formatted in JSON Lines with timestamps, severity level, request UUID, user ID (if authenticated), and sanitized message.

2. **Log Rotation Policy**:
   - `LOG_CHANNEL=daily` automatically manages daily files.
   - `LOG_MAX_FILES=30` retains logs for 30 rolling days, discarding older files automatically to protect disk space.

3. **Nginx & PHP-FPM Logs**:
   - Web server access and error logs reside in `/var/log/nginx/mdd_access.log` and `/var/log/nginx/mdd_error.log`.
   - Rotated weekly via Linux system `logrotate` with gzip compression.

---

### 3. METRICS & TELEMETRY COLLECTION

| Metric Group | Metrics to Monitor | Collection Tool | Critical Threshold |
|---|---|---|---|
| **Host System** | CPU Utilization, RAM Usage, Disk Space, Disk I/O | Node Exporter / Telegraf | Disk > 85%, RAM > 90% |
| **PostgreSQL / PostGIS**| Connection Count, Slow Queries (> 500ms), Replication Lag | `pg_stat_activity`, `pg_stat_statements` | Active Connections > 80% max |
| **Redis In-Memory** | Memory Used, Evicted Keys, Connected Clients | `redis-cli info memory` | Memory > 80% maxmemory |
| **Laravel Queue** | Queue backlog depth, Failed job count | `artisan queue:failed` | Failed Jobs > 5, Backlog > 100 |
| **Evidence Vault** | Vault disk usage, Inode exhaustion | `df -i /var/www/maritime-deep-data` | Inodes > 80% |

---

### 4. ALERTING MATRIX & ESCALATION LEVELS

```
Level 1: CRITICAL (Page On-Duty Sysadmin / Instant Alert)
  ├── Condition: Database unreachable (HTTP 503 on /api/v1/health)
  ├── Condition: Evidence storage disk read-only or full (> 95%)
  ├── Condition: Nginx reporting 5xx rate > 5% over 5-minute window
  └── Escalation: Senior DevOps Engineer + Research Director

Level 2: WARNING (Notify within 30 minutes)
  ├── Condition: AI Microservice unreachable (Operating on PHP fallback)
  ├── Condition: Queue backlog > 50 jobs unhandled for > 15 minutes
  ├── Condition: Disk space utilization > 80%
  └── Escalation: Application Maintenance Lead

Level 3: INFORMATIONAL (Daily Digest)
  ├── Condition: Daily automated backup completed successfully
  ├── Condition: Batch data import pipeline completed
  └── Escalation: Systems Log / Dashboard View
```
