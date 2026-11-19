<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Target Watchlists & Automated Alerts Hub</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            PHASE 5 ENGINE
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Automated surveillance of high-risk IUU fleets, forced labour networks, flags of convenience, and identity fraud targets.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="authStore.canEdit"
          @click="showCreateModal = true"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>➕</span>
          <span>Create Watchlist</span>
        </button>
      </div>
    </div>

    <!-- Navigation Tabs: Watchlists vs Alerts Feed -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
      <button
        @click="activeTab = 'alerts'"
        class="px-4 py-2 rounded-lg text-xs font-medium transition-colors flex items-center gap-2 font-mono"
        :class="activeTab === 'alerts' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-950/50' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
      >
        <span>🚨</span>
        <span>Active Analyst Alerts ({{ activeAlertsCount }})</span>
      </button>

      <button
        @click="activeTab = 'watchlists'"
        class="px-4 py-2 rounded-lg text-xs font-medium transition-colors flex items-center gap-2 font-mono"
        :class="activeTab === 'watchlists' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-950/50' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
      >
        <span>👁️</span>
        <span>Monitoring Watchlists ({{ watchlists.length }})</span>
      </button>
    </div>

    <!-- TAB 1: Analyst Alerts Feed -->
    <div v-if="activeTab === 'alerts'" class="space-y-4">
      <!-- Filter Bar -->
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
          <span class="text-slate-400 font-mono text-xs">Filter Status:</span>
          <select
            v-model="alertFilterStatus"
            @change="fetchAlerts"
            class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs"
          >
            <option value="">All Statuses</option>
            <option value="active">Active Only</option>
            <option value="acknowledged">Acknowledged</option>
            <option value="resolved">Resolved</option>
          </select>

          <select
            v-model="alertFilterSeverity"
            @change="fetchAlerts"
            class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs"
          >
            <option value="">All Severities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>

        <button
          @click="fetchAlerts"
          class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-xs border border-slate-700"
        >
          🔄 Refresh Alerts
        </button>
      </div>

      <!-- Alerts List -->
      <div v-if="loadingAlerts" class="p-12 text-center text-xs text-slate-400 font-mono">
        Loading real-time surveillance alerts...
      </div>

      <div v-else-if="alerts.length === 0" class="p-12 text-center text-xs text-emerald-400 font-mono rounded-xl bg-slate-900 border border-slate-800">
        🛡️ No active alerts matching criteria. All target entities operating within normal bounds.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="a in alerts"
          :key="a.id"
          class="p-4 rounded-xl border transition-all space-y-3"
          :class="{
            'bg-red-950/20 border-red-800/80 hover:border-red-600': a.severity === 'critical',
            'bg-amber-950/20 border-amber-800/80 hover:border-amber-600': a.severity === 'high',
            'bg-slate-900 border-slate-800 hover:border-slate-700': a.severity === 'medium' || a.severity === 'low',
          }"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-xs text-cyan-400">{{ a.alert_reference }}</span>
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold border"
                  :class="{
                    'bg-red-950 text-red-300 border-red-800 animate-pulse': a.severity === 'critical',
                    'bg-amber-950 text-amber-300 border-amber-800': a.severity === 'high',
                    'bg-cyan-950 text-cyan-300 border-cyan-800': a.severity === 'medium',
                    'bg-slate-800 text-slate-400 border-slate-700': a.severity === 'low',
                  }"
                >
                  {{ a.severity }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 border border-slate-700">
                  {{ a.alert_type?.replace(/_/g, ' ') }}
                </span>
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-mono border"
                  :class="a.status === 'active' ? 'bg-emerald-950 text-emerald-300 border-emerald-800' : 'bg-slate-800 text-slate-400 border-slate-700'"
                >
                  {{ a.status }}
                </span>
              </div>

              <h4 class="text-sm font-bold text-white mt-1">{{ a.title }}</h4>
            </div>

            <div class="text-[10px] font-mono text-slate-500 shrink-0">
              {{ formatDate(a.created_at) }}
            </div>
          </div>

          <p class="text-xs text-slate-300 leading-relaxed font-sans">{{ a.details }}</p>

          <!-- Context Metadata -->
          <div v-if="a.context_data && Object.keys(a.context_data).length > 0" class="p-2.5 rounded-lg bg-slate-950 border border-slate-800/80 font-mono text-[11px] text-cyan-300">
            <span class="text-slate-500 block text-[9px] uppercase mb-0.5">Surveillance Event Context:</span>
            <pre class="overflow-x-auto text-[10px]">{{ JSON.stringify(a.context_data, null, 2) }}</pre>
          </div>

          <!-- Resolution Footnote if resolved -->
          <div v-if="a.resolution_notes" class="p-2.5 rounded-lg bg-slate-950 border border-emerald-800/40 text-xs text-slate-400 font-mono">
            <strong class="text-emerald-400">Resolution Conclusion:</strong> {{ a.resolution_notes }}
            <span class="block text-[10px] text-slate-500 mt-0.5">Resolved by {{ a.resolved_by_user?.name || 'Analyst' }} on {{ formatDate(a.resolved_at) }}</span>
          </div>

          <!-- Actions -->
          <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between text-xs font-mono">
            <div class="text-[10px] text-slate-400">
              Target Entity: <strong class="text-white uppercase">{{ a.entity_type }}</strong>
              <span v-if="a.entity?.current_name || a.entity?.company_name || a.entity?.full_name" class="text-cyan-300">
                ({{ a.entity.current_name || a.entity.company_name || a.entity.full_name }})
              </span>
            </div>

            <div v-if="authStore.canEdit" class="flex items-center gap-2">
              <button
                v-if="a.status === 'active'"
                @click="acknowledgeAlert(a)"
                class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-[11px] transition-colors"
              >
                👁️ Acknowledge
              </button>
              <button
                v-if="a.status !== 'resolved'"
                @click="openResolveModal(a)"
                class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-medium transition-colors"
              >
                ✅ Resolve Alert
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Watchlists Catalog -->
    <div v-if="activeTab === 'watchlists'" class="space-y-4">
      <div v-if="loadingWatchlists" class="p-12 text-center text-xs text-slate-400 font-mono">
        Loading target watchlists...
      </div>

      <div v-else-if="watchlists.length === 0" class="p-12 text-center text-xs text-slate-400 font-mono rounded-xl bg-slate-900 border border-slate-800">
        No active watchlists configured. Click "Create Watchlist" to start tracking entities.
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="w in watchlists"
          :key="w.id"
          class="p-5 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between space-y-4"
        >
          <div>
            <div class="flex items-start justify-between gap-2">
              <h4 class="text-sm font-bold text-white">{{ w.name }}</h4>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-mono uppercase border font-bold"
                :class="{
                  'bg-red-950 text-red-300 border-red-800': w.priority === 'critical',
                  'bg-amber-950 text-amber-300 border-amber-800': w.priority === 'high',
                  'bg-cyan-950 text-cyan-300 border-cyan-800': w.priority === 'medium',
                  'bg-slate-800 text-slate-400 border-slate-700': w.priority === 'low',
                }"
              >
                {{ w.priority }}
              </span>
            </div>

            <p class="text-xs text-slate-400 mt-1.5 line-clamp-2">{{ w.description || 'No description recorded.' }}</p>

            <div class="mt-3 flex items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-950 text-slate-300 border border-slate-800">
                Category: {{ w.category?.replace(/_/g, ' ') }}
              </span>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
            <div class="flex items-center gap-3">
              <span class="text-slate-400">
                Entities: <strong class="text-white">{{ w.items_count || 0 }}</strong>
              </span>
              <span class="text-slate-400">
                Alerts: <strong class="text-amber-400">{{ w.alerts_count || 0 }}</strong>
              </span>
            </div>

            <button
              @click="openWatchlistDrawer(w)"
              class="px-3 py-1 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-[11px] font-medium transition-colors"
            >
              Inspect Items →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Watchlist Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <span class="text-base font-bold text-white">Create Target Monitoring Watchlist</span>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form @submit.prevent="createWatchlist" class="p-5 space-y-4 text-xs font-sans">
          <div>
            <label class="block text-slate-400 mb-1 font-mono">Watchlist Name *</label>
            <input
              v-model="newWatchlist.name"
              type="text"
              required
              placeholder="e.g. Flag-hopping IUU Longliners & Reefers"
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-4 font-mono">
            <div>
              <label class="block text-slate-400 mb-1">Surveillance Category *</label>
              <select
                v-model="newWatchlist.category"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="iuu_high_risk">IUU High Risk</option>
                <option value="forced_labour_risk">Forced Labour Suspicion</option>
                <option value="sanctions_monitoring">Sanctions Monitoring</option>
                <option value="identity_fraud">Identity & Callsign Fraud</option>
                <option value="flags_of_convenience">Flags of Convenience</option>
                <option value="custom">Custom Research Group</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Alert Priority *</label>
              <select
                v-model="newWatchlist.priority"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500"
              >
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1 font-mono">Surveillance Objective & Criteria</label>
            <textarea
              v-model="newWatchlist.description"
              rows="3"
              placeholder="Detailed monitoring objective, risk thresholds, and criteria..."
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
          </div>

          <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-3 py-2 rounded-lg bg-slate-800 text-slate-300"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium shadow-lg shadow-cyan-950/40"
            >
              Create Watchlist
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Watchlist Detail / Items Drawer Modal -->
    <div
      v-if="showDrawerModal && activeWatchlist"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base font-bold text-white">{{ activeWatchlist.name }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-cyan-300 border border-slate-700">
                {{ activeWatchlist.category }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ activeWatchlist.description }}</p>
          </div>
          <button @click="showDrawerModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="p-5 flex-1 overflow-y-auto space-y-4 text-xs font-mono">
          <!-- Add Item Form -->
          <div v-if="authStore.canEdit" class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3 font-sans">
            <span class="text-xs font-bold text-white block font-mono">➕ Add Entity Under Surveillance:</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 font-mono text-xs">
              <div>
                <label class="block text-slate-400 mb-1 text-[10px]">Entity Type</label>
                <select
                  v-model="newItem.entity_type"
                  class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white"
                >
                  <option value="vessel">Vessel Master</option>
                  <option value="company">Corporate Entity</option>
                  <option value="person">Person / Skipper</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="block text-slate-400 mb-1 text-[10px]">Select Target Entity</label>
                <select
                  v-model="newItem.entity_id"
                  class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white"
                >
                  <option value="">Choose {{ newItem.entity_type }}</option>
                  <option v-for="cand in entityCandidates" :key="cand.id" :value="cand.id">
                    {{ cand.label }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-slate-400 mb-1 text-[10px] font-mono">Surveillance Rationale & Threat Hypothesis</label>
              <input
                v-model="newItem.reason"
                type="text"
                placeholder="Specific intelligence justification..."
                class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white text-xs font-sans"
              />
            </div>

            <div class="flex justify-end">
              <button
                @click="submitAddItem"
                :disabled="!newItem.entity_id"
                class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium font-sans"
              >
                Add to Watchlist
              </button>
            </div>
          </div>

          <!-- Items List -->
          <div class="space-y-2">
            <span class="text-[10px] text-slate-400 uppercase font-bold block">Entities on Watchlist ({{ activeWatchlist.items?.length || 0 }}):</span>

            <div v-if="!activeWatchlist.items || activeWatchlist.items.length === 0" class="p-6 text-center text-slate-500">
              No entities added to this watchlist yet.
            </div>

            <div
              v-for="item in activeWatchlist.items"
              :key="item.id"
              class="p-3 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-between gap-3"
            >
              <div>
                <div class="flex items-center gap-2">
                  <span class="px-1.5 py-0.5 rounded text-[9px] uppercase bg-slate-800 text-slate-300">
                    {{ item.entity_type }}
                  </span>
                  <span class="font-bold text-white text-xs font-sans">
                    {{ item.entity?.current_name || item.entity?.company_name || item.entity?.full_name || item.entity_id }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 font-sans">
                  <strong>Reason:</strong> {{ item.reason || 'None specified' }}
                </div>
              </div>

              <button
                v-if="authStore.canEdit"
                @click="removeItem(item.id)"
                class="p-1.5 rounded hover:bg-red-950 text-slate-400 hover:text-red-300 transition-colors"
                title="Remove from watchlist"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resolve Alert Modal -->
    <div
      v-if="showResolveModal && activeAlert"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-md p-5 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between">
          <span class="font-bold text-white text-sm">Resolve Surveillance Alert</span>
          <button @click="showResolveModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <p class="text-xs text-slate-400">
          Document the investigative resolution conclusion for alert <strong>{{ activeAlert.alert_reference }}</strong>:
        </p>

        <textarea
          v-model="resolveNotes"
          rows="4"
          required
          placeholder="Analyst conclusion, case reference, or enforcement notification details..."
          class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-cyan-500 font-sans"
        ></textarea>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="showResolveModal = false"
            class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 text-xs"
          >
            Cancel
          </button>
          <button
            @click="submitResolveAlert"
            :disabled="!resolveNotes.trim()"
            class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs shadow-lg shadow-emerald-950/40"
          >
            Confirm Resolution
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import apiClient from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const activeTab = ref<'alerts' | 'watchlists'>('alerts');

// Watchlists
const watchlists = ref<any[]>([]);
const loadingWatchlists = ref(false);
const showCreateModal = ref(false);
const showDrawerModal = ref(false);
const activeWatchlist = ref<any>(null);

const newWatchlist = ref({
  name: '',
  description: '',
  category: 'iuu_high_risk',
  priority: 'critical',
});

// Items & Entity Candidates
const newItem = ref({
  entity_type: 'vessel',
  entity_id: '',
  reason: '',
});
const entityCandidates = ref<any[]>([]);

// Alerts
const alerts = ref<any[]>([]);
const loadingAlerts = ref(false);
const alertFilterStatus = ref('active');
const alertFilterSeverity = ref('');

const showResolveModal = ref(false);
const activeAlert = ref<any>(null);
const resolveNotes = ref('');

const activeAlertsCount = computed(() => {
  return alerts.value.filter(a => a.status === 'active').length;
});

onMounted(async () => {
  await Promise.all([
    fetchAlerts(),
    fetchWatchlists(),
    loadCandidates('vessel'),
  ]);
});

watch(() => newItem.value.entity_type, (newType) => {
  loadCandidates(newType);
});

async function fetchWatchlists() {
  loadingWatchlists.value = true;
  try {
    const res = await apiClient.get('/watchlists');
    watchlists.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load watchlists', err);
  } finally {
    loadingWatchlists.value = false;
  }
}

async function fetchAlerts() {
  loadingAlerts.value = true;
  try {
    const params: any = {};
    if (alertFilterStatus.value) params.status = alertFilterStatus.value;
    if (alertFilterSeverity.value) params.severity = alertFilterSeverity.value;

    const res = await apiClient.get('/watchlists/alerts', { params });
    alerts.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load alerts', err);
  } finally {
    loadingAlerts.value = false;
  }
}

async function loadCandidates(type: string) {
  try {
    if (type === 'vessel') {
      const res = await apiClient.get('/vessels?per_page=50');
      entityCandidates.value = (res.data.data || []).map((v: any) => ({
        id: v.id,
        label: `${v.current_name} (IMO: ${v.imo_number || 'N/A'})`,
      }));
    } else if (type === 'company') {
      const res = await apiClient.get('/companies?per_page=50');
      entityCandidates.value = (res.data.data || []).map((c: any) => ({
        id: c.id,
        label: `${c.company_name} (Reg: ${c.registration_number || 'N/A'})`,
      }));
    } else if (type === 'person') {
      const res = await apiClient.get('/persons?per_page=50');
      entityCandidates.value = (res.data.data || []).map((p: any) => ({
        id: p.id,
        label: `${p.masked_name || p.full_name} (${p.country_of_citizenship?.name || 'Citizen'})`,
      }));
    }
  } catch (err) {
    console.error('Failed to load entity candidates', err);
  }
}

async function createWatchlist() {
  try {
    await apiClient.post('/watchlists', newWatchlist.value);
    showCreateModal.value = false;
    newWatchlist.value = { name: '', description: '', category: 'iuu_high_risk', priority: 'critical' };
    await fetchWatchlists();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to create watchlist.');
  }
}

async function openWatchlistDrawer(w: any) {
  try {
    const res = await apiClient.get(`/watchlists/${w.id}`);
    activeWatchlist.value = res.data.data;
    showDrawerModal.value = true;
    newItem.value = { entity_type: 'vessel', entity_id: '', reason: '' };
    loadCandidates('vessel');
  } catch (err) {
    console.error(err);
  }
}

async function submitAddItem() {
  if (!activeWatchlist.value || !newItem.value.entity_id) return;
  try {
    await apiClient.post(`/watchlists/${activeWatchlist.value.id}/items`, newItem.value);
    await openWatchlistDrawer(activeWatchlist.value);
    await fetchWatchlists();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to add entity to watchlist.');
  }
}

async function removeItem(itemId: string) {
  if (!confirm('Remove this entity from active watchlist monitoring?')) return;
  try {
    await apiClient.delete(`/watchlists/${activeWatchlist.value.id}/items/${itemId}`);
    await openWatchlistDrawer(activeWatchlist.value);
    await fetchWatchlists();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to remove entity.');
  }
}

async function acknowledgeAlert(alert: any) {
  try {
    await apiClient.post(`/watchlists/alerts/${alert.id}/acknowledge`);
    await fetchAlerts();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to acknowledge alert.');
  }
}

function openResolveModal(alert: any) {
  activeAlert.value = alert;
  resolveNotes.value = '';
  showResolveModal.value = true;
}

async function submitResolveAlert() {
  if (!activeAlert.value || !resolveNotes.value.trim()) return;
  try {
    await apiClient.post(`/watchlists/alerts/${activeAlert.value.id}/resolve`, {
      notes: resolveNotes.value,
    });
    showResolveModal.value = false;
    await fetchAlerts();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to resolve alert.');
  }
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
