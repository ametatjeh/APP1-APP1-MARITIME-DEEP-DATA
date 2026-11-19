# MARITIME DEEP DATA — SECURITY HARDENING
## Defense-in-Depth Specification, Cryptographic Integrity & Access Controls

---

### 1. DEFENSE-IN-DEPTH ARCHITECTURE

Maritime Deep Data adopts a zero-trust, multi-layered security posture designed to safeguard sensitive investigative intelligence, whistleblowers, crew records, and legal evidence.

```
[ LAYER 1: NETWORK & PERIMETER ]
  - Ingress: HTTPS only (TLS 1.2 / TLS 1.3), Strict HSTS.
  - Internal: PostgreSQL (5432), Redis (6379), FastAPI (8100) bound to 127.0.0.1.
  - Web Server: Nginx blocks dotfiles, .env, backups, and direct storage URLs.

[ LAYER 2: HTTP & BROWSER SECURITY ]
  - X-Frame-Options: DENY (Prevents Clickjacking).
  - X-Content-Type-Options: nosniff (Prevents MIME confusion).
  - Content-Security-Policy: default-src 'self'; frame-ancestors 'none'.
  - Cookies: Secure, HttpOnly, SameSite=Strict.

[ LAYER 3: AUTHENTICATION & RBAC ]
  - Laravel Sanctum bearer token authentication with SHA-256 hashing.
  - 7 Granular NGO Roles: super_admin, research_director, senior_investigator, 
    investigator, research_analyst, evidence_manager, viewer.
  - Case-level confidentiality flags and investigator assignments.

[ LAYER 4: DATA ENCRYPTION & REDACTION ]
  - PII Fields (Passports, National IDs) encrypted via AES-256-CBC at rest.
  - Dynamic redaction masks PII for non-investigator roles (`viewer`, `analyst`).
  - No passwords, tokens, or PII exposed in application logs.

[ LAYER 5: EVIDENCE VAULT INTEGRITY ]
  - Private filesystem disk located outside public web document root.
  - Files named via randomized UUIDs without executable extensions.
  - Cryptographic SHA-256 hash verified upon upload and binary download.
  - Immutable Chain of Custody ledger.
```

---

### 2. ROLE-BASED ACCESS CONTROL (RBAC) MATRIX

| Role | Vessel / Company Master | GIS / Maritime Map | Cases & Notes | Upload Evidence | Verify Evidence | Data Import | System Admin |
|---|---|---|---|---|---|---|---|
| **super_admin** | Full | Full | Full | Full | Full | Full | **Full** |
| **research_director** | Full | Full | Full | Full | **Approve** | Full | Read-Only |
| **senior_investigator** | Full | Full | **Assigned Cases** | Full | **Review** | Full | None |
| **investigator** | Full | Full | **Assigned Cases** | Full | Submit | None | None |
| **evidence_manager** | Read-Only | Read-Only | Assigned Cases | **Full Vault** | **Verify Hashes**| None | None |
| **research_analyst** | Read-Only | Full | Read Assigned | None | Submit Notes | None | None |
| **viewer** | Read-Only | Read-Only (Public) | Forbidden | Forbidden | Forbidden | Forbidden | None |

---

### 3. SENSITIVE DATA ENCRYPTION & LOGGING REDACTION

1. **PII Field Encryption**:
   - In `Person.php`, sensitive attributes (`national_id_number`, `passport_number`, `date_of_birth`) are encrypted using Laravel's native Eloquent `encrypted` cast (`AES-256-CBC` with MAC).
   - Unauthorized API consumers receive masked strings: `ID: ***-****-1234`.

2. **Log Redaction**:
   - `config/logging.php` and custom formatters strip sensitive headers:
     `Authorization`, `X-Internal-Token`, `password`, `token`, `secret`.
   - Stack traces are suppressed in production (`APP_DEBUG=false`).

---

### 4. PRIVATE EVIDENCE STORAGE & CHAIN OF CUSTODY

1. **Directory Isolation**:
   - Evidence files reside at `/var/www/maritime-deep-data/backend/storage/app/private_evidence`.
   - Nginx explicitly returns `403 Forbidden` for any request matching `/storage/app/private_evidence` or `/storage/backups`.

2. **Download Authorization Workflow**:
   - Files can only be downloaded via `GET /api/v1/evidences/{id}/download`.
   - Laravel evaluates `EvidencePolicy@view`. If authorized:
     - Calculates binary SHA-256 of the physical file on disk.
     - Compares it with the immutable database record `evidences.sha256_hash`.
     - Appends an audit log entry recording: `User`, `Timestamp`, `Action: EVIDENCE_DOWNLOADED`.
     - Streams binary data with `Content-Type: application/octet-stream` and header `X-Evidence-SHA256: <hash>`.

---

### 5. APPLICATION SECURITY & INJECTION PREVENTION

- **SQL Injection**: Prevented by default using PDO parameterized prepared statements via Laravel Eloquent and Query Builder. Native PostGIS queries (`ST_Contains`, `ST_SetSRID`, `ST_Point`) use strict parameter binding:
  `DB::raw("ST_Contains(geom, ST_SetSRID(ST_Point(?, ?), 4326))", [$lon, $lat])`.
- **Cross-Site Scripting (XSS)**: Vue 3 auto-escapes interpolated template text (`{{ variable }}`). No `v-html` directives render unescaped user-supplied content.
- **Mass Assignment**: Eloquent models strictly whitelist writeable fields via `$fillable`. Guarded fields (`id`, `created_at`, `verification_status`) cannot be overwritten via bulk payloads.

---

### 6. PENETRATION TESTING & AUDIT STATUS

> [!NOTE]
> **Audit Status**:
> - Automated vulnerability scans and test suites have been executed (67 backend tests, 12 Python tests, TypeScript compilation passed).
> - **Penetration Testing Clarification**: No formal third-party offensive penetration test has been conducted. A formal penetration test by an accredited cybersecurity firm is recommended prior to exposing the system outside the NGO internal network.
