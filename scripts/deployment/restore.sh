#!/usr/bin/env bash
# ==============================================================================
# Maritime Deep Data (App 1 Only) - Emergency Restoration Script
# Restores PostgreSQL database, PostGIS spatial layers, and private evidence vault.
# ==============================================================================
set -euo pipefail

if [ "$#" -lt 1 ]; then
  echo "Usage: $0 <path-to-backup-dir-or-timestamp>"
  echo "Example: $0 /var/backups/maritime-deep-data/20261119_190000"
  exit 1
fi

TARGET_DIR="$1"
APP_ROOT="${APP_ROOT:-/var/www/maritime-deep-data}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE:-maritime_deep_data}"
DB_USER="${DB_USERNAME:-mdd_app}"
export PGPASSWORD="${DB_PASSWORD:-}"

echo "[ALERT] You are about to restore Maritime Deep Data from: ${TARGET_DIR}"
echo "[ALERT] Target Database: ${DB_NAME}"

# 1. Verify Checksums
echo "[INFO] Verifying archive checksums before restoration..."
cd "${TARGET_DIR}"
if [ -f *.dump.sha256 ]; then
  sha256sum -c *.dump.sha256
fi
if [ -f *.tar.gz.sha256 ]; then
  sha256sum -c *.tar.gz.sha256
fi

# 2. Database Restoration
DUMP_FILE=$(ls "${TARGET_DIR}"/database_*.dump | head -n 1)
if [ -f "${DUMP_FILE}" ]; then
  echo "[INFO] Restoring PostgreSQL database from ${DUMP_FILE}..."
  # Terminate active connections to allow clean restore
  psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d postgres -c \
    "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${DB_NAME}' AND pid <> pg_backend_pid();" || true

  pg_restore -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" \
    -d "${DB_NAME}" --clean --if-exists --no-owner --no-privileges -v "${DUMP_FILE}"
else
  echo "[ERROR] Database dump file not found in ${TARGET_DIR}!"
  exit 1
fi

# 3. Restore Evidence Vault
EVIDENCE_TAR=$(ls "${TARGET_DIR}"/private_evidence_*.tar.gz 2>/dev/null | head -n 1 || true)
if [ -n "${EVIDENCE_TAR}" ] && [ -f "${EVIDENCE_TAR}" ]; then
  echo "[INFO] Restoring private evidence vault..."
  tar -xzf "${EVIDENCE_TAR}" -C "${APP_ROOT}/backend/storage/app"
  chown -R www-data:www-data "${APP_ROOT}/backend/storage/app/private_evidence"
  chmod -R 750 "${APP_ROOT}/backend/storage/app/private_evidence"
fi

# 4. Post-Restore Verification
echo "[INFO] Verifying restored PostGIS extension and table count..."
psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" -c "SELECT PostGIS_Lib_Version();"
psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" -c "SELECT count(*) AS vessels_count FROM vessels;"
psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" -c "SELECT count(*) AS evidence_count FROM evidences;"

echo "[SUCCESS] Restoration completed successfully."
