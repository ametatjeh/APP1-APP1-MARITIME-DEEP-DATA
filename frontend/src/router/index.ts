import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import LoginView from '@/views/LoginView.vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

import DashboardView from '@/views/DashboardView.vue';
import VesselsView from '@/views/VesselsView.vue';
import VesselIdentityView from '@/views/VesselIdentityView.vue';
import CorporateView from '@/views/CorporateView.vue';
import CountriesView from '@/views/CountriesView.vue';
import MaritimeJurisdictionView from '@/views/MaritimeJurisdictionView.vue';
import EezExplorerView from '@/views/EezExplorerView.vue';
import NetworkGraphView from '@/views/NetworkGraphView.vue';
import InvestigationsView from '@/views/InvestigationsView.vue';
import CaseDetailView from '@/views/CaseDetailView.vue';
import EvidenceView from '@/views/EvidenceView.vue';
import EvidenceDetailView from '@/views/EvidenceDetailView.vue';
import DataSourcesView from '@/views/DataSourcesView.vue';
import VerificationWorkspaceView from '@/views/VerificationWorkspaceView.vue';
import ChainOfCustodyView from '@/views/ChainOfCustodyView.vue';
import PersonsView from '@/views/PersonsView.vue';
import CrewIntelligenceView from '@/views/CrewIntelligenceView.vue';
import IuuIntelligenceView from '@/views/IuuIntelligenceView.vue';
import GisResearchMapView from '@/views/GisResearchMapView.vue';
import ImportPipelinesView from '@/views/ImportPipelinesView.vue';
import DatasetVersioningView from '@/views/DatasetVersioningView.vue';
import DataValidationInspectorView from '@/views/DataValidationInspectorView.vue';
import WatchlistsView from '@/views/WatchlistsView.vue';
import ReportsView from '@/views/ReportsView.vue';
import AdvancedSearchView from '@/views/AdvancedSearchView.vue';
import AiAssistantView from '@/views/AiAssistantView.vue';
import GenericPhaseView from '@/views/GenericPhaseView.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true },
    },
    {
      path: '/',
      component: DashboardLayout,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/dashboard' },
        { path: 'dashboard', name: 'dashboard', component: DashboardView, meta: { title: 'Dashboard', icon: 'LayoutDashboard' } },
        { path: 'vessels', name: 'vessels', component: VesselsView, meta: { title: 'Vessel Master Database', icon: 'Ship' } },
        { path: 'vessel-identity', name: 'vessel-identity', component: VesselIdentityView, meta: { title: 'Vessel Identity Intelligence', icon: 'Fingerprint' } },
        { path: 'corporate', name: 'corporate', component: CorporateView, meta: { title: 'Corporate Intelligence', icon: 'Building2' } },
        { path: 'countries', name: 'countries', component: CountriesView, meta: { title: 'Country Management', icon: 'Globe' } },
        { path: 'jurisdictions', name: 'jurisdictions', component: MaritimeJurisdictionView, meta: { title: 'Maritime Jurisdiction', icon: 'Anchor' } },
        { path: 'eez-explorer', name: 'eez-explorer', component: EezExplorerView, meta: { title: 'EEZ Explorer', icon: 'Compass' } },
        { path: 'network-graph', name: 'network-graph', component: NetworkGraphView, meta: { title: 'Network Graph Canvas', icon: 'Share2' } },

        // Phase 2: Active Core Modules
        { path: 'investigations', name: 'investigations', component: InvestigationsView, meta: { title: 'Investigation Cases', icon: 'FolderGit2' } },
        { path: 'investigations/:id', name: 'case-detail', component: CaseDetailView, meta: { title: 'Case Investigation Workspace', icon: 'FolderGit2' } },
        { path: 'evidence', name: 'evidence', component: EvidenceView, meta: { title: 'Evidence Vault & Custody', icon: 'Shield' } },
        { path: 'evidence/:id', name: 'evidence-detail', component: EvidenceDetailView, meta: { title: 'Evidence Dossier & Custody', icon: 'Shield' } },
        { path: 'data-sources', name: 'data-sources', component: DataSourcesView, meta: { title: 'Data Sources Registry', icon: 'Database' } },
        { path: 'verification', name: 'verification', component: VerificationWorkspaceView, meta: { title: 'Verification Queue', icon: 'CheckCircle' } },
        { path: 'chain-of-custody', name: 'chain-of-custody', component: ChainOfCustodyView, meta: { title: 'Forensic Chain of Custody', icon: 'FileText' } },

        // Phase 3: Active Intelligence & Spatial Modules
        { path: 'persons', name: 'persons', component: PersonsView, meta: { title: 'Persons Intelligence', icon: 'Users' } },
        { path: 'crew', name: 'crew', component: CrewIntelligenceView, meta: { title: 'Crew Intelligence & Forced Labour', icon: 'Anchor' } },
        { path: 'iuu', name: 'iuu', component: IuuIntelligenceView, meta: { title: 'IUU Intelligence & Sanctions', icon: 'AlertTriangle' } },
        { path: 'gis-map', name: 'gis-map', component: GisResearchMapView, meta: { title: 'GIS Research Map', icon: 'Map' } },

        // Phase 4: Data Import Pipelines, Validation & Versioning
        { path: 'imports', name: 'imports', component: ImportPipelinesView, meta: { title: 'Data Ingestion Pipelines', icon: 'Package' } },
        { path: 'datasets/versions', name: 'dataset-versions', component: DatasetVersioningView, meta: { title: 'Dataset Versions & Snapshots', icon: 'Layers' } },
        { path: 'validation-inspector', name: 'validation-inspector', component: DataValidationInspectorView, meta: { title: 'Data Validation Engine', icon: 'CheckSquare' } },

        // Phase 5: Watchlists, Reports & Dossiers, Search & Network Graph
        { path: 'watchlists', name: 'watchlists', component: WatchlistsView, meta: { title: 'Target Watchlists & Alerts', icon: 'Eye' } },
        { path: 'reports', name: 'reports', component: ReportsView, meta: { title: 'Dossiers & Research Reports', icon: 'FileText' } },
        { path: 'search', name: 'search', component: AdvancedSearchView, meta: { title: 'Advanced Intelligence Search', icon: 'Search' } },

        // Modules scheduled for Phase 6
        { path: 'ai-assistant', name: 'ai-assistant', component: AiAssistantView, meta: { title: 'AI Research Assistant', icon: 'Bot' } },
        { path: 'users', name: 'users', component: GenericPhaseView, props: { title: 'User Management', phase: 1, description: 'Role-based access control (RBAC), country restrictions, and team assignment.' } },
        { path: 'audit-logs', name: 'audit-logs', component: GenericPhaseView, props: { title: 'Audit Trail', phase: 1, description: 'Immutable log of user actions, evidence access, and identity conflict resolutions.' } },
        { path: 'settings', name: 'settings', component: GenericPhaseView, props: { title: 'System Settings', phase: 1, description: 'System parameters, private storage keys, and PostGIS configuration.' } },
      ],
    },
  ],
});

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' });
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;
