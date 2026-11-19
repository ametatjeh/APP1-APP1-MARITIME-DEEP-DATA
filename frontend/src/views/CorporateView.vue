<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-bold text-white">Corporate Intelligence & Beneficial Ownership</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Entity structures, shadow directors, holding chains, and vessel fleet assignments with full provenance.
        </p>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row gap-3 text-xs">
      <div class="flex-1">
        <label class="block text-slate-400 mb-1">Search Company Name or Registration</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Search by company name, registration number, or tax ID..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
        />
      </div>
    </div>

    <!-- Companies List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div
        v-for="c in companies"
        :key="c.id"
        class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4 hover:border-slate-700 transition-colors"
      >
        <div class="flex items-start justify-between">
          <div>
            <h4 class="text-base font-bold text-white">{{ c.company_name }}</h4>
            <div class="text-[11px] text-cyan-400 font-mono mt-0.5">
              Reg: {{ c.registration_number || 'N/A' }} • Legal Form: {{ c.legal_form || 'Corporation' }}
            </div>
          </div>
          <span
            class="px-2 py-0.5 rounded-full text-[10px] font-mono border"
            :class="c.is_sanctioned ? 'bg-red-950 text-red-300 border-red-800' : 'bg-emerald-950 text-emerald-300 border-emerald-800'"
          >
            {{ c.is_sanctioned ? 'SANCTIONED' : c.corporate_status }}
          </span>
        </div>

        <div class="text-xs text-slate-400">
          Country of Incorporation:
          <span class="text-slate-200 font-medium ml-1">{{ c.country_of_incorporation?.name || 'Offshore' }}</span>
        </div>

        <!-- Quick Metrics -->
        <div class="grid grid-cols-3 gap-2 py-2 px-3 rounded-lg bg-slate-950 border border-slate-800 text-center text-xs">
          <div>
            <div class="text-slate-400 text-[10px]">Officers</div>
            <div class="font-bold text-white font-mono">{{ c.officers_count ?? 0 }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-[10px]">Shareholders</div>
            <div class="font-bold text-white font-mono">{{ c.shareholders_count ?? 0 }}</div>
          </div>
          <div>
            <div class="text-slate-400 text-[10px]">Assigned Vessels</div>
            <div class="font-bold text-cyan-400 font-mono">{{ c.vessels_count ?? 0 }}</div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-1">
          <router-link
            :to="{ path: '/network-graph', query: { type: 'company', id: c.id } }"
            class="text-xs text-cyan-400 hover:text-cyan-300 font-medium flex items-center gap-1"
          >
            <span>🕸️</span>
            <span>View Network Graph</span>
          </router-link>

          <button
            @click="openCompanyDossier(c.id)"
            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium transition-colors"
          >
            Full Dossier →
          </button>
        </div>
      </div>
    </div>

    <!-- Company Detail Dossier Modal -->
    <div v-if="selectedCompany" class="fixed inset-0 z-50 bg-black/75 flex justify-end">
      <div class="w-full max-w-2xl bg-slate-900 border-l border-slate-800 h-full overflow-y-auto p-6 space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h4 class="text-lg font-bold text-white">{{ selectedCompany.company_name }}</h4>
            <div class="text-xs text-cyan-400 font-mono mt-0.5">
              {{ selectedCompany.legal_form }} • {{ selectedCompany.country_of_incorporation?.name }}
            </div>
          </div>
          <button
            @click="selectedCompany = null"
            class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white"
          >
            ✕
          </button>
        </div>

        <!-- Officers & Directors (Demonstrating Field-Level Redaction) -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center justify-between">
            <span>Corporate Officers & Directors</span>
            <span class="text-[10px] text-slate-500 font-mono">PII Redaction Active</span>
          </div>
          <div class="space-y-2">
            <div
              v-for="off in selectedCompany.officers"
              :key="off.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs flex items-center justify-between"
            >
              <div>
                <div class="font-semibold" :class="off.is_redacted ? 'text-amber-400' : 'text-white'">
                  {{ off.officer_name }}
                  <span v-if="off.is_redacted" class="ml-1 text-[9px] px-1 py-0.5 rounded bg-amber-950 text-amber-300 font-mono">
                    PROTECTED PII
                  </span>
                </div>
                <div class="text-slate-400 text-[11px] mt-0.5">{{ off.position }}</div>
              </div>
              <div class="text-[10px] text-slate-500 font-mono">
                Appointed: {{ off.appointment_date || 'N/A' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Beneficial Ownership / Shareholders -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Shareholders & Beneficial Ownership</div>
          <div class="space-y-2">
            <div
              v-for="sh in selectedCompany.shareholders"
              :key="sh.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs flex items-center justify-between"
            >
              <div>
                <div class="font-semibold text-white">{{ sh.shareholder_name }}</div>
                <div class="text-slate-400 text-[11px] capitalize">{{ sh.shareholder_type }} Entity</div>
              </div>
              <div class="text-sm font-bold text-cyan-400 font-mono">
                {{ sh.share_percentage }}%
              </div>
            </div>
          </div>
        </div>

        <!-- Assigned Vessels -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Operated & Owned Fleet</div>
          <div v-if="selectedCompany.vessel_assignments?.length === 0" class="text-xs text-slate-500">No vessels assigned.</div>
          <div v-else class="space-y-2">
            <div
              v-for="a in selectedCompany.vessel_assignments"
              :key="a.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs flex items-center justify-between"
            >
              <div>
                <div class="font-bold text-white">{{ a.vessel?.current_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">
                  MMSI: {{ a.vessel?.current_mmsi || 'N/A' }} • Type: {{ a.vessel?.vessel_type || 'N/A' }}
                </div>
              </div>
              <div class="px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 text-[10px] font-mono">
                {{ a.assignment_type }}
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

const companies = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedCompany = ref<any>(null);

let timeout: any = null;
function debounceSearch() {
  clearTimeout(timeout);
  timeout = setTimeout(fetchCompanies, 300);
}

async function fetchCompanies() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    const res = await apiClient.get(`/companies?${params.toString()}`);
    companies.value = res.data.data;
  } catch (err) {
    console.error('Failed to load companies', err);
  } finally {
    loading.value = false;
  }
}

async function openCompanyDossier(id: string) {
  try {
    const res = await apiClient.get(`/companies/${id}`);
    selectedCompany.value = res.data.data;
  } catch (err) {
    console.error('Failed to load company dossier', err);
  }
}

onMounted(() => {
  fetchCompanies();
});
</script>
