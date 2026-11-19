<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Maritime Data Validation & Pre-flight Engine</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300">
            RULES ENGINE
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Deterministic IMO modulo-10 checksum calculator, ITU 9-digit MMSI MID decoder, and pre-flight dry-run testing suite.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <router-link
          to="/imports"
          class="px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors shadow-lg shadow-cyan-950/40 flex items-center gap-1.5"
        >
          <span>📦</span>
          <span>Open Ingestion Pipelines</span>
        </router-link>
      </div>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
      <button
        @click="activeTab = 'single'"
        class="px-4 py-2 rounded-lg text-xs font-medium transition-colors flex items-center gap-2 font-mono"
        :class="activeTab === 'single' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-950/50' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
      >
        <span>🔍</span>
        <span>Single Identifier Live Inspector</span>
      </button>

      <button
        @click="activeTab = 'batch'"
        class="px-4 py-2 rounded-lg text-xs font-medium transition-colors flex items-center gap-2 font-mono"
        :class="activeTab === 'batch' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-950/50' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
      >
        <span>🧪</span>
        <span>Batch Pre-flight Dry Run Sandbox</span>
      </button>
    </div>

    <!-- TAB 1: Single Identifier Live Inspector -->
    <div v-if="activeTab === 'single'" class="space-y-6">
      <!-- 1. IMO Checksum Calculator -->
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base font-bold text-white">IMO 7-Digit Modulo-10 Checksum Calculator</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
              SOLAS ALGORITHM
            </span>
          </div>
          <span class="text-xs font-mono text-slate-400">Formula: Check Digit = (Σ d[i] × (8 - i)) mod 10</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-1 space-y-3">
            <label class="block text-slate-400 text-xs font-mono">Test IMO Number Input:</label>
            <div class="relative">
              <input
                v-model="imoInput"
                @input="evaluateImo"
                type="text"
                placeholder="e.g. IMO 9074729 or 9235335"
                class="w-full px-3 py-2.5 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono text-sm focus:outline-none focus:border-cyan-500"
              />
            </div>
            <div class="flex items-center gap-2 text-[10px] font-mono text-slate-500">
              <span>Quick tests:</span>
              <button @click="setImo('9074729')" class="text-cyan-400 hover:underline">9074729 (Valid)</button>
              <button @click="setImo('9235335')" class="text-cyan-400 hover:underline">9235335 (Valid)</button>
              <button @click="setImo('9074721')" class="text-red-400 hover:underline">9074721 (Invalid)</button>
            </div>
          </div>

          <div class="md:col-span-2">
            <!-- IMO Result Card -->
            <div
              class="p-4 rounded-xl border font-mono text-xs space-y-3"
              :class="{
                'bg-emerald-950/20 border-emerald-800 text-emerald-300': imoResult?.is_valid,
                'bg-red-950/20 border-red-800 text-red-300': imoResult && !imoResult.is_valid,
                'bg-slate-950 border-slate-800 text-slate-400': !imoResult,
              }"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-sm">
                  {{ imoResult ? (imoResult.is_valid ? '✅ VALID IMO CHECKSUM' : '❌ INVALID / FORGED IMO') : 'ENTER 7 DIGITS' }}
                </span>
                <span v-if="imoResult" class="text-xs">
                  Cleaned: {{ imoResult.cleaned_imo || 'N/A' }}
                </span>
              </div>

              <!-- Mathematical breakdown steps if 7 digits -->
              <div v-if="imoSteps.length === 6" class="p-3 rounded-lg bg-slate-900 border border-slate-800 space-y-2">
                <div class="text-[10px] text-slate-400 uppercase font-semibold">Weighted Multiplications:</div>
                <div class="grid grid-cols-6 gap-1 text-center text-[10px]">
                  <div v-for="s in imoSteps" :key="s.pos" class="p-1 rounded bg-slate-950 border border-slate-800">
                    <div class="text-slate-500">Pos {{ s.pos }}</div>
                    <div class="text-white font-bold">{{ s.digit }} × {{ s.weight }}</div>
                    <div class="text-cyan-400">= {{ s.product }}</div>
                  </div>
                </div>
                <div class="flex items-center justify-between text-[11px] pt-1 text-slate-300 border-t border-slate-800">
                  <span>Sum of Products: <strong class="text-white">{{ imoSum }}</strong></span>
                  <span>Modulo 10: <strong class="text-cyan-400">{{ imoSum }} % 10 = {{ imoExpected }}</strong></span>
                  <span>Actual 7th Digit: <strong :class="imoExpected === imoActual ? 'text-emerald-400' : 'text-red-400'">{{ imoActual }}</strong></span>
                </div>
              </div>

              <p class="text-[11px]">{{ imoResult?.reason || 'Type an IMO number to inspect verification steps.' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. MMSI & ITU MID Decoder -->
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base font-bold text-white">9-Digit MMSI & ITU Maritime Identification Digits (MID)</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
              ITU RADIO REGULATIONS
            </span>
          </div>
          <span class="text-xs font-mono text-slate-400">9 Digits (MID: Digits 1-3)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-1 space-y-3">
            <label class="block text-slate-400 text-xs font-mono">Test MMSI Input:</label>
            <input
              v-model="mmsiInput"
              @input="evaluateMmsi"
              type="text"
              placeholder="e.g. 525123456 (Indonesia) or 351987654 (Panama)"
              class="w-full px-3 py-2.5 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono text-sm focus:outline-none focus:border-cyan-500"
            />
            <div class="flex items-center gap-2 text-[10px] font-mono text-slate-500">
              <span>Quick tests:</span>
              <button @click="setMmsi('525000001')" class="text-cyan-400 hover:underline">525 (Indonesia)</button>
              <button @click="setMmsi('351987654')" class="text-cyan-400 hover:underline">351 (Panama)</button>
              <button @click="setMmsi('412888999')" class="text-cyan-400 hover:underline">412 (China)</button>
            </div>
          </div>

          <div class="md:col-span-2">
            <div
              class="p-4 rounded-xl border font-mono text-xs space-y-2"
              :class="{
                'bg-emerald-950/20 border-emerald-800 text-emerald-300': mmsiResult?.is_valid,
                'bg-red-950/20 border-red-800 text-red-300': mmsiResult && !mmsiResult.is_valid,
                'bg-slate-950 border-slate-800 text-slate-400': !mmsiResult,
              }"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-sm">
                  {{ mmsiResult ? (mmsiResult.is_valid ? '✅ VALID 9-DIGIT MMSI' : '❌ INVALID MMSI FORMAT') : 'ENTER 9 DIGITS' }}
                </span>
                <span v-if="mmsiResult?.mid" class="text-xs">
                  MID Code: <strong class="text-white">{{ mmsiResult.mid }}</strong>
                </span>
              </div>

              <div v-if="mmsiResult?.is_valid" class="p-3 rounded-lg bg-slate-900 border border-slate-800 space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-400">Allocated Flag Administration:</span>
                  <span class="text-cyan-400 font-bold">{{ mmsiResult.country_name }}</span>
                </div>
                <div class="text-[10px] text-slate-500">
                  Station Type: Ship Station (starts with non-zero single digit MID)
                </div>
              </div>

              <p class="text-[11px]">{{ mmsiResult?.reason || 'Enter a 9-digit MMSI to decode Maritime Identification Digits.' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Batch Pre-flight Dry Run Sandbox -->
    <div v-if="activeTab === 'batch'" class="space-y-6">
      <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base font-bold text-white">Pre-flight Batch Dry Run Simulator</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-purple-950 text-purple-300 border border-purple-800">
              ZERO DATABASE MUTATION
            </span>
          </div>
          <span class="text-xs font-mono text-slate-400">Safely analyze files before committing</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
          <div class="space-y-3">
            <label class="block text-slate-300 font-bold">Input JSON Records Array:</label>
            <textarea
              v-model="dryRunInput"
              rows="10"
              class="w-full p-3 rounded-lg bg-slate-950 border border-slate-800 text-cyan-300 font-mono text-xs focus:outline-none focus:border-cyan-500"
            ></textarea>

            <button
              @click="executeDryRun"
              :disabled="loadingDryRun"
              class="w-full py-2.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold transition-colors shadow-lg shadow-cyan-950/40"
            >
              {{ loadingDryRun ? 'Analyzing Batch...' : '⚡ Execute Pre-flight Analysis' }}
            </button>
          </div>

          <!-- Dry Run Results -->
          <div>
            <label class="block text-slate-300 font-bold mb-3">Simulation Summary & Breakdown:</label>

            <div v-if="!dryRunResult" class="p-12 text-center text-xs text-slate-500 border border-slate-800 rounded-xl bg-slate-950">
              Paste a dataset above and click "Execute Pre-flight Analysis" to simulate validation results.
            </div>

            <div v-else class="space-y-4">
              <!-- KPI Cards -->
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                  <span class="text-[9px] text-slate-500 block uppercase">Total Rows</span>
                  <span class="text-lg font-bold text-white">{{ dryRunResult.summary.total_analyzed }}</span>
                </div>
                <div class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-900/60">
                  <span class="text-[9px] text-emerald-400 block uppercase">Clean Rows</span>
                  <span class="text-lg font-bold text-emerald-300">{{ dryRunResult.summary.valid_count }}</span>
                </div>
                <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-900/60">
                  <span class="text-[9px] text-amber-400 block uppercase">Quarantine Rows</span>
                  <span class="text-lg font-bold text-amber-300">{{ dryRunResult.summary.invalid_count }}</span>
                </div>
              </div>

              <!-- Failure Reasons Breakdown -->
              <div v-if="dryRunResult.summary.invalid_count > 0" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Quarantine Reasons Identified:</span>
                <div v-for="(count, reason) in dryRunResult.reasons_summary" :key="reason" class="flex items-center justify-between text-xs">
                  <span class="text-red-300 truncate max-w-[280px]">{{ reason }}</span>
                  <span class="px-2 py-0.5 rounded bg-red-950 text-red-400 font-bold">{{ count }} rows</span>
                </div>
              </div>

              <!-- Sample Quarantined Rows -->
              <div v-if="dryRunResult.sample_quarantined.length > 0" class="space-y-2">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Sample Rows Flagged for Quarantine:</span>
                <div
                  v-for="s in dryRunResult.sample_quarantined"
                  :key="s.row_index"
                  class="p-2.5 rounded-lg bg-slate-950 border border-amber-900/40 text-[11px] space-y-1"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-amber-400 font-bold">Row #{{ s.row_index }}</span>
                    <span class="text-red-400 text-[10px]">{{ s.error_message }}</span>
                  </div>
                  <pre class="text-[10px] text-slate-400 truncate">{{ JSON.stringify(s.data) }}</pre>
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
import { ref } from 'vue';
import apiClient from '@/api/client';

const activeTab = ref<'single' | 'batch'>('single');

// IMO
const imoInput = ref('IMO 9074729');
const imoResult = ref<any>(null);
const imoSteps = ref<any[]>([]);
const imoSum = ref(0);
const imoExpected = ref(0);
const imoActual = ref(0);

// MMSI
const mmsiInput = ref('525123456');
const mmsiResult = ref<any>(null);

// Batch Dry Run
const dryRunInput = ref(`[
  {"name": "KM LAUT CERIA 01", "imo": "9074729", "mmsi": "525123456", "flag": "Indonesia"},
  {"name": "KM PACIFIC 02", "imo": "9235335", "mmsi": "525000999", "flag": "Indonesia"},
  {"name": "SUSPECT VESSEL 03", "imo": "9074721", "mmsi": "351000111", "flag": "Panama"},
  {"name": "MALFORMED ENTRY", "imo": "123", "mmsi": "99"}
]`);
const loadingDryRun = ref(false);
const dryRunResult = ref<any>(null);

// Evaluate IMO on init
evaluateImo();
evaluateMmsi();

function setImo(val: string) {
  imoInput.value = val;
  evaluateImo();
}

async function evaluateImo() {
  const digits = imoInput.value.replace(/\D/g, '');
  if (digits.length === 7) {
    let sum = 0;
    const steps = [];
    for (let i = 0; i < 6; i++) {
      const weight = 8 - (i + 1);
      const digit = parseInt(digits[i], 10);
      const prod = digit * weight;
      sum += prod;
      steps.push({ pos: i + 1, digit, weight, product: prod });
    }
    imoSteps.value = steps;
    imoSum.value = sum;
    imoExpected.value = sum % 10;
    imoActual.value = parseInt(digits[6], 10);
  } else {
    imoSteps.value = [];
  }

  try {
    const res = await apiClient.post('/validation/validate-identifier', {
      type: 'imo',
      value: imoInput.value,
    });
    imoResult.value = res.data.result;
  } catch (err: any) {
    imoResult.value = { is_valid: false, reason: err.response?.data?.message || 'Error checking IMO' };
  }
}

function setMmsi(val: string) {
  mmsiInput.value = val;
  evaluateMmsi();
}

async function evaluateMmsi() {
  try {
    const res = await apiClient.post('/validation/validate-identifier', {
      type: 'mmsi',
      value: mmsiInput.value,
    });
    mmsiResult.value = res.data.result;
  } catch (err: any) {
    mmsiResult.value = { is_valid: false, reason: err.response?.data?.message || 'Error checking MMSI' };
  }
}

async function executeDryRun() {
  loadingDryRun.value = true;
  try {
    const parsedRows = JSON.parse(dryRunInput.value);
    const res = await apiClient.post('/validation/preview', {
      target_entity_type: 'vessel',
      rows: parsedRows,
    });
    dryRunResult.value = res.data;
  } catch (err: any) {
    alert(err.response?.data?.message || 'Pre-flight check failed. Please check JSON syntax.');
  } finally {
    loadingDryRun.value = false;
  }
}
</script>
