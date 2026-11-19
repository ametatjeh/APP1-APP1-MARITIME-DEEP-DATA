<template>
  <div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-slate-900/90 p-8 rounded-2xl border border-slate-800 shadow-2xl backdrop-blur-sm">
      <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-white">MARITIME DEEP DATA</h2>
        <p class="text-xs uppercase tracking-widest text-cyan-400 font-semibold mt-1">App 1 — Private NGO Intelligence Platform</p>
        <p class="text-xs text-slate-400 mt-2">Sign in to access restricted maritime investigation archives</p>
      </div>

      <div v-if="authStore.error" class="p-3 rounded-lg bg-red-950/80 border border-red-800/80 text-red-300 text-xs flex items-center gap-2">
        <span>⚠️</span>
        <span>{{ authStore.error }}</span>
      </div>

      <form class="mt-6 space-y-4" @submit.prevent="handleLogin">
        <div>
          <label class="block text-xs font-medium text-slate-300 uppercase tracking-wider mb-1">Email Address</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all"
            placeholder="analyst@maritimedeepdata.org"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 uppercase tracking-wider mb-1">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all"
            placeholder="••••••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="authStore.loading"
          class="w-full py-2.5 px-4 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-medium text-sm transition-all shadow-lg shadow-cyan-950/50 flex items-center justify-center gap-2 disabled:opacity-50"
        >
          <span v-if="authStore.loading">Authenticating...</span>
          <span v-else>Access Secure Terminal</span>
        </button>
      </form>

      <!-- Quick Test Accounts Switcher for Analyst Convenience -->
      <div class="pt-4 border-t border-slate-800">
        <p class="text-xs text-slate-400 mb-2 font-medium">Quick Login As Role:</p>
        <div class="grid grid-cols-2 gap-2 text-xs">
          <button
            v-for="preset in presets"
            :key="preset.email"
            @click="fillPreset(preset)"
            type="button"
            class="px-2.5 py-1.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-left truncate transition-colors border border-slate-700/50"
          >
            <span class="font-semibold block text-cyan-300 truncate">{{ preset.label }}</span>
            <span class="text-[10px] text-slate-400 block truncate">{{ preset.email }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('admin@maritimedeepdata.org');
const password = ref('SecretAdmin2026!');

const presets = [
  { label: 'Super Admin', email: 'admin@maritimedeepdata.org', pass: 'SecretAdmin2026!' },
  { label: 'Research Director', email: 'director@maritimedeepdata.org', pass: 'DirectorDeepData2026!' },
  { label: 'Senior Investigator', email: 'senior.inv@maritimedeepdata.org', pass: 'InvestigatorSenior2026!' },
  { label: 'Research Analyst', email: 'analyst@maritimedeepdata.org', pass: 'AnalystPass2026!' },
];

function fillPreset(p: { email: string; pass: string }) {
  email.value = p.email;
  password.value = p.pass;
}

async function handleLogin() {
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push({ name: 'dashboard' });
  } catch (err) {
    // Handled in store
  }
}
</script>
