# MARITIME DEEP DATA — DATABASE MIGRATION PLAN
## Non-Destructive Database Management, Integrity Rules & Rollback Runbook

---

### 1. CARDINAL RULES FOR PRODUCTION DATABASE OPERATIONS

1. **NEVER USE `migrate:fresh` OR `migrate:reset` ON PRODUCTION**:
   - Any destructive command that drops tables or resets schemas is strictly forbidden.
   - All migrations must be additive, forward-compatible, and reversible.

2. **MANDATORY PRE-MIGRATION BACKUP**:
   - No migration may be executed on production without a freshly verified logical dump (`pg_dump`) and SHA-256 manifest.

3. **PRESERVE INVESTIGATIVE HISTORICAL DATA & PROVENANCE**:
   - Existing vessel records, corporate shareholding graphs, crew welfare records, evidence hashes, chain of custody logs, and audit entries must never be modified or deleted by migration scripts.

4. **EXPLICIT POSTGIS EXTENSION PROTECTION**:
   - PostGIS extensions (`postgis`, `pg_trgm`, `pgcrypto`) are system extensions. Migrations must never attempt to drop or alter extensions without DBA sign-off.

---

### 2. PRE-MIGRATION VERIFICATION CHECKLIST

Before running `php artisan migrate --force` during any release:

- [ ] **Step 1: Check Database Free Space**
  ```bash
  df -h /var/lib/postgresql
  ```
  *Requirement: At least 25% free disk space available for table rewriting and index rebuilds.*

- [ ] **Step 2: Take Immediate Production Snapshot**
  ```bash
  /var/www/maritime-deep-data/scripts/deployment/backup.sh
  ```
  *Verify backup file size and SHA-256 manifest exist.*

- [ ] **Step 3: Inspect Pending Migration Files**
  ```bash
  cd /var/www/maritime-deep-data/backend
  php artisan migrate:status
  ```
  *Review each pending migration file for dangerous operations (e.g., `dropTable`, `dropColumn`, `renameColumn` without deprecation phase).*

- [ ] **Step 4: Verify PostGIS Extension Status**
  ```sql
  SELECT PostGIS_Full_Version();
  ```
  *Ensure PostGIS 3.6.x is active and healthy.*

---

### 3. SAFE MIGRATION EXECUTION RUNBOOK

#### Execution Steps:
```bash
# 1. Enter backend directory
cd /var/www/maritime-deep-data/backend

# 2. Put application into maintenance mode (graceful bypass for dev/admin)
php artisan down --retry=60 --secret="mdd-emergency-bypass-token"

# 3. Execute migrations in transactional mode
php artisan migrate --force

# 4. Clear and rebuild schema and route caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 5. Bring application back online
php artisan up
```

---

### 4. POST-MIGRATION INTEGRITY VERIFICATION

Run the following automated verification checks immediately after migration:

#### 1. Schema Parity & Table Count
```sql
SELECT count(*) AS total_tables 
FROM information_schema.tables 
WHERE table_schema = 'public';
```
*Baseline: 70 tables registered.*

#### 2. Spatial Index & Geometry Integrity
```sql
-- Check that all geometries remain valid according to OGC/ISO WGS84 standards
SELECT id, name, ST_IsValid(geom) AS valid, ST_GeometryType(geom) AS geom_type 
FROM maritime_jurisdictions 
WHERE ST_IsValid(geom) = false;
```
*Expected: 0 invalid rows.*

#### 3. Spatial Containment Query
```sql
-- Validate Natuna Basin spatial index lookup
SELECT name, jurisdiction_type, legal_status 
FROM maritime_jurisdictions 
WHERE ST_Contains(geom, ST_SetSRID(ST_Point(108.0, 4.0), 4326));
```
*Expected: Returns active EEZ jurisdiction.*

#### 4. Foreign Key & UUID Consistency
```sql
-- Verify no orphaned evidence relationships
SELECT count(*) AS orphaned_evidence 
FROM evidence_relationships er 
LEFT JOIN evidences e ON er.evidence_id = e.id 
WHERE e.id IS NULL;
```
*Expected: 0 orphaned rows.*

---

### 5. ROLLBACK STRATEGY

If a migration fails or causes unexpected database errors:

#### Plan A: Transactional Rollback (Single Migration)
```bash
cd /var/www/maritime-deep-data/backend
php artisan migrate:rollback --step=1
```
*Only applicable if the migration implemented a clean `down()` method and failed without leaving dangling locks.*

#### Plan B: Full Point-in-Time Database Restore
If schema corruption, lock deadlock, or data loss occurred:
```bash
# 1. Put application into permanent maintenance mode
php artisan down

# 2. Restore database from pre-migration backup
LATEST_BACKUP=$(ls -td /var/backups/maritime-deep-data/* | head -n 1)
/var/www/maritime-deep-data/scripts/deployment/restore.sh "${LATEST_BACKUP}"

# 3. Clear application cache
php artisan cache:clear
php artisan config:cache

# 4. Bring application up
php artisan up
```
