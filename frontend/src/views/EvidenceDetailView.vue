<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between text-xs">
      <router-link
        to="/evidence"
        class="inline-flex items-center gap-1.5 text-slate-400 hover:text-cyan-400 transition-colors"
      >
        <span>←</span>
        <span>Back to Evidence Vault</span>
      </router-link>
      <div class="text-slate-500 font-mono">
        EVIDENCE UUID: {{ evidenceId }}
      </div>
    </div>

    <div v-if="loading" class="py-16 text-center text-xs text-slate-400">
      Inspecting vaulted evidence record...
    </div>

    <div v-else-if="!evidence" class="py-16 text-center text-xs text-red-400">
      Evidence record not found or access restricted by classification policy.
    </div>

    <div v-else class="space-y-6">
      <!-- Evidence Dossier Header -->
      <div class="p-6 rounded-xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xl">{{ getEvidenceTypeIcon(evidence.evidence_type) }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ evidence.evidence_type }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="statusBadgeClass(evidence.verification_status)">
                {{ evidence.verification_status.replace('_', ' ') }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="classificationBadgeClass(evidence.classification)">
                {{ evidence.classification.replace('_', ' ') }}
              </span>
              <span v-if="evidence.is_tamper_evident" class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
                ✓ VAULT INTEGRITY VERIFIED
              </span>
            </div>
            <h2 class="text-lg font-bold text-white pt-1">{{ evidence.title }}</h2>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3">
            <button
              v-if="authStore.canEdit"
              @click="showVerificationModal = true"
              class="px-3.5 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-medium transition-colors shadow-lg shadow-purple-950/40 flex items-center gap-1.5"
            >
              <span>⚖️</span>
              <span>Conduct Analyst Review</span>
            </button>

            <button
              @click="downloadEvidence"
              class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-mono transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
            >
              <span>📥</span>
              <span>Download File</span>
            </button>
          </div>
        </div>

        <!-- Cryptographic SHA-256 Card -->
        <div class="p-3.5 rounded-lg bg-slate-950 border border-slate-800 space-y-2">
          <div class="flex items-center justify-between text-xs font-mono">
            <span class="text-emerald-400 font-semibold flex items-center gap-1.5">
              <span>🔒 Cryptographic Hash (SHA-256):</span>
            </span>
            <span class="text-slate-500 text-[11px]">Vault Status: SECURE & VERIFIED</span>
          </div>
          <div class="font-mono text-xs text-cyan-300 bg-slate-900 p-2.5 rounded border border-slate-800 select-all break-all">
            {{ evidence.sha256_hash }}
          </div>
        </div>

        <!-- Metadata Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
          <div>
            <span class="text-slate-500 block">Original Filename</span>
            <span class="text-slate-200 truncate block">{{ evidence.file_name }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">File Size & MIME</span>
            <span class="text-slate-300">{{ formatFileSize(evidence.file_size_bytes) }} • {{ evidence.mime_type }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Collection Date</span>
            <span class="text-slate-300">{{ formatDate(evidence.collection_date) }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Uploaded By</span>
            <span class="text-slate-300">{{ evidence.uploaded_by?.name || 'Analyst' }}</span>
          </div>
        </div>
      </div>

      <!-- Provenance & Source Information -->
      <div v-if="evidence.data_source" class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-slate-400 font-mono">Data Source Provenance:</span>
            <span class="font-bold text-white">{{ evidence.data_source.name }}</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
              {{ evidence.data_source.category }}
            </span>
          </div>
          <p class="text-slate-400 text-[11px]">
            Publisher: <strong class="text-slate-300">{{ evidence.data_source.publisher || 'Official Body' }}</strong>
            <span v-if="evidence.data_source.publication_date"> • Published: {{ formatDate(evidence.data_source.publication_date) }}</span>
          </p>
        </div>

        <div class="flex items-center gap-4 font-mono text-[11px]">
          <div>
            <span class="text-slate-500 block">Reliability Score</span>
            <span class="text-emerald-400 font-bold">{{ (evidence.data_source.reliability_score * 100).toFixed(0) }}%</span>
          </div>
          <a
            v-if="evidence.data_source.source_url"
            :href="evidence.data_source.source_url"
            target="_blank"
            class="px-2.5 py-1.5 rounded bg-slate-800 hover:bg-slate-700 text-cyan-400 transition-colors"
          >
            Original Source ↗
          </a>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-slate-800 flex items-center gap-2 overflow-x-auto text-xs font-medium">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
          :class="activeTab === tab.id ? 'border-cyan-500 text-cyan-400 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
        >
          <span>{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
          <span v-if="tab.count !== undefined" class="px-1.5 py-0.2 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Tab 1: Chain of Custody Ledger (Forensic Audit Trail) -->
      <div v-if="activeTab === 'custody'" class="space-y-4">
        <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
          <div class="flex items-center gap-2">
            <span class="text-base">📜</span>
            <span class="text-slate-300">
              <strong class="text-white">Forensic Chain of Custody:</strong>
              Immutable append-only chronological log. Each access, download, verification, and modification is immutably recorded with actor identity and IP address.
            </span>
          </div>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
            TAMPER-EVIDENT LEDGER
          </span>
        </div>

        <div v-if="!custodyLogs || custodyLogs.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No custody events logged yet.
        </div>

        <div v-else class="relative border-l-2 border-slate-800 ml-4 pl-6 space-y-5">
          <div
            v-for="log in custodyLogs"
            :key="log.id"
            class="relative space-y-1.5"
          >
            <!-- Dot -->
            <div
              class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full border-2 border-slate-900 ring-2"
              :class="custodyActionDotClass(log.action)"
            ></div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase" :class="custodyActionBadgeClass(log.action)">
                {{ log.action }}
              </span>
              <span class="text-xs font-mono text-cyan-400">
                {{ formatDateTime(log.created_at) }}
              </span>
              <span class="text-[11px] text-slate-400 font-mono">
                Actor: <strong class="text-slate-200">{{ log.actor?.name || 'System' }}</strong>
                ({{ log.actor_role || 'Agent' }})
              </span>
              <span v-if="log.ip_address" class="text-[10px] text-slate-500 font-mono">
                IP: {{ log.ip_address }}
              </span>
            </div>

            <div v-if="log.action_description" class="text-xs text-slate-300">
              {{ log.action_description }}
            </div>

            <div v-if="log.hash_at_action" class="text-[10px] font-mono text-slate-500 bg-slate-950 p-1.5 rounded border border-slate-800/80 max-w-2xl truncate">
              Recorded Hash: {{ log.hash_at_action }}
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Analyst Verification Reviews -->
      <div v-if="activeTab === 'verification'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Formal analyst review history. Claims and unverified allegations are never automatically treated as verified facts.
          </p>
          <button
            v-if="authStore.canEdit"
            @click="showVerificationModal = true"
            class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
          >
            <span>⚖️</span>
            <span>Submit Verification Decision</span>
          </button>
        </div>

        <div v-if="!evidence.verification_reviews || evidence.verification_reviews.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No formal verification reviews conducted yet. Current status: <strong class="text-amber-400 uppercase">{{ evidence.verification_status }}</strong>.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="rev in evidence.verification_reviews"
            :key="rev.id"
            class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3"
          >
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded text-[10px] font-mono uppercase font-bold" :class="statusBadgeClass(rev.decision)">
                  DECISION: {{ rev.decision.replace('_', ' ') }}
                </span>
                <span class="text-xs text-slate-400 font-mono">
                  From <span class="text-slate-300">{{ rev.previous_status }}</span> → <strong class="text-cyan-300">{{ rev.new_status }}</strong>
                </span>
              </div>
              <div class="text-[11px] text-slate-400 font-mono">
                Reviewer: <strong class="text-slate-200">{{ rev.reviewer?.name }}</strong> • {{ formatDateTime(rev.review_date || rev.created_at) }}
              </div>
            </div>

            <!-- Mandatory Written Conclusion -->
            <div class="space-y-1">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
                Substantive Written Conclusion & Rationale:
              </span>
              <div class="text-xs text-slate-200 whitespace-pre-line leading-relaxed bg-slate-950 p-3 rounded-lg border border-slate-800 font-sans">
                {{ rev.conclusion }}
              </div>
            </div>

            <div v-if="rev.supporting_evidence_ids && rev.supporting_evidence_ids.length" class="text-[11px] font-mono text-slate-400">
              Supporting references: {{ rev.supporting_evidence_ids.join(', ') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 3: Entity Relationships & Case Links -->
      <div v-if="activeTab === 'links'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Entities (vessels, companies, cases) supported by or linked to this evidence.
          </p>
          <button
            v-if="authStore.canEdit"
            @click="openAddRelationshipModal"
            class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
          >
            <span>➕</span>
            <span>Link Entity Target</span>
          </button>
        </div>

        <div v-if="!evidence.relationships || evidence.relationships.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No entity relationships mapped for this evidence item.
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="rel in evidence.relationships"
            :key="rel.id"
            class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span>{{ getSubjectIcon(rel.target_type) }}</span>
                <span class="text-xs font-mono uppercase text-slate-400">{{ rel.target_type }}</span>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ rel.relation_type }}
              </span>
            </div>

            <div class="text-xs font-bold text-white">
              Target ID: {{ rel.target_id }}
            </div>

            <div v-if="rel.description" class="text-xs text-slate-300 italic">
              "{{ rel.description }}"
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-[11px]">
              <span class="text-slate-500">{{ formatDate(rel.created_at) }}</span>
              <button
                v-if="authStore.canEdit"
                @click="removeRelationship(rel.id)"
                class="text-red-400 hover:text-red-300 text-xs"
              >
                Unlink ✕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Verification Decision Modal -->
    <div
      v-if="showVerificationModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
        <div class="border-b border-slate-800 pb-3">
          <h4 class="text-sm font-bold text-white flex items-center gap-2">
            <span>⚖️</span>
            <span>Formal Verification Review</span>
          </h4>
          <p class="text-[11px] text-slate-400">
            A substantive written conclusion (minimum 15 characters) is mandatory to prevent unjustified assertions.
          </p>
        </div>

        <form @submit.prevent="submitVerificationReview" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Analyst Decision *</label>
            <select
              v-model="verificationForm.decision"
              required
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white font-medium focus:outline-none focus:border-cyan-500"
            >
              <option value="verified">Verified (Sufficient evidence, confirmed as authentic)</option>
              <option value="under_review">Under Review (Requires further scrutiny)</option>
              <option value="needs_additional_evidence">Needs Additional Evidence (Incomplete provenance)</option>
              <option value="conflicting">Conflicting (Directly contradicts verified records)</option>
              <option value="rejected">Rejected (Fabricated, misidentified, or disproven)</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">
              Substantive Written Conclusion *
              <span class="text-slate-500">(Min. 15 characters)</span>
            </label>
            <textarea
              v-model="verificationForm.conclusion"
              required
              minlength="15"
              rows="4"
              placeholder="Detail the analytical reasoning, corroborating sources, cross-references with official registries, or specific contradictions..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
            <span class="text-[10px] text-slate-500 font-mono">
              Current length: {{ verificationForm.conclusion.length }} / 15 chars min
            </span>
          </div>

          <div v-if="verificationError" class="p-2.5 rounded bg-red-950/50 border border-red-800 text-red-300 text-xs">
            {{ verificationError }}
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showVerificationModal = false"
              class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submittingReview || verificationForm.conclusion.length < 15"
              class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-medium transition-colors disabled:opacity-50"
            >
              {{ submittingReview ? 'Recording Decision...' : 'Commit Review Decision' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Link Relationship Modal -->
    <div
      v-if="showRelationshipModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Link Evidence to Entity</h4>
        <form @submit.prevent="submitRelationship" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Target Entity Type</label>
            <select v-model="relationshipForm.target_type" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="vessel">Vessel</option>
              <option value="company">Company</option>
              <option value="investigation_case">Investigation Case</option>
              <option value="location">Maritime Location</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Target UUID *</label>
            <input v-model="relationshipForm.target_id" required type="text" placeholder="UUID of target entity..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Relationship Type</label>
            <input v-model="relationshipForm.relation_type" required type="text" placeholder="e.g. primary_evidence, corroboration, photo_of_vessel" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Context Notes</label>
            <textarea v-model="relationshipForm.description" rows="2" placeholder="Describe relationship..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showRelationshipModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white">Link Entity</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const authStore = useAuthStore();
const evidenceId = computed(() => route.params.id as string);

const evidence = ref<any>(null);
const custodyLogs = ref<any[]>([]);
const loading = ref(false);
const activeTab = ref('custody');

const tabs = computed(() => [
  { id: 'custody', label: 'Chain of Custody', icon: '📜', count: custodyLogs.value?.length || 0 },
  { id: 'verification', label: 'Analyst Reviews', icon: '⚖️', count: evidence.value?.verification_reviews?.length || 0 },
  { id: 'links', label: 'Linked Targets', icon: '🔗', count: evidence.value?.relationships?.length || 0 },
]);

// Modals
const showVerificationModal = ref(false);
const submittingReview = ref(false);
const verificationError = ref('');

const verificationForm = ref({
  decision: 'verified',
  conclusion: '',
  supporting_evidence_ids: [] as string[],
});

const showRelationshipModal = ref(false);
const relationshipForm = ref({
  target_type: 'vessel',
  target_id: '',
  relation_type: 'primary_evidence',
  description: '',
});

async function fetchEvidenceDetail() {
  loading.value = true;
  try {
    const [detailRes, custodyRes] = await Promise.all([
      apiClient.get(`/evidences/${evidenceId.value}`),
      apiClient.get(`/custody/evidence/${evidenceId.value}`),
    ]);
    evidence.value = detailRes.data.data;
    custodyLogs.value = custodyRes.data.data || [];
  } catch (err) {
    console.error('Failed to load evidence detail', err);
  } finally {
    loading.value = false;
  }
}

async function downloadEvidence() {
  try {
    const res = await apiClient.get(`/evidences/${evidenceId.value}/download`, {
      responseType: 'blob',
    });
    const hash = res.headers['x-evidence-sha256'];
    const blob = new Blob([res.data]);
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = evidence.value?.file_name || `evidence-${evidenceId.value}`;
    link.click();
    URL.revokeObjectURL(link.href);
    // Refresh custody logs as download event is appended!
    const custodyRes = await apiClient.get(`/custody/evidence/${evidenceId.value}`);
    custodyLogs.value = custodyRes.data.data || [];
  } catch (err) {
    alert('Failed to download evidence.');
  }
}

async function submitVerificationReview() {
  submittingReview.value = true;
  verificationError.value = '';
  try {
    await apiClient.post('/verification/review', {
      verifiable_type: 'evidence',
      verifiable_id: evidenceId.value,
      decision: verificationForm.value.decision,
      conclusion: verificationForm.value.conclusion,
      supporting_evidence_ids: verificationForm.value.supporting_evidence_ids,
    });
    showVerificationModal.value = false;
    verificationForm.value.conclusion = '';
    await fetchEvidenceDetail();
  } catch (err: any) {
    verificationError.value = err.response?.data?.message || 'Failed to submit verification review.';
  } finally {
    submittingReview.value = false;
  }
}

function openAddRelationshipModal() {
  relationshipForm.value = {
    target_type: 'vessel',
    target_id: '',
    relation_type: 'primary_evidence',
    description: '',
  };
  showRelationshipModal.value = true;
}

async function submitRelationship() {
  try {
    await apiClient.post(`/evidences/${evidenceId.value}/relationships`, relationshipForm.value);
    showRelationshipModal.value = false;
    await fetchEvidenceDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to link relationship.');
  }
}

async function removeRelationship(relId: string) {
  if (!confirm('Remove relationship link?')) return;
  try {
    await apiClient.delete(`/evidences/${evidenceId.value}/relationships/${relId}`);
    await fetchEvidenceDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to remove relationship.');
  }
}

function getEvidenceTypeIcon(type: string) {
  switch (type) {
    case 'document': return '📄';
    case 'image': return '🖼️';
    case 'pdf': return '📕';
    case 'video': return '🎥';
    case 'audio': return '🎙️';
    case 'satellite_imagery': return '🛰️';
    default: return '📁';
  }
}

function getSubjectIcon(type: string) {
  switch (type) {
    case 'vessel': return '🚢';
    case 'company': return '🏢';
    case 'investigation_case': return '📂';
    default: return '📌';
  }
}

function statusBadgeClass(status: string) {
  switch (status) {
    case 'verified': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'under_review': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'pending': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'needs_additional_evidence': return 'bg-yellow-950 text-yellow-300 border border-yellow-800';
    case 'conflicting': return 'bg-rose-950 text-rose-300 border border-rose-800';
    case 'rejected': return 'bg-red-950 text-red-300 border border-red-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function classificationBadgeClass(cls: string) {
  switch (cls) {
    case 'court_admissible': return 'bg-indigo-950 text-indigo-300 border border-indigo-800';
    case 'confidential': return 'bg-red-950 text-red-300 border border-red-800';
    case 'restricted': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'internal': return 'bg-slate-800 text-slate-300 border border-slate-700';
    case 'public': return 'bg-teal-950 text-teal-300 border border-teal-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function custodyActionBadgeClass(action: string) {
  switch (action) {
    case 'UPLOADED': return 'bg-blue-950 text-blue-300 border border-blue-800';
    case 'HASH_GENERATED': return 'bg-cyan-950 text-cyan-300 border border-cyan-800';
    case 'DOWNLOADED': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'ACCESSED': return 'bg-slate-800 text-slate-300';
    case 'VERIFIED': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'REDACTED': return 'bg-amber-950 text-amber-300 border border-amber-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function custodyActionDotClass(action: string) {
  switch (action) {
    case 'UPLOADED': return 'bg-blue-500 ring-blue-500/30';
    case 'HASH_GENERATED': return 'bg-cyan-500 ring-cyan-500/30';
    case 'DOWNLOADED': return 'bg-emerald-500 ring-emerald-500/30';
    case 'VERIFIED': return 'bg-purple-500 ring-purple-500/30';
    case 'REDACTED': return 'bg-amber-500 ring-amber-500/30';
    default: return 'bg-slate-500 ring-slate-500/30';
  }
}

function formatFileSize(bytes: number) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
  fetchEvidenceDetail();
});
</script>
