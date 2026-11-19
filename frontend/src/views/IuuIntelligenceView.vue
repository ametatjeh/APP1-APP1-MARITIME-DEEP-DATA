<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">IUU Intelligence & Multilateral Sanctions</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-red-950 border border-red-800 text-red-300">
            RFMO & INTERPOL
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Official regional fisheries management sanctions, unauthorized high-seas transshipments, and dark vessel intrusions.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          v-if="activeTab === 'listings'"
          @click="showCreateListingModal = true"
          class="px-3.5 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-medium transition-colors shadow-lg shadow-red-950/40 flex items-center gap-1.5"
        >
          <span>🚨</span>
          <span>Record Official IUU Listing</span>
        </button>
        <button
          v-if="activeTab === 'infractions'"
          @click="showCreateInfractionModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>📍</span>
          <span>Record Infraction Event</span>
        </button>
      </div>
    </div>

    <!-- Official vs Allegation Distinction Banner -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
      <div class="flex items-start gap-3">
        <span class="text-xl">⚖️</span>
        <div>
          <strong class="text-white">Strict Adjudication Separation:</strong>
          <span class="text-slate-400 block text-[11px]">
            Official RFMO/Government sanctions are segregated from investigative allegations to preserve strict legal rigor in NGO filings.
          </span>
        </div>
      </div>
      <div class="flex items-start gap-3">
        <span class="text-xl">🌐</span>
        <div>
          <strong class="text-white">Multilateral Cross-Listing:</strong>
          <span class="text-slate-400 block text-[11px]">
            Integrates WCPFC, IOTC, CCAMLR, ICCAT, INTERPOL Purple Notices, and Indonesian PSDKP enforcement registries.
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-slate-800 flex items-center gap-2 overflow-x-auto text-xs font-medium">
      <button
        @click="activeTab = 'listings'"
        class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'listings' ? 'border-red-500 text-red-400 bg-red-950/20' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>🚨</span>
        <span>Official RFMO IUU Vessel Lists</span>
        <span class="px-1.5 py-0.2 rounded text-[10px] bg-red-950 text-red-300 font-mono">{{ listings.length }}</span>
      </button>

      <button
        @click="activeTab = 'infractions'"
        class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'infractions' ? 'border-cyan-500 text-cyan-400 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>📍</span>
        <span>Geospatial Infraction Incidents</span>
        <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">{{ infractions.length }}</span>
      </button>
    </div>

    <!-- Tab 1: Official IUU Listings -->
    <div v-if="activeTab === 'listings'" class="space-y-4">
      <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading official IUU vessel listings...</div>
        <div v-else-if="listings.length === 0" class="py-12 text-center text-xs text-slate-400">
          No official IUU listings recorded.
        </div>
        <div v-else class="divide-y divide-slate-800">
          <div
            v-for="l in listings"
            :key="l.id"
            class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-start justify-between gap-4 text-xs"
          >
            <div class="space-y-1.5 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded text-[10px] font-mono uppercase font-bold bg-red-950 text-red-300 border border-red-800">
                  {{ l.listing_organization }}
                </span>
                <span class="font-bold text-sm text-white">
                  {{ l.vessel?.name }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                  {{ l.listing_status }}
                </span>
                <span v-if="l.official_reference_number" class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-950 border border-slate-800 text-cyan-400">
                  Ref: {{ l.official_reference_number }}
                </span>
              </div>

              <!-- Vessel identifiers -->
              <div class="flex flex-wrap items-center gap-4 text-[11px] font-mono text-slate-400">
                <span>IMO: <strong class="text-slate-200">{{ l.vessel?.imo || 'N/A' }}</strong></span>
                <span>Flag: <strong class="text-slate-200">{{ l.reported_flag_state || l.vessel?.flag_state || 'Unknown' }}</strong></span>
                <span>Listed Date: <strong class="text-slate-300">{{ formatDate(l.listing_date) }}</strong></span>
                <span v-if="l.delisting_date" class="text-amber-400">Delisted: {{ formatDate(l.delisting_date) }}</span>
              </div>

              <div class="text-xs text-slate-300 bg-slate-950 p-3 rounded-lg border border-slate-800 leading-relaxed font-sans">
                {{ l.listing_reason }}
              </div>

              <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono">
                <span>List: {{ l.list_name }}</span>
                <a v-if="l.source_url" :href="l.source_url" target="_blank" class="text-cyan-400 hover:underline">
                  Official Gazette / RFMO Registry ↗
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 2: IUU Infraction Events -->
    <div v-if="activeTab === 'infractions'" class="space-y-4">
      <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading infraction records...</div>
        <div v-else-if="infractions.length === 0" class="py-12 text-center text-xs text-slate-400">
          No infraction events recorded.
        </div>
        <div v-else class="divide-y divide-slate-800">
          <div
            v-for="i in infractions"
            :key="i.id"
            class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-start justify-between gap-4 text-xs"
          >
            <div class="space-y-2 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold" :class="severityBadgeClass(i.severity)">
                  {{ i.severity }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                  {{ formatInfractionType(i.infraction_type) }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="categoryBadgeClass(i.event_category)">
                  {{ i.event_category.replace('_', ' ') }}
                </span>
                <span class="font-bold text-white text-xs">🚢 {{ i.vessel?.name }}</span>
              </div>

              <!-- Location & Date -->
              <div class="flex flex-wrap items-center gap-4 text-[11px] font-mono text-slate-400">
                <span v-if="i.longitude && i.latitude" class="text-cyan-400 font-semibold">
                  📍 {{ i.latitude }}°N, {{ i.longitude }}°E
                </span>
                <span v-if="i.location_text">Area: {{ i.location_text }}</span>
                <span>Date: <strong class="text-slate-300">{{ formatDate(i.event_date) }}</strong></span>
                <span v-if="i.reporting_agency">Agency: <strong class="text-slate-200">{{ i.reporting_agency }}</strong></span>
              </div>

              <div class="text-xs text-slate-200 bg-slate-950 p-3 rounded-lg border border-slate-800 leading-relaxed font-sans">
                {{ i.description }}
              </div>

              <div v-if="i.action_taken" class="text-[11px] text-emerald-400 font-mono bg-emerald-950/40 p-2 rounded border border-emerald-900/60">
                Enforcement Action: {{ i.action_taken }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->

    <!-- Create Listing Modal -->
    <div v-if="showCreateListingModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Record Official RFMO IUU Listing</h4>
        <form @submit.prevent="createListing" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Target Vessel *</label>
            <select v-model="newListing.vessel_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="" disabled>Select vessel...</option>
              <option v-for="v in availableVessels" :key="v.id" :value="v.id">{{ v.name }} (IMO: {{ v.imo || 'N/A' }})</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Listing Organization *</label>
              <select v-model="newListing.listing_organization" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="WCPFC">WCPFC (Western Central Pacific)</option>
                <option value="IOTC">IOTC (Indian Ocean)</option>
                <option value="INTERPOL">INTERPOL (Purple Notice)</option>
                <option value="KKP">KKP RI (Indonesia)</option>
                <option value="EU_COMMISSION">European Commission</option>
                <option value="ICCAT">ICCAT (Atlantic Tunas)</option>
                <option value="CCAMLR">CCAMLR (Antarctic)</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">List Name *</label>
              <input v-model="newListing.list_name" required type="text" placeholder="e.g. WCPFC IUU Vessel List" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Listing Date *</label>
              <input v-model="newListing.listing_date" required type="date" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Official Reference Number</label>
              <input v-model="newListing.official_reference_number" type="text" placeholder="e.g. WCPFC-IUU-2024-001" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Listing Rationale / Infraction Reason *</label>
            <textarea v-model="newListing.listing_reason" required rows="3" placeholder="Factual grounds for listing..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Source URL / Official Gazette Link</label>
            <input v-model="newListing.source_url" type="url" placeholder="https://..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showCreateListingModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-red-600 text-white">Record Sanction</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Infraction Modal -->
    <div v-if="showCreateInfractionModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Record IUU Infraction Event</h4>
        <form @submit.prevent="createInfraction" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Vessel *</label>
            <select v-model="newInfraction.vessel_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="" disabled>Select vessel...</option>
              <option v-for="v in availableVessels" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Infraction Type *</label>
              <select v-model="newInfraction.infraction_type" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="unauthorized_transshipment">Unauthorized Transshipment</option>
                <option value="unlicensed_fishing">Unlicensed Fishing</option>
                <option value="ais_spoofing_dark_vessel">AIS Spoofing / Dark Vessel</option>
                <option value="fishing_in_closed_area">Fishing in Closed Area / MPA</option>
                <option value="prohibited_gear">Prohibited Fishing Gear</option>
                <option value="shark_finning">Shark Finning</option>
                <option value="flag_hopping">Flag Hopping / Double Flag</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Severity *</label>
              <select v-model="newInfraction.severity" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="critical">Critical</option>
                <option value="serious">Serious</option>
                <option value="moderate">Moderate</option>
                <option value="minor">Minor</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Event Date *</label>
              <input v-model="newInfraction.event_date" required type="date" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Longitude (°E)</label>
              <input v-model.number="newInfraction.longitude" type="number" step="0.0001" placeholder="108.5" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white font-mono" />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Latitude (°N)</label>
              <input v-model.number="newInfraction.latitude" type="number" step="0.0001" placeholder="4.2" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white font-mono" />
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Description *</label>
            <textarea v-model="newInfraction.description" required rows="2" placeholder="Circumstances and observations..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Action Taken (if any)</label>
            <input v-model="newInfraction.action_taken" type="text" placeholder="e.g. Seized by patrol, legal proceeding initiated" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showCreateInfractionModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 text-white">Save Event</button>
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
const activeTab = ref('listings');
const loading = ref(false);

const listings = ref<any[]>([]);
const infractions = ref<any[]>([]);
const availableVessels = ref<any[]>([]);

const showCreateListingModal = ref(false);
const showCreateInfractionModal = ref(false);

const newListing = ref({
  vessel_id: '',
  listing_organization: 'WCPFC',
  list_name: 'WCPFC IUU Vessel List',
  listing_date: '',
  official_reference_number: '',
  listing_reason: '',
  source_url: '',
  listing_status: 'listed',
});

const newInfraction = ref({
  vessel_id: '',
  infraction_type: 'unauthorized_transshipment',
  event_category: 'official_allegation',
  severity: 'serious',
  event_date: '',
  longitude: 108.5,
  latitude: 4.2,
  description: '',
  action_taken: '',
});

async function loadData() {
  loading.value = true;
  try {
    const [listRes, infrRes] = await Promise.all([
      apiClient.get('/iuu/listings?per_page=50'),
      apiClient.get('/iuu/infractions?per_page=50'),
    ]);
    listings.value = listRes.data.data || [];
    infractions.value = infrRes.data.data || [];
  } catch (err) {
    console.error('Failed to load IUU data', err);
  } finally {
    loading.value = false;
  }
}

async function loadVessels() {
  try {
    const res = await apiClient.get('/vessels?per_page=100');
    availableVessels.value = res.data.data || [];
  } catch (err) {
    console.error(err);
  }
}

async function createListing() {
  try {
    await apiClient.post('/iuu/listings', newListing.value);
    showCreateListingModal.value = false;
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to record listing.');
  }
}

async function createInfraction() {
  try {
    await apiClient.post('/iuu/infractions', newInfraction.value);
    showCreateInfractionModal.value = false;
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to record infraction.');
  }
}

function formatInfractionType(t: string) {
  return (t || '').replace(/_/g, ' ').toUpperCase();
}

function severityBadgeClass(sev: string) {
  switch (sev) {
    case 'critical': return 'bg-red-950 text-red-300 border border-red-800';
    case 'serious': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'moderate': return 'bg-yellow-950 text-yellow-300 border border-yellow-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function categoryBadgeClass(cat: string) {
  switch (cat) {
    case 'official_adjudication': return 'bg-red-950 text-red-300 border border-red-800';
    case 'official_allegation': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'ngo_investigation_finding': return 'bg-cyan-950 text-cyan-300 border border-cyan-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(() => {
  loadData();
  loadVessels();
});
</script>
