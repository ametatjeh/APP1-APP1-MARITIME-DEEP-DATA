# MARITIME DEEP DATA (APP 1 ONLY)
### Private Internal NGO Research & Investigation Platform

Platform intelijen dan penelitian maritim internal NGO yang mandiri, evidence-driven, GIS-enabled, multi-country, dan AI-ready.

---

## SCOPE RESTRICTION & ARCHITECTURAL ISOLATION
Sesuai arahan arsitektur:
- **APP 1 ONLY**: Bebas dari live tracking, bebas dari raw AIS position ingestion/collector, dan tidak memiliki dependensi pada App 2.
- **Decoupled Architecture**: Frontend (`./frontend`) dan Backend (`./backend`) terpisah total.
- **Zero Auto-Merge Rule**: Identitas kapal dan konflik identifier (MMSI/IMO ganda) tidak pernah digabungkan secara otomatis tanpa ulasan analis.

---

## TECH STACK
- **Backend**: Laravel 13 (REST API headless, Sanctum Bearer Auth, Redis Queue, Scheduler)
- **Frontend**: Vue 3 + TypeScript + Vite + Tailwind CSS + Pinia + Vue Router + Axios
- **Database**: PostgreSQL 18.6 + PostGIS 3.6.2 (`GEOMETRY(MultiPolygon, 4326)`) + `pg_trgm` (GIN fuzzy search) + `pgcrypto` + UUID Primary Keys + JSONB

---

## 21 NAVIGATION MENUS (APP 1)
1. **Dashboard** (`/dashboard`)
2. **Vessels Master** (`/vessels`)
3. **Vessel Identity Intelligence** (`/vessel-identity`)
4. **Corporate Intelligence** (`/corporate`)
5. **Persons Intelligence** (`/persons` - Phase 3)
6. **Crew Intelligence** (`/crew` - Phase 3)
7. **Country Management** (`/countries`)
8. **Maritime Jurisdiction** (`/jurisdictions`)
9. **EEZ Explorer (PostGIS Inspector)** (`/eez-explorer`)
10. **Investigation Cases** (`/investigations` - Phase 2)
11. **Evidence Management** (`/evidence` - Phase 2)
12. **IUU Intelligence** (`/iuu` - Phase 3)
13. **GIS Research Map** (`/gis-map` - Phase 3)
14. **Data Source Registry** (`/data-sources` - Phase 4)
15. **Watchlists & Alerts** (`/watchlists` - Phase 5)
16. **Network Graph Canvas** (`/network-graph`)
17. **Reports & Export** (`/reports` - Phase 5)
18. **AI Research Assistant** (`/ai-assistant` - Phase 6)
19. **User Management** (`/users`)
20. **Audit Logs** (`/audit-logs`)
21. **Settings** (`/settings`)

---

## DEFAULT CREDENTIALS (ROLE-BASED ACCESS CONTROL)
Semua akun menggunakan hashing aman (`bcrypt`):

| Role | Email | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@maritimedeepdata.org` | `SecretAdmin2026!` | Akses sistem penuh, kelola user & role |
| **Research Director** | `director@maritimedeepdata.org` | `DirectorDeepData2026!` | Akses PII terlindungi, hapus data, review konflik |
| **Senior Investigator**| `senior.inv@maritimedeepdata.org`| `InvestigatorSenior2026!` | Review & resolusi konflik identitas, buat data |
| **Investigator** | `investigator@maritimedeepdata.org`| `InvestigatorPass2026!` | Buat/edit data kapal & korporasi |
| **Research Analyst** | `analyst@maritimedeepdata.org` | `AnalystPass2026!` | Analisis data, evaluasi spasial PostGIS |
| **Evidence Manager** | `evidence@maritimedeepdata.org`| `EvidencePass2026!` | Integritas bukti & audit custody |
| **Viewer** | `viewer@maritimedeepdata.org` | `ViewerPass2026!` | Read-only, data PII sensitif otomatis disamarkan |

---

## CARA MENJALANKAN (DEVELOPMENT)

### 1. Menjalankan Backend API
```bash
cd backend
php artisan serve --port=8000
```
Backend berjalan pada: `http://127.0.0.1:8000`  
Endpoint API: `http://127.0.0.1:8000/api/v1`

### 2. Menjalankan Frontend Vue 3
```bash
cd frontend
npm run dev
```
Frontend berjalan pada: `http://localhost:5173`

### 3. Menjalankan Automated Test Suite
```bash
cd backend
php artisan test
```
*Hasil:* 17 Tests passed (186 assertions) mencakup autentikasi Sanctum, otorisasi RBAC, PostGIS spatial point lookup, multi-country engine, vessel identity conflicts, dan corporate ownership provenance.
