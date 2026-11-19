<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Advanced Cross-Domain Intelligence Search</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            UNIFIED QUERY
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Fuzzy match queries across vessels, corporate hierarchies, beneficial owners, investigation dockets, evidence hashes, and IUU listings.
        </p>
      </div>
    </div>

    <!-- Search Input & Filter Card -->
    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
      <div class="relative">
        <input
          v-model="searchQuery"
          @keyup.enter="executeSearch"
          type="text"
          placeholder="Search by vessel name, IMO, MMSI, callsign, corporate name, registration #, person name, or evidence hash..."
          class="w-full pl-11 pr-28 py-3.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 font-sans text-sm focus:outline-none focus:border-cyan-500 transition-colors"
        />
        <span class="absolute left-4 top-4 text-slate-500 text-base">🔍</span>
        <button
          @click="executeSearch"
          :disabled="loading"
          class="absolute right-2 top-2 px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium text-xs shadow-lg shadow-cyan-950/40 transition-colors"
        >
          {{ loading ? 'Searching...' : 'Search Engine' }}
        </button>
      </div>

      <!-- Entity Type Toggles -->
      <div class="flex items-center gap-2 flex-wrap text-xs font-mono pt-1">
        <span class="text-slate-400 text-[11px] mr-2">Target Domains:</span>
        <label
          v-for="t in domainOptions"
          :key="t.key"
          class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border cursor-pointer transition-colors"
          :class="selectedDomains.includes(t.key) ? 'bg-cyan-950/60 text-cyan-300 border-cyan-800' : 'bg-slate-950 text-slate-400 border-slate-800'"
        >
          <input
            type="checkbox"
            :value="t.key"
            v-model="selectedDomains"
            class="hidden"
          />
          <span>{{ t.icon }}</span>
          <span>{{ t.label }}</span>
        </label>
      </div>
    </div>

    <!-- Search Results View -->
    <div class="space-y-4">
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400 font-mono">
        Querying distributed PostgreSQL intelligence indices...
      </div>

      <div v-else-if="hasSearched && results.length === 0" class="p-12 text-center text-xs text-slate-400 font-mono rounded-xl bg-slate-900 border border-slate-800">
        No records matched query: "{{ lastQuery }}". Try broadening your search terms or selecting more target domains.
      </div>

      <div v-else-if="results.length > 0" class="space-y-3">
        <div class="flex items-center justify-between text-xs font-mono text-slate-400 px-1">
          <span>Found {{ results.length }} intelligence matches for "{{ lastQuery }}"</span>
        </div>

        <div
          v-for="res in results"
          :key="res.entity_type + res.id"
          class="p-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-cyan-800/80 transition-all flex items-start justify-between gap-4"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-base">{{ getDomainIcon(res.entity_type) }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-950 text-slate-300 border border-slate-800">
                {{ res.entity_type }}
              </span>
              <span
                v-if="res.status"
                class="px-2 py-0.5 rounded text-[10px] font-mono uppercase border"
                :class="{
                  'bg-emerald-950 text-emerald-300 border-emerald-800': res.status === 'verified',
                  'bg-red-950 text-red-300 border-red-800': res.status === 'active_sanction' || res.status === 'flagged_suspicious',
                  'bg-slate-800 text-slate-400 border-slate-700': res.status === 'unverified',
                }"
              >
                {{ res.status }}
              </span>
            </div>

            <h4 class="text-sm font-bold text-white font-sans">{{ res.title }}</h4>
            <p class="text-xs text-slate-400 font-mono">{{ res.subtitle }}</p>

            <!-- Metadata Pills -->
            <div v-if="res.meta" class="flex items-center gap-2 flex-wrap pt-1 font-mono text-[10px] text-slate-400">
              <span v-for="(val, k) in res.meta" :key="k" class="bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                {{ k }}: <strong class="text-slate-300">{{ val ?? 'N/A' }}</strong>
              </span>
            </div>
          </div>

          <router-link
            :to="res.url"
            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-cyan-600 hover:text-white text-slate-300 font-mono text-xs border border-slate-700 transition-colors shrink-0 self-center"
          >
            Inspect Entity →
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import apiClient from '@/api/client';

const searchQuery = ref('');
const lastQuery = ref('');
const loading = ref(false);
const hasSearched = ref(false);
const results = ref<any[]>([]);

const domainOptions = [
  { key: 'vessel', label: 'Vessels Master', icon: '🚢' },
  { key: 'company', label: 'Corporate Entities', icon: '🏢' },
  { key: 'person', label: 'Persons & Skippers', icon: '👤' },
  { key: 'case', label: 'Investigation Cases', icon: '📂' },
  { key: 'evidence', label: 'Evidence Vault', icon: '🛡️' },
  { key: 'iuu', label: 'IUU Sanctions', icon: '🚨' },
];

const selectedDomains = ref<string[]>(['vessel', 'company', 'person', 'case', 'evidence', 'iuu']);

async function executeSearch() {
  if (!searchQuery.value.trim()) return;
  loading.value = true;
  hasSearched.value = true;
  lastQuery.value = searchQuery.value;

  try {
    const res = await apiClient.get('/search', {
      params: {
        q: searchQuery.value,
        types: selectedDomains.value,
        limit: 50,
      },
    });
    results.value = res.data.results || [];
  } catch (err) {
    console.error('Search failed', err);
    results.value = [];
  } finally {
    loading.value = false;
  }
}

function getDomainIcon(type: string) {
  const opt = domainOptions.find(o => o.key === type);
  return opt ? opt.icon : '📄';
}
</script>
