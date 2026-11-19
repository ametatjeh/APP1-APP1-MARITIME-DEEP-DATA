<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Interactive Corporate & Vessel Network Graph Canvas</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            MULTI-HOP TOPOLOGY
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Dynamic relationship traversal mapping offshore holdings, beneficial owners, managing operators, skippers, and IUU sanctions.
        </p>
      </div>

      <!-- Controls & Seed Switcher -->
      <div class="flex items-center gap-2 flex-wrap">
        <select
          v-model="seedType"
          @change="loadCandidates"
          class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 text-xs font-mono focus:border-cyan-500"
        >
          <option value="">Global Fleet Network</option>
          <option value="company">Seed: Company</option>
          <option value="vessel">Seed: Vessel</option>
          <option value="person">Seed: Person / UBO</option>
        </select>

        <select
          v-if="seedType"
          v-model="seedId"
          @change="loadGraph"
          class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 text-xs font-mono focus:border-cyan-500 max-w-[220px]"
        >
          <option value="">Select Seed Target</option>
          <option v-for="c in candidates" :key="c.id" :value="c.id">
            {{ c.label }}
          </option>
        </select>

        <select
          v-if="seedType && seedId"
          v-model="traversalDepth"
          @change="loadGraph"
          class="px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-200 text-xs font-mono focus:border-cyan-500"
        >
          <option :value="1">1 Hop</option>
          <option :value="2">2 Hops</option>
          <option :value="3">3 Hops</option>
        </select>

        <button
          @click="loadGraph"
          :disabled="loading"
          class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors"
        >
          {{ loading ? 'Traversing...' : '🔄 Refresh' }}
        </button>
      </div>
    </div>

    <!-- Network Canvas Container -->
    <div class="relative w-full min-h-[620px] bg-slate-950 rounded-2xl border border-slate-800 p-6 overflow-hidden shadow-2xl flex flex-col justify-between">
      <!-- Legend & Statistics Bar -->
      <div class="flex items-center justify-between flex-wrap gap-3 z-10 bg-slate-900/90 px-4 py-2.5 rounded-xl border border-slate-800 backdrop-blur-sm">
        <div class="flex items-center gap-4 text-xs font-mono">
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>
            <span class="text-slate-300">Company ({{ companyCount }})</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span class="text-slate-300">Vessel Asset ({{ vesselCount }})</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
            <span class="text-slate-300">Person / UBO ({{ personCount }})</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-red-400 animate-pulse"></span>
            <span class="text-red-300">Sanctioned / IUU ({{ sanctionedCount }})</span>
          </div>
        </div>

        <div class="text-[11px] font-mono text-cyan-400">
          {{ nodes.length }} Nodes • {{ edges.length }} Relational Edges
        </div>
      </div>

      <!-- Graph Topology View -->
      <div v-if="loading" class="flex-1 flex flex-col items-center justify-center text-xs text-slate-400 font-mono">
        <div class="inline-block animate-spin w-6 h-6 border-2 border-cyan-500 border-t-transparent rounded-full mb-2"></div>
        <div>Traversing entity relationship graph...</div>
      </div>

      <div v-else-if="nodes.length === 0" class="flex-1 flex items-center justify-center text-xs text-slate-400 font-mono">
        No graph nodes available. Select an entity or seed above to explore relationship networks.
      </div>

      <div v-else class="flex-1 my-6 overflow-auto">
        <!-- Interactive Nodes Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-2">
          <div
            v-for="n in nodes"
            :key="n.id"
            @click="selectNode(n)"
            class="p-4 rounded-xl border transition-all cursor-pointer shadow-lg flex flex-col justify-between"
            :class="{
              'bg-red-950/30 border-red-700 hover:border-red-500': n.is_sanctioned || n.risk_level === 'critical',
              'bg-amber-950/20 border-amber-800 hover:border-amber-600': n.risk_level === 'high',
              'bg-slate-900/90 border-slate-800 hover:border-cyan-500': n.risk_level === 'normal' || !n.risk_level,
              'ring-2 ring-cyan-400': activeNode?.id === n.id,
            }"
          >
            <div>
              <div class="flex items-start justify-between gap-2">
                <span class="text-xl">
                  {{ n.type === 'vessel' ? '🚢' : (n.type === 'company' ? '🏢' : '👤') }}
                </span>
                <span
                  class="px-1.5 py-0.5 rounded text-[9px] font-mono uppercase font-bold border"
                  :class="{
                    'bg-red-950 text-red-300 border-red-800 animate-pulse': n.is_sanctioned,
                    'bg-cyan-950 text-cyan-300 border-cyan-800': n.type === 'company',
                    'bg-emerald-950 text-emerald-300 border-emerald-800': n.type === 'vessel',
                    'bg-purple-950 text-purple-300 border-purple-800': n.type === 'person',
                  }"
                >
                  {{ n.is_sanctioned ? 'IUU SANCTION' : n.type }}
                </span>
              </div>

              <h5 class="text-xs font-bold text-white mt-2 line-clamp-1 font-sans">{{ n.label }}</h5>
              <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ n.sub_label || 'Entity' }}</p>
            </div>

            <!-- Outgoing / Connected edges snippet -->
            <div class="mt-3 pt-2 border-t border-slate-800/80 text-[10px] font-mono text-slate-500 flex items-center justify-between">
              <span>{{ getNodeConnectionsCount(n.id) }} connections</span>
              <span class="text-cyan-400 hover:underline">Inspect →</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Node Inspector Drawer Footer -->
      <div
        v-if="activeNode"
        class="p-4 rounded-xl bg-slate-900 border border-cyan-800 z-10 backdrop-blur-md flex flex-col md:flex-row md:items-center justify-between gap-4 font-mono text-xs"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="font-bold text-white text-sm font-sans">{{ activeNode.label }}</span>
            <span class="px-2 py-0.5 rounded text-[10px] uppercase bg-slate-800 text-cyan-300 border border-slate-700">
              {{ activeNode.type }}
            </span>
            <span
              v-if="activeNode.is_sanctioned"
              class="px-2 py-0.5 rounded text-[10px] uppercase bg-red-950 text-red-300 border border-red-800"
            >
              IUU SANCTIONED
            </span>
          </div>
          <div class="text-slate-400 text-[11px]">
            {{ activeNode.sub_label }} • Entity ID: {{ activeNode.entity_id }}
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="expandFromNode(activeNode)"
            class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium text-xs transition-colors"
          >
            🕸️ Expand Network from This Node
          </button>
          <router-link
            :to="getNodeEntityUrl(activeNode)"
            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs transition-colors"
          >
            Open Master Record
          </router-link>
          <button @click="activeNode = null" class="text-slate-400 hover:text-white px-2 py-1 text-sm">&times;</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/client';

