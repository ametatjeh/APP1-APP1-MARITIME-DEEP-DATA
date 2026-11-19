<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Dataset Versions & Snapshot Integrity</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            PROVENANCE REPOSITORY
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Semantic dataset release management, immutable SHA-256 snapshots, schema definitions, and ingestion lineage tracking.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="authStore.canEdit"
          @click="showCreateModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>🏷️</span>
          <span>Register Dataset Version</span>
        </button>
      </div>
    </div>

    <!-- Info banner -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-4 text-xs font-mono">
      <div class="flex items-center gap-3">
        <span class="text-xl">🛡️</span>
        <div>
          <span class="text-white font-bold block">Immutable Provenance Architecture:</span>
          <span class="text-slate-400 text-[11px]">
            Every external registry ingestion links directly to a cryptographically hashed dataset snapshot to prevent retroactive falsification.
          </span>
        </div>
      </div>
      <div class="hidden md:flex items-center gap-2">
        <span class="px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-cyan-400 text-[11px]">
          {{ versions.length }} Version Snapshots
        </span>
      </div>
    </div>

    <!-- Versions Grid -->
    <div v-if="loading" class="p-12 text-center text-xs text-slate-400 font-mono">
      <div class="inline-block animate-spin w-5 h-5 border-2 border-cyan-500 border-t-transparent rounded-full mb-2"></div>
      <div>Loading dataset versions...</div>
    </div>

    <div v-else-if="versions.length === 0" class="p-12 text-center text-xs text-slate-400 font-mono rounded-xl bg-slate-900 border border-slate-800">
      No dataset versions registered. Click "Register Dataset Version" to initialize a version snapshot.
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div
        v-for="v in versions"
        :key="v.id"
        class="p-5 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-all space-y-4"
      >
        <!-- Card Header -->
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-white">{{ v.dataset_name }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">
                {{ v.version_tag }}
              </span>
              <span
                v-if="v.is_current"
                class="px-2 py-0.5 rounded text-[9px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800"
              >
                CURRENT ACTIVE
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
              <span>{{ v.data_source?.name }}</span>
              <span v-if="v.country" class="text-slate-500 font-mono">({{ v.country.name }})</span>
            </div>
          </div>

          <span
            class="px-2 py-0.5 rounded text-[10px] font-mono uppercase border"
            :class="{
              'bg-emerald-950 text-emerald-300 border-emerald-800': v.status === 'active',
              'bg-slate-800 text-slate-400 border-slate-700': v.status === 'draft',
              'bg-amber-950 text-amber-300 border-amber-800': v.status === 'deprecated',
              'bg-purple-950 text-purple-300 border-purple-800': v.status === 'superseded',
            }"
          >
            {{ v.status }}
          </span>
        </div>

        <!-- Snapshot Hash -->
        <div class="p-2.5 rounded-lg bg-slate-950 border border-slate-800/80 font-mono text-[11px] flex items-center justify-between gap-2">
          <div class="truncate text-slate-400">
            <span class="text-slate-500">SHA-256: </span>
            <span class="text-cyan-400">{{ v.snapshot_sha256 }}</span>
          </div>
          <button
            @click="copyHash(v.snapshot_sha256)"
            class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] transition-colors shrink-0"
            title="Copy snapshot hash"
          >
            📋 Copy
          </button>
        </div>

        <!-- Metric Details -->
        <div class="grid grid-cols-3 gap-2 font-mono text-[11px]">
          <div class="p-2 rounded bg-slate-950/60 border border-slate-800/60">
            <span class="text-slate-500 text-[9px] block uppercase">Ingested Records</span>
            <span class="text-white font-bold">{{ (v.records_count || 0).toLocaleString() }}</span>
          </div>
          <div class="p-2 rounded bg-slate-950/60 border border-slate-800/60">
            <span class="text-slate-500 text-[9px] block uppercase">Pipeline Batches</span>
            <span class="text-white font-bold">{{ v.imports_count || 0 }}</span>
          </div>
          <div class="p-2 rounded bg-slate-950/60 border border-slate-800/60">
            <span class="text-slate-500 text-[9px] block uppercase">Effective Date</span>
            <span class="text-slate-300">{{ v.effective_date || 'N/A' }}</span>
          </div>
        </div>

        <!-- Changelog / Notes -->
        <div v-if="v.changelog" class="text-xs text-slate-300 bg-slate-950/40 p-2.5 rounded-lg border border-slate-800/40">
          <span class="text-[10px] text-slate-500 uppercase font-mono block mb-0.5">Changelog & Release Notes:</span>
          <p class="text-slate-400 text-[11px] line-clamp-2">{{ v.changelog }}</p>
        </div>

        <!-- Actions -->
        <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between text-xs font-mono">
          <div class="text-[10px] text-slate-500">
            Registered by {{ v.creator?.name || 'Automated Pipeline' }}
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="v.status === 'active' && authStore.canEdit"
              @click="deprecateVersion(v)"
              class="px-2.5 py-1 rounded bg-amber-950 hover:bg-amber-900 text-amber-300 border border-amber-800 text-[11px] transition-colors"
            >
              Deprecate
            </button>
            <button
              @click="viewVersionSchema(v)"
              class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-[11px] transition-colors"
            >
              View Schema
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Register Version Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base font-bold text-white">Register Dataset Version</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
              SNAPSHOT INTEGRITY
            </span>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form @submit.prevent="createVersion" class="p-5 space-y-4 text-xs font-sans">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-400 mb-1 font-mono">Data Source *</label>
              <select
                v-model="form.data_source_id"
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
              <label class="block text-slate-400 mb-1 font-mono">Country (Optional)</label>
              <select
                v-model="form.country_id"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500 font-mono"
              >
                <option value="">Global / Unspecified</option>
                <option v-for="c in countries" :key="c.id" :value="c.id">
                  {{ c.name }} ({{ c.iso_code_3 }})
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-400 mb-1 font-mono">Dataset Name *</label>
              <input
                v-model="form.dataset_name"
                type="text"
                required
                placeholder="e.g. Authorized Tuna Longliners Register"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500"
              />
            </div>

            <div>
              <label class="block text-slate-400 mb-1 font-mono">Semantic Version Tag *</label>
              <input
                v-model="form.version_tag"
                type="text"
                required
                placeholder="e.g. v2026.1 or v2.4.0"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-cyan-300 font-mono focus:outline-none focus:border-cyan-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-400 mb-1 font-mono">Effective Date</label>
              <input
                v-model="form.effective_date"
                type="date"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none font-mono"
              />
            </div>

            <div class="flex items-center gap-2 pt-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.is_current"
                  type="checkbox"
                  class="rounded bg-slate-950 border-slate-800 text-cyan-500 focus:ring-0"
                />
                <span class="text-slate-300 font-mono text-xs">Set as Current Active Version</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1 font-mono">Schema Definition (JSON)</label>
            <textarea
              v-model="form.schema_json"
              rows="4"
              placeholder='{
  "columns": ["vessel_name", "imo", "mmsi", "callsign", "gear_type", "tonnage"],
  "primary_key": "imo"
}'
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-cyan-300 font-mono text-xs focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-slate-400 mb-1 font-mono">Release Changelog / Notes</label>
            <textarea
              v-model="form.changelog"
              rows="3"
              placeholder="Detailed description of changes, updates, or newly integrated fleets..."
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-3.5 py-2 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 font-medium transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium transition-colors shadow-lg shadow-cyan-950/40"
            >
              {{ submitting ? 'Registering...' : 'Register Version Snapshot' }}
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

