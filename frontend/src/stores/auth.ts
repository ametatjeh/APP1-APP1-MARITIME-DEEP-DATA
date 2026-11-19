import { defineStore } from 'pinia';
import apiClient from '@/api/client';

export interface User {
  id: string;
  name: string;
  email: string;
  role: string;
  role_label: string;
  country_restriction_id?: string | null;
  can_edit: boolean;
  can_resolve_conflicts: boolean;
  can_view_redacted: boolean;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('mdd_auth_user') || 'null') as User | null,
    token: localStorage.getItem('mdd_auth_token') || '',
    loading: false,
    error: null as string | null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    userRole: (state) => state.user?.role || 'viewer',
    canEdit: (state) => !!state.user?.can_edit,
    canResolveConflicts: (state) => !!state.user?.can_resolve_conflicts,
  },
  actions: {
    async login(credentials: { email: string; password: string }) {
      this.loading = true;
      this.error = null;
      try {
        const response = await apiClient.post('/auth/login', credentials);
        this.token = response.data.token;
        this.user = response.data.user;
        localStorage.setItem('mdd_auth_token', this.token);
        localStorage.setItem('mdd_auth_user', JSON.stringify(this.user));
        return true;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Login failed. Please verify credentials.';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    async logout() {
      try {
        if (this.token) {
          await apiClient.post('/auth/logout');
        }
      } catch (err) {
        // silent fail on network error during logout
      } finally {
        this.token = '';
        this.user = null;
        localStorage.removeItem('mdd_auth_token');
        localStorage.removeItem('mdd_auth_user');
      }
    },
    async fetchUser() {
      if (!this.token) return null;
      try {
        const response = await apiClient.get('/auth/me');
        this.user = response.data;
        localStorage.setItem('mdd_auth_user', JSON.stringify(this.user));
        return this.user;
      } catch (err) {
        this.logout();
        return null;
      }
    },
  },
});
