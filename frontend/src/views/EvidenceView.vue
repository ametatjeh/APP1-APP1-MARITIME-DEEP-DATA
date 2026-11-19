<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Private Evidence Vault & Custody</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            SHA-256 VAULT
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Tamper-evident private object storage, immutable forensic chain of custody, and cryptographic integrity verification.
        </p>
      </div>

      <div v-if="authStore.canEdit" class="flex items-center gap-2">
        <button
          @click="openUploadModal"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>⬆️</span>
          <span>Upload & Ingest Evidence</span>
        </button>
      </div>
    </div>

    <!-- Security Warning & Protocol Banner -->
    <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between text-xs font-mono">
      <div class="flex items-center gap-3">
        <span class="text-emerald-400 text-base">🛡️</span>
        <div class="text-slate-300">
          <strong class="text-white">Strict Private Object Storage:</strong>
          Evidence is never exposed via public symlinks or static URLs. Access is logged in the immutable Chain of Custody ledger.
        </div>
      </div>
      <div class="hidden md:flex items-center gap-2 text-slate-500 text-[11px]">
        <span>HASH ALGORITHM: SHA-256</span>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
      <div>
        <label class="block text-slate-400 mb-1">Search Evidence</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Title, description, filename, hash..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
        />
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Evidence Type</label>
        <select
          v-model="selectedType"
          @change="fetchEvidences"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Types</option>
          <option value="document">Document / Record</option>
          <option value="image">Photographic Image</option>
          <option value="pdf">PDF Dossier / Report</option>
          <option value="satellite_imagery">Satellite Imagery</option>
          <option value="video">Video Footage</option>
          <option value="audio">Audio Intercept</option>
          <option value="dataset">Dataset / Raw CSV</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Classification</label>
        <select
          v-model="selectedClassification"
          @change="fetchEvidences"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Classifications</option>
          <option value="public">Public Domain</option>
          <option value="internal">Internal Team Only</option>
          <option value="restricted">Restricted Access</option>
          <option value="confidential">Confidential (Need to Know)</option>
          <option value="court_admissible">Court-Admissible Evidence</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Verification Status</label>
        <select
          v-model="selectedStatus"
          @change="fetchEvidences"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
        >
          <option value="">All Statuses</option>
          <option value="verified">Verified (Analyst Confirmed)</option>
          <option value="under_review">Under Review</option>
          <option value="pending">Pending Verification</option>
          <option value="needs_additional_evidence">Needs Additional Evidence</option>
          <option value="conflicting">Conflicting Intelligence</option>
          <option value="rejected">Rejected / Disproven</option>
        </select>
      </div>
    </div>

    <!-- Evidence Grid / List -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading private vault evidence...</div>
      <div v-else-if="evidences.length === 0" class="py-12 text-center text-xs text-slate-400">
        No evidence items found matching criteria.
      </div>
      <div v-else class="divide-y divide-slate-800">
        <div
          v-for="ev in evidences"
          :key="ev.id"
          class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-2 flex-1 cursor-pointer" @click="goToDetail(ev.id)">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-lg">{{ getEvidenceTypeIcon(ev.evidence_type) }}</span>
              <span class="font-bold text-sm text-white hover:text-cyan-300 transition-colors">
                {{ ev.title }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ ev.evidence_type }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="statusBadgeClass(ev.verification_status)">
                {{ ev.verification_status.replace('_', ' ') }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="classificationBadgeClass(ev.classification)">
                {{ ev.classification.replace('_', ' ') }}
              </span>
              <span v-if="ev.is_tamper_evident" class="px-2 py-0.5 rounded text-[9px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
                ✓ VAULT INTEGRITY
              </span>
            </div>

            <p v-if="ev.description" class="text-xs text-slate-400 line-clamp-2 max-w-4xl">
              {{ ev.description }}
            </p>

            <!-- Cryptographic Fingerprint Bar -->
            <div class="flex flex-wrap items-center gap-3 text-[11px] font-mono text-slate-400 bg-slate-950/70 p-2 rounded-lg border border-slate-800/80">
              <span class="text-cyan-400 font-semibold">SHA-256:</span>
              <span class="text-slate-200 truncate max-w-md select-all">{{ ev.sha256_hash }}</span>
              <span class="text-slate-500">|</span>
              <span>Size: <strong class="text-slate-300">{{ formatFileSize(ev.file_size_bytes) }}</strong></span>
              <span class="text-slate-500">|</span>
              <span>File: <span class="text-slate-300">{{ ev.file_name }}</span></span>
              <span class="text-slate-500">|</span>
              <span>Collected: <span class="text-slate-300">{{ formatDate(ev.collection_date) }}</span></span>
            </div>

            <!-- Provenance & Links info -->
            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono">
              <span>Source: <strong class="text-slate-300">{{ ev.data_source?.name || 'Manual Ingestion' }}</strong></span>
              <span>Uploaded By: <span class="text-slate-400">{{ ev.uploaded_by?.name || 'Analyst' }}</span></span>
              <span>Custody Events: <strong class="text-cyan-400">{{ ev.custody_logs_count || 0 }}</strong></span>
              <span>Linked Entities: <strong class="text-slate-300">{{ ev.relationships_count || 0 }}</strong></span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start md:self-center">
            <button
              @click="downloadEvidenceFile(ev.id, ev.file_name)"
              class="px-3 py-1.5 rounded-lg bg-cyan-950 hover:bg-cyan-900 text-cyan-300 text-xs font-mono transition-colors border border-cyan-800 flex items-center gap-1.5"
              title="Secure stream download with SHA-256 validation"
            >
              <span>📥</span>
              <span>Download</span>
            </button>
            <button
              @click="goToDetail(ev.id)"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700"
            >
              Inspect
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Evidence Modal -->
    <div
      v-if="showUploadModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-2xl w-full p-6 space-y-4 shadow-2xl my-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h4 class="text-sm font-bold text-white">Vault Ingestion: Upload Evidence</h4>
            <p class="text-[11px] text-slate-400">File is stored in encrypted private storage; SHA-256 is automatically computed.</p>
          </div>
          <button @click="showUploadModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <form @submit.prevent="submitUpload" class="space-y-3 text-xs">
          <!-- File Picker -->
          <div class="border-2 border-dashed border-slate-700 hover:border-cyan-500/80 rounded-xl p-4 text-center bg-slate-950 cursor-pointer">
            <input
              type="file"
              ref="fileInput"
              @change="handleFileChange"
              required
              class="w-full text-slate-400 text-xs file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-cyan-600 file:text-white hover:file:bg-cyan-500"
            />
            <p class="text-[10px] text-slate-500 mt-2">
              Allowed formats: PDF, PNG, JPG, WEBP, TIFF, DOC, DOCX, XLS, XLSX, CSV, JSON, ZIP (Max 100MB).
            </p>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Evidence Title *</label>
            <input
              v-model="uploadForm.title"
              required
              type="text"
              placeholder="e.g. Official Maritime Enforcement Boarding Report - KM Jaya 88"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Evidence Type *</label>
              <select
                v-model="uploadForm.evidence_type"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="document">Document / Official Record</option>
                <option value="pdf">PDF Report</option>
                <option value="image">Photograph / Image</option>
                <option value="satellite_imagery">Satellite Imagery</option>
                <option value="video">Video Recording</option>
                <option value="audio">Audio Recording</option>
                <option value="dataset">Dataset / Raw CSV</option>
                <option value="other">Other Evidence</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Security Classification *</label>
              <select
                v-model="uploadForm.classification"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="internal">Internal Team Only</option>
                <option value="restricted">Restricted (Controlled Distribution)</option>
                <option value="confidential">Confidential (Need to Know)</option>
                <option value="court_admissible">Court-Admissible Dossier</option>
                <option value="public">Public Domain</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Data Source Registry *</label>
              <select
                v-model="uploadForm.data_source_id"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="" disabled>Select provenance source...</option>
                <option v-for="src in dataSources" :key="src.id" :value="src.id">
                  {{ src.name }} ({{ src.category }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Author / Investigator Origin</label>
              <input
                v-model="uploadForm.author_creator"
                type="text"
                placeholder="e.g. Directorate General of Marine Resources (PSDKP)"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Collection Date</label>
              <input
                v-model="uploadForm.collection_date"
                type="date"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Publication Date</label>
              <input
                v-model="uploadForm.publication_date"
                type="date"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Description / Context</label>
            <textarea
              v-model="uploadForm.description"
              rows="3"
              placeholder="Context of evidence collection, circumstances, and relevance to IUU or illicit operations..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div v-if="uploadError" class="p-2.5 rounded bg-red-950/50 border border-red-800 text-red-300 text-xs">
            {{ uploadError }}
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showUploadModal = false"
              class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="uploading"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium transition-colors disabled:opacity-50"
            >
              {{ uploading ? 'Ingesting & Computing Hash...' : 'Ingest to Vault' }}
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

const evidences = ref<any[]>([]);
const dataSources = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedType = ref('');
const selectedClassification = ref('');
const selectedStatus = ref('');

const showUploadModal = ref(false);
const uploading = ref(false);
const uploadError = ref('');
const selectedFile = ref<File | null>(null);

const uploadForm = ref({
  title: '',
  evidence_type: 'document',
  classification: 'internal',
  data_source_id: '',
  author_creator: '',
  collection_date: new Date().toISOString().slice(0, 10),
  publication_date: '',
  description: '',
});

let searchTimeout: any = null;

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchEvidences();
  }, 300);
}

