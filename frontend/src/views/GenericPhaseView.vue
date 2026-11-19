<template>
  <div class="space-y-6">
    <div class="p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center max-w-2xl mx-auto space-y-4 shadow-xl">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-cyan-600/10 border border-cyan-500/30 text-cyan-400 text-2xl mb-2">
        🔒
      </div>
      <div>
        <h3 class="text-xl font-bold text-white">{{ title }}</h3>
        <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-950 text-amber-300 border border-amber-800">
          SCHEDULED FOR PHASE {{ phase }} IMPLEMENTATION
        </span>
      </div>

      <p class="text-xs text-slate-300 leading-relaxed max-w-lg mx-auto">
        {{ description }}
      </p>

      <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-left text-xs space-y-2 font-mono">
        <div class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">NGO Scope Directive</div>
        <div class="text-slate-300">
          • Strict adherence to project architecture: Phase 1 Foundation & Master Data must be fully stabilized and tested before Phase {{ phase }} unlocks.
        </div>
        <div class="text-slate-300">
          • App 1 Standalone: No live AIS streaming or raw receiver dependency.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  phase: number | string;
  description: string;
}>();
</script>
