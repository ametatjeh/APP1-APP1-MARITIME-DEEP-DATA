<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-bold text-white">Vessel Identity Intelligence & Conflict Resolution</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Detects identity cloning, recycled callsigns, and flag hopping. Automatic merging is strictly prohibited.
        </p>
      </div>
    </div>

    <!-- Conflict Resolution Desk -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 p-5 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <div class="flex items-center gap-2">
          <span class="text-amber-400">⚠️</span>
          <h4 class="text-sm font-bold text-white">Detected Identifier Conflicts & Spoofing Alerts</h4>
        </div>
        <span class="text-xs text-slate-400 font-mono">
          Total Conflicts: {{ conflicts.length }}
        </span>
      </div>

      <div v-if="loading" class="py-8 text-center text-xs text-slate-400">Loading conflict records...</div>
      <div v-else-if="conflicts.length === 0" class="py-8 text-center text-xs text-slate-400">
        No active identity conflicts detected.
      </div>
      <div v-else class="space-y-3">
        <div
          v-for="c in conflicts"
          :key="c.id"
          class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2 font-mono">
              <span class="px-2 py-0.5 rounded text-[10px] bg-red-950 text-red-300 border border-red-800/80 font-bold uppercase">
                {{ c.conflict_nature }}
              </span>
              <span class="text-xs text-white font-bold">
                {{ c.conflicting_identifier_type.toUpperCase() }}: {{ c.conflicting_identifier_value }}
              </span>
            </div>
            <div class="flex items-center gap-2 text-xs">
              <span class="text-slate-400">Conflict Threat Score:</span>
              <span class="font-mono font-bold text-amber-400">{{ c.conflict_score }}%</span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-mono border"
                :class="c.resolution_status === 'confirmed_distinct_vessels' ? 'bg-emerald-950 text-emerald-300 border-emerald-800' : 'bg-amber-950 text-amber-300 border-amber-800'"
              >
                {{ c.resolution_status }}
              </span>
            </div>
          </div>

          <!-- Vessel Comparison Side-by-Side -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <!-- Vessel A -->
            <div class="p-3 rounded-lg bg-slate-900/80 border border-slate-800/80 text-xs space-y-1">
              <div class="text-[10px] text-cyan-400 font-mono font-semibold uppercase">VESSEL A RECORD</div>
              <div class="font-bold text-white text-sm">{{ c.vessel_a?.current_name }}</div>
              <div class="text-slate-400">
                Flag: <span class="text-slate-200">{{ c.vessel_a?.current_flag_country?.name || 'Unknown' }}</span> • Type: <span class="capitalize text-slate-200">{{ c.vessel_a?.vessel_type || 'N/A' }}</span>
              </div>
              <div class="text-[11px] text-slate-400 font-mono">
                LOA: {{ c.vessel_a?.length_overall ? `${c.vessel_a.length_overall}m` : 'N/A' }} • GT: {{ c.vessel_a?.gross_tonnage ? `${c.vessel_a.gross_tonnage} GT` : 'N/A' }}
              </div>
            </div>

            <!-- Vessel B -->
            <div class="p-3 rounded-lg bg-slate-900/80 border border-slate-800/80 text-xs space-y-1">
              <div class="text-[10px] text-amber-400 font-mono font-semibold uppercase">VESSEL B RECORD (CONFLICTING)</div>
              <div class="font-bold text-white text-sm">{{ c.vessel_b?.current_name }}</div>
              <div class="text-slate-400">
                Flag: <span class="text-slate-200">{{ c.vessel_b?.current_flag_country?.name || 'Unknown' }}</span> • Type: <span class="capitalize text-slate-200">{{ c.vessel_b?.vessel_type || 'N/A' }}</span>
              </div>
              <div class="text-[11px] text-slate-400 font-mono">
                LOA: {{ c.vessel_b?.length_overall ? `${c.vessel_b.length_overall}m` : 'N/A' }} • GT: {{ c.vessel_b?.gross_tonnage ? `${c.vessel_b.gross_tonnage} GT` : 'N/A' }}
              </div>
            </div>
          </div>

          <!-- Existing Analyst Conclusion Note -->
          <div v-if="c.analyst_conclusion" class="p-3 rounded bg-slate-900 text-xs text-slate-300 border-l-2 border-cyan-500">
            <span class="font-bold text-cyan-300">Analyst Conclusion:</span> {{ c.analyst_conclusion }}
            <span v-if="c.reviewed_by_user" class="block text-[10px] text-slate-500 font-mono mt-1">
              Reviewed by: {{ c.reviewed_by_user.name }} at {{ c.reviewed_at }}
            </span>
          </div>

          <!-- Action Button -->
          <div class="flex justify-end pt-1">
            <button
              v-if="authStore.canResolveConflicts"
              @click="openResolveModal(c)"
              class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors"
            >
              Resolve / Review Conflict
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Conflict Resolution Modal -->
    <div v-if="activeConflictModal" class="fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4">
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
        <h4 class="text-base font-bold text-white">Investigative Resolution Verdict</h4>
        <p class="text-xs text-slate-400">
          Document analytical findings. Vessels will NOT be merged automatically. Both historical entities are retained in the master database.
        </p>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-300 font-medium mb-1">Resolution Status</label>
            <select
              v-model="resolutionStatusInput"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"
            >
              <option value="confirmed_distinct_vessels">Confirmed Distinct Vessels (Shared ID History / Spoofing)</option>
              <option value="under_review">Keep Under Active Review</option>
              <option value="confirmed_same_vessel_pending_merge">Confirmed Same Vessel (Pending Merge Authorization)</option>
              <option value="dismissed_noise">Dismiss as Input Error / Noise</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-300 font-medium mb-1">Mandatory Analyst Conclusion & Evidence Basis</label>
            <textarea
              v-model="analystConclusionInput"
              rows="4"
              placeholder="Detail reasons why these vessels are distinct or why the identifier was shared (e.g. flag hopping, shipyard reallocation, spoofing in WPPNRI 711)..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-600"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button
            @click="activeConflictModal = null"
            class="px-4 py-2 rounded-lg bg-slate-800 text-slate-300 text-xs font-medium hover:bg-slate-700"
          >
            Cancel
          </button>
          <button
            @click="submitResolution"
            :disabled="!analystConclusionInput || analystConclusionInput.length < 10"
            class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors disabled:opacity-50"
          >
            Submit Formal Resolution
          </button>
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
const conflicts = ref<any[]>([]);
const loading = ref(false);

const activeConflictModal = ref<any>(null);
const resolutionStatusInput = ref('confirmed_distinct_vessels');
const analystConclusionInput = ref('');

async function fetchConflicts() {
  loading.value = true;
  try {
    const res = await apiClient.get('/identity/conflicts');
    conflicts.value = res.data.data;
  } catch (err) {
    console.error('Failed to load conflicts', err);
  } finally {
    loading.value = false;
  }
}

function openResolveModal(c: any) {
  activeConflictModal.value = c;
  resolutionStatusInput.value = c.resolution_status || 'confirmed_distinct_vessels';
  analystConclusionInput.value = c.analyst_conclusion || '';
}

async function submitResolution() {
  if (!activeConflictModal.value) return;
  try {
    await apiClient.post(`/identity/conflicts/${activeConflictModal.value.id}/resolve`, {
      resolution_status: resolutionStatusInput.value,
      analyst_conclusion: analystConclusionInput.value,
    });
    activeConflictModal.value = null;
    fetchConflicts();
  } catch (err) {
    console.error('Failed to submit resolution', err);
  }
}

onMounted(() => {
  fetchConflicts();
});
</script>