const versions = ref<any[]>([]);
const sources = ref<any[]>([]);
const countries = ref<any[]>([]);
const loading = ref(false);
const showCreateModal = ref(false);
const submitting = ref(false);

const form = ref({
  data_source_id: '',
  country_id: '',
  dataset_name: '',
  version_tag: '',
  effective_date: new Date().toISOString().split('T')[0],
  is_current: true,
  schema_json: '{\n  "columns": ["name", "imo", "mmsi", "callsign", "flag", "vessel_type"],\n  "primary_key": "imo"\n}',
  changelog: '',
});

onMounted(async () => {
  await Promise.all([
    fetchVersions(),
    fetchSources(),
    fetchCountries(),
  ]);
});

async function fetchVersions() {
  loading.value = true;
  try {
    const res = await apiClient.get('/datasets/versions');
    versions.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch dataset versions', err);
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

async function fetchCountries() {
  try {
    const res = await apiClient.get('/countries');
    countries.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch countries', err);
  }
}

async function createVersion() {
  submitting.value = true;
  try {
    let schemaObj = null;
    if (form.value.schema_json) {
      schemaObj = JSON.parse(form.value.schema_json);
    }

    await apiClient.post('/datasets/versions', {
      data_source_id: form.value.data_source_id,
      country_id: form.value.country_id || null,
      dataset_name: form.value.dataset_name,
      version_tag: form.value.version_tag,
      effective_date: form.value.effective_date,
      is_current: form.value.is_current,
      schema_definition: schemaObj,
      changelog: form.value.changelog,
    });

    showCreateModal.value = false;
    await fetchVersions();
    alert('Dataset version snapshot registered successfully.');
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to register version snapshot.');
  } finally {
    submitting.value = false;
  }
}

async function deprecateVersion(v: any) {
  const reason = prompt('Enter deprecation justification notes:');
  if (reason === null) return;

  try {
    await apiClient.put(`/datasets/versions/${v.id}`, {
      status: 'deprecated',
      deprecation_date: new Date().toISOString().split('T')[0],
      is_current: false,
      changelog: `${v.changelog || ''}\n[DEPRECATED ${new Date().toISOString().split('T')[0]}]: ${reason}`.trim(),
    });
    await fetchVersions();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to deprecate version.');
  }
}

function copyHash(hashStr?: string) {
  if (!hashStr) return;
  navigator.clipboard.writeText(hashStr);
  alert('SHA-256 snapshot hash copied to clipboard!');
}

function viewVersionSchema(v: any) {
  const formatted = JSON.stringify(v.schema_definition || {}, null, 2);
  alert(`Dataset Schema Definition for [${v.dataset_name} ${v.version_tag}]:\n\n${formatted}`);
}
</script>
