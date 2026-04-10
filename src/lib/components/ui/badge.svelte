<script lang="ts">
  import { cn } from "$lib/utils/cn";
  export let className = "";
</script>

<span
  class={cn(
    "inline-flex items-center rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.24em] text-amber-200",
    className,
  )}
>
  <slot />
</span>
