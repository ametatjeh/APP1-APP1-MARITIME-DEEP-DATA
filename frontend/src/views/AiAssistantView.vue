<template>
  <div class="h-[calc(100vh-8rem)] flex gap-6">
    <!-- Left Sidebar: Conversations & Intelligence Threads -->
    <div class="w-80 flex flex-col bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shrink-0">
      <!-- Sidebar Header -->
      <div class="p-4 border-b border-slate-800 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
            <span>🤖</span> AI Research Threads
          </h3>
          <p class="text-[10px] text-slate-400">Multi-Agent & RAG Engine</p>
        </div>
        <button
          @click="openNewInquiryModal"
          class="px-2.5 py-1 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium font-mono flex items-center gap-1 shadow-sm"
        >
          <span>+</span> New
        </button>
      </div>

      <!-- Sync Vectors & Knowledge Tool -->
      <div class="p-3 bg-slate-950/60 border-b border-slate-800/80 flex items-center justify-between">
        <span class="text-[11px] text-slate-400 font-mono">Vector Knowledge Base:</span>
        <button
          @click="syncVectors"
          :disabled="syncingVectors"
          class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-cyan-300 text-[10px] font-mono border border-slate-700 flex items-center gap-1"
        >
          <span v-if="syncingVectors" class="animate-spin">⏳</span>
          <span v-else>🔄</span>
          Sync
        </button>
      </div>

      <!-- Conversations List -->
      <div class="flex-1 overflow-y-auto divide-y divide-slate-800/50">
        <div v-if="loadingConversations" class="p-6 text-center text-xs text-slate-500">
          Loading inquiry threads...
        </div>
        <div v-else-if="conversations.length === 0" class="p-6 text-center text-xs text-slate-500">
          No inquiry sessions found. Start a new investigation thread.
        </div>
        <div
          v-for="conv in conversations"
          :key="conv.id"
          @click="selectConversation(conv.id)"
          :class="[
            'p-3.5 cursor-pointer transition text-xs relative group',
            activeConversation?.id === conv.id ? 'bg-cyan-950/40 border-l-2 border-cyan-400' : 'hover:bg-slate-800/50'
          ]"
        >
          <div class="flex items-start justify-between gap-2">
            <span class="font-medium text-slate-200 line-clamp-1 flex-1">{{ conv.title }}</span>
            <button
              @click.stop="deleteConversation(conv.id)"
              class="opacity-0 group-hover:opacity-100 text-slate-500 hover:text-red-400 text-[11px] transition"
              title="Delete session"
            >
              ✕
            </button>
          </div>

          <div class="flex items-center gap-2 mt-2">
            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono uppercase bg-slate-800 text-cyan-300">
              {{ conv.agent_mode }}
            </span>
            <span v-if="conv.context_type !== 'general'" class="px-1.5 py-0.2 rounded text-[9px] font-mono uppercase bg-amber-950/80 text-amber-300 border border-amber-800/40">
              {{ conv.context_type }}
            </span>
            <span class="text-[10px] text-slate-500 ml-auto font-mono">
              {{ conv.messages_count || 0 }} msgs
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Research Workspace & Message Stream -->
    <div class="flex-1 flex flex-col bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
      <!-- Workspace Header -->
      <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/90 backdrop-blur-sm">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-cyan-950 border border-cyan-800 flex items-center justify-center text-cyan-300 font-bold">
            🤖
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-sm font-bold text-white">
                {{ activeConversation?.title || 'Select or Start an Intelligence Inquiry' }}
              </h2>
              <span
                v-if="activeConversation"
                class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 border border-cyan-800 text-cyan-300"
              >
                MODE: {{ activeConversation.agent_mode.toUpperCase() }}
              </span>
            </div>
            <p class="text-[11px] text-slate-400">
              Grounding: PostGIS Boundaries • Master Vessels • Corporate UBOs • ILO Forced Labour • RFMO IUU Blacklists
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="openVectorSearchModal"
            class="px-3 py-1.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-cyan-300 text-xs font-mono flex items-center gap-1.5"
          >
            <span>🧭</span> Vector Cosine Search
          </button>
          <button
            @click="showRosterModal = true"
            class="px-3 py-1.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-mono flex items-center gap-1.5"
          >
            <span>👥</span> Agent Roster
          </button>
        </div>
      </div>

      <!-- Messages Stream -->
      <div ref="chatContainer" class="flex-1 p-6 overflow-y-auto space-y-6 bg-slate-950/40">
        <div v-if="!activeConversation" class="h-full flex flex-col items-center justify-center text-center p-8 space-y-4">
          <div class="w-16 h-16 rounded-2xl bg-cyan-950/40 border border-cyan-800/60 flex items-center justify-center text-3xl">
            🧠
          </div>
          <div class="max-w-md space-y-1">
            <h4 class="text-base font-bold text-white">Autonomous Maritime Research Assistant</h4>
            <p class="text-xs text-slate-400 leading-relaxed">
              Multi-agent investigation synthesis combining 8 domain agents. Formulates evidence-grounded hypotheses, surfaces unverified collisions, and strictly isolates leads from master data.
            </p>
          </div>
          <button
            @click="openNewInquiryModal"
            class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium font-mono"
          >
            Launch Intelligence Inquiry →
          </button>
        </div>

        <template v-else>
          <!-- Message history -->
          <div
            v-for="msg in activeConversation.messages"
            :key="msg.id"
            :class="[
              'flex flex-col space-y-2',
              msg.role === 'user' ? 'items-end' : 'items-start'
            ]"
          >
            <!-- Sender Header -->
            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400 px-1">
              <span v-if="msg.role === 'user'">👤 Investigator</span>
              <span v-else class="text-cyan-400 font-bold flex items-center gap-1">
                <span>🤖</span> {{ msg.agent_name || 'OrchestratorAgent' }}
              </span>
              <span>•</span>
              <span class="text-[10px] text-slate-500">{{ formatTimestamp(msg.created_at) }}</span>
            </div>

            <!-- Message Card -->
            <div
              :class="[
                'p-4 rounded-xl text-xs max-w-3xl leading-relaxed',
                msg.role === 'user'
                  ? 'bg-cyan-950/60 border border-cyan-800 text-white rounded-tr-none'
                  : 'bg-slate-900 border border-slate-800 text-slate-200 rounded-tl-none space-y-4 shadow-lg'
              ]"
            >
              <!-- Assistant Message Content -->
              <div class="whitespace-pre-wrap font-sans text-xs leading-relaxed space-y-2">
                {{ msg.content }}
              </div>

              <!-- Multi-Agent Reasoning Trace (Collapsible) -->
              <div v-if="msg.reasoning_trace && msg.reasoning_trace.steps" class="p-3 rounded-lg bg-slate-950 border border-slate-800/80 space-y-2">
                <div class="flex items-center justify-between text-[11px] font-mono text-cyan-400">
                  <span class="font-bold flex items-center gap-1">
                    <span>⚡</span> Multi-Agent Reasoning Trace ({{ msg.reasoning_trace.total_steps || msg.reasoning_trace.steps.length }} Steps)
                  </span>
                  <span class="text-[10px] text-slate-500">
                    {{ msg.reasoning_trace.agents_invoked?.join(', ') }}
                  </span>
                </div>
                <div class="space-y-1 text-[11px] font-mono text-slate-400 divide-y divide-slate-800/40">
                  <div v-for="step in msg.reasoning_trace.steps" :key="step.step" class="pt-1 flex items-start gap-2">
                    <span class="text-cyan-500 font-bold">[{{ step.step }}]</span>
                    <div class="flex-1">
                      <span v-if="step.agent" class="text-purple-300 font-semibold">{{ step.agent }}: </span>
                      <span>{{ step.action || step.details }}</span>
                      <span v-if="step.findings_count !== undefined" class="text-amber-400 ml-1">
                        ({{ step.findings_count }} findings)
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Evidentiary Citations Strip -->
              <div v-if="msg.citations && msg.citations.length > 0" class="p-3 rounded-lg bg-slate-950 border border-emerald-900/40 space-y-2">
                <div class="text-[11px] font-mono text-emerald-400 font-bold flex items-center gap-1">
                  <span>🛡️</span> Grounded Evidentiary Citations & Verification Chain
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] font-mono">
                  <div
                    v-for="(cit, idx) in msg.citations"
                    :key="idx"
                    class="p-2 rounded bg-slate-900/90 border border-slate-800 text-slate-300 flex flex-col justify-between"
                  >
                    <div class="font-semibold text-white truncate">{{ cit.title }}</div>
                    <div class="text-[10px] text-slate-400 flex items-center justify-between mt-1">
                      <span>Source: {{ cit.source_name }}</span>
                      <span class="text-emerald-400">SHA: {{ cit.sha256_prefix }}...</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending AI Investigation Findings Review Deck -->
          <div v-if="activeConversation.findings && activeConversation.findings.length > 0" class="p-4 rounded-xl bg-slate-900 border border-amber-900/60 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-base text-amber-400">⚖️</span>
                <h4 class="text-xs font-bold text-white uppercase tracking-wider font-mono">
                  Extracted AI Findings — Analyst Review Gate
                </h4>
              </div>
              <span class="text-[10px] font-mono text-slate-400">
                Rule: AI cannot alter master data without explicit human verification
              </span>
            </div>

            <div class="space-y-2">
              <div
                v-for="finding in activeConversation.findings"
                :key="finding.id"
                class="p-3 rounded-lg bg-slate-950 border border-slate-800 flex items-start justify-between gap-4 text-xs"
              >
                <div class="space-y-1 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-white">{{ finding.title }}</span>
                    <span
                      :class="[
                        'px-1.5 py-0.2 rounded text-[10px] font-mono uppercase font-bold',
                        finding.confidence_level === 'critical' ? 'bg-red-950 text-red-300 border border-red-800' :
                        finding.confidence_level === 'high' ? 'bg-amber-950 text-amber-300 border border-amber-800' :
                        'bg-slate-800 text-slate-300'
                      ]"
                    >
                      {{ finding.confidence_level }}
                    </span>
                    <span v-if="finding.is_verified_by_analyst" class="px-1.5 py-0.2 rounded text-[10px] font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
                      VERIFIED LEAD
                    </span>
                    <span v-else class="px-1.5 py-0.2 rounded text-[10px] font-mono bg-slate-800 text-amber-400">
                      PENDING REVIEW
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-400">{{ finding.summary }}</p>
                  <div v-if="finding.analyst_review_notes" class="text-[10px] font-mono text-cyan-400 mt-1">
                    Analyst Notes: {{ finding.analyst_review_notes }} (by {{ finding.reviewer?.name || 'Investigator' }})
                  </div>
                </div>

                <div v-if="!finding.is_verified_by_analyst" class="flex items-center gap-2 shrink-0">
                  <button
                    @click="openReviewModal(finding, true)"
                    class="px-2.5 py-1 rounded bg-emerald-900/60 hover:bg-emerald-800 text-emerald-300 text-[11px] font-mono border border-emerald-700/60"
                  >
                    Verify Lead
                  </button>
                  <button
                    @click="openReviewModal(finding, false)"
                    class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-400 text-[11px] font-mono"
                  >
                    Dismiss
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Chat Input Area -->
      <div v-if="activeConversation" class="p-4 border-t border-slate-800 bg-slate-900/90 space-y-3">
        <!-- Prompt suggestion chips -->
        <div class="flex items-center gap-2 overflow-x-auto text-[11px] font-mono text-slate-400 no-scrollbar">
          <span class="text-slate-500 shrink-0">Suggested:</span>
          <button
            @click="useSuggestion('Analyze vessel FoC flag-hopping history and ultimate beneficial ownership.')"
            class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white shrink-0"
          >
            Vessel & FoC Analysis
          </button>
          <button
            @click="useSuggestion('Check official RFMO IUU blacklist and overlapping disputed claims in Natuna.')"
            class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white shrink-0"
          >
            IUU & Natuna Disputed EEZ
          </button>
          <button
            @click="useSuggestion('Audit crew welfare, manning agency compliance, and ILO 11 forced labour indicators.')"
            class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white shrink-0"
          >
            Crew & Labour Compliance
          </button>
        </div>

        <form @submit.prevent="sendQuery" class="flex gap-3">
          <textarea
            v-model="promptInput"
            rows="2"
            :disabled="sendingPrompt"
            placeholder="Type your investigative inquiry... (e.g. 'Investigate corporate secrecy and flag-hopping patterns for Pacific longliners')"
            class="flex-1 px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 resize-none font-sans"
            @keydown.enter.exact.prevent="sendQuery"
          ></textarea>
          <button
            type="submit"
            :disabled="sendingPrompt || !promptInput.trim()"
            class="px-5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white font-bold text-xs font-mono shrink-0 flex items-center gap-1.5 transition"
          >
            <span v-if="sendingPrompt" class="animate-spin">⏳</span>
            <span v-else>🚀</span>
            Execute
          </button>
        </form>
      </div>
    </div>

    <!-- Modal: New Intelligence Inquiry -->
    <div v-if="showNewModal" class="fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 max-w-md w-full space-y-4">
        <h4 class="text-sm font-bold text-white flex items-center gap-2">
          <span>🤖</span> Initialize Intelligence Inquiry Thread
        </h4>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Inquiry Title / Case Reference</label>
            <input
              v-model="newForm.title"
              type="text"
              placeholder="e.g. Pacific Longliner Fleet Network Investigation"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded text-white"
            />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Agent Specialization Mode</label>
            <select
              v-model="newForm.agent_mode"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded text-white"
            >
              <option value="orchestrator">Multi-Agent Orchestrator (All 8 Specialists)</option>
              <option value="vessel">Vessel Intelligence & Identity Resolution</option>
              <option value="corporate">Corporate Intelligence & Beneficial Ownership</option>
              <option value="iuu">IUU Sanctions & RFMO Blacklists</option>
              <option value="crew">Crew Welfare & Forced Labour (ILO 11)</option>
              <option value="jurisdiction">Maritime Jurisdiction & PostGIS Boundaries</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Context Attachment (Optional)</label>
            <div class="grid grid-cols-2 gap-2">
              <select
                v-model="newForm.context_type"
                class="px-3 py-2 bg-slate-950 border border-slate-800 rounded text-white"
              >
                <option value="general">General Research</option>
                <option value="vessel">Specific Vessel</option>
                <option value="company">Specific Company</option>
              </select>
              <select
                v-if="newForm.context_type === 'vessel'"
                v-model="newForm.context_id"
                class="px-3 py-2 bg-slate-950 border border-slate-800 rounded text-white"
              >
                <option :value="null">-- Select Vessel --</option>
                <option v-for="v in vesselsList" :key="v.id" :value="v.id">
                  {{ v.current_name || v.name }} (IMO: {{ v.imo_number || 'N/A' }})
                </option>
              </select>
              <select
                v-else-if="newForm.context_type === 'company'"
                v-model="newForm.context_id"
                class="px-3 py-2 bg-slate-950 border border-slate-800 rounded text-white"
              >
                <option :value="null">-- Select Company --</option>
                <option v-for="c in companiesList" :key="c.id" :value="c.id">
                  {{ c.company_name || c.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            @click="showNewModal = false"
            class="px-3 py-1.5 rounded bg-slate-800 text-slate-300 text-xs font-mono"
          >
            Cancel
          </button>
          <button
            @click="createConversation"
            :disabled="!newForm.title.trim()"
            class="px-4 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white text-xs font-bold font-mono"
          >
            Create Session
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Semantic Vector Search -->
    <div v-if="showVectorModal" class="fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 max-w-2xl w-full space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h4 class="text-sm font-bold text-white flex items-center gap-2">
            <span>🧭</span> Vector Cosine Similarity Search
          </h4>
          <button @click="showVectorModal = false" class="text-slate-500 hover:text-white">✕</button>
        </div>

        <div class="flex gap-2">
          <input
            v-model="vectorQuery"
            type="text"
            placeholder="Search semantics: e.g. 'offshore shell company Marshall Islands reefer'"
            class="flex-1 px-3 py-2 bg-slate-950 border border-slate-800 rounded text-xs text-white"
            @keydown.enter="runVectorSearch"
          />
          <button
            @click="runVectorSearch"
            :disabled="searchingVector || !vectorQuery.trim()"
            class="px-4 py-2 rounded bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white text-xs font-mono font-bold"
          >
            Search
          </button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2">
          <div v-if="searchingVector" class="p-6 text-center text-xs text-slate-400">
            Calculating vector similarity against PostgreSQL pgvector cosine index...
          </div>
          <div v-else-if="vectorResults.length === 0" class="p-6 text-center text-xs text-slate-500">
            No matching vector embeddings found. Try different search terms or sync vectors first.
          </div>
          <div
            v-for="item in vectorResults"
            :key="item.id"
            class="p-3 rounded-lg bg-slate-950 border border-slate-800 text-xs space-y-1"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-white">{{ item.title }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
                SIMILARITY: {{ (item.similarity * 100).toFixed(1) }}%
              </span>
            </div>
            <p class="text-[11px] text-slate-400">{{ item.content }}</p>
            <div class="text-[9px] font-mono text-slate-500 flex items-center gap-2 mt-1">
              <span>Domain: {{ item.entity_type }}</span>
              <span>ID: {{ item.entity_id }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Analyst Review Finding Gate -->
    <div v-if="showReviewModal" class="fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 max-w-md w-full space-y-4">
        <h4 class="text-sm font-bold text-white flex items-center gap-2">
          <span>⚖️</span> Log Analyst Review Decision
        </h4>

        <div class="space-y-3 text-xs">
          <div class="p-3 rounded bg-slate-950 border border-slate-800">
            <span class="font-bold text-white block">{{ activeFinding?.title }}</span>
            <span class="text-[11px] text-slate-400 block mt-1">{{ activeFinding?.summary }}</span>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Review Decision</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 text-emerald-300 font-mono">
                <input type="radio" :value="true" v-model="reviewForm.is_verified" />
                Verify Lead as Actionable
              </label>
              <label class="flex items-center gap-2 text-slate-400 font-mono">
                <input type="radio" :value="false" v-model="reviewForm.is_verified" />
                Dismiss Lead
              </label>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Substantive Analyst Notes (Mandatory)</label>
            <textarea
              v-model="reviewForm.notes"
              rows="3"
              placeholder="State corroborating registry filings, Port State Control inspections, or reasons for dismissal..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded text-white"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            @click="showReviewModal = false"
            class="px-3 py-1.5 rounded bg-slate-800 text-slate-300 text-xs font-mono"
          >
            Cancel
          </button>
          <button
            @click="submitReview"
            :disabled="!reviewForm.notes.trim() || submittingReview"
            class="px-4 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 text-white text-xs font-bold font-mono"
          >
            Confirm Review
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Agent Roster -->
    <div v-if="showRosterModal" class="fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 max-w-lg w-full space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h4 class="text-sm font-bold text-white flex items-center gap-2">
            <span>👥</span> Multi-Agent Roster & Capabilities
          </h4>
          <button @click="showRosterModal = false" class="text-slate-500 hover:text-white">✕</button>
        </div>

        <div class="space-y-2 text-xs">
          <div
            v-for="agent in agentRoster"
            :key="agent.name"
            class="p-2.5 rounded bg-slate-950 border border-slate-800 flex items-start justify-between gap-3"
          >
            <div>
              <span class="font-bold text-cyan-300 font-mono">{{ agent.name }}</span>
              <p class="text-[11px] text-slate-400 mt-0.5">{{ agent.description }}</p>
            </div>
            <span class="px-2 py-0.5 rounded text-[9px] font-mono uppercase bg-slate-800 text-slate-300 shrink-0">
              {{ agent.domain }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import apiClient from '@/api/client';

const conversations = ref<any[]>([]);
const activeConversation = ref<any>(null);
const loadingConversations = ref(false);
const sendingPrompt = ref(false);
const syncingVectors = ref(false);
const promptInput = ref('');
const chatContainer = ref<HTMLElement | null>(null);

const vesselsList = ref<any[]>([]);
const companiesList = ref<any[]>([]);

// Modals
const showNewModal = ref(false);
const newForm = ref({
  title: '',
  agent_mode: 'orchestrator',
  context_type: 'general',
  context_id: null,
});

const showVectorModal = ref(false);
const vectorQuery = ref('');
const vectorResults = ref<any[]>([]);
const searchingVector = ref(false);

const showReviewModal = ref(false);
const activeFinding = ref<any>(null);
const reviewForm = ref({
  is_verified: true,
  notes: '',
});
const submittingReview = ref(false);

const showRosterModal = ref(false);
const agentRoster = ref([
  { name: 'VesselIntelligenceAgent', domain: 'vessel_master', description: 'Evaluates vessel particulars, FoC flags, and dimensions.' },
  { name: 'IdentityResolutionAgent', domain: 'identity_resolution', description: 'Audits identifier collisions, duplicate MMSIs, and flag-hopping.' },
  { name: 'CorporateIntelligenceAgent', domain: 'corporate_networks', description: 'Examines beneficial ownership, offshore secrecy, and holding webs.' },
  { name: 'CrewResearchAgent', domain: 'labour_rights', description: 'Inspects ILO 11 forced labour indicators and skipper track records.' },
  { name: 'MaritimeJurisdictionAgent', domain: 'maritime_boundaries', description: 'Analyzes EEZ baselines, Natuna waters, and disputed borders.' },
  { name: 'IuuResearchAgent', domain: 'iuu_sanctions', description: 'Checks RFMO blacklists and unlicensed fishing allegations.' },
  { name: 'EvidenceAnalysisAgent', domain: 'evidence_verification', description: 'Audits cryptographic SHA-256 integrity and custody chains.' },
  { name: 'ResearchAssistantAgent', domain: 'synthesis_and_reporting', description: 'Formulates evidence-grounded reports and analyst next steps.' },
]);

function formatTimestamp(ts: string) {
  if (!ts) return '';
  return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function useSuggestion(text: string) {
  promptInput.value = text;
}

async function loadConversations() {
  loadingConversations.value = true;
  try {
    const res = await apiClient.get('/ai/conversations');
    conversations.value = res.data.data ?? [];
    if (conversations.value.length > 0 && !activeConversation.value) {
      await selectConversation(conversations.value[0].id);
    }
  } catch (err) {
    console.error('Failed to load conversations', err);
  } finally {
    loadingConversations.value = false;
  }
}

async function selectConversation(id: string) {
  try {
    const res = await apiClient.get(`/ai/conversations/${id}`);
    activeConversation.value = res.data.data;
    scrollToBottom();
  } catch (err) {
    console.error('Failed to select conversation', err);
  }
}

async function deleteConversation(id: string) {
  if (!confirm('Are you sure you want to delete this intelligence thread?')) return;
  try {
    await apiClient.delete(`/ai/conversations/${id}`);
    if (activeConversation.value?.id === id) {
      activeConversation.value = null;
    }
    await loadConversations();
  } catch (err) {
    console.error('Failed to delete conversation', err);
  }
}

function openNewInquiryModal() {
  newForm.value = {
    title: '',
    agent_mode: 'orchestrator',
    context_type: 'general',
    context_id: null,
  };
  showNewModal.value = true;
}

async function createConversation() {
  try {
    const res = await apiClient.post('/ai/conversations', newForm.value);
    showNewModal.value = false;
    await loadConversations();
    if (res.data.data?.id) {
      await selectConversation(res.data.data.id);
    }
  } catch (err) {
    console.error('Failed to create conversation', err);
  }
}

async function sendQuery() {
  if (!promptInput.value.trim() || !activeConversation.value || sendingPrompt.value) return;

  const promptText = promptInput.value.trim();
  promptInput.value = '';
  sendingPrompt.value = true;

  try {
    const res = await apiClient.post(`/ai/conversations/${activeConversation.value.id}/query`, {
      prompt: promptText,
    });

    if (res.data.data) {
      await selectConversation(activeConversation.value.id);
      await loadConversations();
    }
  } catch (err) {
    console.error('Failed to execute query', err);
  } finally {
    sendingPrompt.value = false;
    scrollToBottom();
  }
}

function openReviewModal(finding: any, isVerified: boolean) {
  activeFinding.value = finding;
  reviewForm.value = {
    is_verified: isVerified,
    notes: '',
  };
  showReviewModal.value = true;
}

async function submitReview() {
  if (!activeFinding.value || !reviewForm.value.notes.trim()) return;

  submittingReview.value = true;
  try {
    await apiClient.post(`/ai/findings/${activeFinding.value.id}/review`, reviewForm.value);
    showReviewModal.value = false;
    if (activeConversation.value) {
      await selectConversation(activeConversation.value.id);
    }
  } catch (err) {
    console.error('Failed to submit review', err);
  } finally {
    submittingReview.value = false;
  }
}

async function syncVectors() {
  syncingVectors.value = true;
  try {
    const res = await apiClient.post('/ai/vector-sync');
    alert(`Vector Sync Complete! Synced ${res.data.data?.synced_records || 0} entities.`);
  } catch (err) {
    console.error('Failed to sync vectors', err);
  } finally {
    syncingVectors.value = false;
  }
}

function openVectorSearchModal() {
  vectorQuery.value = '';
  vectorResults.value = [];
  showVectorModal.value = true;
}

async function runVectorSearch() {
  if (!vectorQuery.value.trim()) return;

  searchingVector.value = true;
  try {
    const res = await apiClient.post('/ai/vector-search', {
      query: vectorQuery.value.trim(),
      top_k: 5,
    });
    vectorResults.value = res.data.data ?? [];
  } catch (err) {
    console.error('Failed to run vector search', err);
  } finally {
    searchingVector.value = false;
  }
}

async function loadContextEntities() {
  try {
    const [vesselsRes, companiesRes] = await Promise.all([
      apiClient.get('/vessels?per_page=20').catch(() => ({ data: { data: [] } })),
      apiClient.get('/companies?per_page=20').catch(() => ({ data: { data: [] } })),
    ]);
    vesselsList.value = vesselsRes.data.data ?? [];
    companiesList.value = companiesRes.data.data ?? [];
  } catch (err) {
    console.error('Failed to load context entities', err);
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
}

onMounted(() => {
  loadConversations();
  loadContextEntities();
});
</script>
