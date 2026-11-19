#!/usr/bin/env bash
# ==============================================================================
# Maritime Deep Data (App 1 Only) - Automated Production Backup Script
# Backs up PostgreSQL database, PostGIS spatial layers, and private evidence vault.
# ==============================================================================
set -euo pipefail

BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/maritime-deep-data}"
APP_ROOT="${APP_ROOT:-/var/www/maritime-deep-data}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="${BACKUP_ROOT}/${TIMESTAMP}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE:-maritime_deep_data}"
DB_USER="${DB_USERNAME:-mdd_app}"
export PGPASSWORD="${DB_PASSWORD:-}"

mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

echo "[INFO] [${TIMESTAMP}] Starting Maritime Deep Data backup..."

# 1. Database Logical Dump (PostgreSQL + PostGIS)
echo "[INFO] Exporting PostgreSQL/PostGIS database '${DB_NAME}'..."
pg_dump -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -F c -b -v \
  -f "${BACKUP_DIR}/database_${DB_NAME}_${TIMESTAMP}.dump" "${DB_NAME}"

# Calculate Database Dump SHA-256
sha256sum "${BACKUP_DIR}/database_${DB_NAME}_${TIMESTAMP}.dump" > "${BACKUP_DIR}/database_${DB_NAME}_${TIMESTAMP}.dump.sha256"

# 2. Private Evidence Vault Backup (Archive)
EVIDENCE_SRC="${APP_ROOT}/backend/storage/app/private_evidence"
if [ -d "${EVIDENCE_SRC}" ]; then
  echo "[INFO] Archiving private evidence vault..."
  tar -czf "${BACKUP_DIR}/private_evidence_${TIMESTAMP}.tar.gz" -C "${APP_ROOT}/backend/storage/app" private_evidence
  sha256sum "${BACKUP_DIR}/private_evidence_${TIMESTAMP}.tar.gz" > "${BACKUP_DIR}/private_evidence_${TIMESTAMP}.tar.gz.sha256"
else
  echo "[WARN] Evidence directory not found at ${EVIDENCE_SRC}, skipping evidence archive."
fi

# 3. Environment & Configuration Template (NO SECRETS)
echo "[INFO] Archiving sanitized configuration templates..."
tar -czf "${BACKUP_DIR}/config_templates_${TIMESTAMP}.tar.gz" \
  -C "${APP_ROOT}" \
  --exclude="*.env" \
  --exclude="node_modules" \
  --exclude="vendor" \
  --exclude=".git" \
  backend/.env.production.example frontend/nginx scripts docs

sha256sum "${BACKUP_DIR}/config_templates_${TIMESTAMP}.tar.gz" > "${BACKUP_DIR}/config_templates_${TIMESTAMP}.tar.gz.sha256"

# 4. Create Combined Verification Manifest
cat <<EOF > "${BACKUP_DIR}/manifest.json"
{
  "timestamp": "${TIMESTAMP}",
  "database": "${DB_NAME}",
  "db_dump_file": "database_${DB_NAME}_${TIMESTAMP}.dump",
  "evidence_archive": "private_evidence_${TIMESTAMP}.tar.gz",
  "config_archive": "config_templates_${TIMESTAMP}.tar.gz",
  "created_by": "mdd_backup_automation",
  "status": "COMPLETED"
}
EOF

echo "[INFO] Backup successfully completed in ${BACKUP_DIR}"

# 5. Apply Retention Policy (Prune archives older than RETENTION_DAYS)
echo "[INFO] Enforcing retention policy: deleting backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_ROOT}" -maxdepth 1 -mindepth 1 -type d -mtime +"${RETENTION_DAYS}" -exec rm -rf {} +

echo "[INFO] Backup process finished cleanly."
