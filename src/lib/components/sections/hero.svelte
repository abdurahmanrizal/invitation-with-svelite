<script lang="ts">
  import { CalendarDays, MapPin, Sparkles } from "lucide-svelte";
  import Badge from "$lib/components/ui/badge.svelte";
  import Button from "$lib/components/ui/button.svelte";
  import Card from "$lib/components/ui/card.svelte";
  import { invitation, highlights } from "$lib/data";
</script>

<section
  class="relative overflow-hidden px-4 pb-14 pt-8 sm:px-6 lg:px-8 lg:pb-20 lg:pt-12"
>
  <div
    class="mx-auto grid max-w-7xl items-center gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:gap-10"
  >
    <div>
      <Badge>Private Invitation</Badge>
      <h1
        class="mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl"
      >
        {invitation.title}
      </h1>
      <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl">
        {invitation.subtitle}
      </p>

      <div
        class="mt-8 flex flex-col gap-3 text-sm text-slate-200 sm:flex-row sm:flex-wrap sm:items-center"
      >
        <div
          class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 backdrop-blur"
        >
          <CalendarDays size={16} />
          <span>{invitation.date} · {invitation.time}</span>
        </div>
        <div
          class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 backdrop-blur"
        >
          <MapPin size={16} />
          <span>{invitation.venue}</span>
        </div>
      </div>

      <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <Button href="#details" variant="outline" size="lg"
          >View Event Details</Button
        >
      </div>

      <p class="mt-5 text-sm text-slate-400">{invitation.host}</p>
    </div>

    <Card className="relative overflow-hidden p-0">
      <div
        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,.24),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(251,191,36,.14),transparent_30%)]"
      ></div>
      <div class="relative p-7 sm:p-8">
        <div class="flex items-center gap-3 text-amber-200">
          <Sparkles size={18} />
          <p class="text-sm font-medium uppercase tracking-[0.22em]">
            Blessed Gathering
          </p>
        </div>

        <div class="mt-7 space-y-4">
          {#each highlights as item}
            <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
              <h3 class="text-lg font-medium text-white">{item.title}</h3>
              <p class="mt-2 text-sm leading-6 text-slate-300">
                {item.description}
              </p>
            </div>
          {/each}
        </div>

        <div
          class="mt-6 rounded-2xl bg-amber-400/10 p-4 text-sm leading-6 text-amber-100 ring-1 ring-inset ring-amber-300/20"
        >
          “Labbaik Allahumma Labbaik” — may this gathering become the first step
          toward a journey filled with sincerity and barakah.
        </div>
      </div>
    </Card>
  </div>
</section>
