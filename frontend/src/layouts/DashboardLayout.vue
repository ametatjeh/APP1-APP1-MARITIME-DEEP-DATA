<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-row">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col fixed inset-y-0 z-30">
      <!-- App Brand -->
      <div class="h-16 flex items-center px-5 border-b border-slate-800 gap-3">
        <div class="w-8 h-8 rounded-lg bg-cyan-600/20 border border-cyan-500/40 flex items-center justify-center text-cyan-400 font-bold text-sm">
          MD
        </div>
        <div>
          <h1 class="text-xs font-bold tracking-wider text-white">MARITIME DEEP DATA</h1>
          <span class="text-[10px] text-cyan-400 block font-mono">APP 1 • STANDALONE</span>
        </div>
      </div>

      <!-- Navigation Menu Groups (21 Menus) -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-xs">
        <!-- Group 1: Core Operations -->
        <div>
          <div class="px-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
            Core Master Data
          </div>
          <div class="space-y-0.5">
            <router-link
              v-for="item in group1"
              :key="item.path"
              :to="item.path"
              class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg font-medium transition-colors"
              :class="$route.path.startsWith(item.path) ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
            >
              <span>{{ item.icon }}</span>
              <span>{{ item.label }}</span>
              <span v-if="item.badge" class="ml-auto text-[9px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-mono">
                {{ item.badge }}
              </span>
            </router-link>
          </div>
        </div>

        <!-- Group 2: Maritime Geopolitics & GIS -->
        <div>
          <div class="px-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
            Maritime Jurisdiction
          </div>
          <div class="space-y-0.5">
            <router-link
              v-for="item in group2"
              :key="item.path"
              :to="item.path"
              class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg font-medium transition-colors"
              :class="$route.path.startsWith(item.path) ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
            >
              <span>{{ item.icon }}</span>
              <span>{{ item.label }}</span>
              <span v-if="item.badge" class="ml-auto text-[9px] px-1.5 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-mono">
                {{ item.badge }}
              </span>
            </router-link>
          </div>
        </div>

        <!-- Group 3: Investigation & Intelligence -->
        <div>
          <div class="px-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
            Investigation & IUU
          </div>
          <div class="space-y-0.5">
            <router-link
              v-for="item in group3"
              :key="item.path"
              :to="item.path"
              class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg font-medium transition-colors"
              :class="$route.path.startsWith(item.path) ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
            >
              <span>{{ item.icon }}</span>
              <span>{{ item.label }}</span>
              <span v-if="item.badge" class="ml-auto text-[9px] px-1.5 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 font-mono">
                {{ item.badge }}
              </span>
              <span v-else-if="item.phase" class="ml-auto text-[8px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">
                P{{ item.phase }}
              </span>
            </router-link>
          </div>
        </div>

        <!-- Group 4: Analytics, AI & Administration -->
        <div>
          <div class="px-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
            Analytics & Governance
          </div>
          <div class="space-y-0.5">
            <router-link
              v-for="item in group4"
              :key="item.path"
              :to="item.path"
              class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg font-medium transition-colors"
              :class="$route.path.startsWith(item.path) ? 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'"
            >
              <span>{{ item.icon }}</span>
              <span>{{ item.label }}</span>
              <span v-if="item.badge" class="ml-auto text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono">
                {{ item.badge }}
              </span>
              <span v-else-if="item.phase" class="ml-auto text-[8px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">
                P{{ item.phase }}
              </span>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- User Profile Card -->
      <div class="p-3 border-t border-slate-800 bg-slate-900/80">
        <div class="flex items-center justify-between mb-2">
          <div class="truncate">
            <div class="text-xs font-semibold text-white truncate">{{ authStore.user?.name }}</div>
            <div class="text-[10px] text-cyan-400 truncate">{{ authStore.user?.role_label }}</div>
          </div>
          <button
            @click="handleLogout"
            title="Sign out"
            class="p-1.5 rounded bg-slate-800 hover:bg-red-950/60 hover:text-red-300 text-slate-400 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
        <div class="text-[9px] text-slate-500 flex items-center justify-between font-mono">
          <span>SEC LEVEL: RESTRICTED</span>
          <span class="text-emerald-400">ONLINE</span>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">
      <!-- Top Bar -->
      <header class="h-16 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between px-8 sticky top-0 z-20 backdrop-blur-sm">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-bold text-white tracking-wide uppercase">
            {{ $route.meta.title || 'Intelligence Workspace' }}
          </h2>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-cyan-950 border border-cyan-800/80 text-cyan-300">
            PHASE 5 ACTIVE
          </span>
        </div>

        <div class="flex items-center gap-4 text-xs">
          <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded bg-slate-950 border border-slate-800 text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>PostgreSQL 18 + PostGIS Connected</span>
          </div>
          <div class="px-2.5 py-1 rounded bg-slate-800 text-slate-300 font-mono text-[11px]">
            {{ authStore.user?.role }}
          </div>
        </div>
      </header>

      <!-- View Router Container -->
      <main class="flex-1 p-8 bg-slate-950">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

