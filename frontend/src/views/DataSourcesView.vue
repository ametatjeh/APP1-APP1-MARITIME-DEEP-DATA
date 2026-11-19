<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Data Source & Provenance Registry</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            PROVENANCE TRACEABILITY
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Standardized provenance tracking across government registries, RFMO databases, academic publications, and NGO dossiers.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          @click="showCreateModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Register Data Source</span>
        </button>
      </div>
    </div>

    <!-- Reliability Rating Guide Banner -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs font-mono">
      <div class="flex items-center gap-2.5">
        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
        <div>
          <strong class="text-white">0.90 – 1.00:</strong>
          <span class="text-slate-400 block text-[10px]">Official Gazettes & IMO Primary Data</span>
        </div>
      </div>
      <div class="flex items-center gap-2.5">
        <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
        <div>
          <strong class="text-white">0.75 – 0.89:</strong>
          <span class="text-slate-400 block text-[10px]">Peer-reviewed Research & NGO Reports</span>
        </div>
      </div>
      <div class="flex items-center gap-2.5">
        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
        <div>
          <strong class="text-white">0.50 – 0.74:</strong>
          <span class="text-slate-400 block text-[10px]">Commercial Databases & Media</span>
        </div>
      </div>
      <div class="flex items-center gap-2.5">
        <span class="w-3 h-3 rounded-full bg-red-500"></span>
        <div>
          <strong class="text-white">&lt; 0.50:</strong>
          <span class="text-slate-400 block text-[10px]">Unverified Crowdsourced Claims</span>
        </div>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
      <div class="sm:col-span-1">
        <label class="block text-slate-400 mb-1">Search Registry</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Search by source name, code, or publisher..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
        />
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Category</label>
        <select
          v-model="selectedCategory"
          @change="fetchSources"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Categories</option>
          <option value="government">Government Official</option>
          <option value="international_org">International Organization (UN, IMO, RFMO)</option>
          <option value="research_institution">Research Institution</option>
          <option value="academic">Academic Publication</option>
          <option value="ngo_report">NGO Investigative Report</option>
          <option value="news">News & Investigative Media</option>
          <option value="commercial">Commercial Maritime Database</option>
          <option value="open_dataset">Open Dataset</option>
          <option value="other">Other Documented Source</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Active Status</label>
        <select
          v-model="selectedActive"
          @change="fetchSources"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Statuses</option>
          <option value="true">Active Ingestion Sources</option>
          <option value="false">Inactive / Archived Sources</option>
        </select>
      </div>
    </div>

    <!-- Sources Grid -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading data sources...</div>
      <div v-else-if="sources.length === 0" class="py-12 text-center text-xs text-slate-400">
        No data sources found matching filters.
      </div>
      <div v-else class="divide-y divide-slate-800">
        <div
          v-for="s in sources"
          :key="s.id"
          class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-mono text-xs font-bold text-cyan-400 bg-cyan-950/80 border border-cyan-800 px-2 py-0.5 rounded">
                {{ s.code }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ formatCategory(s.category) }}
              </span>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                :class="reliabilityBadgeClass(s.reliability_score)"
              >
                RELIABILITY: {{ (s.reliability_score * 100).toFixed(0) }}%
              </span>
              <span v-if="s.country" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">
                🌍 {{ s.country.name }} ({{ s.country.iso_alpha3 }})
              </span>
            </div>

            <h4 class="text-sm font-semibold text-white">
              {{ s.name }}
            </h4>

            <p v-if="s.description" class="text-xs text-slate-400 line-clamp-2 max-w-4xl">
              {{ s.description }}
            </p>

            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono pt-1">
              <span>Publisher: <strong class="text-slate-300">{{ s.publisher || 'Not Specified' }}</strong></span>
              <span v-if="s.publication_date">Published: <span class="text-slate-300">{{ formatDate(s.publication_date) }}</span></span>
              <span>Source Records: <strong class="text-cyan-400">{{ s.source_records_count || 0 }}</strong></span>
              <span>Linked Evidence: <strong class="text-emerald-400">{{ s.evidences_count || 0 }}</strong></span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start md:self-center">
            <a
              v-if="s.source_url"
              :href="s.source_url"
              target="_blank"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-400 text-xs font-mono transition-colors border border-slate-700 flex items-center gap-1"
            >
              <span>URL ↗</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Register Source Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-2xl w-full p-6 space-y-4 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h4 class="text-sm font-bold text-white">Register Data Source in Registry</h4>
            <p class="text-[11px] text-slate-400">Strict provenance tracking ensures intelligence is never attributed to undocumented origins.</p>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="createSource" class="space-y-3 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Source Name *</label>
              <input
                v-model="newSource.name"
                required
                type="text"
                placeholder="e.g. Western & Central Pacific Fisheries Commission (WCPFC) Record of Fishing Vessels"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Unique Code *</label>
              <input
                v-model="newSource.code"
                required
                type="text"
                placeholder="e.g. WCPFC_RFV"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white uppercase font-mono focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Category *</label>
              <select
                v-model="newSource.category"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="government">Government Official</option>
                <option value="international_org">International Organization</option>
                <option value="research_institution">Research Institution</option>
                <option value="academic">Academic Publication</option>
                <option value="ngo_report">NGO Report</option>
                <option value="news">News & Media</option>
                <option value="commercial">Commercial Database</option>
                <option value="open_dataset">Open Dataset</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Reliability Score (0.00 - 1.00) *</label>
              <input
                v-model.number="newSource.reliability_score"
                required
                type="number"
                step="0.01"
                min="0.00"
                max="1.00"
                placeholder="0.95"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white font-mono focus:outline-none focus:border-cyan-500"
              />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Country Relevance</label>
              <select
                v-model="newSource.country_id"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="">Global / International</option>
                <option v-for="c in countries" :key="c.id" :value="c.id">
                  {{ c.name }} ({{ c.iso_alpha3 }})
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Publisher</label>
              <input
                v-model="newSource.publisher"
                type="text"
                placeholder="e.g. WCPFC Secretariat"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Publication Date</label>
              <input
                v-model="newSource.publication_date"
                type="date"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Source URL / Reference Link</label>
            <input
              v-model="newSource.source_url"
              type="url"
              placeholder="https://www.wcpfc.int/record-fishing-vessel"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Description & Scope</label>
            <textarea
              v-model="newSource.description"
              rows="3"
              placeholder="Scope of coverage, updating frequency, verification guidelines..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div v-if="createError" class="p-2.5 rounded bg-red-950/50 border border-red-800 text-red-300 text-xs">
            {{ createError }}
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium transition-colors disabled:opacity-50"
            >
              {{ creating ? 'Registering...' : 'Register Source' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const sources = ref<any[]>([]);
const countries = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedActive = ref('');

const showCreateModal = ref(false);
const creating = ref(false);
const createError = ref('');

const newSource = ref({
  name: '',
  code: '',
  category: 'government',
  publisher: '',
  publication_date: '',
  reliability_score: 0.95,
  source_url: '',
  country_id: '',
  description: '',
  is_active: true,
});

let searchTimeout: any = null;

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchSources();
  }, 300);
}

