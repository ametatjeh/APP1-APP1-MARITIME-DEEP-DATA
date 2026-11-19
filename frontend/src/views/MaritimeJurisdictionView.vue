<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-white">Maritime Jurisdictions & Disputed Zones (PostGIS)</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Internal waters, archipelagic baselines, territorial sea, EEZ/ZEE, continental shelf, and overlapping claims.
        </p>
      </div>
      <div>
        <button
          @click="downloadGeoJson"
          class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 text-xs font-mono font-medium border border-slate-700 flex items-center gap-1.5"
        >
          <span>📥</span>
          <span>Export GeoJSON (RFC 7946)</span>
        </button>
      </div>
    </div>

    <!-- Overlapping & Disputed Claims Section -->
    <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <div class="flex items-center gap-2">
          <span class="text-amber-400">⚔️</span>
          <h4 class="text-sm font-bold text-white">Overlapping Maritime Claims & Contested Sectors</h4>
        </div>
        <span class="text-xs text-slate-400 font-mono">
          UNCLOS Article 74/83 Registry
        </span>
      </div>

      <div v-if="overlappingClaims.length === 0" class="text-xs text-slate-400 py-4">
        No overlapping claims recorded.
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="claim in overlappingClaims"
          :key="claim.id"
          class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2"
        >
          <div class="flex items-center justify-between">
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-950 text-amber-300 border border-amber-800 font-bold uppercase">
              {{ claim.claim_type }}
            </span>
            <span class="text-xs text-slate-400 font-mono">{{ claim.resolution_status }}</span>
          </div>

          <div class="text-sm font-bold text-white pt-1">
            {{ claim.jurisdiction_a?.country?.name }} ↔ {{ claim.jurisdiction_b?.country?.name }}
          </div>

          <p class="text-xs text-slate-300">
            {{ claim.analyst_notes }}
          </p>

          <div class="text-[10px] text-slate-500 font-mono pt-1">
            Sector: {{ claim.jurisdiction_a?.area_name }}
          </div>
        </div>
      </div>
    </div>

    <!-- Maritime Jurisdictions Master Table -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <h4 class="text-sm font-bold text-white">Sovereign Maritime Zones & Legal Baselines</h4>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800 font-mono text-[11px]">
              <th class="py-3 px-4">AREA NAME</th>
              <th class="py-3 px-4">COUNTRY</th>
              <th class="py-3 px-4">JURISDICTION TYPE</th>
              <th class="py-3 px-4">LEGAL STATUS</th>
              <th class="py-3 px-4">LEGAL BASIS / TREATY</th>
              <th class="py-3 px-4">EFFECTIVE DATE</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr
              v-for="j in jurisdictions"
              :key="j.id"
              class="hover:bg-slate-800/40 transition-colors"
            >
              <td class="py-3 px-4 font-bold text-white">{{ j.area_name }}</td>
              <td class="py-3 px-4 text-cyan-300 font-medium">{{ j.country?.name }}</td>
              <td class="py-3 px-4 font-mono text-[11px] uppercase text-slate-300">{{ j.jurisdiction_type }}</td>
              <td class="py-3 px-4">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-mono border"
                  :class="j.legal_status === 'agreed' ? 'bg-emerald-950 text-emerald-300 border-emerald-800' : 'bg-amber-950 text-amber-300 border-amber-800'"
                >
                  {{ j.legal_status }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-300 max-w-xs truncate">{{ j.legal_basis || 'N/A' }}</td>
              <td class="py-3 px-4 font-mono text-[11px] text-slate-400">{{ j.effective_date || 'N/A' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';

const jurisdictions = ref<any[]>([]);
const overlappingClaims = ref<any[]>([]);

async function fetchJurisdictions() {
  try {
    const [jRes, oRes] = await Promise.all([
      apiClient.get('/jurisdictions?per_page=50'),
      apiClient.get('/jurisdictions/overlapping-claims'),
    ]);
    jurisdictions.value = jRes.data.data;
    overlappingClaims.value = oRes.data.data;
  } catch (err) {
    console.error('Failed to load maritime jurisdictions', err);
  }
}

async function downloadGeoJson() {
  try {
    const res = await apiClient.get('/jurisdictions/export-geojson');
    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(res.data, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute('href', dataStr);
    downloadAnchor.setAttribute('download', 'maritime_jurisdictions.geojson');
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  } catch (err) {
    console.error('Failed to export GeoJSON', err);
  }
}

onMounted(() => {
  fetchJurisdictions();
});
</script>
