<template>
  <div class="space-y-6">
    <!-- Header Notice -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-white">Maritime Deep Data Intelligence Deck</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Restricted NGO investigative platform for fisheries compliance, corporate networks, and maritime boundaries.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <span class="px-2.5 py-1 rounded bg-emerald-950 border border-emerald-800/80 text-emerald-300 font-mono text-xs">
          APP 1 STANDALONE: OPERATIONAL
        </span>
      </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
      <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-1">
          <span>Vessels</span>
          <span>🚢</span>
        </div>
        <div class="text-xl font-bold text-white">{{ stats.vessels }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">Master DB</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-1">
          <span>Companies</span>
          <span>🏢</span>
        </div>
        <div class="text-xl font-bold text-white">{{ stats.companies }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">Corporate</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-1">
          <span>Persons</span>
          <span>👤</span>
        </div>
        <div class="text-xl font-bold text-cyan-300">{{ stats.persons }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">UBOs & Skippers</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-cyan-800/60 bg-cyan-950/20">
        <div class="flex items-center justify-between text-cyan-300 text-xs mb-1">
          <span>Active Cases</span>
          <span>📂</span>
        </div>
        <div class="text-xl font-bold text-cyan-300">{{ stats.cases }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">Investigations</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-emerald-800/60 bg-emerald-950/20">
        <div class="flex items-center justify-between text-emerald-300 text-xs mb-1">
          <span>Evidence Vault</span>
          <span>🛡️</span>
        </div>
        <div class="text-xl font-bold text-emerald-300">{{ stats.evidences }}</div>
        <div class="text-[9px] text-emerald-400 mt-0.5 font-mono">SHA-256 Valid</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-rose-800/60 bg-rose-950/20">
        <div class="flex items-center justify-between text-rose-300 text-xs mb-1">
          <span>IUU Listed</span>
          <span>🚨</span>
        </div>
        <div class="text-xl font-bold text-rose-300">{{ stats.iuuListings }}</div>
        <div class="text-[9px] text-rose-400 mt-0.5 font-mono">RFMO Sanctions</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-purple-800/60 bg-purple-950/20">
        <div class="flex items-center justify-between text-purple-300 text-xs mb-1">
          <span>Pending Reviews</span>
          <span>⚖️</span>
        </div>
        <div class="text-xl font-bold text-purple-300">{{ stats.pendingVerifications }}</div>
        <div class="text-[9px] text-purple-400 mt-0.5 font-mono">Verification</div>
      </div>

      <div class="p-3 rounded-xl bg-slate-900 border border-amber-800/60 bg-amber-950/20">
        <div class="flex items-center justify-between text-amber-300 text-xs mb-1">
          <span>Spatial Layers</span>
          <span>📍</span>
        </div>
        <div class="text-xl font-bold text-amber-300">{{ stats.researchPolygons }}</div>
        <div class="text-[9px] text-amber-400 mt-0.5 font-mono">AOIs & Events</div>
      </div>
    </div>

    <!-- Phase 1 Focus Modules -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- High Priority Identity Review Panel -->
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-lg">🔍</span>
            <h4 class="text-sm font-bold text-white">Pending Identity Resolution</h4>
          </div>
          <router-link to="/vessel-identity" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium">
            Review Conflicts →
          </router-link>
        </div>

        <div v-if="loading" class="text-xs text-slate-400 py-4 text-center">Loading conflicts...</div>
        <div v-else-if="conflictsList.length === 0" class="text-xs text-slate-400 py-4 text-center">
          No unresolved identity conflicts.
        </div>
        <div v-else class="space-y-2">
          <div
            v-for="c in conflictsList"
            :key="c.id"
            class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs flex items-center justify-between"
          >
            <div>
              <div class="font-semibold text-white flex items-center gap-2">
                <span>{{ c.conflicting_identifier_type.toUpperCase() }}: {{ c.conflicting_identifier_value }}</span>
                <span class="px-1.5 py-0.5 rounded text-[10px] bg-red-950 text-red-300 border border-red-800/80">
                  {{ c.conflict_nature }}
                </span>
              </div>
              <div class="text-slate-400 text-[11px] mt-1">
                Conflict Score: <span class="text-amber-400 font-mono">{{ c.conflict_score }}%</span> • Status: {{ c.resolution_status }}
              </div>
            </div>
            <router-link
              to="/vessel-identity"
              class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-cyan-300 text-xs font-medium"
            >
              Analyze
            </router-link>
          </div>
        </div>
      </div>

      <!-- Quick GIS Spatial Engine Test Panel -->
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-lg">🧭</span>
            <h4 class="text-sm font-bold text-white">PostGIS Jurisdiction Point Inspector</h4>
          </div>
          <router-link to="/eez-explorer" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium">
            Full EEZ Explorer →
          </router-link>
        </div>

        <p class="text-xs text-slate-400">
          Execute instantaneous spatial point containment over Indonesian archipelagic baselines and overlapping Natuna claims.
        </p>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Longitude (WGS 84)</label>
            <input
              v-model="quickLon"
              type="number"
              step="0.0001"
              class="w-full px-3 py-1.5 bg-slate-950 border border-slate-800 rounded text-xs text-white"
            />
          </div>
          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Latitude (WGS 84)</label>
            <input
              v-model="quickLat"
              type="number"
              step="0.0001"
              class="w-full px-3 py-1.5 bg-slate-950 border border-slate-800 rounded text-xs text-white"
            />
          </div>
        </div>

        <button
          @click="runQuickPointLookup"
          class="w-full py-2 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors"
        >
          Query PostGIS Point Intersection
        </button>

        <div v-if="pointResult" class="p-3 rounded bg-slate-950 border border-slate-800 text-xs space-y-1">
          <div class="flex items-center justify-between font-mono">
            <span class="text-slate-400">Overlapping / Disputed Zone:</span>
            <span :class="pointResult.in_disputed_or_overlapping_zone ? 'text-amber-400 font-bold' : 'text-emerald-400'">
              {{ pointResult.in_disputed_or_overlapping_zone ? 'YES (OVERLAP)' : 'NO (CLEAR)' }}
            </span>
          </div>
          <div v-for="j in pointResult.containing_jurisdictions" :key="j.id" class="text-[11px] text-cyan-300">
            • {{ j.country }} - {{ j.area_name }} ({{ j.legal_status }})
          </div>
        </div>
      </div>
    </div>

    <!-- Phase 4 Ingestion & Staging Operations Banner -->
    <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-base">📦</span>
          <h4 class="text-sm font-bold text-white">Data Ingestion Pipelines & Quarantine Staging</h4>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
            PHASE 4 ACTIVE
          </span>
        </div>
        <div class="flex items-center gap-2">
          <router-link to="/validation-inspector" class="text-xs text-slate-400 hover:text-white font-mono">
            Syntax Calculator →
          </router-link>
          <router-link to="/imports" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium font-mono">
            View All Batches →
          </router-link>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 font-mono text-xs">
        <div class="p-3 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-between">
          <div>
            <span class="text-slate-500 block text-[10px] uppercase">Import Pipelines</span>
            <span class="text-lg font-bold text-white">{{ stats.imports }}</span>
          </div>
          <span class="text-cyan-400 text-lg">🚀</span>
        </div>
        <div class="p-3 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-between">
          <div>
            <span class="text-slate-500 block text-[10px] uppercase">Dataset Versions</span>
            <span class="text-lg font-bold text-cyan-300">{{ stats.datasetVersions }}</span>
          </div>
          <span class="text-cyan-400 text-lg">🏷️</span>
        </div>
        <div class="p-3 rounded-lg bg-slate-950 border border-amber-900/60 flex items-center justify-between">
          <div>
            <span class="text-amber-400 block text-[10px] uppercase">Quarantined Staged</span>
            <span class="text-lg font-bold text-amber-300">{{ stats.quarantined }}</span>
          </div>
          <span class="text-amber-400 text-lg">⚠️</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';

const stats = ref({
  vessels: 0,
  companies: 0,
  conflicts: 0,
  jurisdictions: 0,
  countries: 0,
  cases: 0,
  evidences: 0,
  sources: 0,
  pendingVerifications: 0,
  persons: 0,
  crew: 0,
  iuuListings: 0,
  researchPolygons: 0,
  imports: 0,
  datasetVersions: 0,
  quarantined: 0,
});

const loading = ref(true);
const conflictsList = ref<any[]>([]);

const quickLon = ref(108.0);
const quickLat = ref(4.5);
const pointResult = ref<any>(null);

async function loadDashboardData() {
  loading.value = true;
  try {
    const [
      vesselsRes,
      companiesRes,
      conflictsRes,
      jurisdictionsRes,
      countriesRes,
      casesRes,
      evidencesRes,
      sourcesRes,
      queueRes,
      personsRes,
      crewRes,
      iuuRes,
      polyRes,
      importsRes,
      versionsRes,
    ] = await Promise.all([
      apiClient.get('/vessels?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/companies?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/identity/conflicts?per_page=5').catch(() => ({ data: { total: 0, data: [] } })),
      apiClient.get('/jurisdictions?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/countries?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/cases?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/evidences?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/sources?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/verification/queue').catch(() => ({ data: { data: [] } })),
      apiClient.get('/persons?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/crew/employments?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/iuu/listings?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/gis/polygons?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/imports?per_page=100').catch(() => ({ data: { total: 0, data: [] } })),
      apiClient.get('/datasets/versions?per_page=1').catch(() => ({ data: { total: 0 } })),
    ]);

    const importBatches = importsRes.data.data ?? [];
    const quarantinedCount = importBatches.reduce((acc: number, b: any) => acc + (b.quarantined_rows_count || 0), 0);

    stats.value = {
      vessels: vesselsRes.data.total ?? 0,
      companies: companiesRes.data.total ?? 0,
      conflicts: conflictsRes.data.total ?? 0,
      jurisdictions: jurisdictionsRes.data.total ?? 0,
      countries: countriesRes.data.total ?? 0,
      cases: casesRes.data.total ?? 0,
      evidences: evidencesRes.data.total ?? 0,
      sources: sourcesRes.data.total ?? 0,
      pendingVerifications: queueRes.data.data?.length ?? 0,
      persons: personsRes.data.total ?? 0,
      crew: crewRes.data.total ?? 0,
      iuuListings: iuuRes.data.total ?? 0,
      researchPolygons: polyRes.data.total ?? 0,
      imports: importsRes.data.total ?? 0,
      datasetVersions: versionsRes.data.total ?? 0,
      quarantined: quarantinedCount,
    };

    conflictsList.value = conflictsRes.data.data ?? [];
  } catch (err) {
    console.error('Failed to load dashboard metrics', err);
  } finally {
    loading.value = false;
  }
}

async function runQuickPointLookup() {
  try {
    const res = await apiClient.get(`/jurisdictions/point-lookup?longitude=${quickLon.value}&latitude=${quickLat.value}`);
    pointResult.value = res.data;
  } catch (err) {
    console.error(err);
  }
}

onMounted(() => {
  loadDashboardData();
  runQuickPointLookup();
});
</script>
