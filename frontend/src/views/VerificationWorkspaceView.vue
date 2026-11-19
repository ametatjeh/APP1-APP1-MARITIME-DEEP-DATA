<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-base font-bold text-white">Verification Queue & Review Workspace</h3>
          <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-purple-950 border border-purple-800 text-purple-300">
            ANALYST WORKSPACE
          </span>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">
          Pending intelligence review queue. Unverified claims are quarantined until corroborated with formal written findings.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="fetchQueue"
          class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono transition-colors border border-slate-700 flex items-center gap-1.5"
        >
          <span>🔄</span>
          <span>Refresh Queue</span>
        </button>
      </div>
    </div>

    <!-- Verification Rules Banner -->
    <div class="p-4 rounded-xl bg-purple-950/30 border border-purple-900/60 flex items-start gap-3 text-xs">
      <span class="text-xl">⚖️</span>
      <div class="space-y-1">
        <h4 class="font-bold text-purple-200">Mandatory Verification Standard</h4>
        <p class="text-slate-300 leading-relaxed text-[11px]">
          Under NGO research protocol, no intelligence or vessel record may transition to <strong class="text-emerald-400">VERIFIED</strong> without a recorded reviewer, timestamp, and substantive analytical conclusion (min. 15 characters). Contradictory evidence must be explicitly tagged as <strong class="text-rose-400">CONFLICTING</strong>.
        </p>
      </div>
    </div>

    <!-- Queue List -->
    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
      <div v-if="loading" class="py-12 text-center text-xs text-slate-400">Loading pending verification items...</div>
      <div v-else-if="queueItems.length === 0" class="py-12 text-center text-xs text-slate-400">
        ✨ No pending items awaiting verification in the queue.
      </div>
      <div v-else class="divide-y divide-slate-800">
        <div
          v-for="item in queueItems"
          :key="item.id"
          class="p-4 hover:bg-slate-800/40 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div class="space-y-1.5 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                {{ item.type || 'EVIDENCE' }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold" :class="statusBadgeClass(item.status)">
                {{ item.status }}
              </span>
              <span class="text-xs font-bold text-white">{{ item.title }}</span>
            </div>

            <!-- Identifiers & Hashes -->
            <div v-if="item.sha256" class="text-[11px] font-mono text-slate-400 flex items-center gap-2">
              <span class="text-cyan-400 font-semibold">SHA-256:</span>
              <span class="truncate max-w-lg bg-slate-950 px-2 py-0.5 rounded border border-slate-800">{{ item.sha256 }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-500 font-mono">
              <span>Originating Source: <strong class="text-slate-300">{{ item.source_name || 'Direct Upload' }}</strong></span>
              <span>Queued At: <span class="text-slate-400">{{ formatDate(item.created_at) }}</span></span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start md:self-center">
            <button
              v-if="authStore.canEdit"
              @click="openReviewModal(item)"
              class="px-3.5 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-medium transition-colors shadow-lg shadow-purple-950/40 flex items-center gap-1.5"
            >
              <span>⚖️</span>
              <span>Review Item</span>
            </button>
            <router-link
              :to="`/evidence/${item.id}`"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700"
            >
              Inspect
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div
      v-if="showReviewModal"
      class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
        <div class="border-b border-slate-800 pb-3">
          <h4 class="text-sm font-bold text-white">Verify Intelligence Record</h4>
          <p class="text-[11px] text-slate-400 truncate">{{ activeItem?.title }}</p>
        </div>

        <form @submit.prevent="submitReview" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Verification Decision *</label>
            <select
              v-model="reviewForm.decision"
              required
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white font-medium focus:outline-none focus:border-cyan-500"
            >
              <option value="verified">Verified (Confirmed authentic by primary evidence)</option>
              <option value="under_review">Under Review (Requires further corroboration)</option>
              <option value="needs_additional_evidence">Needs Additional Evidence</option>
              <option value="conflicting">Conflicting (Directly contradicts official records)</option>
              <option value="rejected">Rejected (Disproven or unreliable)</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">
              Substantive Written Conclusion *
              <span class="text-slate-500">(Min. 15 characters)</span>
            </label>
            <textarea
              v-model="reviewForm.conclusion"
              required
              minlength="15"
              rows="4"
              placeholder="State the analytical findings, corroborating databases, or contradictory facts..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white focus:outline-none focus:border-cyan-500"
            ></textarea>
            <span class="text-[10px] text-slate-500 font-mono">
              {{ reviewForm.conclusion.length }} / 15 characters minimum
            </span>
          </div>

          <div v-if="reviewError" class="p-2.5 rounded bg-red-950/50 border border-red-800 text-red-300 text-xs">
            {{ reviewError }}
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showReviewModal = false"
              class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting || reviewForm.conclusion.length < 15"
              class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-medium transition-colors disabled:opacity-50"
            >
              {{ submitting ? 'Submitting...' : 'Commit Decision' }}
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

const queueItems = ref<any[]>([]);
const loading = ref(false);

const showReviewModal = ref(false);
const activeItem = ref<any>(null);
const submitting = ref(false);
const reviewError = ref('');

const reviewForm = ref({
  decision: 'verified',
  conclusion: '',
});

async function fetchQueue() {
  loading.value = true;
  try {
    const res = await apiClient.get('/verification/queue');
    queueItems.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load verification queue', err);
  } finally {
    loading.value = false;
  }
}

function openReviewModal(item: any) {
  activeItem.value = item;
  reviewForm.value = {
    decision: 'verified',
    conclusion: '',
  };
  reviewError.value = '';
  showReviewModal.value = true;
}

async function submitReview() {
  if (!activeItem.value) return;

  submitting.value = true;
  reviewError.value = '';

  try {
    await apiClient.post('/verification/review', {
      verifiable_type: activeItem.value.type || 'evidence',
      verifiable_id: activeItem.value.id,
      decision: reviewForm.value.decision,
      conclusion: reviewForm.value.conclusion,
    });
    showReviewModal.value = false;
    await fetchQueue();
  } catch (err: any) {
    reviewError.value = err.response?.data?.message || 'Failed to submit review.';
  } finally {
    submitting.value = false;
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

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(() => {
  fetchQueue();
});
</script>
