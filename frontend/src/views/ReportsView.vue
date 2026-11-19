<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Investigative Dossiers & Research Reports</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            EVIDENCE-LINKED DOSSIERS
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Court-ready intelligence dossiers with immutable SHA-256 integrity checksums, structured snapshots, and strict evidence citations.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          @click="openCompileVesselModal"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>🚢</span>
          <span>Compile Vessel Dossier</span>
        </button>
        <button
          @click="openCompileCompanyModal"
          class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors flex items-center gap-1.5"
        >
          <span>🏢</span>
          <span>Compile Corporate Dossier</span>
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
      <div class="flex items-center gap-3">
        <select
          v-model="filterType"
          @change="fetchReports"
          class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs"
        >
          <option value="">All Report Types</option>
          <option value="vessel_dossier">Vessel Dossier</option>
          <option value="corporate_network_investigation">Corporate Investigation</option>
          <option value="case_summary">Case Summary</option>
          <option value="iuu_infraction_brief">IUU Infraction Brief</option>
          <option value="forced_labour_assessment">Forced Labour Assessment</option>
        </select>

        <select
          v-model="filterClassification"
          @change="fetchReports"
          class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs"
        >
          <option value="">All Classifications</option>
          <option value="restricted">Restricted (Internal NGO)</option>
          <option value="confidential">Confidential (Legal Privileged)</option>
          <option value="public_summary">Public Briefing</option>
        </select>
      </div>

      <div class="text-[11px] font-mono text-slate-400">
        {{ reports.length }} Reports Compiled
      </div>
    </div>

    <!-- Reports Grid -->
    <div v-if="loading" class="p-12 text-center text-xs text-slate-400 font-mono">
      Loading intelligence reports archive...
    </div>

    <div v-else-if="reports.length === 0" class="p-12 text-center text-xs text-slate-400 font-mono rounded-xl bg-slate-900 border border-slate-800">
      No compiled dossiers found. Click "Compile Vessel Dossier" to generate your first evidence-backed report.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="r in reports"
        :key="r.id"
        class="p-5 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between space-y-4"
      >
        <div>
          <div class="flex items-start justify-between gap-2">
            <span class="font-mono text-xs font-bold text-cyan-400">{{ r.report_reference }}</span>
            <span
              class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold border"
              :class="{
                'bg-red-950 text-red-300 border-red-800': r.classification === 'confidential',
                'bg-amber-950 text-amber-300 border-amber-800': r.classification === 'restricted',
                'bg-slate-800 text-slate-300 border-slate-700': r.classification === 'public_summary',
              }"
            >
              {{ r.classification }}
            </span>
          </div>

          <h4 class="text-sm font-bold text-white mt-1.5 line-clamp-2">{{ r.title }}</h4>
          <p class="text-xs text-slate-400 mt-2 line-clamp-3 leading-relaxed">{{ r.executive_summary }}</p>

          <div class="mt-3 flex items-center gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-950 text-slate-300 border border-slate-800">
              Type: {{ r.report_type?.replace(/_/g, ' ') }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-950 text-emerald-400 border border-slate-800">
              {{ r.citations_count || 0 }} Citations
            </span>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
          <div class="text-[10px] text-slate-500">
            {{ formatDate(r.created_at) }}
          </div>

          <div class="flex items-center gap-1.5">
            <button
              @click="exportReport(r.id)"
              class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[11px] transition-colors"
              title="Export structured snapshot"
            >
              📥 Export
            </button>
            <button
              @click="viewReport(r.id)"
              class="px-3 py-1 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-[11px] font-medium transition-colors"
            >
              Read Dossier →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Compile Vessel Dossier Modal -->
    <div
      v-if="showCompileVesselModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <span class="text-base font-bold text-white">Compile Evidence-Linked Vessel Dossier</span>
          <button @click="showCompileVesselModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form @submit.prevent="submitCompileVessel" class="p-5 space-y-4 text-xs font-sans">
          <div>
            <label class="block text-slate-400 mb-1 font-mono">Select Target Vessel *</label>
            <select
              v-model="vesselCompileTargetId"
              required
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-cyan-500"
            >
              <option value="">Select Vessel Master Entity</option>
              <option v-for="v in vesselCandidates" :key="v.id" :value="v.id">
                {{ v.current_name }} (IMO: {{ v.imo_number || 'N/A' }} | MMSI: {{ v.current_mmsi || 'N/A' }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1 font-mono">Security Classification *</label>
            <select
              v-model="vesselClassification"
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-cyan-500"
            >
              <option value="restricted">Restricted (Internal NGO Investigation)</option>
              <option value="confidential">Confidential (Legal Privileged Docket)</option>
              <option value="public_summary">Public Briefing Summary</option>
            </select>
          </div>

          <div class="p-3.5 rounded-lg bg-slate-950 border border-slate-800/80 font-mono text-[11px] text-slate-400 space-y-1">
            <strong class="text-cyan-300 block">Automated Compilation Scope:</strong>
            <p>• Historical names & temporal flag transitions</p>
            <p>• Corporate ownership, managing operators, and UBOs</p>
            <p>• Multilateral RFMO IUU sanctions & maritime infractions</p>
            <p>• Crew forced labour violation reports & ILO 11 indicators</p>
            <p>• Deterministic SHA-256 snapshot checksum & evidence citations</p>
          </div>

          <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
            <button
              type="button"
              @click="showCompileVesselModal = false"
              class="px-3 py-2 rounded-lg bg-slate-800 text-slate-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="compiling"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium shadow-lg shadow-cyan-950/40"
            >
              {{ compiling ? 'Compiling Dossier...' : 'Generate Evidence Dossier' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Compile Corporate Dossier Modal -->
    <div
      v-if="showCompileCompanyModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <span class="text-base font-bold text-white">Compile Corporate Intelligence Dossier</span>
          <button @click="showCompileCompanyModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form @submit.prevent="submitCompileCompany" class="p-5 space-y-4 text-xs font-sans">
          <div>
            <label class="block text-slate-400 mb-1 font-mono">Select Target Corporation *</label>
            <select
              v-model="companyCompileTargetId"
              required
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-cyan-500"
            >
              <option value="">Select Corporate Entity</option>
              <option v-for="c in companyCandidates" :key="c.id" :value="c.id">
                {{ c.company_name }} (Reg: {{ c.registration_number || 'N/A' }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1 font-mono">Security Classification *</label>
            <select
              v-model="companyClassification"
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono focus:outline-none focus:border-cyan-500"
            >
              <option value="restricted">Restricted (Internal NGO Investigation)</option>
              <option value="confidential">Confidential (Legal Privileged Docket)</option>
              <option value="public_summary">Public Briefing Summary</option>
            </select>
          </div>

          <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
            <button
              type="button"
              @click="showCompileCompanyModal = false"
              class="px-3 py-2 rounded-lg bg-slate-800 text-slate-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="compiling"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium shadow-lg shadow-cyan-950/40"
            >
              {{ compiling ? 'Compiling Dossier...' : 'Generate Corporate Dossier' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Dossier Viewer Modal / Drawer -->
    <div
      v-if="showViewerModal && activeReport"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden shadow-2xl">
        <!-- Top Toolbar -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between flex-wrap gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-mono text-xs font-bold text-cyan-400">{{ activeReport.report_reference }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300 border border-slate-700">
                {{ activeReport.classification }}
              </span>
            </div>
            <h3 class="text-base font-bold text-white mt-1">{{ activeReport.title }}</h3>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="copyDossierMarkdown"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-xs border border-slate-700 transition-colors"
            >
              📋 Copy Markdown
            </button>
            <button
              @click="exportReport(activeReport.id)"
              class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-mono text-xs font-medium transition-colors"
            >
              📥 Export JSON
            </button>
            <button @click="showViewerModal = false" class="text-slate-400 hover:text-white text-lg ml-2">&times;</button>
          </div>
        </div>

        <!-- Dossier Body Content -->
        <div class="p-6 flex-1 overflow-y-auto space-y-6 text-xs font-sans">
          <!-- Integrity Hash Banner -->
          <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-4 font-mono text-[11px]">
            <div class="truncate text-slate-400">
              <span class="text-slate-500">IMMUTABLE SHA-256 CHECKSUM: </span>
              <span class="text-cyan-400">{{ activeReport.sha256_checksum }}</span>
            </div>
            <span class="text-emerald-400 shrink-0">VERIFIED SNAPSHOT</span>
          </div>

          <!-- Executive Summary -->
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-1">
            <span class="text-[10px] font-mono text-slate-400 uppercase font-bold block">Executive Summary:</span>
            <p class="text-slate-200 text-xs leading-relaxed">{{ activeReport.executive_summary }}</p>
          </div>

          <!-- Markdown Body -->
          <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 prose prose-invert max-w-none text-xs space-y-3 font-mono whitespace-pre-wrap text-slate-300 leading-relaxed">
{{ activeReport.body_markdown }}
          </div>

          <!-- Citations & Evidence Traceability Table -->
          <div class="space-y-3 font-mono">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-white uppercase">Evidence Citations & Source Provenance ({{ activeReport.citations?.length || 0 }}):</span>
              <span class="text-[10px] text-slate-500">Strict Forensic Traceability</span>
            </div>

            <div v-if="!activeReport.citations || activeReport.citations.length === 0" class="p-4 text-center text-slate-500 rounded bg-slate-950 border border-slate-800">
              No specific source record citations mapped to this brief.
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="c in activeReport.citations"
                :key="c.id"
                class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-[11px] flex items-start justify-between gap-3"
              >
                <div>
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 font-bold text-[10px]">
                      {{ c.citation_key }}
                    </span>
                    <span class="text-slate-300 font-sans">{{ c.statement_supported }}</span>
                  </div>
                  <div class="text-[10px] text-slate-500 mt-1 font-mono">
                    <span v-if="c.source_record_id">Source Record: {{ c.source_record_id }}</span>
                    <span v-if="c.evidence_id"> | Evidence Vault ID: {{ c.evidence_id }}</span>
                  </div>
                </div>
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

const reports = ref<any[]>([]);
const loading = ref(false);
const filterType = ref('');
const filterClassification = ref('');

// Modals
const showCompileVesselModal = ref(false);
const showCompileCompanyModal = ref(false);
const showViewerModal = ref(false);
const compiling = ref(false);

const vesselCandidates = ref<any[]>([]);
const vesselCompileTargetId = ref('');
const vesselClassification = ref('restricted');

const companyCandidates = ref<any[]>([]);
const companyCompileTargetId = ref('');
const companyClassification = ref('restricted');

const activeReport = ref<any>(null);

onMounted(async () => {
  await Promise.all([
    fetchReports(),
    loadVesselCandidates(),
    loadCompanyCandidates(),
  ]);
});

async function fetchReports() {
  loading.value = true;
  try {
    const params: any = {};
    if (filterType.value) params.report_type = filterType.value;
    if (filterClassification.value) params.classification = filterClassification.value;

    const res = await apiClient.get('/reports', { params });
    reports.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load reports', err);
  } finally {
    loading.value = false;
  }
}

async function loadVesselCandidates() {
  try {
    const res = await apiClient.get('/vessels?per_page=50');
    vesselCandidates.value = res.data.data || [];
  } catch (err) {
    console.error(err);
  }
}

async function loadCompanyCandidates() {
  try {
    const res = await apiClient.get('/companies?per_page=50');
    companyCandidates.value = res.data.data || [];
  } catch (err) {
    console.error(err);
  }
}

function openCompileVesselModal() {
  vesselCompileTargetId.value = '';
  showCompileVesselModal.value = true;
}

function openCompileCompanyModal() {
  companyCompileTargetId.value = '';
  showCompileCompanyModal.value = true;
}

async function submitCompileVessel() {
  if (!vesselCompileTargetId.value) return;
  compiling.value = true;
  try {
    const res = await apiClient.post(`/reports/vessels/${vesselCompileTargetId.value}/dossier`, {
      classification: vesselClassification.value,
    });
    showCompileVesselModal.value = false;
    await fetchReports();
    viewReport(res.data.data.id);
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to compile vessel dossier.');
  } finally {
    compiling.value = false;
  }
}

async function submitCompileCompany() {
  if (!companyCompileTargetId.value) return;
  compiling.value = true;
  try {
    const res = await apiClient.post(`/reports/companies/${companyCompileTargetId.value}/dossier`, {
      classification: companyClassification.value,
    });
    showCompileCompanyModal.value = false;
    await fetchReports();
    viewReport(res.data.data.id);
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to compile corporate dossier.');
  } finally {
    compiling.value = false;
  }
}

async function viewReport(reportId: string) {
  try {
    const res = await apiClient.get(`/reports/${reportId}`);
    activeReport.value = res.data.data;
    showViewerModal.value = true;
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to view report.');
  }
}

async function exportReport(reportId: string) {
  try {
    const res = await apiClient.get(`/reports/${reportId}/export`);
    const blob = new Blob([JSON.stringify(res.data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${res.data.report_reference}_dossier_snapshot.json`;
    a.click();
  } catch (err: any) {
    alert('Export failed.');
  }
}

function copyDossierMarkdown() {
  if (!activeReport.value?.body_markdown) return;
  navigator.clipboard.writeText(activeReport.value.body_markdown);
  alert('Dossier Markdown copied to clipboard!');
}

function formatDate(dateStr?: string) {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
