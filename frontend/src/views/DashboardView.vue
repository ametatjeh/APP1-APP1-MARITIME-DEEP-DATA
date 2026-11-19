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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-2">
          <span>Tracked Vessels</span>
          <span>🚢</span>
        </div>
        <div class="text-2xl font-bold text-white">{{ stats.vessels }}</div>
        <div class="text-[10px] text-cyan-400 mt-1 font-mono">Verified & Disputed Records</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-2">
          <span>Corporate Entities</span>
          <span>🏢</span>
        </div>
        <div class="text-2xl font-bold text-white">{{ stats.companies }}</div>
        <div class="text-[10px] text-cyan-400 mt-1 font-mono">UBOs & Operators</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-amber-900/40 bg-amber-950/10">
        <div class="flex items-center justify-between text-amber-300 text-xs mb-2">
          <span>Identity Conflicts</span>
          <span>⚠️</span>
        </div>
        <div class="text-2xl font-bold text-amber-300">{{ stats.conflicts }}</div>
        <div class="text-[10px] text-amber-400/80 mt-1 font-mono">Shared MMSI / Cloned IMO</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-2">
          <span>Maritime Jurisdictions</span>
          <span>🗺️</span>
        </div>
        <div class="text-2xl font-bold text-white">{{ stats.jurisdictions }}</div>
        <div class="text-[10px] text-cyan-400 mt-1 font-mono">PostGIS Polygons</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="flex items-center justify-between text-slate-400 text-xs mb-2">
          <span>Active Countries</span>
          <span>🌐</span>
        </div>
        <div class="text-2xl font-bold text-white">{{ stats.countries }}</div>
        <div class="text-[10px] text-cyan-400 mt-1 font-mono">Multi-Country Engine</div>
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
});

const loading = ref(true);
const conflictsList = ref<any[]>([]);

const quickLon = ref(108.0);
const quickLat = ref(4.5);
const pointResult = ref<any>(null);

async function loadDashboardData() {
  loading.value = true;
  try {
    const [vesselsRes, companiesRes, conflictsRes, jurisdictionsRes, countriesRes] = await Promise.all([
      apiClient.get('/vessels?per_page=1'),
      apiClient.get('/companies?per_page=1'),
      apiClient.get('/identity/conflicts?per_page=5'),
      apiClient.get('/jurisdictions?per_page=1'),
      apiClient.get('/countries?per_page=1'),
    ]);

    stats.value = {
      vessels: vesselsRes.data.total ?? 0,
      companies: companiesRes.data.total ?? 0,
      conflicts: conflictsRes.data.total ?? 0,
      jurisdictions: jurisdictionsRes.data.total ?? 0,
      countries: countriesRes.data.total ?? 0,
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
