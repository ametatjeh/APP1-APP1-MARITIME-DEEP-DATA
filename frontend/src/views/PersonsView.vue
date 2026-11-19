<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Persons & Beneficial Ownership Intelligence</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            PHASE 3 CORE
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Encrypted person dossiers, Politically Exposed Persons (PEPs), vessel masters, and corporate beneficial owners.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          @click="showCreateModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Register Person Profile</span>
        </button>
      </div>
    </div>

    <!-- PII Security & Encryption Notice -->
    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs font-mono">
      <div class="flex items-center gap-3">
        <span class="text-emerald-400 text-base">🔒</span>
        <div class="text-slate-300">
          <strong class="text-white">Strict PII Cryptographic Encryption:</strong>
          National ID, passport, and seaman book numbers are encrypted at rest with AES-256. Viewers receive masked tokens only.
        </div>
      </div>
      <div class="hidden md:flex items-center gap-2 text-slate-500 text-[11px]">
        <span>ACCESS LOGGED: YES</span>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
      <div>
        <label class="block text-slate-400 mb-1">Search Persons (Fuzzy Name / Alias)</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Search full name, alias, or identifier..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
        />
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Primary Role</label>
        <select
          v-model="selectedRole"
          @change="fetchPersons"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Roles</option>
          <option value="beneficial_owner">Beneficial Owner (UBO)</option>
          <option value="director">Corporate Director</option>
          <option value="officer">Company Officer</option>
          <option value="skipper">Vessel Master / Skipper</option>
          <option value="crew">Crew Member / Seafarer</option>
          <option value="manning_agent">Manning Agent Recruiter</option>
          <option value="broker">Quota / Vessel Broker</option>
          <option value="target">Investigation Target</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Verification Status</label>
        <select
          v-model="selectedStatus"
          @change="fetchPersons"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Statuses</option>
          <option value="verified">Verified Profile</option>
          <option value="unverified">Unverified Claims</option>
          <option value="under_review">Under Review</option>
          <option value="flagged_suspicious">Flagged Suspicious</option>
          <option value="disputed">Disputed Record</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Sanctions / PEP</label>
        <select
          v-model="selectedSanctioned"
          @change="fetchPersons"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Individuals</option>
          <option value="sanctioned">Sanctioned Targets Only</option>
          <option value="pep">Politically Exposed Persons (PEP)</option>
        </select>
      </div>
    </div>

    <!-- Persons Table / Grid -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading person intelligence profiles...</div>
      <div v-else-if="persons.length === 0" class="py-12 text-center text-xs text-slate-400">
        No person profiles found matching criteria.
      </div>
      <div v-else class="divide-y divide-slate-800">
        <div
          v-for="p in persons"
          :key="p.id"
          class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-1.5 flex-1 cursor-pointer" @click="openPersonDetail(p.id)">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-base">{{ getRoleIcon(p.primary_role) }}</span>
              <span class="font-bold text-sm text-white hover:text-cyan-300 transition-colors">
                {{ p.full_name }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ formatRole(p.primary_role) }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="statusBadgeClass(p.verification_status)">
                {{ p.verification_status }}
              </span>
              <span v-if="p.is_sanctioned" class="px-2 py-0.5 rounded text-[10px] font-mono bg-red-950 text-red-300 border border-red-800 font-bold">
                ⚠️ SANCTIONED TARGET
              </span>
              <span v-if="p.is_pep" class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-950 text-amber-300 border border-amber-800">
                ⭐ PEP
              </span>
            </div>

            <!-- Aliases & Identifiers -->
            <div class="flex flex-wrap items-center gap-3 text-[11px] font-mono text-slate-400">
              <span v-if="p.masked_identifier" class="text-slate-300 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                ID: {{ p.masked_identifier }}
              </span>
              <span v-if="p.nationality_country" class="text-slate-400">
                Nationality: <strong class="text-slate-200">{{ p.nationality_country.name }}</strong>
              </span>
              <span v-if="p.aliases && p.aliases.length" class="text-cyan-400">
                Aliases: {{ p.aliases.map((a: any) => a.alias_name).join(', ') }}
              </span>
            </div>

            <p v-if="p.notes" class="text-xs text-slate-400 line-clamp-1 max-w-4xl italic">
              "{{ p.notes }}"
            </p>

            <!-- Relationships count -->
            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono pt-0.5">
              <span>Corporate Ties: <strong class="text-cyan-400">{{ p.corporate_ties_count || 0 }}</strong></span>
              <span>Vessel Ties: <strong class="text-emerald-400">{{ p.vessel_ties_count || 0 }}</strong></span>
              <span>Sea Employments: <strong class="text-slate-300">{{ p.crew_employments_count || 0 }}</strong></span>
              <span v-if="p.labour_reports_count > 0" class="text-rose-400 font-bold">
                Labour Reports: {{ p.labour_reports_count }}
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start md:self-center">
            <button
              @click="openPersonDetail(p.id)"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700 flex items-center gap-1"
            >
              <span>Inspect Profile</span>
              <span>→</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Person Detail Modal -->
    <div
      v-if="selectedPerson"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-3xl w-full p-6 space-y-5 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-lg">{{ getRoleIcon(selectedPerson.primary_role) }}</span>
              <h4 class="text-base font-bold text-white">{{ selectedPerson.full_name }}</h4>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ selectedPerson.primary_role }}
              </span>
              <span v-if="selectedPerson.is_sanctioned" class="px-2 py-0.5 rounded text-[10px] font-mono bg-red-950 text-red-300 border border-red-800 font-bold">
                SANCTIONED
              </span>
            </div>
            <p class="text-[11px] text-slate-400 font-mono">PERSON UUID: {{ selectedPerson.id }}</p>
          </div>
          <button @click="selectedPerson = null" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <!-- Identity & PII Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono bg-slate-950 p-3.5 rounded-lg border border-slate-800">
          <div>
            <span class="text-slate-500 block">Nationality</span>
            <span class="text-slate-200 font-semibold">{{ selectedPerson.nationality_country?.name || 'Unknown' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Date of Birth</span>
            <span class="text-slate-300">{{ selectedPerson.date_of_birth || '-' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Encrypted Passport</span>
            <span class="text-cyan-300">{{ selectedPerson.encrypted_passport_number || selectedPerson.masked_identifier || '[REDACTED]' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">National Tax / ID</span>
            <span class="text-cyan-300">{{ selectedPerson.encrypted_national_id || selectedPerson.masked_identifier || '[REDACTED]' }}</span>
          </div>
        </div>

        <!-- Aliases Section -->
        <div v-if="selectedPerson.aliases && selectedPerson.aliases.length" class="space-y-2">
          <h5 class="text-xs font-bold text-slate-300 uppercase font-mono">Known Aliases & Transliterations</h5>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="a in selectedPerson.aliases"
              :key="a.id"
              class="px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono"
            >
              {{ a.alias_name }} <span class="text-slate-500 text-[10px]">({{ a.alias_type }})</span>
            </span>
          </div>
        </div>

        <!-- Corporate Ties -->
        <div class="space-y-2">
          <h5 class="text-xs font-bold text-slate-300 uppercase font-mono">Corporate Ownership & Directorships</h5>
          <div v-if="!selectedPerson.corporate_ties || selectedPerson.corporate_ties.length === 0" class="text-xs text-slate-500 italic p-3 bg-slate-950 rounded border border-slate-800">
            No corporate entities currently linked to this profile.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="tie in selectedPerson.corporate_ties"
              :key="tie.id"
              class="p-3 rounded bg-slate-950 border border-slate-800 flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-white">{{ tie.company?.company_name || 'Company ID: ' + tie.company_id }}</span>
                <span class="text-slate-400 block text-[11px] font-mono">
                  Role: {{ tie.role }} • Shares: {{ tie.shares_percentage ? tie.shares_percentage + '%' : 'N/A' }}
                </span>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ tie.is_current ? 'Current' : 'Historical' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Vessel Ties -->
        <div class="space-y-2">
          <h5 class="text-xs font-bold text-slate-300 uppercase font-mono">Vessel Master & Operations</h5>
          <div v-if="!selectedPerson.vessel_ties || selectedPerson.vessel_ties.length === 0" class="text-xs text-slate-500 italic p-3 bg-slate-950 rounded border border-slate-800">
            No vessels currently operated or chartered by this profile.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="vt in selectedPerson.vessel_ties"
              :key="vt.id"
              class="p-3 rounded bg-slate-950 border border-slate-800 flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-white">🚢 {{ vt.vessel?.name }}</span>
                <span class="text-slate-400 block text-[11px] font-mono">
                  Role: {{ vt.role }} • Flag: {{ vt.vessel?.flag_state || 'N/A' }}
                </span>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ vt.is_current ? 'Active' : 'Past' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Close -->
        <div class="flex justify-end pt-3 border-t border-slate-800">
          <button
            @click="selectedPerson = null"
            class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs transition-colors"
          >
            Close Dossier
          </button>
        </div>
      </div>
    </div>

    <!-- Register Person Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-xl w-full p-6 space-y-4 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h4 class="text-sm font-bold text-white">Register Person Profile</h4>
            <p class="text-[11px] text-slate-400">All sensitive identifiers are automatically hashed and encrypted at rest.</p>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="createPerson" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Full Legal Name *</label>
            <input
              v-model="newPerson.full_name"
              required
              type="text"
              placeholder="e.g. Alexander Chen"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Primary Role *</label>
              <select
                v-model="newPerson.primary_role"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="beneficial_owner">Beneficial Owner (UBO)</option>
                <option value="director">Corporate Director</option>
                <option value="officer">Company Officer</option>
                <option value="skipper">Vessel Master / Skipper</option>
                <option value="crew">Crew Member</option>
                <option value="manning_agent">Manning Agent Recruiter</option>
                <option value="broker">Broker</option>
                <option value="target">Investigation Target</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Nationality</label>
              <select
                v-model="newPerson.nationality_country_id"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="">Select country...</option>
                <option v-for="c in countries" :key="c.id" :value="c.id">
                  {{ c.name }} ({{ c.iso_code_3 }})
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Date of Birth</label>
              <input
                v-model="newPerson.date_of_birth"
                type="date"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Passport Number (Encrypted)</label>
              <input
                v-model="newPerson.passport_number"
                type="text"
                placeholder="e.g. E12345678"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white font-mono focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div class="flex items-center gap-6 pt-1 font-mono">
            <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
              <input v-model="newPerson.is_pep" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-cyan-600" />
              <span>Politically Exposed Person (PEP)</span>
            </label>
            <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
              <input v-model="newPerson.is_sanctioned" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-red-600" />
              <span>Sanctioned Target</span>
            </label>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Investigative Notes</label>
            <textarea
              v-model="newPerson.notes"
              rows="3"
              placeholder="Background, corporate ownership structures, known associates..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium"
            >
              {{ creating ? 'Registering...' : 'Register Profile' }}
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

const persons = ref<any[]>([]);
const countries = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedRole = ref('');
const selectedStatus = ref('');
const selectedSanctioned = ref('');

const selectedPerson = ref<any>(null);
const showCreateModal = ref(false);
const creating = ref(false);

const newPerson = ref({
  full_name: '',
  primary_role: 'beneficial_owner',
  nationality_country_id: '',
  date_of_birth: '',
  passport_number: '',
  is_pep: false,
  is_sanctioned: false,
  notes: '',
});

let searchTimeout: any = null;

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchPersons();
  }, 300);
}

async function fetchPersons() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (selectedRole.value) params.append('primary_role', selectedRole.value);
    if (selectedStatus.value) params.append('verification_status', selectedStatus.value);
    if (selectedSanctioned.value === 'sanctioned') params.append('is_sanctioned', 'true');
    if (selectedSanctioned.value === 'pep') params.append('is_pep', 'true');

    const res = await apiClient.get(`/persons?${params.toString()}`);
    persons.value = res.data.data;
  } catch (err) {
    console.error('Failed to load persons', err);
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

async function openPersonDetail(id: string) {
  try {
    const res = await apiClient.get(`/persons/${id}`);
    selectedPerson.value = res.data.data;
  } catch (err) {
    console.error('Failed to load person detail', err);
  }
}

async function createPerson() {
  creating.value = true;
  try {
    const payload: any = { ...newPerson.value };
    if (!payload.nationality_country_id) delete payload.nationality_country_id;
    if (!payload.date_of_birth) delete payload.date_of_birth;
    if (!payload.passport_number) delete payload.passport_number;

    await apiClient.post('/persons', payload);
    showCreateModal.value = false;
    newPerson.value = {
      full_name: '',
      primary_role: 'beneficial_owner',
      nationality_country_id: '',
      date_of_birth: '',
      passport_number: '',
      is_pep: false,
      is_sanctioned: false,
      notes: '',
    };
    await fetchPersons();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to create person.');
  } finally {
    creating.value = false;
  }
}

function getRoleIcon(role: string) {
  switch (role) {
    case 'beneficial_owner': return '👑';
    case 'director': return '👔';
    case 'officer': return '💼';
    case 'skipper': return '⚓';
    case 'crew': return '🧑‍✈️';
    case 'manning_agent': return '🏢';
    case 'target': return '🎯';
    default: return '👤';
  }
}

function formatRole(role: string) {
  return (role || '').replace(/_/g, ' ').toUpperCase();
}

function statusBadgeClass(status: string) {
  switch (status) {
    case 'verified': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'under_review': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'flagged_suspicious': return 'bg-rose-950 text-rose-300 border border-rose-800 font-bold';
    case 'unverified': return 'bg-amber-950 text-amber-300 border border-amber-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

onMounted(() => {
  fetchPersons();
  fetchCountries();
});
</script>
