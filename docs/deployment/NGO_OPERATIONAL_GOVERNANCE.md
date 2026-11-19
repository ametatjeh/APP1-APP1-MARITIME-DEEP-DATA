# MARITIME DEEP DATA — NGO OPERATIONAL GOVERNANCE
## Research Ethics, Case Classification, Human Review Gates & Data Custody

---

### 1. CORE OPERATIONAL PRINCIPLES

Maritime Deep Data is operated under strict adherence to international research standards, human rights safeguards, and legal chain of custody requirements:

1. **Evidentiary Grounding**: No finding, vessel link, corporate beneficial ownership tie, or IUU infraction may be asserted without an accountable, verified source provenance record.
2. **Absolute Human Governance**: Artificial Intelligence microservices are advisory only. Under no circumstances may AI algorithms automatically create, alter, or certify verified master intelligence records.
3. **Protection of Informants & Vulnerable Crew**: Whistleblower identities and vulnerable crew records must be handled under strict confidentiality, with PII encrypted at rest and access restricted to authorized investigators.
4. **Non-Partisan Accuracy**: Disputed maritime jurisdictions and overlapping boundaries must reflect official treaties, national claims, and recognized UNCLOS legal frameworks without political bias.

---

### 2. INVESTIGATION CASE CLASSIFICATION FRAMEWORK

Every investigation case created in Maritime Deep Data must be assigned one of three confidentiality tiers:

| Classification | Visibility / Access | Evidence Handling | Typical Scenario |
|---|---|---|---|
| **Routine / Internal** | All authenticated NGO analysts and investigators. | Standard private storage; access logged. | Baseline vessel ownership research, corporate filings analysis. |
| **Confidential** | Explicitly assigned investigators + Research Director. | Restricted download permissions; individual access audited. | Active IUU fishing investigation, multi-jurisdiction smuggling inquiries. |
| **Restricted / Sensitive** | Designated Lead Investigator and Research Director only. | Strict compartmentalization; downloads require dual-confirmation. | Whistleblower-originated evidence, sensitive informant testimony, human trafficking/forced labour cases. |

---

### 3. HUMAN-IN-THE-LOOP VERIFICATION GATES

Maritime Deep Data implements immutable review gates for critical intelligence actions:

```
[ INCOMING INTELLIGENCE / RAW DATA ]
                  │
                  ▼
┌──────────────────────────────────────────────┐
│           AUTOMATED ENRICHMENT &             │
│        AI MULTI-AGENT SYNTHESIS              │
└──────────────────────┬───────────────────────┘
                       │
                       │ Outputs status: "pending_review"
                       ▼
┌──────────────────────────────────────────────┐
│         HUMAN ANALYST REVIEW GATE            │
│  - Senior Investigator or Research Director  │
│  - Inspects Original Source Documents        │
│  - Checks SHA-256 Hashes & Legal Grounds     │
│  - Evaluates Source Reliability (Tier 1-5)   │
└──────────────────────┬───────────────────────┘
                       │
            ┌──────────┴──────────┐
            ▼                     ▼
     [ REJECTED ]          [ VERIFIED & APPROVED ]
   - Detailed reason      - Appended to Chain of Custody
   - Never alters DB      - Master Database Updated
   - Audit trail logged   - Verified Badge Issued
```

1. **Identity Conflict Resolution**:
   - When conflicting identifiers (e.g., dual MMSI or duplicated IMO) are detected, the system generates an identity conflict alert.
   - An analyst must submit a formal, written investigation note before any identifier update is accepted. The system never merges records automatically.

2. **AI Finding Approval**:
   - Agent outputs from `VesselIntelligenceAgent`, `CorporateIntelligenceAgent`, etc. are stored in `ai_findings`.
   - Only a `research_director` or `senior_investigator` can mark findings as `verified`.

---

### 4. DATA RETENTION & PERIODIC AUDIT SCHEDULE

| Activity | Frequency | Responsible Role | Deliverable |
|---|---|---|---|
| **User Access & Role Audit** | Monthly | Super Admin + Research Director | Inactive accounts deactivated; permissions verified. |
| **Backup & Restoration Drill** | Quarterly | Senior DevOps Engineer / DBA | Executed restoration test in isolated test DB with report. |
| **Evidence Vault Hash Audit** | Semi-Annually | Evidence Manager | Complete audit of all evidence files against SHA-256 DB hashes. |
| **Data Retention Review** | Annually | Legal Counsel / Director | Identification of expired cases or records subject to right-of-erasure. |
| **Security Architecture Audit** | Annually | Application Security Engineer | Complete review of firewall, TLS certificates, and dependency CVEs. |
