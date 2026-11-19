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
        <router-link
          to="/search"
          class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-cyan-300 font-mono text-xs flex items-center gap-1.5"
        >
          <span>🔍</span> Global Search
        </router-link>
        <router-link
          to="/network-graph"
          class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-purple-300 font-mono text-xs flex items-center gap-1.5"
        >
          <span>🕸️</span> Network Canvas
        </router-link>
        <router-link
          to="/ai-assistant"
          class="px-2.5 py-1 rounded bg-cyan-950 hover:bg-cyan-900 border border-cyan-700 text-cyan-300 font-mono text-xs flex items-center gap-1.5"
        >
          <span>🤖</span> AI Assistant
        </router-link>
        <span class="px-2.5 py-1 rounded bg-emerald-950 border border-emerald-800/80 text-emerald-300 font-mono text-xs">
          APP 1 STANDALONE: PHASE 6 ACTIVE
        </span>
      </div>
    </div>

    <!-- Quick Stats Grid (10 Columns) -->
    <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-10 gap-2.5">
      <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-[11px] mb-1">
          <span>Vessels</span>
          <span>🚢</span>
        </div>
        <div class="text-lg font-bold text-white">{{ stats.vessels }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">Master DB</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-[11px] mb-1">
          <span>Companies</span>
          <span>🏢</span>
        </div>
        <div class="text-lg font-bold text-white">{{ stats.companies }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">Corporate</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-[11px] mb-1">
          <span>Persons</span>
          <span>👤</span>
        </div>
        <div class="text-lg font-bold text-cyan-300">{{ stats.persons }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">UBOs/Crew</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-cyan-800/60 bg-cyan-950/20">
        <div class="flex items-center justify-between text-cyan-300 text-[11px] mb-1">
          <span>Active Cases</span>
          <span>📂</span>
        </div>
        <div class="text-lg font-bold text-cyan-300">{{ stats.cases }}</div>
        <div class="text-[9px] text-cyan-400 mt-0.5 font-mono">Investigations</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-emerald-800/60 bg-emerald-950/20">
        <div class="flex items-center justify-between text-emerald-300 text-[11px] mb-1">
          <span>Evidence</span>
          <span>🛡️</span>
        </div>
        <div class="text-lg font-bold text-emerald-300">{{ stats.evidences }}</div>
        <div class="text-[9px] text-emerald-400 mt-0.5 font-mono">SHA-256 Valid</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-rose-800/60 bg-rose-950/20">
        <div class="flex items-center justify-between text-rose-300 text-[11px] mb-1">
          <span>IUU Listed</span>
          <span>🚨</span>
        </div>
        <div class="text-lg font-bold text-rose-300">{{ stats.iuuListings }}</div>
        <div class="text-[9px] text-rose-400 mt-0.5 font-mono">RFMO Banned</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-red-800/60 bg-red-950/30">
        <div class="flex items-center justify-between text-red-300 text-[11px] mb-1">
          <span>Active Alerts</span>
          <span>⚡</span>
        </div>
        <div class="text-lg font-bold text-red-400">{{ stats.activeAlerts }}</div>
        <div class="text-[9px] text-red-400 mt-0.5 font-mono">Surveillance</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-amber-800/60 bg-amber-950/20">
        <div class="flex items-center justify-between text-amber-300 text-[11px] mb-1">
          <span>Watchlists</span>
          <span>🎯</span>
        </div>
        <div class="text-lg font-bold text-amber-300">{{ stats.watchlists }}</div>
        <div class="text-[9px] text-amber-400 mt-0.5 font-mono">Targets Active</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-blue-800/60 bg-blue-950/20">
        <div class="flex items-center justify-between text-blue-300 text-[11px] mb-1">
          <span>Dossiers</span>
          <span>📑</span>
        </div>
        <div class="text-lg font-bold text-blue-300">{{ stats.reports }}</div>
        <div class="text-[9px] text-blue-400 mt-0.5 font-mono">Evidence-Linked</div>
      </div>

      <div class="p-2.5 rounded-xl bg-slate-900 border border-purple-800/60 bg-purple-950/20">
        <div class="flex items-center justify-between text-purple-300 text-[11px] mb-1">
          <span>Verifications</span>
          <span>⚖️</span>
        </div>
        <div class="text-lg font-bold text-purple-300">{{ stats.pendingVerifications }}</div>
        <div class="text-[9px] text-purple-400 mt-0.5 font-mono">Queue Pending</div>
      </div>
    </div>

    <!-- Phase 5 Surveillance Alerts Stream & High Priority Resolution -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Live Surveillance Alerts Feed (2 Cols) -->
      <div class="lg:col-span-2 p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base text-red-400 animate-pulse">⚡</span>
            <h4 class="text-sm font-bold text-white">Target Watchlists & Automated Surveillance Alerts</h4>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-red-950 text-red-300 border border-red-800">
              REAL-TIME ENGINE
            </span>
          </div>
          <router-link to="/watchlists" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium">
            Manage Watchlists & Alerts →
          </router-link>
        </div>

        <div v-if="loadingAlerts" class="text-xs text-slate-400 py-6 text-center">Loading surveillance feed...</div>
        <div v-else-if="recentAlerts.length === 0" class="text-xs text-slate-400 py-6 text-center">
          No unacknowledged high-severity surveillance alerts. Target entities are clear.
        </div>
        <div v-else class="space-y-2.5">
          <div
            v-for="alert in recentAlerts"
            :key="alert.id"
            class="p-3.5 rounded-lg bg-slate-950 border border-slate-800 hover:border-slate-700 transition flex items-start justify-between gap-4"
          >
            <div class="space-y-1 text-xs flex-1">
              <div class="flex items-center gap-2">
                <span class="font-mono text-red-400 font-bold">{{ alert.alert_code }}</span>
                <span
                  :class="[
                    'px-1.5 py-0.2 rounded text-[10px] font-mono uppercase font-bold',
                    alert.severity === 'critical' ? 'bg-red-950 text-red-300 border border-red-800' :
                    alert.severity === 'high' ? 'bg-amber-950 text-amber-300 border border-amber-800' :
                    'bg-slate-800 text-slate-300'
                  ]"
                >
                  {{ alert.severity }}
                </span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-mono bg-slate-800 text-slate-400">
                  {{ alert.watchlist?.name || 'General Rule' }}
                </span>
                <span class="text-[10px] text-slate-500 font-mono">
                  {{ formatDate(alert.created_at) }}
                </span>
              </div>
              <div class="font-semibold text-white text-xs">
                {{ alert.title }}
              </div>
              <p class="text-[11px] text-slate-400 line-clamp-1">
                {{ alert.description }}
              </p>
              <div v-if="alert.entity" class="text-[11px] text-cyan-400 font-mono mt-0.5">
                Target Entity: {{ alert.entity.name || alert.entity.vessel_name || alert.entity.full_name || alert.entity_id }}
                <span v-if="alert.entity.imo_number" class="text-slate-400 ml-1">IMO: {{ alert.entity.imo_number }}</span>
                <span v-if="alert.entity.flag_country_code" class="text-slate-400 ml-1">Flag: {{ alert.entity.flag_country_code }}</span>
              </div>
            </div>

            <div class="flex flex-col gap-1 items-end">
              <button
                v-if="alert.status === 'active'"
                @click="acknowledgeAlert(alert.id)"
                class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-cyan-300 text-[11px] font-mono"
              >
                Acknowledge
              </button>
              <span v-else class="text-[10px] text-slate-500 font-mono uppercase">
                {{ alert.status }}
              </span>
              <router-link
                to="/watchlists"
                class="text-[10px] text-slate-400 hover:text-white font-mono"
              >
                Inspect →
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick GIS Spatial Inspector (1 Col) -->
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base">🧭</span>
            <h4 class="text-sm font-bold text-white">PostGIS Jurisdiction Point Inspector</h4>
          </div>
          <router-link to="/eez-explorer" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium">
            EEZ Explorer →
          </router-link>
        </div>

        <p class="text-xs text-slate-400">
          Execute instantaneous spatial point containment over Indonesian archipelagic baselines and overlapping Natuna claims.
        </p>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-[10px] text-slate-400 mb-1">Longitude (WGS 84)</label>
            <input
              v-model="quickLon"
              type="number"
              step="0.0001"
              class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded text-xs text-white"
            />
          </div>
          <div>
            <label class="block text-[10px] text-slate-400 mb-1">Latitude (WGS 84)</label>
            <input
              v-model="quickLat"
              type="number"
              step="0.0001"
              class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded text-xs text-white"
            />
          </div>
        </div>

        <button
          @click="runQuickPointLookup"
          class="w-full py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors"
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

      <!-- Quick Research & Dossier Hub -->
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-lg">📑</span>
            <h4 class="text-sm font-bold text-white">Evidence-Linked Dossiers & Network Graphs</h4>
          </div>
          <router-link to="/reports" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium">
            View All Reports →
          </router-link>
        </div>

        <p class="text-xs text-slate-400">
          Generate tamper-proof intelligence reports sealed with cryptographic SHA-256 snapshots, or explore corporate ownership graphs with multi-hop seed analysis.
        </p>

        <div class="grid grid-cols-2 gap-3 pt-2">
          <router-link
            to="/reports"
            class="p-3 rounded-lg bg-slate-950 border border-slate-800 hover:border-cyan-700/60 transition group"
          >
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-bold text-white group-hover:text-cyan-300">Dossier Hub</span>
              <span class="text-sm">📄</span>
            </div>
            <p class="text-[11px] text-slate-400">Vessel and corporate profiles compiled with exact evidence citations.</p>
          </router-link>

          <router-link
            to="/network-graph"
            class="p-3 rounded-lg bg-slate-950 border border-slate-800 hover:border-purple-700/60 transition group"
          >
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-bold text-white group-hover:text-purple-300">Network Canvas</span>
              <span class="text-sm">🕸️</span>
            </div>
            <p class="text-[11px] text-slate-400">Interactive multi-hop node visualizer tracing beneficial owners.</p>
          </router-link>
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
  activeAlerts: 0,
  watchlists: 0,
  reports: 0,
});

