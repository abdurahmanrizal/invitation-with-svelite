<script lang="ts">
  import Hero from "$lib/components/sections/hero.svelte";
  import Details from "$lib/components/sections/details.svelte";
  import Agenda from "$lib/components/sections/agenda.svelte";
  import Gallery from "$lib/components/sections/gallery.svelte";
  import Rsvp from "$lib/components/sections/rsvp.svelte";
</script>

<svelte:head>
  <title>The Sultan Umroh - Umrah Gathering Invitation</title>
  <meta
    name="description"
    content="Elegant SvelteKit invitation landing page for an Umrah gathering event."
  />
  <link
    rel="icon"
    type="image/png"
    href="https://thesultanumroh.co.id/favicon.png"
  />
  <link
    rel="apple-touch-icon"
    type="image/png"
    href="https://thesultanumroh.co.id/favicon.png"
  />
</svelte:head>

<div class="min-h-screen bg-slate-950 text-white">
  <div
    class="pointer-events-none absolute inset-x-0 top-0 -z-0 h-[32rem] bg-[radial-gradient(circle_at_top,rgba(245,158,11,0.2),transparent_36%),radial-gradient(circle_at_top_right,rgba(251,191,36,0.12),transparent_24%)]"
  ></div>
  <main class="relative z-10">
    <Hero />
    <Details />
    <Agenda />
    <Gallery />
    <Rsvp />
  </main>
</div>
