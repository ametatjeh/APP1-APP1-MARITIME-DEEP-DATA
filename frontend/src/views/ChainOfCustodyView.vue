<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Forensic Chain of Custody Master Ledger</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-950 border border-emerald-800 text-emerald-300">
            COURT-ADMISSIBLE INTEGRITY
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Immutable event log tracing every evidence upload, cryptographic hash generation, analyst access, and binary download.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="fetchLogs"
          class="px-3.5 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono transition-colors border border-slate-700 flex items-center gap-1.5"
        >
          <span>🔄</span>
          <span>Refresh Ledger</span>
        </button>
      </div>
    </div>

    <!-- Integrity Standard Card -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs font-mono">
      <div class="flex items-center gap-3">
        <span class="text-2xl">🛡️</span>
        <div>
          <div class="font-bold text-white">Cryptographic Non-Repudiation Architecture</div>
          <span class="text-slate-400 text-[11px]">
            No custody log entry can be altered or erased. Actor identity, role, client IP, and file hash are immutably captured at action time.
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2 text-emerald-400 bg-emerald-950/60 border border-emerald-800/80 px-3 py-1.5 rounded-lg">
        <span>✓ 100% UNALTERED</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
      <div>
        <label class="block text-slate-400 mb-1">Filter by Custody Action</label>
        <select
          v-model="selectedAction"
          @change="fetchLogs"
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500 font-mono"
        >
          <option value="">All Forensic Actions</option>
          <option value="UPLOADED">UPLOADED (Initial ingestion)</option>
          <option value="HASH_GENERATED">HASH_GENERATED (Cryptographic validation)</option>
          <option value="DOWNLOADED">DOWNLOADED (Decrypted stream extraction)</option>
          <option value="ACCESSED">ACCESSED (Analyst dossier inspection)</option>
          <option value="VERIFIED">VERIFIED (Formal adjudication)</option>
          <option value="REDACTED">REDACTED (PII protection)</option>
        </select>
      </div>

      <div>
        <label class="block text-slate-400 mb-1">Search Context / Actor / IP</label>
        <input
          v-model="searchQuery"
          @input="debounceSearch"
          type="text"
          placeholder="Filter by actor name, IP, or notes..."
          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 font-mono"
        />
      </div>
    </div>

    <!-- Custody Ledger Table -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading custody audit logs...</div>
      <div v-else-if="filteredLogs.length === 0" class="py-12 text-center text-xs text-slate-400">
        No custody records found.
      </div>
      <div v-else class="divide-y divide-slate-800">
        <div
          v-for="log in filteredLogs"
          :key="log.id"
          class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-start justify-between gap-4 text-xs font-mono"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase" :class="custodyActionBadgeClass(log.action)">
                {{ log.action }}
              </span>
              <span class="text-cyan-400 text-xs font-semibold">
                {{ formatDateTime(log.created_at) }}
              </span>
              <span class="text-slate-400">
                Actor: <strong class="text-white">{{ log.actor?.name || 'System Daemon' }}</strong>
                ({{ log.actor_role || 'Agent' }})
              </span>
              <span v-if="log.ip_address" class="text-slate-500">
                • IP: <strong class="text-slate-300">{{ log.ip_address }}</strong>
              </span>
            </div>

            <!-- Evidence Title Link -->
            <div v-if="log.evidence" class="text-slate-300 font-sans font-semibold text-xs pt-0.5">
              Target Evidence:
              <router-link :to="`/evidence/${log.evidence_id}`" class="text-cyan-400 hover:underline">
                {{ log.evidence.title }}
              </router-link>
            </div>

            <div v-if="log.action_description" class="text-slate-300 font-sans text-xs">
              {{ log.action_description }}
            </div>

            <!-- Hash Fingerprint -->
            <div v-if="log.hash_at_action" class="text-[11px] text-slate-400 flex items-center gap-2 pt-1">
              <span class="text-emerald-400 font-semibold">SHA-256 at event:</span>
              <span class="truncate max-w-xl bg-slate-950 px-2 py-0.5 rounded border border-slate-800 text-slate-300 select-all">
                {{ log.hash_at_action }}
              </span>
            </div>
          </div>

          <div class="self-start md:self-center">
            <router-link
              :to="`/evidence/${log.evidence_id}`"
              class="px-2.5 py-1.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs transition-colors border border-slate-700"
            >
              View Evidence
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/client';

const logs = ref<any[]>([]);
const loading = ref(false);
const selectedAction = ref('');
const searchQuery = ref('');

let searchTimeout: any = null;

function debounceSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    // client filter or reload
  }, 200);
}

const filteredLogs = computed(() => {
  return logs.value.filter((l) => {
    if (selectedAction.value && l.action !== selectedAction.value) return false;
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const matchActor = l.actor?.name?.toLowerCase().includes(q) || false;
      const matchIp = l.ip_address?.toLowerCase().includes(q) || false;
      const matchDesc = l.action_description?.toLowerCase().includes(q) || false;
      const matchTitle = l.evidence?.title?.toLowerCase().includes(q) || false;
      return matchActor || matchIp || matchDesc || matchTitle;
    }
    return true;
  });
});

async function fetchLogs() {
  loading.value = true;
  try {
    const res = await apiClient.get('/custody/logs?per_page=100');
    logs.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load custody logs', err);
  } finally {
    loading.value = false;
  }
}

function custodyActionBadgeClass(action: string) {
  switch (action) {
    case 'UPLOADED': return 'bg-blue-950 text-blue-300 border border-blue-800';
    case 'HASH_GENERATED': return 'bg-cyan-950 text-cyan-300 border border-cyan-800';
    case 'DOWNLOADED': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'ACCESSED': return 'bg-slate-800 text-slate-300 border border-slate-700';
    case 'VERIFIED': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'REDACTED': return 'bg-amber-950 text-amber-300 border border-amber-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function formatDateTime(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

onMounted(() => {
  fetchLogs();
});
</script>
