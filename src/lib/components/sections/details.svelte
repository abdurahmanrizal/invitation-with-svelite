<script lang="ts">
  import { Clock3, MapPinned, PlaneTakeoff } from 'lucide-svelte';
  import Card from '$lib/components/ui/card.svelte';
  import SectionHeading from '$lib/components/ui/section-heading.svelte';
  import { invitation, essentials } from '$lib/data';
</script>

<section id="details" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
  <div class="mx-auto max-w-7xl">
    <SectionHeading
      eyebrow="Event Details"
      title="Designed to feel warm, calm, and spiritually uplifting"
      description="A polished invitation page for your Umrah gathering with clear event details, a luxury feel, and responsive layout."
    />

    <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_1fr]">
      <Card className="space-y-6">
        <div class="flex items-start gap-4">
          <div class="rounded-2xl bg-amber-400/10 p-3 text-amber-200"><Clock3 size={20} /></div>
          <div>
            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">Schedule</p>
            <h3 class="mt-2 text-xl font-semibold text-white">{invitation.date}</h3>
            <p class="mt-1 text-slate-300">{invitation.time}</p>
          </div>
        </div>

        <div class="flex items-start gap-4">
          <div class="rounded-2xl bg-amber-400/10 p-3 text-amber-200"><MapPinned size={20} /></div>
          <div>
            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">Location</p>
            <h3 class="mt-2 text-xl font-semibold text-white">{invitation.venue}</h3>
            <p class="mt-1 text-slate-300">{invitation.address}</p>
            <a class="mt-3 inline-flex text-sm font-medium text-amber-200 hover:text-amber-100" href={invitation.mapUrl} target="_blank" rel="noreferrer">Open map →</a>
          </div>
        </div>
      </Card>

      <Card>
        <div class="flex items-center gap-3 text-white">
          <PlaneTakeoff size={18} class="text-amber-200" />
          <h3 class="text-xl font-semibold">Guest Notes</h3>
        </div>
        <div class="mt-6 grid gap-3">
          {#each essentials as item}
            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3 text-sm text-slate-300">
              {item}
            </div>
          {/each}
        </div>
      </Card>
    </div>
  </div>
</section>