const route = useRoute();

const seedType = ref<string>('');
const seedId = ref<string>('');
const traversalDepth = ref<number>(2);
const candidates = ref<any[]>([]);

const nodes = ref<any[]>([]);
const edges = ref<any[]>([]);
const activeNode = ref<any>(null);
const loading = ref(false);

const companyCount = computed(() => nodes.value.filter(n => n.type === 'company').length);
const vesselCount = computed(() => nodes.value.filter(n => n.type === 'vessel').length);
const personCount = computed(() => nodes.value.filter(n => n.type === 'person').length);
const sanctionedCount = computed(() => nodes.value.filter(n => n.is_sanctioned).length);

onMounted(() => {
  if (route.query.type && route.query.id) {
    seedType.value = route.query.type as string;
    seedId.value = route.query.id as string;
  }
  loadCandidates();
  loadGraph();
});

async function loadCandidates() {
  if (!seedType.value) {
    candidates.value = [];
    seedId.value = '';
    return;
  }

  try {
    if (seedType.value === 'company') {
      const res = await apiClient.get('/companies?per_page=50');
      candidates.value = (res.data.data || []).map((c: any) => ({
        id: c.id,
        label: c.company_name,
      }));
    } else if (seedType.value === 'vessel') {
      const res = await apiClient.get('/vessels?per_page=50');
      candidates.value = (res.data.data || []).map((v: any) => ({
        id: v.id,
        label: `${v.current_name} (IMO: ${v.imo_number || 'N/A'})`,
      }));
    } else if (seedType.value === 'person') {
      const res = await apiClient.get('/persons?per_page=50');
      candidates.value = (res.data.data || []).map((p: any) => ({
        id: p.id,
        label: p.masked_name || p.full_name,
      }));
    }
  } catch (err) {
    console.error('Failed to load seed candidates', err);
  }
}

async function loadGraph() {
  loading.value = true;
  activeNode.value = null;
  try {
    const params: any = {};
    if (seedType.value && seedId.value) {
      params.seed_type = seedType.value;
      params.seed_id = seedId.value;
      params.depth = traversalDepth.value;
    }

    const res = await apiClient.get('/network/graph', { params });
    nodes.value = res.data.nodes || [];
    edges.value = res.data.edges || [];
  } catch (err) {
    console.error('Failed to load network graph', err);
  } finally {
    loading.value = false;
  }
}

function selectNode(n: any) {
  activeNode.value = n;
}

function expandFromNode(n: any) {
  seedType.value = n.type;
  seedId.value = n.entity_id;
  loadCandidates();
  loadGraph();
}

function getNodeConnectionsCount(nodeId: string): number {
  return edges.value.filter(e => e.from === nodeId || e.to === nodeId).length;
}

function getNodeEntityUrl(n: any): string {
  if (n.type === 'vessel') return `/vessels?highlight=${n.entity_id}`;
  if (n.type === 'company') return `/corporate?highlight=${n.entity_id}`;
  if (n.type === 'person') return `/persons?highlight=${n.entity_id}`;
  return '/dashboard';
}
</script>
