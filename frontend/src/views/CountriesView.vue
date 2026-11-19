<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-white">Country Management & Multi-Country Architecture</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Country-agnostic design with sovereign baselines, national regulations, and registered datasets.
        </p>
      </div>
    </div>

    <!-- Country Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="c in countries"
        :key="c.id"
        class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4 hover:border-cyan-500/50 transition-colors"
      >
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base font-bold text-white">{{ c.name }}</span>
              <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
                {{ c.iso_code_2 }}
              </span>
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ c.official_name }}</div>
          </div>
          <span
            class="w-2 h-2 rounded-full"
            :class="c.is_active ? 'bg-emerald-400' : 'bg-slate-600'"
          ></span>
        </div>

        <div class="grid grid-cols-3 gap-2 py-2 px-3 rounded-lg bg-slate-950 border border-slate-800 text-center text-xs font-mono">
          <div>
            <div class="text-[10px] text-slate-400">Regs</div>
            <div class="text-white font-bold">{{ c.regulations_count }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400">Datasets</div>
            <div class="text-white font-bold">{{ c.datasets_count }}</div>
          </div>
          <div>
            <div class="text-[10px] text-slate-400">Fleet</div>
            <div class="text-cyan-400 font-bold">{{ c.vessels_count }}</div>
          </div>
        </div>

        <button
          @click="openCountryProfile(c.id)"
          class="w-full py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 text-xs font-medium transition-colors"
        >
          View Profile & Regs →
        </button>
      </div>
    </div>

    <!-- Country Profile Modal -->
    <div v-if="selectedCountry" class="fixed inset-0 z-50 bg-black/75 flex justify-end">
      <div class="w-full max-w-xl bg-slate-900 border-l border-slate-800 h-full overflow-y-auto p-6 space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h4 class="text-lg font-bold text-white">{{ selectedCountry.name }}</h4>
            <div class="text-xs text-cyan-400 font-mono mt-0.5">
              ISO-3166: {{ selectedCountry.iso_code_2 }} / {{ selectedCountry.iso_code_3 }} (Numeric: {{ selectedCountry.numeric_code }})
            </div>
          </div>
          <button
            @click="selectedCountry = null"
            class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white"
          >
            ✕
          </button>
        </div>

        <!-- National Regulations -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">National Fisheries & Maritime Regulations</div>
          <div v-if="selectedCountry.regulations?.length === 0" class="text-xs text-slate-500">No regulations uploaded.</div>
          <div v-else class="space-y-2">
            <div
              v-for="r in selectedCountry.regulations"
              :key="r.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs space-y-1"
            >
              <div class="font-bold text-white">{{ r.title }}</div>
              <div class="text-slate-400 text-[11px] font-mono">
                Nomor: {{ r.regulation_number }} • Authority: {{ r.issuing_authority }}
              </div>
              <div class="text-slate-300 text-xs mt-1">{{ r.summary }}</div>
            </div>
          </div>
        </div>

        <!-- Datasets -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Country Datasets & Ingestion Provenance</div>
          <div v-if="selectedCountry.datasets?.length === 0" class="text-xs text-slate-500">No datasets registered.</div>
          <div v-else class="space-y-2">
            <div
              v-for="d in selectedCountry.datasets"
              :key="d.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs flex items-center justify-between"
            >
              <div>
                <div class="font-semibold text-white">{{ d.dataset_name }}</div>
                <div class="text-[11px] text-slate-400 font-mono">
                  Type: {{ d.dataset_type }} • Version: {{ d.version }}
                </div>
              </div>
              <div class="text-right">
                <span class="text-xs font-mono font-bold text-cyan-300">{{ d.records_count }}</span>
                <span class="text-[10px] text-slate-500 block">records</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';

const countries = ref<any[]>([]);
const selectedCountry = ref<any>(null);

async function fetchCountries() {
  try {
    const res = await apiClient.get('/countries');
    countries.value = res.data.data;
  } catch (err) {
    console.error('Failed to load countries', err);
  }
}

async function openCountryProfile(id: string) {
  try {
    const res = await apiClient.get(`/countries/${id}`);
    selectedCountry.value = res.data.data;
  } catch (err) {
    console.error('Failed to load country profile', err);
  }
}

onMounted(() => {
  fetchCountries();
});
</script>
