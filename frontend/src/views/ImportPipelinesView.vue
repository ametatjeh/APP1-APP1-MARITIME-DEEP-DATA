<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Data Ingestion Pipelines & Quarantine Engine</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            PHASE 4 ENGINE
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Automated batch ingestion pipelines with modulo-10 IMO checksum verification, ITU MID mapping, and isolated quarantine staging.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <router-link
          to="/validation-inspector"
          class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700 flex items-center gap-1.5"
        >
          <span>🔬</span>
          <span>Syntax & Pre-flight Inspector</span>
        </router-link>
        <button
          v-if="authStore.canEdit"
          @click="openRunModal"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>🚀</span>
          <span>Run Ingestion Pipeline</span>
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[10px] font-mono text-slate-400 uppercase">Batches Executed</div>
        <div class="text-2xl font-bold text-white mt-1">{{ totalBatches }}</div>
        <div class="text-[10px] text-slate-500 mt-1 font-mono">Pipelines tracked</div>
      </div>
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[10px] font-mono text-slate-400 uppercase">Processed Records</div>
        <div class="text-2xl font-bold text-cyan-400 mt-1">{{ totalProcessed.toLocaleString() }}</div>
        <div class="text-[10px] text-slate-500 mt-1 font-mono">Raw input rows</div>
      </div>
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[10px] font-mono text-slate-400 uppercase">Master Ingested</div>
        <div class="text-2xl font-bold text-emerald-400 mt-1">{{ totalValid.toLocaleString() }}</div>
        <div class="text-[10px] text-emerald-500/80 mt-1 font-mono">{{ validRate }}% success rate</div>
      </div>
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[10px] font-mono text-slate-400 uppercase">Quarantined Staged</div>
        <div class="text-2xl font-bold text-amber-400 mt-1">{{ totalQuarantined.toLocaleString() }}</div>
        <div class="text-[10px] text-amber-500/80 mt-1 font-mono">Isolated for triage</div>
      </div>
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[10px] font-mono text-slate-400 uppercase">Checksum Rule</div>
        <div class="text-xs font-bold text-cyan-300 mt-1 font-mono">IMO Modulo-10</div>
        <div class="text-[10px] text-slate-400 mt-1">9-digit ITU MID Enforced</div>
      </div>
    </div>

    <!-- Batches Table -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
      <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-white">Ingestion Batches History</span>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300">
            {{ batches.length }} batches
          </span>
        </div>

        <div class="flex items-center gap-2">
          <select
            v-model="filterStatus"
            @change="fetchBatches"
            class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-cyan-500 font-mono"
          >
            <option value="">All Statuses</option>
            <option value="completed">Completed</option>
            <option value="partially_imported">Partially Imported</option>
            <option value="failed">Failed</option>
            <option value="importing">Importing</option>
          </select>

          <select
            v-model="filterTarget"
            @change="fetchBatches"
            class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-cyan-500 font-mono"
          >
            <option value="">All Targets</option>
            <option value="vessel">Vessel Master</option>
            <option value="company">Corporate Master</option>
            <option value="person">Persons / Crew</option>
            <option value="iuu_listing">IUU Sanctions</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-12 text-center text-xs text-slate-400 font-mono">
        <div class="inline-block animate-spin w-5 h-5 border-2 border-cyan-500 border-t-transparent rounded-full mb-2"></div>
        <div>Loading ingestion pipeline batches...</div>
      </div>

      <!-- Empty State -->
      <div v-else-if="batches.length === 0" class="p-12 text-center text-xs text-slate-400 font-mono">
        No ingestion batches recorded yet. Click "Run Ingestion Pipeline" to process your first dataset.
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-950/60 text-[10px] font-mono text-slate-400 uppercase">
              <th class="py-3 px-4">Batch Reference</th>
              <th class="py-3 px-4">Source & Dataset</th>
              <th class="py-3 px-4">Target Entity</th>
              <th class="py-3 px-4">Format / File</th>
              <th class="py-3 px-4">Valid / Quarantined</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Execution Time</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-mono">
            <tr v-for="b in batches" :key="b.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4 font-bold text-cyan-400">
                {{ b.batch_reference }}
                <div class="text-[9px] text-slate-500 font-normal truncate max-w-[130px]" :title="b.file_sha256">
                  SHA: {{ b.file_sha256?.substring(0, 12) }}...
                </div>
              </td>
              <td class="py-3 px-4">
                <div class="font-sans font-semibold text-slate-200">{{ b.data_source?.name || 'N/A' }}</div>
                <div class="text-[10px] text-slate-400">
                  {{ b.dataset_version?.version_tag ? `Version ${b.dataset_version.version_tag}` : 'Ad-hoc Ingestion' }}
                </div>
              </td>
              <td class="py-3 px-4 uppercase text-slate-300">
                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700">
                  {{ b.target_entity_type }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-400">
                <span class="text-white">{{ b.file_format?.toUpperCase() }}</span>
                <span class="block text-[10px] text-slate-500 truncate max-w-[140px]" :title="b.source_filename">
                  {{ b.source_filename }}
                </span>
              </td>
              <td class="py-3 px-4">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-emerald-400 font-bold">{{ b.valid_rows_count }}</span>
                  <span class="text-slate-500">/</span>
                  <span class="text-amber-400 font-bold">{{ b.quarantined_rows_count }}</span>
                  <span class="text-[10px] text-slate-500">({{ b.total_rows }} total)</span>
                </div>
                <!-- Progress bar -->
                <div class="w-24 h-1.5 rounded-full bg-slate-800 overflow-hidden flex">
                  <div
                    class="bg-emerald-500"
                    :style="{ width: `${b.total_rows ? (b.valid_rows_count / b.total_rows) * 100 : 0}%` }"
                  ></div>
                  <div
                    class="bg-amber-500"
                    :style="{ width: `${b.total_rows ? (b.quarantined_rows_count / b.total_rows) * 100 : 0}%` }"
                  ></div>
                </div>
              </td>
              <td class="py-3 px-4">
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-mono border"
                  :class="{
                    'bg-emerald-950 text-emerald-300 border-emerald-800': b.status === 'completed',
                    'bg-amber-950 text-amber-300 border-amber-800': b.status === 'partially_imported',
                    'bg-red-950 text-red-300 border-red-800': b.status === 'failed',
                    'bg-cyan-950 text-cyan-300 border-cyan-800 animate-pulse': b.status === 'importing',
                  }"
                >
                  {{ b.status?.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-400 text-[10px]">
                {{ formatDate(b.created_at) }}
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="b.quarantined_rows_count > 0"
                    @click="openQuarantineDrawer(b)"
                    class="px-2 py-1 rounded bg-amber-950 hover:bg-amber-900 text-amber-200 border border-amber-800 text-[10px] font-medium transition-colors"
                    title="Inspect & Resolve Quarantined Records"
                  >
                    ⚠️ Quarantine ({{ b.quarantined_rows_count }})
                  </button>
                  <button
                    @click="inspectBatch(b)"
                    class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-[10px] font-medium transition-colors"
                  >
                    Inspect
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Run Ingestion Pipeline Modal -->
    <div
      v-if="showRunModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base font-bold text-white">Execute Data Ingestion Pipeline</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
              STAGING & QUARANTINE
            </span>
          </div>
          <button @click="showRunModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form @submit.prevent="executePipeline" class="p-5 space-y-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-400 mb-1 font-mono">Data Source *</label>
              <select
                v-model="runForm.data_source_id"
                required
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500 font-mono"
              >
                <option value="">Select Originating Source</option>
                <option v-for="s in sources" :key="s.id" :value="s.id">
                  {{ s.name }} ({{ s.code }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1 font-mono">Target Entity Master *</label>
              <select
                v-model="runForm.target_entity_type"
                required
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500 font-mono"
              >
                <option value="vessel">Vessel Master Database</option>
                <option value="company">Corporate Master Intelligence</option>
                <option value="person">Persons & Crew Registry</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-400 mb-1 font-mono">Dataset Version (Optional)</label>
              <select
                v-model="runForm.dataset_version_id"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500 font-mono"
              >
                <option value="">Unlinked Ad-hoc Ingestion</option>
                <option v-for="v in datasetVersions" :key="v.id" :value="v.id">
                  {{ v.dataset_name }} ({{ v.version_tag }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1 font-mono">Jurisdiction Country (Optional)</label>
              <select
                v-model="runForm.country_id"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500 font-mono"
              >
                <option value="">Global / Unspecified</option>
                <option v-for="c in countries" :key="c.id" :value="c.id">
                  {{ c.name }} ({{ c.iso_code_3 }})
                </option>
              </select>
            </div>
          </div>

          <!-- Input Mode: File or JSON Array -->
          <div class="border-t border-slate-800 pt-3">
            <div class="flex items-center justify-between mb-2">
              <label class="block text-slate-300 font-semibold">Payload Data Source</label>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="payloadMode = 'file'"
                  class="px-2 py-0.5 rounded text-[10px] font-mono border"
                  :class="payloadMode === 'file' ? 'bg-cyan-600 text-white border-cyan-500' : 'bg-slate-800 text-slate-400 border-slate-700'"
                >
                  Upload File (.csv / .json)
                </button>
                <button
                  type="button"
                  @click="payloadMode = 'json'"
                  class="px-2 py-0.5 rounded text-[10px] font-mono border"
                  :class="payloadMode === 'json' ? 'bg-cyan-600 text-white border-cyan-500' : 'bg-slate-800 text-slate-400 border-slate-700'"
                >
                  Paste JSON Array
                </button>
              </div>
            </div>

            <div v-if="payloadMode === 'file'" class="space-y-2">
              <input
                type="file"
                ref="fileInputRef"
                accept=".csv,.json"
                @change="handleFileUpload"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none font-mono"
              />
              <p class="text-[10px] text-slate-500">
                Supports CSV (with header row: name, imo, mmsi, callsign, flag, vessel_type) or standard JSON array.
              </p>
            </div>

            <div v-else class="space-y-2">
              <textarea
                v-model="rawJsonInput"
                rows="6"
                placeholder='[
  {"name": "KM LAUT CERIA 01", "imo": "9074729", "mmsi": "525123456", "flag": "Indonesia"},
  {"name": "CORRUPT VESSEL 02", "imo": "9074721", "mmsi": "351987654", "flag": "Panama"}
]'
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-cyan-300 font-mono text-xs focus:outline-none focus:border-cyan-500"
              ></textarea>
              <p class="text-[10px] text-slate-500">
                Rows with invalid IMO checksums or bad formats will be automatically isolated in the Quarantine Database.
              </p>
            </div>
          </div>

          <!-- Pre-flight Validation Dry-Run Result Preview -->
          <div v-if="previewResult" class="p-3.5 rounded-xl bg-slate-950 border border-cyan-800/60 font-mono space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-white">Pre-flight Validation Result:</span>
              <span class="text-cyan-300">{{ previewResult.summary.valid_percentage }}% Valid</span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-[11px]">
              <div class="p-2 rounded bg-slate-900 border border-slate-800">
                <span class="text-slate-400 block text-[9px]">TOTAL ROWS</span>
                <span class="text-white font-bold">{{ previewResult.summary.total_analyzed }}</span>
              </div>
              <div class="p-2 rounded bg-emerald-950/40 border border-emerald-900/60">
                <span class="text-emerald-400 block text-[9px]">VALID FOR INGESTION</span>
                <span class="text-emerald-300 font-bold">{{ previewResult.summary.valid_count }}</span>
              </div>
              <div class="p-2 rounded bg-amber-950/40 border border-amber-900/60">
                <span class="text-amber-400 block text-[9px]">WILL BE QUARANTINED</span>
                <span class="text-amber-300 font-bold">{{ previewResult.summary.invalid_count }}</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
            <button
              type="button"
              @click="runDryRunPreview"
              :disabled="previewing"
              class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium transition-colors border border-slate-700 font-mono text-xs"
            >
              {{ previewing ? 'Checking...' : '🧪 Run Pre-flight Dry Run' }}
            </button>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="showRunModal = false"
                class="px-3 py-2 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 font-medium transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
              >
                <span>{{ submitting ? 'Processing...' : 'Execute Ingestion' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Quarantined Records Inspector Modal/Drawer -->
    <div
      v-if="showQuarantineModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base font-bold text-amber-300">Quarantine Records Workspace</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-950 text-amber-300 border border-amber-800">
                {{ activeBatch?.batch_reference }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Isolated corrupt or malformed rows. Correct payload data or discard safely without polluting master database.
            </p>
          </div>
          <button @click="showQuarantineModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="p-5 flex-1 overflow-y-auto space-y-4">
          <div v-if="loadingQuarantine" class="p-12 text-center text-xs text-slate-400 font-mono">
            Loading quarantined records...
          </div>

          <div v-else-if="quarantinedList.length === 0" class="p-12 text-center text-xs text-emerald-400 font-mono">
            🎉 All quarantined records for this batch have been resolved!
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="q in quarantinedList"
              :key="q.id"
              class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3 font-mono text-xs"
            >
              <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-[10px]">
                    Row #{{ q.row_index }}
                  </span>
                  <span class="px-2 py-0.5 rounded text-[10px] bg-red-950 text-red-300 border border-red-800">
                    {{ q.quarantine_reason }}
                  </span>
                  <span
                    class="px-2 py-0.5 rounded text-[10px] border"
                    :class="q.resolution_status === 'pending_review' ? 'bg-amber-950/60 text-amber-300 border-amber-800' : 'bg-slate-800 text-slate-400 border-slate-700'"
                  >
                    {{ q.resolution_status }}
                  </span>
                </div>

                <div v-if="q.resolution_status === 'pending_review'" class="flex items-center gap-2">
                  <button
                    @click="startResolvingRecord(q)"
                    class="px-2.5 py-1 rounded bg-cyan-700 hover:bg-cyan-600 text-white text-[11px] transition-colors"
                  >
                    ✏️ Correct & Ingest
                  </button>
                  <button
                    @click="discardRecord(q)"
                    class="px-2.5 py-1 rounded bg-slate-800 hover:bg-red-950 text-slate-300 hover:text-red-300 border border-slate-700 text-[11px] transition-colors"
                  >
                    🗑️ Discard
                  </button>
                </div>
              </div>

              <!-- Error details -->
              <div class="p-2.5 rounded bg-red-950/30 border border-red-900/40 text-red-300 text-[11px]">
                <strong>Error:</strong> {{ q.error_details }}
              </div>

              <!-- Raw Payload Accordion/Display -->
              <div>
                <span class="text-slate-400 text-[10px] block mb-1">ORIGINAL ROW PAYLOAD:</span>
                <pre class="p-2.5 rounded bg-slate-900 border border-slate-800 text-cyan-300 text-[10px] overflow-x-auto">{{ JSON.stringify(q.raw_payload, null, 2) }}</pre>
              </div>

              <!-- Resolution Editor if active -->
              <div
                v-if="editingRecordId === q.id"
                class="p-4 rounded-xl bg-slate-900 border border-cyan-800/80 space-y-3 mt-2"
              >
                <div class="flex items-center justify-between">
                  <span class="font-bold text-cyan-300 text-xs">Correct Payload & Ingest to Master:</span>
                  <button @click="editingRecordId = null" class="text-slate-400 hover:text-white text-xs">Cancel</button>
                </div>

                <textarea
                  v-model="correctionJson"
                  rows="4"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-cyan-300 text-xs focus:outline-none focus:border-cyan-500 font-mono"
                ></textarea>

                <input
                  v-model="correctionNotes"
                  type="text"
                  placeholder="Analyst reason / registry justification notes..."
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none"
                />

                <div class="flex justify-end gap-2">
                  <button
                    @click="submitCorrection(q)"
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs transition-colors"
                  >
                    Confirm Correction & Ingest
                  </button>
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
import { ref, onMounted, computed } from 'vue';
import apiClient from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const batches = ref<any[]>([]);
const sources = ref<any[]>([]);
const datasetVersions = ref<any[]>([]);
const countries = ref<any[]>([]);

const loading = ref(false);
const filterStatus = ref('');
const filterTarget = ref('');

// Modals
const showRunModal = ref(false);
const submitting = ref(false);
const payloadMode = ref<'file' | 'json'>('json');
const rawJsonInput = ref('');
const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const runForm = ref({
  data_source_id: '',
  dataset_version_id: '',
  country_id: '',
  target_entity_type: 'vessel',
});

// Pre-flight Dry Run
const previewing = ref(false);
const previewResult = ref<any>(null);

// Quarantined Drawer
const showQuarantineModal = ref(false);
const activeBatch = ref<any>(null);
const quarantinedList = ref<any[]>([]);
const loadingQuarantine = ref(false);

const editingRecordId = ref<string | null>(null);
const correctionJson = ref('');
const correctionNotes = ref('');

// KPIs
const totalBatches = computed(() => batches.value.length);
const totalProcessed = computed(() => batches.value.reduce((sum, b) => sum + (b.total_rows || 0), 0));
const totalValid = computed(() => batches.value.reduce((sum, b) => sum + (b.valid_rows_count || 0), 0));
const totalQuarantined = computed(() => batches.value.reduce((sum, b) => sum + (b.quarantined_rows_count || 0), 0));
const validRate = computed(() => {
  if (totalProcessed.value === 0) return 0;
  return Math.round((totalValid.value / totalProcessed.value) * 100);
});

onMounted(async () => {
  await Promise.all([
    fetchBatches(),
    fetchSources(),
    fetchVersions(),
    fetchCountries(),
  ]);
});

async function fetchBatches() {
  loading.value = true;
  try {
    const params: any = {};
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterTarget.value) params.target_entity_type = filterTarget.value;

    const res = await apiClient.get('/imports', { params });
    batches.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch import batches', err);
  } finally {
    loading.value = false;
  }
}

async function fetchSources() {
  try {
    const res = await apiClient.get('/sources');
    sources.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch data sources', err);
  }
}

async function fetchVersions() {
  try {
    const res = await apiClient.get('/datasets/versions');
    datasetVersions.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch versions', err);
  }
}

async function fetchCountries() {
  try {
    const res = await apiClient.get('/countries');
    countries.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch countries', err);
  }
}

function openRunModal() {
  previewResult.value = null;
  showRunModal.value = true;
}

function handleFileUpload(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0];
  }
}

async function runDryRunPreview() {
  previewing.value = true;
  try {
    let payloadRows: any[] = [];
    if (payloadMode.value === 'json') {
      try {
        payloadRows = JSON.parse(rawJsonInput.value || '[]');
      } catch (err) {
        alert('Invalid JSON format in payload text area.');
        previewing.value = false;
        return;
      }
    }

    const formData = new FormData();
    formData.append('target_entity_type', runForm.value.target_entity_type);

    if (payloadMode.value === 'file' && selectedFile.value) {
      formData.append('file', selectedFile.value);
    } else {
      formData.append('rows', JSON.stringify(payloadRows));
    }

    const res = await apiClient.post('/validation/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    previewResult.value = res.data;
  } catch (err: any) {
    alert(err.response?.data?.message || 'Pre-flight check failed.');
  } finally {
    previewing.value = false;
  }
}

async function executePipeline() {
  submitting.value = true;
  try {
    let payloadRows: any[] = [];
    if (payloadMode.value === 'json') {
      try {
        payloadRows = JSON.parse(rawJsonInput.value || '[]');
      } catch (err) {
        alert('Invalid JSON format in payload text area.');
        submitting.value = false;
        return;
      }
    }

    const formData = new FormData();
    formData.append('data_source_id', runForm.value.data_source_id);
    formData.append('target_entity_type', runForm.value.target_entity_type);
    if (runForm.value.dataset_version_id) formData.append('dataset_version_id', runForm.value.dataset_version_id);
    if (runForm.value.country_id) formData.append('country_id', runForm.value.country_id);

    if (payloadMode.value === 'file' && selectedFile.value) {
      formData.append('file', selectedFile.value);
    } else {
      payloadRows.forEach((r, idx) => {
        Object.keys(r).forEach((k) => {
          formData.append(`rows[${idx}][${k}]`, r[k]);
        });
      });
    }

    await apiClient.post('/imports', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    showRunModal.value = false;
    await fetchBatches();
    alert('Ingestion pipeline executed successfully. Check the batch status.');
  } catch (err: any) {
    alert(err.response?.data?.message || 'Pipeline execution failed.');
  } finally {
    submitting.value = false;
  }
}

async function openQuarantineDrawer(batch: any) {
  activeBatch.value = batch;
  showQuarantineModal.value = true;
  loadingQuarantine.value = true;
  try {
    const res = await apiClient.get(`/imports/${batch.id}/quarantine`);
    quarantinedList.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch quarantined records', err);
  } finally {
    loadingQuarantine.value = false;
  }
}

function startResolvingRecord(q: any) {
  editingRecordId.value = q.id;
  correctionJson.value = JSON.stringify(q.raw_payload, null, 2);
  correctionNotes.value = '';
}

async function submitCorrection(q: any) {
  try {
    const parsedPayload = JSON.parse(correctionJson.value);
    await apiClient.post(`/imports/${activeBatch.value.id}/quarantine/${q.id}/resolve`, {
      decision: 'correct_and_import',
      corrected_payload: parsedPayload,
      notes: correctionNotes.value,
    });

    editingRecordId.value = null;
    await openQuarantineDrawer(activeBatch.value);
    await fetchBatches();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Resolution failed. Make sure JSON syntax is valid.');
  }
}

async function discardRecord(q: any) {
  const reason = prompt('Enter analyst justification for discarding this corrupt record:');
  if (reason === null) return;

  try {
    await apiClient.post(`/imports/${activeBatch.value.id}/quarantine/${q.id}/resolve`, {
      decision: 'discard',
      notes: reason || 'Discarded by analyst as unrecoverable malformed record.',
    });

    await openQuarantineDrawer(activeBatch.value);
    await fetchBatches();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Discard failed.');
  }
}

function inspectBatch(batch: any) {
  alert(`Batch Reference: ${batch.batch_reference}\nTarget: ${batch.target_entity_type}\nStatus: ${batch.status}\nValid Rows: ${batch.valid_rows_count}\nQuarantined Rows: ${batch.quarantined_rows_count}\nFile SHA-256: ${batch.file_sha256}`);
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
