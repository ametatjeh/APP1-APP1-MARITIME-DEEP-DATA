<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Investigation Cases Management</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            PHASE 2 CORE
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Multi-subject case dossiers, hypotheses, chronological timelines, evidence custody, and analyst collaboration.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          @click="showCreateModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Open New Investigation</span>
        </button>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
      <div class="sm:col-span-1">
        <label class="block text-slate-400 mb-1">Search Cases</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Search by title, reference #, or summary..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
        />
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Category</label>
        <select
          v-model="selectedCategory"
          @change="fetchCases"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Categories</option>
          <option value="illegal_fishing">Illegal Fishing (IUU)</option>
          <option value="forced_labour">Forced Labour / Human Rights</option>
          <option value="transshipment">Unauthorized Transshipment</option>
          <option value="tax_fraud">Tax Fraud & Financial Crime</option>
          <option value="corporate_opacity">Corporate Opacity & Shell Co</option>
          <option value="identity_laundering">Identity Laundering / Flag-hop</option>
          <option value="marine_pollution">Marine Pollution</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Priority</label>
        <select
          v-model="selectedPriority"
          @change="fetchCases"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Priorities</option>
          <option value="critical">Critical</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Status</label>
        <select
          v-model="selectedStatus"
          @change="fetchCases"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Statuses</option>
          <option value="open">Open</option>
          <option value="active">Active Investigation</option>
          <option value="pending_evidence">Pending Evidence</option>
          <option value="under_review">Under Review</option>
          <option value="closed">Closed</option>
          <option value="archived">Archived</option>
        </select>
      </div>
    </div>

    <!-- Cases Grid / Table -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading investigation cases...</div>
      <div v-else-if="cases.length === 0" class="py-12 text-center text-xs text-slate-400">
        No investigation cases match the selected filters.
      </div>
      <div v-else class="divide-y divide-slate-800">
        <div
          v-for="c in cases"
          :key="c.id"
          class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-1.5 flex-1 cursor-pointer" @click="goToDetail(c.id)">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-mono text-xs font-bold text-cyan-400 bg-cyan-950/70 border border-cyan-800/60 px-2 py-0.5 rounded">
                {{ c.reference_number }}
              </span>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase"
                :class="priorityBadgeClass(c.priority)"
              >
                {{ c.priority }}
              </span>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase"
                :class="statusBadgeClass(c.status)"
              >
                {{ c.status.replace('_', ' ') }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">
                {{ formatCategory(c.category) }}
              </span>
              <span v-if="c.confidentiality !== 'internal'" class="px-2 py-0.5 rounded text-[10px] bg-red-950/70 text-red-300 border border-red-800/40 font-mono">
                🔒 {{ c.confidentiality }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-white hover:text-cyan-300 transition-colors">
              {{ c.title }}
            </h4>

            <p class="text-xs text-slate-400 line-clamp-2 max-w-4xl">
              {{ c.summary || c.hypothesis || 'No summary provided.' }}
            </p>

            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 pt-1 font-mono">
              <span>Jurisdiction: <strong class="text-slate-300">{{ c.primary_jurisdiction || 'International' }}</strong></span>
              <span>Lead: <strong class="text-slate-300">{{ c.lead_investigator?.name || 'Unassigned' }}</strong></span>
              <span>Subjects: <strong class="text-cyan-400">{{ c.subjects_count || c.subjects?.length || 0 }}</strong></span>
              <span>Evidence: <strong class="text-emerald-400">{{ c.evidences_count || c.evidences?.length || 0 }}</strong></span>
              <span>Updated: {{ formatDate(c.updated_at) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start md:self-center">
            <button
              @click="goToDetail(c.id)"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700 flex items-center gap-1"
            >
              <span>Inspect Workspace</span>
              <span>→</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Case Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-2xl w-full p-6 space-y-4 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h4 class="text-sm font-bold text-white">Create New Investigation Case</h4>
            <p class="text-[11px] text-slate-400">Reference number will be generated automatically in sequence.</p>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="createCase" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Case Title *</label>
            <input
              v-model="newCase.title"
              required
              type="text"
              placeholder="e.g. Pacific Reefer Transshipment & Beneficial Ownership Network"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Category *</label>
              <select
                v-model="newCase.category"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="illegal_fishing">Illegal Fishing (IUU)</option>
                <option value="forced_labour">Forced Labour</option>
                <option value="transshipment">Unauthorized Transshipment</option>
                <option value="tax_fraud">Tax Fraud</option>
                <option value="corporate_opacity">Corporate Opacity</option>
                <option value="identity_laundering">Identity Laundering</option>
                <option value="marine_pollution">Marine Pollution</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Priority *</label>
              <select
                v-model="newCase.priority"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Confidentiality *</label>
              <select
                v-model="newCase.confidentiality"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="internal">Internal Team</option>
                <option value="confidential">Confidential (Restricted)</option>
                <option value="strictly_confidential">Strictly Confidential (Need to know)</option>
                <option value="public">Public</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Primary Jurisdiction / Area</label>
            <input
              v-model="newCase.primary_jurisdiction"
              type="text"
              placeholder="e.g. Indonesia EEZ / Natuna Sea / WCPFC Convention Area"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Hypothesis / Theory of Violation</label>
            <textarea
              v-model="newCase.hypothesis"
              rows="3"
              placeholder="State the investigative hypothesis grounded on observable patterns, corporate structures, or intelligence records..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Executive Summary / Background</label>
            <textarea
              v-model="newCase.summary"
              rows="2"
              placeholder="Initial briefing and operational context..."
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
              {{ creating ? 'Creating...' : 'Initialize Case' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const cases = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedPriority = ref('');
const selectedStatus = ref('');

const showCreateModal = ref(false);
const creating = ref(false);
const createError = ref('');

const newCase = ref({
  title: '',
  category: 'illegal_fishing',
  priority: 'high',
  confidentiality: 'internal',
  primary_jurisdiction: '',
  hypothesis: '',
  summary: '',
});

let searchTimeout: any = null;

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchCases();
  }, 300);
}

async function fetchCases() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (selectedCategory.value) params.append('category', selectedCategory.value);
    if (selectedPriority.value) params.append('priority', selectedPriority.value);
    if (selectedStatus.value) params.append('status', selectedStatus.value);

    const res = await apiClient.get(`/cases?${params.toString()}`);
    cases.value = res.data.data;
  } catch (err) {
    console.error('Failed to load cases', err);
  } finally {
    loading.value = false;
  }
}

async function createCase() {
  creating.value = true;
  createError.value = '';
  try {
    const res = await apiClient.post('/cases', newCase.value);
    showCreateModal.value = false;
    newCase.value = {
      title: '',
      category: 'illegal_fishing',
      priority: 'high',
      confidentiality: 'internal',
      primary_jurisdiction: '',
      hypothesis: '',
      summary: '',
    };
    await fetchCases();
    if (res.data?.data?.id) {
      router.push(`/investigations/${res.data.data.id}`);
    }
  } catch (err: any) {
    createError.value = err.response?.data?.message || 'Failed to create case.';
  } finally {
    creating.value = false;
  }
}

function goToDetail(id: string) {
  router.push(`/investigations/${id}`);
}

function priorityBadgeClass(priority: string) {
  switch (priority) {
    case 'critical': return 'bg-red-950/80 text-red-300 border border-red-800/60';
    case 'high': return 'bg-amber-950/80 text-amber-300 border border-amber-800/60';
    case 'medium': return 'bg-yellow-950/80 text-yellow-300 border border-yellow-800/60';
    case 'low': return 'bg-slate-800 text-slate-300 border border-slate-700';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function statusBadgeClass(status: string) {
  switch (status) {
    case 'active': return 'bg-cyan-950 text-cyan-300 border border-cyan-800';
    case 'open': return 'bg-blue-950 text-blue-300 border border-blue-800';
    case 'under_review': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'pending_evidence': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'closed': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'archived': return 'bg-slate-800 text-slate-400 border border-slate-700';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function formatCategory(cat: string) {
  return (cat || '').replace(/_/g, ' ').toUpperCase();
}

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

onMounted(() => {
  fetchCases();
});
</script>
