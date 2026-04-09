<script lang="ts">
  import Card from '$lib/components/ui/card.svelte';
  import SectionHeading from '$lib/components/ui/section-heading.svelte';
  import { agenda } from '$lib/data';
</script>

<section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
  <div class="mx-auto max-w-7xl">
    <SectionHeading
      eyebrow="Program Flow"
      title="A simple, elegant evening itinerary"
      description="Structured to balance reflection, prayer, and warm companionship in one memorable gathering."
    />

    <div class="mx-auto mt-10 max-w-4xl space-y-4">
      {#each agenda as item, index}
        <Card className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-400/10 text-sm font-semibold text-amber-200">
              {index + 1}
            </div>
            <div>
              <h3 class="text-lg font-medium text-white">{item.title}</h3>
              <p class="text-sm text-slate-400">Gathering agenda</p>
            </div>
          </div>
          <div class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200">
            {item.time} WIB
          </div>
        </Card>
      {/each}
    </div>
  </div>
</section>
