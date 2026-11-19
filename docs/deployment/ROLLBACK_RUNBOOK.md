# MARITIME DEEP DATA — ROLLBACK RUNBOOK
## Emergency Release Reversal, Version Downgrade & Recovery Procedures

---

### 1. ROLLBACK TRIGGERS & DECISION THRESHOLDS

A release rollback must be initiated immediately upon detecting any of the following conditions within 60 minutes of deployment:

1. **API Error Rate Spike**: HTTP 500/502/503 responses exceed 2% of total traffic.
2. **Authentication Failure**: Users across roles are unable to log in, renew Sanctum tokens, or access assigned cases.
3. **Database Integrity Violation**: PostGIS spatial queries fail or throw SQL errors; foreign key constraints trigger unhandled exceptions.
4. **Evidence Vault Inaccessibility**: Evidence file downloads fail or return SHA-256 checksum mismatches.
5. **Data Import Engine Crash**: Background queue workers fail repeatedly on import pipeline batches.

---

### 2. SYMLINK-BASED ATOMIC RELEASE ARCHITECTURE

Maritime Deep Data deployment uses a timestamped release directory structure:

```
/var/www/maritime-deep-data/
  ├── releases/
  │   ├── 20261119_180000/    (Previous Stable Release)
  │   └── 20261119_190000/    (Failed Current Release)
  ├── shared/
  │   ├── backend_env         (.env master production secrets)
  │   ├── storage/            (Storage root: logs, private_evidence, backups)
  │   └── ai_env              (ai_service/.env secrets)
  └── current -> releases/20261119_190000/
```

---

### 3. STEP-BY-STEP ROLLBACK RUNBOOK

#### Step 1: Enable Graceful Maintenance Mode
```bash
cd /var/www/maritime-deep-data/current/backend
php artisan down --retry=30 --secret="mdd-emergency-rollback"
```

#### Step 2: Atomic Symlink Switch to Previous Stable Release
```bash
PREV_RELEASE="/var/www/maritime-deep-data/releases/20261119_180000"

if [ -d "${PREV_RELEASE}" ]; then
    ln -sfn "${PREV_RELEASE}" /var/www/maritime-deep-data/current
    echo "[INFO] Switched current release symlink to ${PREV_RELEASE}"
else
    echo "[ERROR] Previous release directory does not exist!"
    exit 1
fi
```

#### Step 3: Database Rollback (If Migrations Were Executed)
- **Case A: Migration had reversible `down()` method**:
  ```bash
  cd /var/www/maritime-deep-data/current/backend
  php artisan migrate:rollback --step=1
  ```
- **Case B: Migration caused data corruption or schema deadlock**:
  Restore from the pre-deployment backup:
  ```bash
  /var/www/maritime-deep-data/scripts/deployment/restore.sh /var/backups/maritime-deep-data/PRE_DEPLOY_BACKUP
  ```

#### Step 4: Re-link Shared Configuration and Storage
Ensure persistent storage and environment files remain attached:
```bash
cd /var/www/maritime-deep-data/current

# Ensure backend .env and storage symlinks
rm -f backend/.env
ln -s /var/www/maritime-deep-data/shared/backend_env backend/.env
rm -rf backend/storage
ln -s /var/www/maritime-deep-data/shared/storage backend/storage

# Ensure AI service .env
rm -f ai_service/.env
ln -s /var/www/maritime-deep-data/shared/ai_env ai_service/.env
```

#### Step 5: Flush and Rebuild Application Caches
```bash
cd /var/www/maritime-deep-data/current/backend
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 6: Restart Process Supervisors
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart mdd-laravel-queue.service
sudo systemctl restart mdd-ai-service.service
sudo systemctl reload nginx
```

#### Step 7: Disable Maintenance Mode
```bash
cd /var/www/maritime-deep-data/current/backend
php artisan up
```

---

### 4. POST-ROLLBACK SANITY CHECKLIST

- [ ] Check `/api/v1/health` returns `status: operational`.
- [ ] Verify database queries succeed without schema errors.
- [ ] Test user login with an authorized test account.
- [ ] Verify that `storage/app/private_evidence` contains all uploaded files and hashes match.
- [ ] Inspect `storage/logs/laravel-*.log` for errors or unhandled exceptions.
- [ ] Inform NGO Research Director that rollback completed successfully.
