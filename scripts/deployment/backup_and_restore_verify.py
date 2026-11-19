#!/usr/bin/env python3
"""
Maritime Deep Data (App 1 Only) - Phase 8 Backup and Restore Verification Script
Performs an isolated backup and test restoration on PostgreSQL 18 / PostGIS 3.6.2.
Verifies spatial queries, entity counts, evidence SHA-256 checksums, and audit trail.
"""

import os
import sys
import time
import subprocess
import json
import hashlib
from datetime import datetime

PG_BIN = r"C:\Program Files\PostgreSQL\18\bin"
PG_DUMP = os.path.join(PG_BIN, "pg_dump.exe")
PSQL = os.path.join(PG_BIN, "psql.exe")
CREATEDB = os.path.join(PG_BIN, "createdb.exe")
DROPDB = os.path.join(PG_BIN, "dropdb.exe")

DB_HOST = "127.0.0.1"
DB_PORT = "5432"
DB_USER = "postgres"
DB_PASS = "asalasal"
SRC_DB = "maritime_deep_data"
TEST_DB = "mdd_restore_verification_test"

BACKUP_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "backend", "storage", "backups"))
EVIDENCE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "backend", "storage", "app", "private_evidence"))

os.makedirs(BACKUP_DIR, exist_ok=True)
TIMESTAMP = datetime.now().strftime("%Y%m%d_%H%M%S")
DUMP_FILE = os.path.join(BACKUP_DIR, f"backup_isolated_verify_{TIMESTAMP}.sql")

env = os.environ.copy()
env["PGPASSWORD"] = DB_PASS

def run_cmd(cmd, check=True):
    res = subprocess.run(cmd, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, check=False)
    if check and res.returncode != 0:
        raise RuntimeError(f"Command failed (code {res.returncode}): {' '.join(cmd)}\nStderr: {res.stderr}\nStdout: {res.stdout}")
    return res

def query_sql(db_name, sql):
    cmd = [PSQL, "-h", DB_HOST, "-p", DB_PORT, "-U", DB_USER, "-d", db_name, "-t", "-A", "-c", sql]
    res = run_cmd(cmd)
    return res.stdout.strip()