async function fetchEvidences() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append('search', searchQuery.value);
    if (selectedType.value) params.append('type', selectedType.value);
    if (selectedClassification.value) params.append('classification', selectedClassification.value);
    if (selectedStatus.value) params.append('verification_status', selectedStatus.value);

    const res = await apiClient.get(`/evidences?${params.toString()}`);
    evidences.value = res.data.data;
  } catch (err) {
    console.error('Failed to load evidences', err);
  } finally {
    loading.value = false;
  }
}

async function openUploadModal() {
  uploadError.value = '';
  selectedFile.value = null;
  try {
    const res = await apiClient.get('/sources?per_page=50');
    dataSources.value = res.data.data || [];
    if (dataSources.value.length > 0 && !uploadForm.value.data_source_id) {
      uploadForm.value.data_source_id = dataSources.value[0].id;
    }
  } catch (err) {
    console.error('Failed to load data sources', err);
  }
  showUploadModal.value = true;
}

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0];
    if (!uploadForm.value.title) {
      uploadForm.value.title = selectedFile.value.name.replace(/\.[^/.]+$/, '');
    }
  }
}

async function submitUpload() {
  if (!selectedFile.value) {
    uploadError.value = 'Please select a file to upload.';
    return;
  }

  uploading.value = true;
  uploadError.value = '';

  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('title', uploadForm.value.title);
    formData.append('evidence_type', uploadForm.value.evidence_type);
    formData.append('classification', uploadForm.value.classification);
    formData.append('data_source_id', uploadForm.value.data_source_id);
    if (uploadForm.value.author_creator) formData.append('author_creator', uploadForm.value.author_creator);
    if (uploadForm.value.collection_date) formData.append('collection_date', uploadForm.value.collection_date);
    if (uploadForm.value.publication_date) formData.append('publication_date', uploadForm.value.publication_date);
    if (uploadForm.value.description) formData.append('description', uploadForm.value.description);

    const res = await apiClient.post('/evidences', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    showUploadModal.value = false;
    await fetchEvidences();
    if (res.data?.data?.id) {
      router.push(`/evidence/${res.data.data.id}`);
    }
  } catch (err: any) {
    uploadError.value = err.response?.data?.message || 'Failed to upload evidence.';
  } finally {
    uploading.value = false;
  }
}

async function downloadEvidenceFile(evidenceId: string, filename: string) {
  try {
    const res = await apiClient.get(`/evidences/${evidenceId}/download`, {
      responseType: 'blob',
    });
    const hash = res.headers['x-evidence-sha256'];
    const blob = new Blob([res.data]);
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || `evidence-${evidenceId}`;
    link.click();
    URL.revokeObjectURL(link.href);
    if (hash) {
      console.log(`Downloaded with verified SHA-256: ${hash}`);
    }
  } catch (err) {
    alert('Failed to download evidence from private vault.');
  }
}

function goToDetail(id: string) {
  router.push(`/evidence/${id}`);
}

function getEvidenceTypeIcon(type: string) {
  switch (type) {
    case 'document': return '📄';
    case 'image': return '🖼️';
    case 'pdf': return '📕';
    case 'video': return '🎥';
    case 'audio': return '🎙️';
    case 'satellite_imagery': return '🛰️';
    case 'dataset': return '📊';
    default: return '📁';
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

onMounted(() => {
  fetchEvidences();
});
</script>
