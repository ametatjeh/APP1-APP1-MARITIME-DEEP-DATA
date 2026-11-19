# MARITIME DEEP DATA — BACKUP AND RESTORE
## Enterprise Backup Policy, Empirical Restoration Verification & Recovery Runbook

---

### 1. BACKUP POLICY & COVERAGE MATRIX

Maritime Deep Data enforces a comprehensive backup policy designed to protect sensitive NGO research, investigative evidence, and regulatory boundary datasets.

| Asset | Location | Backup Tool / Method | Frequency | Retention Schedule |
|---|---|---|---|---|
| **PostgreSQL Database** | `127.0.0.1:5432` (`maritime_deep_data`) | `pg_dump` (Custom binary / plain) | Hourly diff / Daily full | 30 days daily, 12 months monthly |
| **PostGIS Spatial Layers** | Integrated in PostgreSQL (`geom` columns) | Included in database dump | Daily full | Synchronized with DB |
| **Private Evidence Vault** | `storage/app/private_evidence` | Compressed archive (`tar.gz`) + SHA-256 | Daily full | 90 days daily, permanent cold archive |
| **Configuration Templates** | `backend/.env.production.example`, `scripts/` | Git versioned + Daily archive | On change / Daily | Lifetime of software release |
| **Audit Logs & Provenance** | `audit_logs` & `dataset_versions` tables | Included in database dump | Daily full | 7 years (compliance requirement) |
| **Master Secrets & Keys** | Encrypted Vault / Offline Safe | GPG-encrypted manual backup | On key rotation | Dual-custody offline archival |

---

### 2. EMPIRICAL RESTORATION TEST RESULTS

In accordance with Phase 8 operational acceptance criteria, an actual test restoration was executed in an isolated environment (`mdd_restore_verification_test`) on PostgreSQL 18.6 with PostGIS 3.6.2.

#### Test Execution Metadata:
- **Execution Date**: 2026-11-19 19:21:39 UTC+7
- **Source Database**: `maritime_deep_data`
- **Target Test Database**: `mdd_restore_verification_test`
- **Test Automation Script**: `scripts/deployment/backup_and_restore_verify.py`
- **Audit Report Log**: `backend/storage/backups/restoration_audit_report_20261119_192139.json`

#### Empirical Verification Metrics:
| Metric | Measured Value | Acceptance Threshold | Result |
|---|---|---|---|
| **Database Backup Duration** | **0.650 seconds** | < 120 seconds | **PASS** |
| **Backup Artifact Size** | **216,902 bytes** (211.8 kB) | > 0 bytes | **PASS** |
| **Backup SHA-256 Integrity** | `51e70b5773e2ee58d967ce7e3dad2cd3e6cbb65978592c34a5dcbace75606eaf` | 64-char valid hash | **PASS** |
| **Restoration Duration (Measured RTO)** | **2.063 seconds** | < 300 seconds | **PASS** |
| **PostGIS Extension Verification** | Version `3.6.2` operational post-restore | Must match source | **PASS** |
| **Spatial Query Verification** | `ST_Contains` spatial query matched 1 Natuna zone | $\ge 1$ matching zone | **PASS** |
| **Table Count Parity** | **70 / 70 tables** restored identically | Exact match | **PASS** |
| **Entity Record Parity** | Vessels, cases, jurisdictions, evidences matched | Exact match | **PASS** |
| **Evidence SHA-256 Vault Verification** | `f4048435d386605ba4afce908631209a7288a4f9430418e38c51a037e84d9b4b` verified | Bit-perfect match | **PASS** |
| **Audit Trail Preservation** | Historical logs structure preserved | No data loss | **PASS** |

**Final Restoration Verdict**: `SUCCESSFUL_RESTORATION_VERIFIED`.

---

### 3. AUTOMATED BACKUP PROCEDURE

Production backups are executed automatically via cron or systemd timer using `/var/www/maritime-deep-data/scripts/deployment/backup.sh`.

#### Manual Execution Command:
```bash
sudo -u mdd_app /var/www/maritime-deep-data/scripts/deployment/backup.sh
```

#### What the Script Performs:
1. Generates custom format PostgreSQL dump with blob tables.
2. Generates SHA-256 checksum for the database dump.
3. Compresses `backend/storage/app/private_evidence` into a timestamped archive.
4. Generates SHA-256 checksum for the evidence archive.
5. Archives sanitized configuration templates (excluding live `.env`).
6. Produces a unified `manifest.json` recording backup provenance.
7. Enforces 30-day retention pruning.

---

### 4. DISASTER RESTORATION PROCEDURE

In the event of database corruption, ransomware, or server hardware loss:

#### Step 1: Identify Backup Set
```bash
ls -lt /var/backups/maritime-deep-data
TARGET_BACKUP="/var/backups/maritime-deep-data/20261119_192139"
```

#### Step 2: Verify Checksum Integrity
```bash
cd "${TARGET_BACKUP}"
sha256sum -c *.dump.sha256
sha256sum -c *.tar.gz.sha256
```
*If checksum mismatch occurs, STOP and evaluate secondary backup mirror.*

#### Step 3: Execute Restoration Script
```bash
sudo -u mdd_app /var/www/maritime-deep-data/scripts/deployment/restore.sh "${TARGET_BACKUP}"
```

#### Step 4: Verify Post-Restoration System Health
```bash
curl -s -k https://mdd-investigations.ngo.internal/api/v1/health | jq .
```
Verify that `status` is `operational` and all sub-checks return `healthy`.
