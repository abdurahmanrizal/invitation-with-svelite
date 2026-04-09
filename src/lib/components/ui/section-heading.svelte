<script lang="ts">
  import Badge from '$lib/components/ui/badge.svelte';
  export let eyebrow = '';
  export let title = '';
  export let description = '';
</script>

<div class="mx-auto max-w-2xl text-center">
  {#if eyebrow}
    <Badge>{eyebrow}</Badge>
  {/if}
  <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">{title}</h2>
  {#if description}
    <p class="mt-4 text-base leading-7 text-slate-300 sm:text-lg">{description}</p>
  {/if}
</div>
