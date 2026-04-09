<script lang="ts">
  import { cn } from '$lib/utils/cn';
  export let className = '';
</script>

<div class={cn('rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-xl', className)}>
  <slot />
</div>
