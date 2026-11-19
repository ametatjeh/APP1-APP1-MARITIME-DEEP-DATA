<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-bold text-white">Corporate & Beneficial Ownership Network Graph</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Interactive graph mapping relationships between offshore holdings, operating companies, vessels, and directors.
        </p>
      </div>

      <!-- Entity Switcher -->
      <div class="flex items-center gap-2">
        <select
          v-model="selectedCompanyId"
          @change="loadGraph"
          class="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-white text-xs focus:border-cyan-500 font-mono"
        >
          <option v-for="c in companyList" :key="c.id" :value="c.id">
            {{ c.company_name }}
          </option>
        </select>
        <button
          @click="loadGraph"
          class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium"
        >
          Refresh Graph
        </button>
      </div>
    </div>

    <!-- Network Graph Canvas -->
    <div class="relative w-full h-[600px] bg-slate-950 rounded-2xl border border-slate-800 p-6 overflow-hidden shadow-2xl flex flex-col justify-between">
      <!-- Legend Overlay -->
      <div class="flex items-center gap-4 text-xs z-10 bg-slate-900/80 px-4 py-2 rounded-xl border border-slate-800 backdrop-blur-sm self-start">
        <div class="flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
          <span class="text-slate-300">Company Entity</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
          <span class="text-slate-300">Vessel Asset</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-full bg-purple-500"></span>
          <span class="text-slate-300">Director / Officer</span>
        </div>
      </div>

      <!-- SVG Graph Visualizer -->
      <div v-if="loading" class="flex-1 flex items-center justify-center text-xs text-slate-400">
        Constructing corporate graph topology...
      </div>
      <div v-else-if="nodes.length === 0" class="flex-1 flex items-center justify-center text-xs text-slate-400">
        No graph data available. Select a company to view relationships.
      </div>
      <div v-else class="flex-1 relative overflow-auto flex items-center justify-center p-8">
        <!-- Render Visual Topology in Grid / Node Cloud -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center w-full max-w-4xl">
          <!-- Left Column: Parent / Holding -->
          <div class="space-y-4">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider text-center">HOLDING & OFFSHORE UBO</div>
            <div
              v-for="n in holdingNodes"
              :key="n.id"
              class="p-4 rounded-xl bg-slate-900 border border-cyan-500/40 text-center shadow-lg hover:border-cyan-400 transition-all cursor-pointer"
              @click="activeNode = n"
            >
              <div class="w-8 h-8 rounded-full bg-cyan-500/20 text-cyan-300 flex items-center justify-center mx-auto mb-2 text-sm font-bold">
                🏢
              </div>
              <div class="font-bold text-white text-xs">{{ n.label }}</div>
              <div class="text-[10px] text-cyan-400 font-mono mt-0.5">{{ n.country || 'Offshore' }}</div>
            </div>
          </div>

          <!-- Middle Column: Operating Entity & Officers -->
          <div class="space-y-4">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider text-center">OPERATING COMPANY & DIRECTORS</div>
            <div
              v-for="n in operatingNodes"
              :key="n.id"
              class="p-4 rounded-xl bg-slate-900 border-2 border-cyan-500 text-center shadow-xl hover:scale-105 transition-all cursor-pointer"
              @click="activeNode = n"
            >
              <div class="w-10 h-10 rounded-full bg-cyan-500/30 text-cyan-300 flex items-center justify-center mx-auto mb-2 text-base font-bold">
                🏢
              </div>
              <div class="font-bold text-white text-sm">{{ n.label }}</div>
              <div class="text-[11px] text-emerald-400 font-mono mt-0.5">Primary Target</div>
            </div>

            <!-- Officers under operating company -->
            <div
              v-for="n in officerNodes"
              :key="n.id"
              class="p-3 rounded-xl bg-slate-900/80 border border-purple-500/40 text-center shadow hover:border-purple-400 transition-all cursor-pointer"
              @click="activeNode = n"
            >
              <div class="text-xs font-semibold text-purple-300">{{ n.label }}</div>
              <div class="text-[10px] text-slate-400">{{ n.position }}</div>
            </div>
          </div>

          <!-- Right Column: Vessel Fleet -->
          <div class="space-y-4">
            <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider text-center">OWNED & OPERATED FLEET</div>
            <div
              v-for="n in vesselNodes"
              :key="n.id"
              class="p-4 rounded-xl bg-slate-900 border border-emerald-500/40 text-center shadow-lg hover:border-emerald-400 transition-all cursor-pointer"
              @click="activeNode = n"
            >
              <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center mx-auto mb-2 text-sm font-bold">
                🚢
              </div>
              <div class="font-bold text-white text-xs">{{ n.label }}</div>
              <div class="text-[10px] text-emerald-400 font-mono mt-0.5">
                IMO: {{ n.imo || 'N/A' }} • MMSI: {{ n.mmsi || 'N/A' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Node Detail Box -->
      <div v-if="activeNode" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs flex items-center justify-between z-10 backdrop-blur-sm">
        <div>
          <span class="font-bold text-white">{{ activeNode.label }}</span>
          <span class="text-slate-400 ml-2 font-mono text-[11px]">Type: {{ activeNode.type.toUpperCase() }}</span>
        </div>
        <button @click="activeNode = null" class="text-slate-400 hover:text-white">✕</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/client';

const route = useRoute();
const companyList = ref<any[]>([]);
const selectedCompanyId = ref('');
const loading = ref(false);

const nodes = ref<any[]>([]);
const edges = ref<any[]>([]);
const activeNode = ref<any>(null);

const holdingNodes = computed(() => nodes.value.filter(n => n.type === 'company' && n.id !== selectedCompanyId.value));
const operatingNodes = computed(() => nodes.value.filter(n => n.type === 'company' && n.id === selectedCompanyId.value));
const officerNodes = computed(() => nodes.value.filter(n => n.type === 'officer'));
const vesselNodes = computed(() => nodes.value.filter(n => n.type === 'vessel'));

async function loadCompanies() {
  try {
    const res = await apiClient.get('/companies?per_page=50');
    companyList.value = res.data.data;
    if (companyList.value.length > 0) {
      selectedCompanyId.value = (route.query.id as string) || companyList.value[0].id;
      loadGraph();
    }
  } catch (err) {
    console.error('Failed to load companies for graph', err);
  }
}

async function loadGraph() {
  if (!selectedCompanyId.value) return;
  loading.value = true;
  try {
    const res = await apiClient.get(`/companies/network-graph?type=company&id=${selectedCompanyId.value}`);
    nodes.value = res.data.nodes;
    edges.value = res.data.edges;
  } catch (err) {
    console.error('Failed to load corporate graph', err);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadCompanies();
});
</script>
