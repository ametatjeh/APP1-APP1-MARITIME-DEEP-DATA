<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-bold text-white">Vessel Master Database</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Temporal identity tracking with non-unique identifier tolerance and source provenance.
        </p>
      </div>
      <div v-if="authStore.canEdit">
        <button
          @click="showCreateModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Register New Vessel</span>
        </button>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
      <div class="sm:col-span-2">
        <label class="block text-slate-400 mb-1">Search (Fuzzy Trigram & Identifiers)</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Search by vessel name, IMO, MMSI, Callsign, or Reg No..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
        />
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Vessel Type</label>
        <select
          v-model="selectedType"
          @change="fetchVessels"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Types</option>
          <option value="purse_seiner">Purse Seiner</option>
          <option value="trawler">Trawler</option>
          <option value="longliner">Longliner</option>
          <option value="carrier_reefer">Carrier / Reefer</option>
          <option value="research">Research Vessel</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Verification Status</label>
        <select
          v-model="selectedStatus"
          @change="fetchVessels"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Statuses</option>
          <option value="verified">Verified</option>
          <option value="unverified">Unverified</option>
          <option value="disputed">Disputed / Conflict</option>
          <option value="flagged_suspicious">Flagged Suspicious</option>
        </select>
      </div>
    </div>

    <!-- Vessels Table -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading vessel records...</div>
      <div v-else-if="vessels.length === 0" class="py-12 text-center text-xs text-slate-400">
        No vessels matching the search criteria.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-950/80 text-slate-400 border-b border-slate-800 font-mono text-[11px]">
              <th class="py-3 px-4">VESSEL NAME</th>
              <th class="py-3 px-4">FLAG</th>
              <th class="py-3 px-4">IMO / MMSI</th>
              <th class="py-3 px-4">TYPE & GEAR</th>
              <th class="py-3 px-4">TONNAGE & SPECS</th>
              <th class="py-3 px-4">VERIFICATION</th>
              <th class="py-3 px-4 text-right">ACTION</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr
              v-for="v in vessels"
              :key="v.id"
              class="hover:bg-slate-800/40 transition-colors"
            >
              <td class="py-3 px-4">
                <div class="font-bold text-white">{{ v.current_name }}</div>
                <div class="text-[10px] text-slate-400 font-mono">
                  Callsign: {{ v.current_callsign || 'N/A' }} • Reg: {{ v.national_registration_number || 'N/A' }}
                </div>
              </td>
              <td class="py-3 px-4">
                <div class="flex items-center gap-1.5">
                  <span class="font-semibold text-slate-200">{{ v.current_flag_country?.name || 'Unknown' }}</span>
                  <span v-if="v.current_flag_country" class="px-1.5 py-0.2 rounded text-[9px] bg-slate-800 text-cyan-300 font-mono">
                    {{ v.current_flag_country.iso_code_2 }}
                  </span>
                </div>
              </td>
              <td class="py-3 px-4 font-mono text-[11px]">
                <div>IMO: <span class="text-slate-200">{{ v.imo_number || 'None' }}</span></div>
                <div>MMSI: <span class="text-slate-300">{{ v.current_mmsi || 'None' }}</span></div>
              </td>
              <td class="py-3 px-4">
                <div class="capitalize text-slate-200 font-medium">{{ v.vessel_type?.replace('_', ' ') || 'General' }}</div>
                <div class="text-[10px] text-slate-400">{{ v.fishing_gear_type || 'Unspecified' }}</div>
              </td>
              <td class="py-3 px-4 font-mono text-[11px] text-slate-300">
                <div>{{ v.gross_tonnage ? `${v.gross_tonnage} GT` : 'N/A' }}</div>
                <div class="text-[10px] text-slate-500">LOA: {{ v.length_overall ? `${v.length_overall}m` : 'N/A' }}</div>
              </td>
              <td class="py-3 px-4">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-mono border"
                  :class="statusBadgeClass(v.verification_status)"
                >
                  {{ v.verification_status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button
                  @click="openVesselDetail(v.id)"
                  class="px-2.5 py-1 rounded bg-slate-800 hover:bg-cyan-600 hover:text-white text-cyan-300 text-xs font-medium transition-colors"
                >
                  Dossier →
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Vessel Detail Modal / Dossier -->
    <div v-if="selectedVessel" class="fixed inset-0 z-50 bg-black/70 flex justify-end">
      <div class="w-full max-w-2xl bg-slate-900 border-l border-slate-800 h-full overflow-y-auto p-6 space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
          <div>
            <h4 class="text-lg font-bold text-white">{{ selectedVessel.current_name }}</h4>
            <div class="text-xs text-cyan-400 font-mono mt-0.5">
              UUID: {{ selectedVessel.id }}
            </div>
          </div>
          <button
            @click="selectedVessel = null"
            class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white"
          >
            ✕
          </button>
        </div>

        <!-- Identity Identifiers Card -->
        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Identifiers & Broadcast IDs</div>
          <div class="grid grid-cols-2 gap-3 text-xs font-mono">
            <div>IMO: <span class="text-cyan-300">{{ selectedVessel.imo_number || 'N/A' }}</span></div>
            <div>MMSI: <span class="text-cyan-300">{{ selectedVessel.current_mmsi || 'N/A' }}</span></div>
            <div>Callsign: <span class="text-cyan-300">{{ selectedVessel.current_callsign || 'N/A' }}</span></div>
            <div>Registration: <span class="text-cyan-300">{{ selectedVessel.national_registration_number || 'N/A' }}</span></div>
          </div>
        </div>

        <!-- Timeline of Identity & Ownership Changes -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Identity & Ownership Timeline</div>
          <div class="space-y-2">
            <div
              v-for="(event, idx) in vesselTimeline"
              :key="idx"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800/80 text-xs flex items-start gap-3"
            >
              <div class="w-2 h-2 rounded-full bg-cyan-400 mt-1.5 shrink-0"></div>
              <div class="flex-1">
                <div class="font-semibold text-white">{{ event.title }}</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                  Valid From: {{ event.valid_from || 'N/A' }} • Current: {{ event.is_current ? 'YES' : 'HISTORICAL' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Licenses & Authorizations -->
        <div class="space-y-3">
          <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Fishing Licenses & Permits</div>
          <div v-if="selectedVessel.licenses?.length === 0" class="text-xs text-slate-500">No licenses recorded.</div>
          <div v-else class="space-y-2">
            <div
              v-for="lic in selectedVessel.licenses"
              :key="lic.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs"
            >
              <div class="font-semibold text-cyan-300 font-mono">{{ lic.license_number }}</div>
              <div class="text-slate-300 mt-1">{{ lic.license_type }}</div>
              <div class="text-[10px] text-slate-400 mt-0.5">
                Target: {{ lic.target_species }} • Valid: {{ lic.valid_from }} to {{ lic.valid_to }}
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
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const vessels = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedType = ref('');
const selectedStatus = ref('');

const selectedVessel = ref<any>(null);
const vesselTimeline = ref<any[]>([]);
const showCreateModal = ref(false);

let searchTimeout: any = null;

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchVessels();
  }, 300);
}

async function fetchVessels() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (selectedType.value) params.append('vessel_type', selectedType.value);
    if (selectedStatus.value) params.append('verification_status', selectedStatus.value);

    const res = await apiClient.get(`/vessels?${params.toString()}`);
    vessels.value = res.data.data;
  } catch (err) {
    console.error('Failed to load vessels', err);
  } finally {
    loading.value = false;
  }
}

async function openVesselDetail(id: string) {
  try {
    const [detailRes, timelineRes] = await Promise.all([
      apiClient.get(`/vessels/${id}`),
      apiClient.get(`/vessels/${id}/timeline`),
    ]);
    selectedVessel.value = detailRes.data.data;
    vesselTimeline.value = timelineRes.data.data;
  } catch (err) {
    console.error('Failed to load vessel detail', err);
  }
}

function statusBadgeClass(status: string) {
  switch (status) {
    case 'verified':
      return 'bg-emerald-950/80 text-emerald-300 border-emerald-800/80';
    case 'disputed':
      return 'bg-red-950/80 text-red-300 border-red-800/80';
    case 'flagged_suspicious':
      return 'bg-amber-950/80 text-amber-300 border-amber-800/80';
    default:
      return 'bg-slate-800 text-slate-400 border-slate-700';
  }
}

onMounted(() => {
  fetchVessels();
});
</script>
