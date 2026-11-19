# MARITIME DEEP DATA — DISASTER RECOVERY PLAN
## Business Continuity, Recovery Objectives & Catastrophic Failure Runbooks

---

### 1. RECOVERY OBJECTIVES (RPO & RTO RECOMMENDATIONS)

> [!IMPORTANT]
> The RPO and RTO values below are operational targets and architectural recommendations based on private NGO infrastructure constraints, not legally guaranteed commercial SLAs.

| Objective | Target Value | Empirical Verification Basis |
|---|---|---|
| **Recovery Point Objective (RPO)** | **$\le$ 1 Hour** | Achieved via daily full backups combined with hourly database differentials and WAL archiving. |
| **Recovery Time Objective (RTO)** | **$\le$ 30 Minutes** | Measured baseline database restoration is **2.063 seconds**; full server cold-rebuild runbook requires under 25 minutes. |

---

### 2. CATASTROPHIC DISASTER SCENARIOS & RUNBOOKS

#### Scenario 1: Accidental Data Modification or Malicious Deletion
- **Symptoms**: An analyst or rogue process accidentally modified or truncated records in `vessels`, `persons`, or `investigation_cases`.
- **Immediate Action**:
  1. Terminate all active investigator sessions to prevent further writes:
     ```bash
     cd /var/www/maritime-deep-data/backend && php artisan down --secret="emergency-recovery"
     ```
  2. Inspect the audit logs to identify the exact timestamp of data corruption:
     ```sql
     SELECT id, user_id, action, auditable_type, created_at 
     FROM audit_logs 
     ORDER BY created_at DESC LIMIT 20;
     ```
  3. Spin up an isolated recovery database from the latest verified backup:
     ```bash
     python3 /var/www/maritime-deep-data/scripts/deployment/backup_and_restore_verify.py
     ```
  4. Perform table-level restoration or full database restoration using `restore.sh`.

---

#### Scenario 2: Host Hardware Crash / Total Virtual Machine Loss
- **Symptoms**: Host server is completely dead, disk non-responsive, or hypervisor destroyed.
- **Recovery Procedure**:
  1. **Provision Fresh Linux Server**:
     - Spin up fresh Ubuntu 24.04 LTS instance with minimum 16 GB RAM and 100 GB NVMe storage.
  2. **Install Core Runtime Packages**:
     ```bash
     sudo apt-get update && sudo apt-get install -y nginx postgresql-18 postgresql-18-postgis-3 redis-server php8.3-fpm php8.3-pgsql php8.3-redis python3-venv git
     ```
  3. **Retrieve Cold Backup Archive**:
     - Pull the latest off-site backup archive from secure remote NGO cold storage (e.g. encrypted S3 bucket or isolated backup NAS).
  4. **Restore Database and Evidence**:
     ```bash
     /var/www/maritime-deep-data/scripts/deployment/restore.sh /mnt/cold_backups/LATEST_STABLE
     ```
  5. **Restore Application Code & Systemd Units**:
     - Re-clone repository or unpack configuration templates.
     - Restore `APP_KEY` and master `.env` from secure offline password safe.
  6. **Launch Services & Run Health Check**:
     ```bash
     sudo systemctl restart nginx php8.3-fpm redis-server mdd-laravel-queue mdd-ai-service
     curl -s -k https://127.0.0.1/api/v1/health | jq .
     ```

---

#### Scenario 3: Host Ransomware or Security Compromise
- **Symptoms**: Unauthorized SSH logins detected, files encrypted, or anomalous outbound traffic.
- **Emergency Containment (Zero Trust)**:
  1. **Sever Network Connections**: Immediately disconnect network interface or enable perimeter firewall drop.
  2. **Never Pay Ransom**: Never attempt to run software on a compromised host.
  3. **Cold Recovery**: Discard the compromised instance entirely. Provision a clean, hardened OS from scratch.
  4. **Restore from Immutable Cold Backup**: Ensure the backup archive was taken *before* the intrusion date by verifying file integrity against known cryptographic manifests.
  5. **Force Secret & Password Rotation**: Rotate all database passwords, Sanctum tokens, Redis passwords, and SSH keys.

---

### 3. ROLES & INCIDENT GOVERNANCE

| Role | Key Responsibility | Personnel Assigned |
|---|---|---|
| **Incident Commander** | Authorizes disaster recovery mode, maintenance downtime, and restoration. | Research Director / Executive Lead |
| **Recovery Lead** | Executes database restoration, validates PostGIS queries, verifies SHA-256 hashes. | Senior DevOps Engineer / DBA |
| **Evidence Custodian** | Validates that restored evidence files match chain of custody and legal hash records. | Evidence Manager / Senior Investigator |
| **Communications Lead** | Informs active NGO investigators of downtime and system recovery status. | Operational Coordinator |
