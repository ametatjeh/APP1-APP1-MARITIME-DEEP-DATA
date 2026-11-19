<template>
  <div class="space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between text-xs">
      <router-link
        to="/investigations"
        class="inline-flex items-center gap-1.5 text-slate-400 hover:text-cyan-400 transition-colors"
      >
        <span>←</span>
        <span>Back to Investigation Cases</span>
      </router-link>
      <div class="text-slate-500 font-mono">
        CASE UUID: {{ caseId }}
      </div>
    </div>

    <div v-if="loading" class="py-16 text-center text-xs text-slate-400">
      Loading case workspace...
    </div>

    <div v-else-if="!caseData" class="py-16 text-center text-xs text-red-400">
      Investigation case not found or access restricted.
    </div>

    <div v-else class="space-y-6">
      <!-- Case Header Card -->
      <div class="p-6 rounded-xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-800 pb-4">
          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-mono text-xs font-bold text-cyan-400 bg-cyan-950/80 border border-cyan-800 px-2.5 py-0.5 rounded">
                {{ caseData.reference_number }}
              </span>
              <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold uppercase" :class="priorityBadgeClass(caseData.priority)">
                {{ caseData.priority }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">
                {{ formatCategory(caseData.category) }}
              </span>
              <span v-if="caseData.confidentiality !== 'internal'" class="px-2 py-0.5 rounded text-[10px] bg-red-950 text-red-300 border border-red-800 font-mono">
                🔒 {{ caseData.confidentiality.toUpperCase() }}
              </span>
            </div>
            <h2 class="text-lg font-bold text-white pt-1">{{ caseData.title }}</h2>
          </div>

          <!-- Status Dropdown & Actions -->
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs">
              <span class="text-slate-400">Status:</span>
              <select
                v-model="caseData.status"
                @change="updateStatus"
                class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-lg text-white font-medium focus:outline-none focus:border-cyan-500"
                :disabled="!authStore.canEdit"
              >
                <option value="open">Open</option>
                <option value="active">Active Investigation</option>
                <option value="pending_evidence">Pending Evidence</option>
                <option value="under_review">Under Review</option>
                <option value="closed">Closed</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Case Metadata Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
          <div>
            <span class="text-slate-500 block">Lead Investigator</span>
            <span class="text-slate-200 font-semibold">{{ caseData.lead_investigator?.name || 'Unassigned' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Primary Jurisdiction</span>
            <span class="text-cyan-300">{{ caseData.primary_jurisdiction || 'International' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Incident Date</span>
            <span class="text-slate-300">{{ formatDate(caseData.incident_date) }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Opened Date</span>
            <span class="text-slate-300">{{ formatDate(caseData.opened_date || caseData.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-slate-800 flex items-center gap-2 overflow-x-auto text-xs font-medium">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="px-4 py-2.5 border-b-2 transition-colors flex items-center gap-2 whitespace-nowrap"
          :class="activeTab === tab.id ? 'border-cyan-500 text-cyan-400 bg-cyan-950/20' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
        >
          <span>{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
          <span v-if="tab.count !== undefined" class="px-1.5 py-0.2 rounded text-[10px] bg-slate-800 text-slate-300 font-mono">
            {{ tab.count }}
          </span>
        </button>
      </div>

      <!-- Tab 1: Overview & Hypothesis -->
      <div v-if="activeTab === 'overview'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
            <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>💡</span>
              <span>Investigative Hypothesis & Theory</span>
            </h4>
            <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed bg-slate-950 p-4 rounded-lg border border-slate-800 font-sans">
              {{ caseData.hypothesis || 'No explicit hypothesis registered.' }}
            </div>
          </div>

          <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
            <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>📋</span>
              <span>Executive Briefing & Summary</span>
            </h4>
            <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed bg-slate-950 p-4 rounded-lg border border-slate-800 font-sans">
              {{ caseData.summary || 'No executive briefing summary.' }}
            </div>
          </div>
        </div>

        <div v-if="caseData.tags && caseData.tags.length" class="p-4 rounded-xl bg-slate-900 border border-slate-800">
          <span class="text-xs text-slate-400 block mb-2">Operational Tags:</span>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="t in caseData.tags"
              :key="t"
              class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800"
            >
              #{{ t }}
            </span>
          </div>
        </div>
      </div>

      <!-- Tab 2: Multi-Subjects (Vessels, Companies, Persons, Locations) -->
      <div v-if="activeTab === 'subjects'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Multi-subject investigation targets linked from Phase 1 master databases (Vessels, Companies, Entities).
          </p>
          <button
            v-if="authStore.canEdit"
            @click="openAddSubjectModal"
            class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
          >
            <span>➕</span>
            <span>Link Subject Entity</span>
          </button>
        </div>

        <div v-if="!caseData.subjects || caseData.subjects.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No subjects currently linked to this case. Click "Link Subject Entity" to connect vessels or corporate targets.
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="sub in caseData.subjects"
            :key="sub.id"
            class="p-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-colors space-y-3"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-2">
                <span class="text-base">{{ getSubjectIcon(sub.entity_type) }}</span>
                <div>
                  <span class="text-[10px] font-mono uppercase text-slate-400">{{ sub.entity_type }}</span>
                  <div class="text-xs font-bold text-white">
                    {{ sub.entity?.name || sub.entity_id }}
                  </div>
                </div>
              </div>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-mono uppercase"
                :class="subjectRoleBadgeClass(sub.role)"
              >
                {{ sub.role }}
              </span>
            </div>

            <!-- Subject Details -->
            <div v-if="sub.entity_type === 'vessel' && sub.entity" class="text-[11px] text-slate-400 font-mono bg-slate-950 p-2 rounded border border-slate-800 space-y-0.5">
              <div>IMO: <span class="text-slate-200">{{ sub.entity.imo || '-' }}</span> | MMSI: <span class="text-slate-200">{{ sub.entity.mmsi || '-' }}</span></div>
              <div>Flag: <span class="text-slate-200">{{ sub.entity.flag_country?.name || sub.entity.flag_state || '-' }}</span></div>
            </div>

            <div v-if="sub.entity_type === 'company' && sub.entity" class="text-[11px] text-slate-400 font-mono bg-slate-950 p-2 rounded border border-slate-800 space-y-0.5">
              <div>Jurisdiction: <span class="text-slate-200">{{ sub.entity.jurisdiction_country?.name || '-' }}</span></div>
              <div>Type: <span class="text-slate-200">{{ sub.entity.company_type || '-' }}</span></div>
            </div>

            <div v-if="sub.notes" class="text-xs text-slate-300 italic">
              "{{ sub.notes }}"
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-[11px]">
              <span class="text-slate-500">Added: {{ formatDate(sub.created_at) }}</span>
              <button
                v-if="authStore.canEdit"
                @click="removeSubject(sub.id)"
                class="text-red-400 hover:text-red-300 transition-colors"
                title="Unlink subject from case"
              >
                Unlink ✕
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 3: Evidence Files Vault -->
      <div v-if="activeTab === 'evidence'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Vaulted evidence items secured with SHA-256 cryptographic hashes and strict private storage enforcement.
          </p>
          <div class="flex items-center gap-2">
            <button
              v-if="authStore.canEdit"
              @click="openAttachEvidenceModal"
              class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
            >
              <span>📎</span>
              <span>Attach Evidence</span>
            </button>
            <router-link
              to="/evidence"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700 flex items-center gap-1"
            >
              <span>Evidence Vault</span>
              <span>→</span>
            </router-link>
          </div>
        </div>

        <div v-if="!caseData.evidences || caseData.evidences.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No evidence files currently attached to this case dossier.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="rel in caseData.evidences"
            :key="rel.id"
            class="p-4 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
          >
            <div class="space-y-1.5 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-base">{{ getEvidenceTypeIcon(rel.evidence?.evidence_type) }}</span>
                <span class="font-bold text-xs text-white">{{ rel.evidence?.title }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                  {{ rel.evidence?.evidence_type }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase" :class="evidenceStatusBadgeClass(rel.evidence?.verification_status)">
                  {{ rel.evidence?.verification_status }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-red-950 text-red-300 border border-red-800/40">
                  {{ rel.evidence?.classification }}
                </span>
              </div>

              <!-- SHA-256 Badge -->
              <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                <span class="text-emerald-400 font-semibold flex items-center gap-1">
                  <span>🔒 SHA-256:</span>
                </span>
                <span class="text-slate-300 truncate max-w-md bg-slate-950 px-2 py-0.5 rounded border border-slate-800 select-all">
                  {{ rel.evidence?.sha256_hash }}
                </span>
                <span class="text-slate-500">({{ formatFileSize(rel.evidence?.file_size_bytes) }})</span>
              </div>

              <div v-if="rel.relationship_notes" class="text-xs text-slate-300 italic">
                Case context: "{{ rel.relationship_notes }}"
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 self-start md:self-center">
              <button
                @click="downloadEvidenceFile(rel.evidence?.id, rel.evidence?.file_name)"
                class="px-3 py-1.5 rounded-lg bg-cyan-950 hover:bg-cyan-900 text-cyan-300 text-xs font-mono transition-colors border border-cyan-800 flex items-center gap-1.5"
                title="Download from private storage with SHA-256 verification"
              >
                <span>📥</span>
                <span>Download</span>
              </button>
              <router-link
                :to="`/evidence/${rel.evidence?.id}`"
                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium transition-colors border border-slate-700"
              >
                Inspect
              </router-link>
              <button
                v-if="authStore.canEdit"
                @click="detachEvidence(rel.id)"
                class="p-1.5 text-red-400 hover:text-red-300 transition-colors"
                title="Detach evidence from case"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 4: Chronological Timeline -->
      <div v-if="activeTab === 'timeline'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Chronological investigation timeline reconstructs sequence of incidents, sightings, and official actions.
          </p>
          <button
            v-if="authStore.canEdit"
            @click="showAddTimelineModal = true"
            class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
          >
            <span>➕</span>
            <span>Record Timeline Event</span>
          </button>
        </div>

        <div v-if="!caseData.timelines || caseData.timelines.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No chronological timeline events recorded yet.
        </div>

        <div v-else class="relative border-l-2 border-slate-800 ml-4 pl-6 space-y-6">
          <div
            v-for="tl in sortedTimelines"
            :key="tl.id"
            class="relative space-y-1.5"
          >
            <!-- Timeline Pin Dot -->
            <div class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-cyan-500 border-2 border-slate-900 ring-2 ring-cyan-500/30"></div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs font-mono font-bold text-cyan-400">
                {{ formatDateTime(tl.event_date) }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 uppercase">
                {{ tl.event_type.replace('_', ' ') }}
              </span>
              <span v-if="tl.location_text" class="text-[11px] text-slate-400 font-mono">
                📍 {{ tl.location_text }}
              </span>
            </div>

            <h5 class="text-xs font-bold text-white">{{ tl.title }}</h5>

            <p v-if="tl.description" class="text-xs text-slate-300 leading-relaxed max-w-3xl">
              {{ tl.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Tab 5: Investigative Notes -->
      <div v-if="activeTab === 'notes'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Analyst work log, field notes, and hypotheses tagged with access confidentiality.
          </p>
          <button
            v-if="authStore.canEdit"
            @click="showAddNoteModal = true"
            class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
          >
            <span>➕</span>
            <span>Add Case Note</span>
          </button>
        </div>

        <div v-if="!caseData.notes || caseData.notes.length === 0" class="p-8 rounded-xl bg-slate-900 border border-slate-800 text-center text-xs text-slate-400">
          No investigative notes logged for this case.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="n in caseData.notes"
            :key="n.id"
            class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-white">{{ n.title }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-800 text-slate-300">
                  {{ n.confidentiality }}
                </span>
              </div>
              <div class="text-[11px] text-slate-500 font-mono">
                By <strong class="text-slate-300">{{ n.author?.name || 'Analyst' }}</strong> • {{ formatDateTime(n.created_at) }}
              </div>
            </div>

            <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed bg-slate-950 p-3 rounded-lg border border-slate-800">
              {{ n.content }}
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 6: Investigator Team -->
      <div v-if="activeTab === 'team'" class="space-y-4">
        <div class="flex items-center justify-between">
          <p class="text-xs text-slate-400">
            Investigator roster assigned to this case with designated case roles and clearance.
          </p>
          <button
            v-if="authStore.canEdit"
            @click="openAssignModal"
            class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors flex items-center gap-1"
          >
            <span>➕</span>
            <span>Assign Collaborator</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Lead Investigator Card -->
          <div class="p-4 rounded-xl bg-slate-900 border border-cyan-800/60 space-y-2">
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800 uppercase font-bold">
                LEAD INVESTIGATOR
              </span>
            </div>
            <div class="text-sm font-bold text-white">{{ caseData.lead_investigator?.name || 'Unassigned' }}</div>
            <div class="text-[11px] text-slate-400 font-mono">{{ caseData.lead_investigator?.email }}</div>
          </div>

          <!-- Other Assignments -->
          <div
            v-for="asgn in caseData.assignments"
            :key="asgn.id"
            class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2"
          >
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 uppercase">
                {{ asgn.role.replace('_', ' ') }}
              </span>
              <button
                v-if="authStore.canEdit"
                @click="removeAssignment(asgn.id)"
                class="text-red-400 hover:text-red-300 text-xs"
              >
                ✕
              </button>
            </div>
            <div class="text-sm font-bold text-white">{{ asgn.user?.name }}</div>
            <div class="text-[11px] text-slate-400 font-mono">{{ asgn.user?.email }} • Level: {{ asgn.access_level }}</div>
            <div v-if="asgn.notes" class="text-xs text-slate-400 italic">"{{ asgn.notes }}"</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->

    <!-- Add Subject Modal -->
    <div v-if="showAddSubjectModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Link Subject Entity to Case</h4>
        <form @submit.prevent="submitAddSubject" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Entity Type</label>
            <select
              v-model="newSubject.entity_type"
              @change="fetchAvailableEntities"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"
            >
              <option value="vessel">Vessel (from Master DB)</option>
              <option value="company">Company (Corporate Master)</option>
              <option value="person">Person / Officer</option>
              <option value="location">Maritime Location / Port</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Select Entity</label>
            <select
              v-model="newSubject.entity_id"
              required
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"
            >
              <option value="" disabled>Choose {{ newSubject.entity_type }}...</option>
              <option v-for="item in availableEntities" :key="item.id" :value="item.id">
                {{ item.name }} {{ item.extra ? `(${item.extra})` : '' }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Subject Role in this Case</label>
            <select v-model="newSubject.role" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="target">Target (Primary Subject)</option>
              <option value="suspect">Suspect (Active Suspicion)</option>
              <option value="associate">Associate / Related Entity</option>
              <option value="witness">Witness / Reporter</option>
              <option value="victim">Victim (e.g. exploited crew)</option>
              <option value="person_of_interest">Person / Entity of Interest</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Investigative Notes</label>
            <textarea
              v-model="newSubject.notes"
              rows="2"
              placeholder="Why this entity is linked to the case..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showAddSubjectModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white">Link Subject</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Attach Evidence Modal -->
    <div v-if="showAttachEvidenceModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Attach Evidence from Vault</h4>
        <form @submit.prevent="submitAttachEvidence" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Select Vaulted Evidence</label>
            <select
              v-model="attachEvidenceForm.evidence_id"
              required
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"
            >
              <option value="" disabled>Select evidence item...</option>
              <option v-for="ev in availableEvidences" :key="ev.id" :value="ev.id">
                [{{ ev.evidence_type.toUpperCase() }}] {{ ev.title }} ({{ ev.verification_status }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Case Relationship Context</label>
            <textarea
              v-model="attachEvidenceForm.relationship_notes"
              rows="2"
              placeholder="How this evidence supports this case dossier..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showAttachEvidenceModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white">Attach Evidence</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Timeline Event Modal -->
    <div v-if="showAddTimelineModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Record Chronological Timeline Event</h4>
        <form @submit.prevent="submitTimelineEvent" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Event Title *</label>
            <input v-model="newTimeline.title" required type="text" placeholder="e.g. Unlicensed Transshipment at High Seas" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Event Type *</label>
              <select v-model="newTimeline.event_type" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="incident_occurred">Incident Occurred</option>
                <option value="detected">Detected / Sighted</option>
                <option value="investigation_opened">Investigation Opened</option>
                <option value="port_entry">Port Entry / Departure</option>
                <option value="inspection">Official Inspection</option>
                <option value="arrest_detention">Arrest / Detention</option>
                <option value="evidence_collected">Evidence Collected</option>
                <option value="legal_action">Legal Action / Sanction</option>
                <option value="other">Other Event</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Date & Time *</label>
              <input v-model="newTimeline.event_date" required type="datetime-local" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Location Text / Coordinates</label>
            <input v-model="newTimeline.location_text" type="text" placeholder="e.g. 03°12'N 108°45'E (North Natuna Sea)" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Event Narrative Description</label>
            <textarea v-model="newTimeline.description" rows="3" placeholder="Factual observation and sources..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showAddTimelineModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white">Add Event</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Case Note Modal -->
    <div v-if="showAddNoteModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Add Investigative Case Note</h4>
        <form @submit.prevent="submitCaseNote" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Note Title *</label>
            <input v-model="newNote.title" required type="text" placeholder="e.g. Cross-check of Beneficial Ownership via Panama Registry" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white" />
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Confidentiality Level</label>
            <select v-model="newNote.confidentiality" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="internal">Internal Team</option>
              <option value="confidential">Confidential</option>
              <option value="strictly_confidential">Strictly Confidential</option>
            </select>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Content Narrative *</label>
            <textarea v-model="newNote.content" required rows="4" placeholder="Detailed analytical note..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showAddNoteModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white">Save Note</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Collaborator Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-5 space-y-4">
        <h4 class="text-sm font-bold text-white">Assign Investigator to Case</h4>
        <form @submit.prevent="submitAssignment" class="space-y-3 text-xs">
          <div>
            <label class="block text-slate-400 mb-1">Select User</label>
            <select v-model="newAssignment.user_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
              <option value="" disabled>Choose investigator...</option>
              <option v-for="u in availableUsers" :key="u.id" :value="u.id">
                {{ u.name }} ({{ u.email }} - {{ u.role }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 mb-1">Case Role</label>
              <select v-model="newAssignment.role" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="analyst">Analyst</option>
                <option value="lead_investigator">Lead Investigator</option>
                <option value="legal_counsel">Legal Counsel</option>
                <option value="reviewer">Reviewer / Verifier</option>
                <option value="field_agent">Field Agent</option>
              </select>
            </div>

            <div>
              <label class="block text-slate-400 mb-1">Access Level</label>
              <select v-model="newAssignment.access_level" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white">
                <option value="full">Full Read/Write</option>
                <option value="read_only">Read-Only</option>
                <option value="restricted">Restricted</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 mb-1">Assignment Notes</label>
            <textarea v-model="newAssignment.notes" rows="2" placeholder="Specific task or responsibility..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-white"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showAssignModal = false" class="px-3 py-1.5 rounded bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" class="px-3 py-1.5 rounded bg-cyan-600 hover:bg-cyan-500 text-white">Assign User</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/client';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const authStore = useAuthStore();
const caseId = computed(() => route.params.id as string);

const caseData = ref<any>(null);
const loading = ref(false);
const activeTab = ref('overview');

const tabs = computed(() => [
  { id: 'overview', label: 'Dossier Overview', icon: '📋' },
  { id: 'subjects', label: 'Multi-Subjects', icon: '🎯', count: caseData.value?.subjects?.length || 0 },
  { id: 'evidence', label: 'Evidence Vault', icon: '🛡️', count: caseData.value?.evidences?.length || 0 },
  { id: 'timeline', label: 'Timeline', icon: '⏱️', count: caseData.value?.timelines?.length || 0 },
  { id: 'notes', label: 'Investigative Notes', icon: '📝', count: caseData.value?.notes?.length || 0 },
  { id: 'team', label: 'Investigators', icon: '👥', count: (caseData.value?.assignments?.length || 0) + 1 },
]);

const sortedTimelines = computed(() => {
  if (!caseData.value?.timelines) return [];
  return [...caseData.value.timelines].sort((a, b) => new Date(a.event_date).getTime() - new Date(b.event_date).getTime());
});

// Modals
const showAddSubjectModal = ref(false);
const showAttachEvidenceModal = ref(false);
const showAddTimelineModal = ref(false);
const showAddNoteModal = ref(false);
const showAssignModal = ref(false);

const availableEntities = ref<any[]>([]);
const availableEvidences = ref<any[]>([]);
const availableUsers = ref<any[]>([]);

const newSubject = ref({
  entity_type: 'vessel',
  entity_id: '',
  role: 'target',
  notes: '',
});

const attachEvidenceForm = ref({
  evidence_id: '',
  relationship_notes: '',
});

const newTimeline = ref({
  event_type: 'incident_occurred',
  event_date: '',
  title: '',
  description: '',
  location_text: '',
});

const newNote = ref({
  title: '',
  confidentiality: 'internal',
  content: '',
});

const newAssignment = ref({
  user_id: '',
  role: 'analyst',
  access_level: 'full',
  notes: '',
});

async function fetchCaseDetail() {
  loading.value = true;
  try {
    const res = await apiClient.get(`/cases/${caseId.value}`);
    caseData.value = res.data.data;
  } catch (err) {
    console.error('Failed to load case detail', err);
  } finally {
    loading.value = false;
  }
}

async function updateStatus() {
  try {
    await apiClient.put(`/cases/${caseId.value}`, {
      status: caseData.value.status,
    });
  } catch (err) {
    console.error('Failed to update case status', err);
    fetchCaseDetail();
  }
}

async function openAddSubjectModal() {
  newSubject.value = { entity_type: 'vessel', entity_id: '', role: 'target', notes: '' };
  await fetchAvailableEntities();
  showAddSubjectModal.value = true;
}

async function fetchAvailableEntities() {
  try {
    if (newSubject.value.entity_type === 'vessel') {
      const res = await apiClient.get('/vessels?per_page=50');
      availableEntities.value = (res.data.data || []).map((v: any) => ({
        id: v.id,
        name: v.name,
        extra: `IMO: ${v.imo || 'N/A'}, Flag: ${v.flag_state || 'N/A'}`,
      }));
    } else if (newSubject.value.entity_type === 'company') {
      const res = await apiClient.get('/companies?per_page=50');
      availableEntities.value = (res.data.data || []).map((c: any) => ({
        id: c.id,
        name: c.name,
        extra: `Jurisdiction: ${c.jurisdiction_country?.name || 'N/A'}`,
      }));
    } else {
      availableEntities.value = [];
    }
  } catch (err) {
    console.error('Failed to load available entities', err);
  }
}

async function submitAddSubject() {
  try {
    await apiClient.post(`/cases/${caseId.value}/subjects`, newSubject.value);
    showAddSubjectModal.value = false;
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to link subject.');
  }
}

async function removeSubject(subjectId: string) {
  if (!confirm('Are you sure you want to unlink this subject from the case?')) return;
  try {
    await apiClient.delete(`/cases/${caseId.value}/subjects/${subjectId}`);
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to remove subject.');
  }
}

async function openAttachEvidenceModal() {
  try {
    const res = await apiClient.get('/evidences?per_page=50');
    availableEvidences.value = res.data.data || [];
    attachEvidenceForm.value = { evidence_id: '', relationship_notes: '' };
    showAttachEvidenceModal.value = true;
  } catch (err) {
    console.error('Failed to load evidences', err);
  }
}

async function submitAttachEvidence() {
  try {
    await apiClient.post(`/cases/${caseId.value}/evidences`, attachEvidenceForm.value);
    showAttachEvidenceModal.value = false;
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to attach evidence.');
  }
}

async function detachEvidence(relId: string) {
  if (!confirm('Detach this evidence from this case dossier?')) return;
  try {
    await apiClient.delete(`/cases/${caseId.value}/evidences/${relId}`);
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to detach evidence.');
  }
}

async function submitTimelineEvent() {
  try {
    await apiClient.post(`/cases/${caseId.value}/timelines`, newTimeline.value);
    showAddTimelineModal.value = false;
    newTimeline.value = {
      event_type: 'incident_occurred',
      event_date: '',
      title: '',
      description: '',
      location_text: '',
    };
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to add timeline event.');
  }
}

async function submitCaseNote() {
  try {
    await apiClient.post(`/cases/${caseId.value}/notes`, newNote.value);
    showAddNoteModal.value = false;
    newNote.value = { title: '', confidentiality: 'internal', content: '' };
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to save note.');
  }
}

async function openAssignModal() {
  try {
    // In our backend, users can be listed
    const res = await apiClient.get('/users').catch(() => ({ data: { data: [] } }));
    availableUsers.value = res.data?.data || [];
    showAssignModal.value = true;
  } catch (err) {
    console.error('Failed to load users', err);
  }
}

async function submitAssignment() {
  try {
    await apiClient.post(`/cases/${caseId.value}/assignments`, newAssignment.value);
    showAssignModal.value = false;
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to assign collaborator.');
  }
}

async function removeAssignment(assignmentId: string) {
  if (!confirm('Remove collaborator from case?')) return;
  try {
    await apiClient.delete(`/cases/${caseId.value}/assignments/${assignmentId}`);
    await fetchCaseDetail();
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to remove collaborator.');
  }
}

async function downloadEvidenceFile(evidenceId: string, filename: string) {
  try {
    const res = await apiClient.get(`/evidences/${evidenceId}/download`, {
      responseType: 'blob',
    });
    const hash = res.headers['x-evidence-sha256'];
    const blob = new Blob([res.data]);
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || `evidence-${evidenceId}`;
    link.click();
    URL.revokeObjectURL(link.href);
    if (hash) {
      console.log(`Downloaded with verified SHA-256: ${hash}`);
    }
  } catch (err) {
    alert('Failed to download evidence from private vault.');
  }
}

function priorityBadgeClass(priority: string) {
  switch (priority) {
    case 'critical': return 'bg-red-950 text-red-300 border border-red-800';
    case 'high': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'medium': return 'bg-yellow-950 text-yellow-300 border border-yellow-800';
    case 'low': return 'bg-slate-800 text-slate-300';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function subjectRoleBadgeClass(role: string) {
  switch (role) {
    case 'target': return 'bg-red-950 text-red-300 border border-red-800';
    case 'suspect': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'associate': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'witness': return 'bg-blue-950 text-blue-300 border border-blue-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function evidenceStatusBadgeClass(status: string) {
  switch (status) {
    case 'verified': return 'bg-emerald-950 text-emerald-300 border border-emerald-800';
    case 'under_review': return 'bg-purple-950 text-purple-300 border border-purple-800';
    case 'pending': return 'bg-amber-950 text-amber-300 border border-amber-800';
    case 'rejected': return 'bg-red-950 text-red-300 border border-red-800';
    case 'conflicting': return 'bg-rose-950 text-rose-300 border border-rose-800';
    default: return 'bg-slate-800 text-slate-300';
  }
}

function getSubjectIcon(type: string) {
  switch (type) {
    case 'vessel': return '🚢';
    case 'company': return '🏢';
    case 'person': return '👤';
    case 'location': return '📍';
    default: return '📌';
  }
}

function getEvidenceTypeIcon(type: string) {
  switch (type) {
    case 'document': return '📄';
    case 'image': return '🖼️';
    case 'pdf': return '📕';
    case 'video': return '🎥';
    case 'audio': return '🎙️';
    case 'satellite_imagery': return '🛰️';
    default: return '📁';
  }
}

function formatCategory(cat: string) {
  return (cat || '').replace(/_/g, ' ').toUpperCase();
}

function formatDate(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(d: string) {
  if (!d) return '-';
  return new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatFileSize(bytes: number) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

onMounted(() => {
  fetchCaseDetail();
});
</script>
