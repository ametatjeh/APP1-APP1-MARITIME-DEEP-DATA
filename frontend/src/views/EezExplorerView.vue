<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-white">EEZ Explorer & Spatial Jurisdiction Analysis</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Real-time PostGIS spatial verification of fishing activity against sovereign and contested maritime zones.
        </p>
      </div>
      <button
        @click="exportGeoJson"
        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 text-xs font-mono font-medium border border-slate-700 flex items-center gap-1.5"
      >
        <span>📥</span>
        <span>Download EEZ Polygons (.geojson)</span>
      </button>
    </div>

    <!-- Coordinate Intersect Tool Card -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
      <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
        <span class="text-xl">🧭</span>
        <div>
          <h4 class="text-sm font-bold text-white">Point Containment Inspector (PostGIS ST_Contains)</h4>
          <p class="text-xs text-slate-400">
            Input WGS 84 coordinates (Latitude, Longitude) to verify jurisdiction boundaries, archipelagic baselines, and contested waters.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Longitude (Decimal Degrees)</label>
          <input
            v-model.number="longitude"
            type="number"
            step="0.00001"
            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-white font-mono text-sm focus:border-cyan-500 focus:outline-none"
            placeholder="e.g. 108.2450"
          />
          <span class="text-[10px] text-slate-500 mt-0.5 block">Range: -180.0 to 180.0</span>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Latitude (Decimal Degrees)</label>
          <input
            v-model.number="latitude"
            type="number"
            step="0.00001"
            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-white font-mono text-sm focus:border-cyan-500 focus:outline-none"
            placeholder="e.g. 4.8120"
          />
          <span class="text-[10px] text-slate-500 mt-0.5 block">Range: -90.0 to 90.0</span>
        </div>

        <div class="flex items-end">
          <button
            @click="inspectPoint"
            :disabled="loading"
            class="w-full py-2.5 px-4 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium text-sm transition-all shadow-lg shadow-cyan-950/50 flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <span v-if="loading">Processing Spatial Query...</span>
            <span v-else>Evaluate Point in PostGIS</span>
          </button>
        </div>
      </div>

      <!-- Quick Preset Locations -->
      <div class="pt-2">
        <span class="text-[11px] text-slate-400 mr-2">Quick Test Sectors:</span>
        <div class="inline-flex flex-wrap gap-2 text-xs">
          <button
            v-for="p in presets"
            :key="p.name"
            @click="setPreset(p.lon, p.lat)"
            class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] border border-slate-700/60"
          >
            {{ p.name }}
          </button>
        </div>
      </div>

      <!-- Spatial Query Result -->
      <div v-if="result" class="mt-4 p-5 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider font-mono">
            Spatial Intersection Results: [{{ result.point.longitude }}, {{ result.point.latitude }}]
          </div>
          <span
            class="px-2.5 py-1 rounded-full text-xs font-bold font-mono border"
            :class="result.in_disputed_or_overlapping_zone ? 'bg-amber-950 text-amber-300 border-amber-800' : 'bg-emerald-950 text-emerald-300 border-emerald-800'"
          >
            {{ result.in_disputed_or_overlapping_zone ? '⚠️ OVERLAPPING / DISPUTED CLAIM' : '✓ CLEAR SOVEREIGN ZONE' }}
          </span>
        </div>

        <!-- Containing Jurisdictions -->
        <div class="space-y-2">
          <div class="text-xs text-slate-400 font-medium">Intersecting Maritime Zones:</div>
          <div v-if="result.containing_jurisdictions.length === 0" class="text-xs text-slate-500">
            Point lies in High Seas / International Waters outside registered EEZ polygons.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
              v-for="j in result.containing_jurisdictions"
              :key="j.id"
              class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs space-y-1"
            >
              <div class="font-bold text-white">{{ j.area_name }}</div>
              <div class="text-cyan-300 font-medium">{{ j.country }} ({{ j.iso_code }})</div>
              <div class="text-slate-400 text-[11px]">{{ j.jurisdiction_label }}</div>
              <div class="text-[10px] text-slate-500 font-mono pt-1">
                Legal Basis: {{ j.legal_basis || 'UNCLOS 1982' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Overlapping Claims Detail -->
        <div v-if="result.overlapping_claims?.length > 0" class="space-y-2 pt-2 border-t border-slate-800/80">
          <div class="text-xs text-amber-300 font-bold flex items-center gap-1.5">
            <span>⚠️</span>
            <span>Contested Sector Legal Breakdown:</span>
          </div>
          <div
            v-for="o in result.overlapping_claims"
            :key="o.id"
            class="p-3 rounded-lg bg-amber-950/20 border border-amber-800/50 text-xs space-y-1"
          >
            <div class="font-semibold text-white">
              {{ o.claim_type.toUpperCase() }} — Claimants: {{ o.countries_involved.join(' vs ') }}
            </div>
            <p class="text-slate-300 text-xs">{{ o.notes }}</p>
            <div class="text-[10px] text-amber-400/80 font-mono">
              Status: {{ o.resolution_status }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import apiClient from '@/api/client';

const longitude = ref(108.0);
const latitude = ref(4.5);
const loading = ref(false);
const result = ref<any>(null);

const presets = [
  { name: 'North Natuna Overlap (VN/ID)', lon: 108.0, lat: 4.5 },
  { name: 'Java Sea Archipelagic Waters', lon: 110.5, lat: -5.5 },
  { name: 'Banda Sea Deep Waters', lon: 128.0, lat: -4.5 },
];

function setPreset(lon: number, lat: number) {
  longitude.value = lon;
  latitude.value = lat;
  inspectPoint();
}

async function inspectPoint() {
  loading.value = true;
  try {
    const res = await apiClient.get(`/jurisdictions/point-lookup?longitude=${longitude.value}&latitude=${latitude.value}`);
    result.value = res.data;
  } catch (err) {
    console.error('Failed to inspect coordinates', err);
  } finally {
    loading.value = false;
  }
}

async function exportGeoJson() {
  try {
    const res = await apiClient.get('/jurisdictions/export-geojson');
    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(res.data, null, 2));
    const a = document.createElement('a');
    a.href = dataStr;
    a.download = 'eez_jurisdictions.geojson';
    document.body.appendChild(a);
    a.click();
    a.remove();
  } catch (err) {
    console.error(err);
  }
}
</script>
