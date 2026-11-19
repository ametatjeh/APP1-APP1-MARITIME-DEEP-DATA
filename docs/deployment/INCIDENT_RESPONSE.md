# MARITIME DEEP DATA — INCIDENT RESPONSE
## Security Incident Classification, Containment Protocols & Forensics Runbooks

---

### 1. INCIDENT CLASSIFICATION MATRIX

| Severity | Definition | Examples | SLA to Contain |
|---|---|---|---|
| **P1 - CRITICAL** | Active compromise of system integrity, private evidence vault exfiltration, or database destruction. | Unauthorized DB dump download, compromised root/admin account, ransomware attack. | **< 15 Minutes** |
| **P2 - HIGH** | Compromised user credentials, unauthorized case access, evidence checksum mismatch, or critical service failure. | Sanctum token stolen, analyst accessing confidential case without authorization, SHA-256 mismatch. | **< 1 Hour** |
| **P3 - MEDIUM** | Suspicious login attempts, failed batch imports, AI service intermittent failures, rate limit exhaustion. | Brute force login spike, repeated 403 Forbidden events on API endpoints. | **< 4 Hours** |
| **P4 - LOW** | Minor configuration anomalies, non-critical log warnings, deprecated dataset version queries. | Stale cache key warnings, minor frontend UI formatting errors. | **< 24 Hours** |

---

### 2. INCIDENT CONTAINMENT RUNBOOKS

#### Incident Type A: Emergency Account Suspension (Compromised Credentials)
When an investigator or analyst account is suspected of compromise:
```bash
# 1. Immediate revocation of all Sanctum personal access tokens for the user
cd /var/www/maritime-deep-data/backend
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'compromised@ngo.internal')->first();
if (\$user) {
    \$user->tokens()->delete();
    \$user->is_active = false;
    \$user->save();
    echo 'User tokens revoked and account deactivated.';
}
"

# 2. Invalidate active Redis sessions
redis-cli --scan --pattern "mdd_cache_*" | xargs -r redis-cli del
```

---

#### Incident Type B: Evidence Tampering / SHA-256 Checksum Mismatch
If an evidence file fails its cryptographic integrity check during download:
1. **Immediate Quarantine**: The system automatically halts the file download and logs an integrity failure alert.
2. **Lock Evidence Record**:
   ```sql
   UPDATE evidences 
   SET verification_status = 'disputed', 
       notes = notes || ' [SYSTEM ALERT: Checksum mismatch detected during download]' 
   WHERE id = 'TARGET_EVIDENCE_UUID';
   ```
3. **Forensic Audit**: Compare the physical file on disk with the immutable cold backup:
   ```bash
   sha256sum /var/www/maritime-deep-data/backend/storage/app/private_evidence/path/to/file.pdf
   ```
4. **Restore Original Bitstream**: Retrieve the pristine original file from the verified cold backup archive and re-verify its SHA-256 hash.

---

#### Incident Type C: AI Hallucination / Unsupported Legal Claim Flagged
When an AI assistant output generates an ungrounded or contradictory claim (e.g., alleging an IUU violation or boundary breach without evidence grounding):
1. **Analyst Review Gate Enforcement**: The platform architecture inherently prevents AI findings from modifying master records. All AI outputs are saved in `ai_findings` with `review_status = 'pending_review'`.
2. **Rejection & Audit**: The investigator or Research Director formally marks the finding as `rejected` with reasons recorded.
3. **Prompt & Context Investigation**: Review the prompt trace and retrieved vector chunks in `ai_findings.raw_trace` to ascertain whether source data was corrupted or contradictory.

---

### 3. POST-INCIDENT FORENSICS & BLAMELESS POST-MORTEM

Following any P1 or P2 incident, the Incident Commander must assemble a Post-Incident Report within 48 hours containing:
1. **Timeline**: Exact chronological sequence of events (UTC timestamps from `audit_logs`).
2. **Root Cause Analysis (RCA)**: Identification of systemic vulnerability or procedural failure.
3. **Impact Assessment**: Inventory of vessels, persons, cases, or evidence files accessed.
4. **Remediation Action Items**: Specific code changes, firewall rules, or access policy updates.
5. **Sign-off**: Formal review by Research Director and Lead Security Engineer.