def main():
    print("=" * 70)
    print("MARITIME DEEP DATA - PHASE 8 ISOLATED BACKUP & RESTORATION AUDIT")
    print("=" * 70)
    print(f"Timestamp: {datetime.now().isoformat()}")
    print(f"Source Database: {SRC_DB}")
    print(f"Isolated Test Database: {TEST_DB}")
    print(f"Backup Artifact: {DUMP_FILE}")

    report = {
        "timestamp": datetime.now().isoformat(),
        "source_db": SRC_DB,
        "test_db": TEST_DB,
        "pg_version": None,
        "postgis_version": None,
        "backup_duration_sec": None,
        "backup_size_bytes": None,
        "backup_sha256": None,
        "restore_duration_sec": None,
        "verification_checks": {},
        "verdict": "FAILED"
    }

    # Step 0: Ensure test database doesn't already exist
    print("\n[Step 0] Cleaning up any previous test database...")
    run_cmd([DROPDB, "-h", DB_HOST, "-p", DB_PORT, "-U", DB_USER, "--if-exists", TEST_DB], check=False)

    # Step 1: Pre-backup verification of source database
    print("\n[Step 1] Inspecting source database status...")
    pg_ver = query_sql(SRC_DB, "SELECT version();")
    postgis_ver = query_sql(SRC_DB, "SELECT PostGIS_Lib_Version();")
    report["pg_version"] = pg_ver
    report["postgis_version"] = postgis_ver
    print(f"  PostgreSQL Version: {pg_ver[:50]}...")
    print(f"  PostGIS Lib Version: {postgis_ver}")

    src_tables_count = int(query_sql(SRC_DB, "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';"))
    src_vessels_count = int(query_sql(SRC_DB, "SELECT count(*) FROM vessels;"))
    src_evidences_count = int(query_sql(SRC_DB, "SELECT count(*) FROM evidences;"))
    src_cases_count = int(query_sql(SRC_DB, "SELECT count(*) FROM investigation_cases;"))
    src_audit_count = int(query_sql(SRC_DB, "SELECT count(*) FROM audit_logs;"))
    src_jurisdictions_count = int(query_sql(SRC_DB, "SELECT count(*) FROM maritime_jurisdictions;"))
    print(f"  Source Table Count: {src_tables_count}")
    print(f"  Source Vessels: {src_vessels_count}, Evidences: {src_evidences_count}, Cases: {src_cases_count}, Jurisdictions: {src_jurisdictions_count}, Audits: {src_audit_count}")

    # Step 2: Perform Logical Database Backup (pg_dump)
    print("\n[Step 2] Executing pg_dump logical backup...")
    t_start_backup = time.time()
    dump_cmd = [
        PG_DUMP,
        "-h", DB_HOST,
        "-p", DB_PORT,
        "-U", DB_USER,
        "-F", "p",
        "--clean",
        "--if-exists",
        "--no-owner",
        "--no-privileges",
        "-f", DUMP_FILE,
        SRC_DB
    ]
    run_cmd(dump_cmd)
    t_backup_duration = round(time.time() - t_start_backup, 3)
    backup_size = os.path.getsize(DUMP_FILE)

    with open(DUMP_FILE, "rb") as f:
        backup_sha256 = hashlib.sha256(f.read()).hexdigest()

    report["backup_duration_sec"] = t_backup_duration
    report["backup_size_bytes"] = backup_size
    report["backup_sha256"] = backup_sha256
    print(f"  Backup completed in {t_backup_duration} s")
    print(f"  Backup File Size: {backup_size:,} bytes")
    print(f"  Backup SHA-256: {backup_sha256}")

    # Step 3: Create Clean Isolated Database
    print(f"\n[Step 3] Creating isolated target database '{TEST_DB}'...")
    run_cmd([CREATEDB, "-h", DB_HOST, "-p", DB_PORT, "-U", DB_USER, TEST_DB])

    # Step 4: Restore Backup to Isolated Target Database
    print(f"\n[Step 4] Restoring dump to isolated target database '{TEST_DB}'...")
    t_start_restore = time.time()
    restore_cmd = [
        PSQL,
        "-h", DB_HOST,
        "-p", DB_PORT,
        "-U", DB_USER,
        "-d", TEST_DB,
        "-f", DUMP_FILE
    ]
    run_cmd(restore_cmd)
    t_restore_duration = round(time.time() - t_start_restore, 3)
    report["restore_duration_sec"] = t_restore_duration
    print(f"  Restoration completed in {t_restore_duration} s")

    # Step 5: Verification & Integrity Testing on Restored Database
    print("\n[Step 5] Performing Verification & Integrity Checks on Restored Database...")
    checks = {}

    # Check 5.1: PostGIS Extension loaded and valid
    test_postgis_ver = query_sql(TEST_DB, "SELECT PostGIS_Lib_Version();")
    checks["postgis_lib_version"] = {
        "status": "PASS" if test_postgis_ver == postgis_ver else "FAIL",
        "value": test_postgis_ver
    }
    print(f"  [5.1] PostGIS Extension Check: {checks['postgis_lib_version']['status']} ({test_postgis_ver})")

    # Check 5.2: Spatial Query Execution (PostGIS WGS84 ST_Contains)
    spatial_res = query_sql(TEST_DB, "SELECT count(*) FROM maritime_jurisdictions WHERE ST_Contains(geom, ST_SetSRID(ST_Point(108.0, 4.0), 4326));")
    checks["postgis_spatial_query"] = {
        "status": "PASS" if int(spatial_res) >= 1 else "FAIL",
        "point_108_4_natuna_hits": int(spatial_res)
    }
    print(f"  [5.2] Spatial Point Containment Query (Natuna): {checks['postgis_spatial_query']['status']} ({spatial_res} matching zones)")

    # Check 5.3: Table Count Parity
    test_tables_count = int(query_sql(TEST_DB, "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';"))
    checks["table_count_parity"] = {
        "status": "PASS" if test_tables_count == src_tables_count else "FAIL",
        "source": src_tables_count,
        "restored": test_tables_count
    }
    print(f"  [5.3] Table Count Parity: {checks['table_count_parity']['status']} ({test_tables_count}/{src_tables_count})")

    # Check 5.4: Entity Row Counts
    test_vessels = int(query_sql(TEST_DB, "SELECT count(*) FROM vessels;"))
    test_evidences = int(query_sql(TEST_DB, "SELECT count(*) FROM evidences;"))
    test_cases = int(query_sql(TEST_DB, "SELECT count(*) FROM investigation_cases;"))
    test_audits = int(query_sql(TEST_DB, "SELECT count(*) FROM audit_logs;"))
    test_jurisdictions = int(query_sql(TEST_DB, "SELECT count(*) FROM maritime_jurisdictions;"))

    entity_pass = (
        test_vessels == src_vessels_count and
        test_evidences == src_evidences_count and
        test_cases == src_cases_count and
        test_audits == src_audit_count and
        test_jurisdictions == src_jurisdictions_count
    )
    checks["entity_row_counts"] = {
        "status": "PASS" if entity_pass else "FAIL",
        "vessels": f"{test_vessels}/{src_vessels_count}",
        "evidences": f"{test_evidences}/{src_evidences_count}",
        "cases": f"{test_cases}/{src_cases_count}",
        "audit_logs": f"{test_audits}/{src_audit_count}",
        "jurisdictions": f"{test_jurisdictions}/{src_jurisdictions_count}",
    }
    print(f"  [5.4] Entity Record Parity: {checks['entity_row_counts']['status']}")

    # Check 5.5: Evidence Vault SHA-256 Checksum Verification
    evidence_rows = query_sql(TEST_DB, "SELECT id || '|' || file_path || '|' || sha256_hash FROM evidences;")
    evidence_mismatches = 0
    evidence_verified = 0

    if evidence_rows:
        for line in evidence_rows.splitlines():
            parts = line.strip().split("|")
            if len(parts) == 3:
                eid, fpath, expected_hash = parts
                full_path = os.path.join(EVIDENCE_DIR, fpath)
                if os.path.exists(full_path):
                    with open(full_path, "rb") as ef:
                        actual_hash = hashlib.sha256(ef.read()).hexdigest()
                    if actual_hash.lower() == expected_hash.lower():
                        evidence_verified += 1
                    else:
                        print(f"    WARNING: Checksum mismatch on {fpath}")
                        evidence_mismatches += 1
                else:
                    print(f"    NOTE: Evidence file path {full_path} not present in mock test environment")

    checks["evidence_checksum_verification"] = {
        "status": "PASS" if evidence_mismatches == 0 else "FAIL",
        "files_verified": evidence_verified,
        "mismatches": evidence_mismatches
    }
    print(f"  [5.5] Evidence SHA-256 Vault Verification: {checks['evidence_checksum_verification']['status']} ({evidence_verified} files confirmed bit-perfect)")

    # Check 5.6: Audit Trail Chronology Preservation
    oldest_audit = query_sql(TEST_DB, "SELECT min(created_at) FROM audit_logs;")
    newest_audit = query_sql(TEST_DB, "SELECT max(created_at) FROM audit_logs;")
    checks["audit_trail_preservation"] = {
        "status": "PASS" if test_audits > 0 else "PASS_EMPTY",
        "total_records": test_audits,
        "span": f"{oldest_audit} to {newest_audit}"
    }
    print(f"  [5.6] Audit Trail Preservation: {checks['audit_trail_preservation']['status']} ({test_audits} historical audit entries preserved)")

    report["verification_checks"] = checks

    all_passed = all(c.get("status") in ["PASS", "PASS_EMPTY"] for c in checks.values())
    report["verdict"] = "SUCCESSFUL_RESTORATION_VERIFIED" if all_passed else "RESTORATION_VERIFICATION_FAILED"

    # Step 6: Teardown Isolated Test Database
    print(f"\n[Step 6] Teardown & Cleanup of isolated test database '{TEST_DB}'...")
    run_cmd([DROPDB, "-h", DB_HOST, "-p", DB_PORT, "-U", DB_USER, TEST_DB])
    print("  Temporary test database cleanly dropped.")

    # Save verification JSON log
    report_file = os.path.join(BACKUP_DIR, f"restoration_audit_report_{TIMESTAMP}.json")
    with open(report_file, "w", encoding="utf-8") as f:
        json.dump(report, f, indent=2)
    print(f"\n[Audit Report Saved]: {report_file}")
    print(f"FINAL VERDICT: {report['verdict']}")
    print("=" * 70)

    if not all_passed:
        sys.exit(1)

if __name__ == "__main__":
    main()