interface NavItem {
  path: string;
  label: string;
  icon: string;
  badge?: string;
  phase?: number;
}

// 21 Navigation Menus strictly mapped
const group1: NavItem[] = [
  { path: '/dashboard', label: '1. Dashboard', icon: '📊' },
  { path: '/vessels', label: '2. Vessels Master', icon: '🚢' },
  { path: '/vessel-identity', label: '3. Vessel Identity', icon: '🔍', badge: 'Alerts' },
  { path: '/corporate', label: '4. Corporate Intel', icon: '🏢' },
  { path: '/persons', label: '5. Persons & UBOs', icon: '👤', badge: 'Encrypted' },
  { path: '/crew', label: '6. Crew Intelligence', icon: '⚓', badge: 'ILO 11' },
];

const group2: NavItem[] = [
  { path: '/countries', label: '7. Countries', icon: '🌐' },
  { path: '/jurisdictions', label: '8. Maritime Jurisdiction', icon: '🗺️' },
  { path: '/eez-explorer', label: '9. EEZ Explorer', icon: '🧭', badge: 'PostGIS' },
  { path: '/gis-map', label: '13. GIS Research Map', icon: '📍', badge: 'Layers' },
];

const group3: NavItem[] = [
  { path: '/investigations', label: '10. Investigation Cases', icon: '📂', badge: 'Active' },
  { path: '/evidence', label: '11. Evidence Vault', icon: '🛡️', badge: 'SHA-256' },
  { path: '/verification', label: '11b. Verification Queue', icon: '⚖️', badge: 'Review' },
  { path: '/chain-of-custody', label: '11c. Chain of Custody', icon: '📜' },
  { path: '/iuu', label: '12. IUU Intelligence', icon: '🚨', badge: 'RFMO' },
  { path: '/watchlists', label: '15. Target Watchlists', icon: '👁️', badge: 'P5 Active' },
  { path: '/reports', label: '17. Reports & Dossiers', icon: '📑', badge: 'Evidence' },
];

const group4: NavItem[] = [
  { path: '/network-graph', label: '16. Network Graph Canvas', icon: '🕸️', badge: 'Multi-Hop' },
  { path: '/search', label: '20b. Global Intelligence Search', icon: '🔍', badge: 'Fuzzy' },
  { path: '/data-sources', label: '14. Data Sources', icon: '📡', badge: 'Registry' },
  { path: '/imports', label: '14b. Ingestion Pipelines', icon: '📦', badge: 'Staging' },
  { path: '/datasets/versions', label: '14c. Dataset Versions', icon: '🏷️', badge: 'SHA-256' },
  { path: '/validation-inspector', label: '14d. Validation Engine', icon: '🔬', badge: 'IMO/MID' },
  { path: '/ai-assistant', label: '18. AI Research Assistant', icon: '🤖', phase: 6 },
  { path: '/users', label: '19. User Management', icon: '👥' },
  { path: '/audit-logs', label: '20. Audit Logs', icon: '📜' },
  { path: '/settings', label: '21. Settings', icon: '⚙️' },
];

async function handleLogout() {
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>