async function fetchSources() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (selectedCategory.value) params.append('category', selectedCategory.value);
    if (selectedActive.value) params.append('is_active', selectedActive.value);

    const res = await apiClient.get(`/sources?${params.toString()}`);
    sources.value = res.data.data;
  } catch (err) {
    console.error('Failed to load data sources', err);
  } finally {
    loading.value = false;
  }
}

async function fetchCountries() {
  try {
    const res = await apiClient.get('/countries?per_page=100');
    countries.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load countries', err);
  }
}

async function createSource() {
  creating.value = true;
  createError.value = '';
  try {
    const payload: any = { ...newSource.value };
    if (!payload.country_id) delete payload.country_id;
    if (!payload.publication_date) delete payload.publication_date;
    if (!payload.source_url) delete payload.source_url;

    await apiClient.post('/sources', payload);
    showCreateModal.value = false;
    newSource.value = {
      name: '',
      code: '',
      category: 'government',
      publisher: '',
      publication_date: '',
      reliability_score: 0.95,
      source_url: '',
      country_id: '',
      description: '',
      is_active: true,
    };
    await fetchSources();
  } catch (err: any) {
    createError.value = err.response?.data?.message || 'Failed to register data source.';
  } finally {
    creating.value = false;
  }
}

function reliabilityBadgeClass(score: number) {
  if (score >= 0.90) return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
  if (score >= 0.75) return 'bg-cyan-950 text-cyan-300 border border-cyan-800';
  if (score >= 0.50) return 'bg-yellow-950 text-yellow-300 border border-yellow-800';
  return 'bg-red-950 text-red-300 border border-red-800';
}

function formatCategory(cat: string) {
  return (cat || '').replace(/_/g, ' ').toUpperCase();
}

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(() => {
  fetchSources();
  fetchCountries();
});
</script>
