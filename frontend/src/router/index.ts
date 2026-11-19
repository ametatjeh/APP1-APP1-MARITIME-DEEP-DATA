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

        // Modules scheduled for Phase 2-6 (Displayed with clear NGO status badge and Phase tag)
        { path: 'persons', name: 'persons', component: GenericPhaseView, props: { title: 'Persons Intelligence', phase: 3, description: 'Encrypted person profiles, intelligence links, and corporate ties.' } },
        { path: 'crew', name: 'crew', component: GenericPhaseView, props: { title: 'Crew Intelligence', phase: 3, description: 'Crew lists, sea employment history, skipper track records, and labour compliance.' } },
        { path: 'investigations', name: 'investigations', component: GenericPhaseView, props: { title: 'Investigation Cases', phase: 2, description: 'Case subjects, timelines, hypotheses, and analytical conclusions.' } },
        { path: 'evidence', name: 'evidence', component: GenericPhaseView, props: { title: 'Evidence Management', phase: 2, description: 'SHA-256 authenticated documents, private object storage, chain of custody.' } },
        { path: 'iuu', name: 'iuu', component: GenericPhaseView, props: { title: 'IUU Intelligence', phase: 3, description: 'Official RFMO lists, illegal fishing sanctions, and unverified allegations.' } },
        { path: 'gis-map', name: 'gis-map', component: GenericPhaseView, props: { title: 'GIS Research Map', phase: 3, description: 'Multi-layer spatial analytics, boundary overlays, and research polygons.' } },
        { path: 'data-sources', name: 'data-sources', component: GenericPhaseView, props: { title: 'Data Sources Registry', phase: 4, description: 'Source reliability scoring, ingestion pipelines, and verification logs.' } },
        { path: 'watchlists', name: 'watchlists', component: GenericPhaseView, props: { title: 'Watchlists & Alerts', phase: 5, description: 'Automated monitoring of target vessels, corporations, and beneficial owners.' } },
        { path: 'reports', name: 'reports', component: GenericPhaseView, props: { title: 'Reports & Export', phase: 5, description: 'Evidence-linked dossier compilation, PDF export, and court-ready documentation.' } },
        { path: 'ai-assistant', name: 'ai-assistant', component: GenericPhaseView, props: { title: 'AI Research Assistant', phase: 6, description: 'Multi-Agent research orchestration, vector search, and RAG synthesis with strict evidence citations.' } },
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