const loading = ref(true);
const loadingAlerts = ref(false);
const conflictsList = ref<any[]>([]);
const recentAlerts = ref<any[]>([]);

const quickLon = ref(108.0);
const quickLat = ref(4.5);
const pointResult = ref<any>(null);

function formatDate(dateStr: string) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString();
}

async function acknowledgeAlert(alertId: string) {
  try {
    await apiClient.post(`/watchlists/alerts/${alertId}/acknowledge`);
    await loadRecentAlerts();
    await loadDashboardData();
  } catch (err) {
    console.error('Failed to acknowledge alert', err);
  }
}

async function loadRecentAlerts() {
  loadingAlerts.value = true;
  try {
    const res = await apiClient.get('/watchlists/alerts?status=active&per_page=5');
    recentAlerts.value = res.data.data ?? [];
    stats.value.activeAlerts = res.data.total ?? 0;
  } catch (err) {
    console.error('Failed to load alerts', err);
  } finally {
    loadingAlerts.value = false;
  }
}

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
      watchlistsRes,
      reportsRes,
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
      apiClient.get('/watchlists?per_page=1').catch(() => ({ data: { total: 0 } })),
      apiClient.get('/reports?per_page=1').catch(() => ({ data: { total: 0 } })),
    ]);

    const importBatches = importsRes.data.data ?? [];
    const quarantinedCount = importBatches.reduce((acc: number, b: any) => acc + (b.quarantined_rows_count || 0), 0);

    stats.value = {
      ...stats.value,
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
      watchlists: watchlistsRes.data.total ?? 0,
      reports: reportsRes.data.total ?? 0,
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
  loadRecentAlerts();
  runQuickPointLookup();
});
</script>
