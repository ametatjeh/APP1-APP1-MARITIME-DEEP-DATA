<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Crew Intelligence & Human Rights Oversight</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            ILO 11 PROTOCOL
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Monitoring seafarer contracts, manning agency compliance, and indicators of forced labour and at-sea isolation.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          v-if="activeTab === 'employments'"
          @click="showCreateEmploymentModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Register Crew Embarkation</span>
        </button>
        <button
          v-if="activeTab === 'violations'"
          @click="showCreateViolationModal = true"
          class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-medium transition-colors shadow-lg shadow-rose-950/40 flex items-center gap-1.5"
        >
          <span>⚠️</span>
          <span>Report Labour Infraction</span>
        </button>
        <button
          v-if="activeTab === 'agents'"
          @click="showCreateAgentModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>🏢</span>
          <span>Register Manning Agency</span>
        </button>
      </div>
    </div>

    <!-- ILO 11 Indicators Banner -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs font-mono">
      <div class="flex items-center gap-3">
        <span class="text-2xl">⚓</span>
        <div>
          <div class="font-bold text-white">International Labour Organization (ILO) 11 Indicators</div>
          <span class="text-slate-400 text-[11px]">
            Systematic identification of physical violence, document retention, deceptive contracts, and prolonged high-seas transshipment isolation.
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2 text-rose-400 bg-rose-950/60 border border-rose-800/80 px-3 py-1.5 rounded-lg">
        <span>ALERT: HUMAN RIGHTS MONITORING</span>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-slate-800 flex items-center gap-2 overflow-x-auto text-xs font-medium">
      <button
        @click="activeTab = 'employments'"
        class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'employments' ? 'border-cyan-500 text-cyan-400 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>📋</span>
        <span>Crew Sea Service & Contracts</span>
        <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">{{ employments.length }}</span>
      </button>

      <button
        @click="activeTab = 'violations'"
        class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'violations' ? 'border-rose-500 text-rose-400 bg-rose-950/20' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>⚠️</span>
        <span>Labour & Forced Labour Reports</span>
        <span class="px-1.5 py-0.2 rounded text-[10px] bg-rose-950 text-rose-300 font-mono">{{ violations.length }}</span>
      </button>

      <button
        @click="activeTab = 'agents'"
        class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'agents' ? 'border-cyan-500 text-cyan-400 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>🏢</span>
        <span>Manning & Recruitment Agencies</span>
        <span class="px-1.5 py-0.2 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">{{ agents.length }}</span>
      </button>
    </div>

    <!-- Tab 1: Crew Employments -->
    <div v-if="activeTab === 'employments'" class="space-y-4">
      <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading crew embarkation records...</div>
        <div v-else-if="employments.length === 0" class="py-12 text-center text-xs text-slate-400">
          No crew sea service records found.
        </div>
        <div v-else class="divide-y divide-slate-800">
          <div
            v-for="emp in employments"
            :key="emp.id"
            class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs"
          >
            <div class="space-y-1.5 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-sm text-white">
                  {{ emp.person?.full_name || 'Seafarer' }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                  {{ formatRole(emp.role_on_board) }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="contractStatusBadge(emp.contract_status)">
                  {{ emp.contract_status.replace('_', ' ') }}
                </span>
                <span v-if="emp.is_forced_labour_suspected" class="px-2 py-0.5 rounded text-[10px] font-mono bg-rose-950 text-rose-300 border border-rose-800 font-bold animate-pulse">
                  ⚠️ FORCED LABOUR SUSPECTED
                </span>
              </div>

              <!-- Vessel & Manning Info -->
              <div class="flex flex-wrap items-center gap-4 text-[11px] font-mono text-slate-400">
                <span>Vessel: <strong class="text-cyan-400">{{ emp.vessel?.name }}</strong> (IMO: {{ emp.vessel?.imo || 'N/A' }})</span>
                <span v-if="emp.manning_agent">Agency: <strong class="text-slate-200">{{ emp.manning_agent.agent_name }}</strong></span>
                <span>Wage: <strong class="text-slate-200">{{ emp.salary_currency }} {{ emp.reported_monthly_wage || '-' }}/mo</strong></span>
              </div>

              <!-- Ports & Dates -->
              <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono">
                <span>Embarked: <span class="text-slate-300">{{ emp.embarkation_port || '-' }} ({{ formatDate(emp.embarkation_date) }})</span></span>
                <span>Disembarked: <span class="text-slate-300">{{ emp.disembarkation_port || '-' }} ({{ formatDate(emp.disembarkation_date) }})</span></span>
                <span v-if="emp.labour_reports_count > 0" class="text-rose-400 font-bold">
                  Infractions: {{ emp.labour_reports_count }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 2: Labour Violation Reports -->
    <div v-if="activeTab === 'violations'" class="space-y-4">
      <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading labour violation reports...</div>
        <div v-else-if="violations.length === 0" class="py-12 text-center text-xs text-slate-400">
          No labour violations registered.
        </div>
        <div v-else class="divide-y divide-slate-800">
          <div
            v-for="v in violations"
            :key="v.id"
            class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-start justify-between gap-4 text-xs"
          >
            <div class="space-y-2 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded text-[10px] font-mono uppercase font-bold" :class="severityBadgeClass(v.severity)">
                  SEVERITY: {{ v.severity }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 uppercase">
                  {{ formatIndicator(v.indicator_type) }}
                </span>
                <span v-if="v.is_verified" class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
                  ✓ VERIFIED REPORT
                </span>
              </div>

              <!-- Vessel & Victim -->
              <div class="flex flex-wrap items-center gap-4 text-[11px] font-mono text-slate-400">
                <span>Vessel: <strong class="text-cyan-400">{{ v.vessel?.name }}</strong></span>
                <span v-if="v.person">Subject: <strong class="text-slate-200">{{ v.person.full_name }}</strong></span>
                <span>Incident Date: <strong class="text-slate-300">{{ formatDate(v.incident_date) }}</strong></span>
              </div>

              <div class="text-xs text-slate-200 bg-slate-950 p-3 rounded-lg border border-slate-800 leading-relaxed font-sans">
                {{ v.incident_description }}
              </div>

              <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono">
                <span>Reported By: <strong class="text-slate-400">{{ v.reported_by || 'Confidential Whistleblower' }}</strong></span>
                <span v-if="v.reporting_organization">Org: {{ v.reporting_organization }}</span>
                <span v-if="v.investigation_case">Linked Case: <router-link :to="`/investigations/${v.investigation_case.id}`" class="text-cyan-400 hover:underline">{{ v.investigation_case.reference_number }}</router-link></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 3: Manning Agencies -->
    <div v-if="activeTab === 'agents'" class="space-y-4">
      <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading manning agencies...</div>
        <div v-else-if="agents.length === 0" class="py-12 text-center text-xs text-slate-400">
          No manning agencies registered.
        </div>
        <div v-else class="divide-y divide-slate-800">
          <div
            v-for="a in agents"
            :key="a.id"
            class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs"
          >
            <div class="space-y-1.5 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-sm text-white">{{ a.agent_name }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="agentRatingBadge(a.compliance_rating)">
                  {{ a.compliance_rating.replace('_', ' ') }}
                </span>
                <span v-if="a.license_number" class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300">
                  License: {{ a.license_number }}
                </span>
                <span v-if="a.country" class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">
                  🌍 {{ a.country.name }}
                </span>
              </div>

              <p v-if="a.past_violations_summary" class="text-xs text-slate-400 line-clamp-2 italic">
                "{{ a.past_violations_summary }}"
              </p>

              <div class="text-[11px] text-slate-500 font-mono">
                Address: <span class="text-slate-400">{{ a.registered_address || 'Not Recorded' }}</span> • Active Contracts: <strong class="text-cyan-400">{{ a.employments_count || 0 }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->

    <!-- Create Employment Modal -->
    <div v-if="showCreateEmploymentModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Register Crew Embarkation</h4>
        <form @submit.prevent="createEmployment" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Select Seafarer / Crew *</label>
            <select v-model="newEmployment.person_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="" disabled>Select person...</option>
              <option v-for="p in availablePersons" :key="p.id" :value="p.id">{{ p.full_name }} ({{ p.primary_role }})</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Select Vessel *</label>
            <select v-model="newEmployment.vessel_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="" disabled>Select vessel...</option>
              <option v-for="v in availableVessels" :key="v.id" :value="v.id">{{ v.name }} (IMO: {{ v.imo || 'N/A' }})</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Role on Board *</label>
              <select v-model="newEmployment.role_on_board" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="deckhand">Deckhand / Crew</option>
                <option value="master_skipper">Master / Skipper</option>
                <option value="chief_engineer">Chief Engineer</option>
                <option value="second_engineer">Second Engineer</option>
                <option value="bosun">Bosun</option>
                <option value="cook">Cook</option>
                <option value="fisheries_observer">Fisheries Observer</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Manning Agency</label>
              <select v-model="newEmployment.manning_agent_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="">Direct Hire / Unspecified</option>
                <option v-for="a in agents" :key="a.id" :value="a.id">{{ a.agent_name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Embarkation Date</label>
              <input v-model="newEmployment.embarkation_date" type="date" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Embarkation Port</label>
              <input v-model="newEmployment.embarkation_port" type="text" placeholder="e.g. Benoa, Bali" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showCreateEmploymentModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 text-white">Register Embarkation</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Violation Modal -->
    <div v-if="showCreateViolationModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Report Labour & Forced Labour Infraction</h4>
        <form @submit.prevent="createViolation" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Target Vessel *</label>
            <select v-model="newViolation.vessel_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="" disabled>Select vessel...</option>
              <option v-for="v in availableVessels" :key="v.id" :value="v.id">{{ v.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">ILO Indicator *</label>
              <select v-model="newViolation.indicator_type" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="retention_of_documents">Retention of Identity Documents</option>
                <option value="isolation_transshipment">At-Sea Isolation / Transshipment</option>
                <option value="withholding_of_wages">Withholding of Wages</option>
                <option value="violence_intimidation">Physical Violence / Intimidation</option>
                <option value="deception">Deceptive Recruitment</option>
                <option value="debt_bondage">Debt Bondage</option>
                <option value="abusive_conditions">Abusive Working Conditions</option>
                <option value="excessive_overtime">Excessive Overtime</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Severity Level *</label>
              <select v-model="newViolation.severity" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Incident Narrative & Findings *</label>
            <textarea v-model="newViolation.incident_description" required rows="3" placeholder="Provide factual details..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showCreateViolationModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-rose-600 text-white">Submit Infraction Report</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create Manning Agent Modal -->
    <div v-if="showCreateAgentModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Register Manning Agency</h4>
        <form @submit.prevent="createAgent" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Agency Name *</label>
            <input v-model="newAgent.agent_name" required type="text" placeholder="e.g. PT Samudera Bahari Sentosa" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">License Number</label>
              <input v-model="newAgent.license_number" type="text" placeholder="e.g. SIP3MI-2024-001" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Compliance Rating</label>
              <select v-model="newAgent.compliance_rating" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="compliant">Compliant</option>
                <option value="under_scrutiny">Under Scrutiny</option>
                <option value="high_risk_flagged">High Risk Flagged</option>
                <option value="suspended_revoked">Suspended / Revoked</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Past Violations / Compliance Notes</label>
            <textarea v-model="newAgent.past_violations_summary" rows="2" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
            <button type="button" @click="showCreateAgentModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 text-white">Save Agency</button>
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
const activeTab = ref('employments');
const loading = ref(false);

const employments = ref<any[]>([]);
const violations = ref<any[]>([]);
const agents = ref<any[]>([]);
const availablePersons = ref<any[]>([]);
const availableVessels = ref<any[]>([]);

const showCreateEmploymentModal = ref(false);
const showCreateViolationModal = ref(false);
const showCreateAgentModal = ref(false);

const newEmployment = ref({
  person_id: '',
  vessel_id: '',
  manning_agent_id: '',
  role_on_board: 'deckhand',
  embarkation_date: '',
  embarkation_port: '',
  contract_status: 'active_on_board',
});

const newViolation = ref({
  vessel_id: '',
  indicator_type: 'retention_of_documents',
  severity: 'high',
  incident_description: '',
});

const newAgent = ref({
  agent_name: '',
  license_number: '',
  compliance_rating: 'under_scrutiny',
  past_violations_summary: '',
});

async function loadData() {
  loading.value = true;
  try {
    const [empRes, violRes, agentRes] = await Promise.all([
      apiClient.get('/crew/employments?per_page=50'),
      apiClient.get('/crew/labour-violations?per_page=50'),
      apiClient.get('/crew/agents?per_page=50'),
    ]);
    employments.value = empRes.data.data || [];
    violations.value = violRes.data.data || [];
    agents.value = agentRes.data.data || [];
  } catch (err) {
    console.error('Failed to load crew intelligence data', err);
  } finally {
    loading.value = false;
  }
}

async function loadDropdowns() {
  try {
    const [pRes, vRes] = await Promise.all([
      apiClient.get('/persons?per_page=100'),
      apiClient.get('/vessels?per_page=100'),
    ]);
    availablePersons.value = pRes.data.data || [];
    availableVessels.value = vRes.data.data || [];
  } catch (err) {
    console.error(err);
  }
}

async function createEmployment() {
  try {
    await apiClient.post('/crew/employments', newEmployment.value);
    showCreateEmploymentModal.value = false;
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to register employment.');
  }
}

async function createViolation() {
  try {
    await apiClient.post('/crew/labour-violations', newViolation.value);
    showCreateViolationModal.value = false;
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to submit report.');
  }
}

async function createAgent() {
  try {
    await apiClient.post('/crew/agents', newAgent.value);
    showCreateAgentModal.value = false;
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to register agency.');
  }
}

function formatRole(r: string) {
  return (r || '').replace(/_/g, ' ').toUpperCase();
}

function formatIndicator(i: string) {
  return (i || '').replace(/_/g, ' ').toUpperCase();
}

function contractStatusBadge(status: string) {
  switch (status) {
    case 'active_on_board': return 'bg-cyan-950 text-cyan-300 border border-cyan-800';
    case 'completed': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'escaped_reported': return 'bg-rose-950 text-rose-300 border border-rose-800 font-bold';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function severityBadgeClass(sev: string) {
  switch (sev) {
    case 'critical': return 'bg-rose-950 text-rose-300 border border-rose-800';
    case 'high': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'medium': return 'bg-yellow-950 text-yellow-300 border border-yellow-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function agentRatingBadge(rating: string) {
  switch (rating) {
    case 'compliant': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'under_scrutiny': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'high_risk_flagged': return 'bg-rose-950 text-rose-300 border border-rose-800 font-bold';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(() => {
  loadData();
  loadDropdowns();
});
</script>
