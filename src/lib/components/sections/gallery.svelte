<script lang="ts">
  import Card from '$lib/components/ui/card.svelte';
  import SectionHeading from '$lib/components/ui/section-heading.svelte';
  import { gallery } from '$lib/data';
</script>

<section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
  <div class="mx-auto max-w-7xl">
    <SectionHeading
      eyebrow="Atmosphere"
      title="Visual placeholders ready for your real photos or event moodboards"
      description="Replace these cards with venue previews, family moments, or a custom gallery once you have your final assets."
    />

    <div class="mt-10 grid gap-6 md:grid-cols-3">
      {#each gallery as item}
        <Card className="overflow-hidden p-0">
          <div class="aspect-[4/5] bg-[linear-gradient(160deg,rgba(217,119,6,0.28),rgba(15,23,42,0.2)),radial-gradient(circle_at_top,rgba(255,255,255,0.16),transparent_34%),linear-gradient(180deg,#1e293b,#0f172a)]"></div>
          <div class="p-5">
            <h3 class="text-lg font-semibold text-white">{item.title}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-300">{item.description}</p>
          </div>
        </Card>
      {/each}
    </div>
  </div>
</section>
