<script lang="ts">
  import { onMount } from "svelte";
  import { Mail, Phone, QrCode, UserRound } from "lucide-svelte";
  import Card from "$lib/components/ui/card.svelte";
  import type { GuestReservation } from "$lib/types";
  import { PUBLIC_PHP_API_URL } from "$env/static/public";
  let reservations: GuestReservation[] = [];
  let isLoading = true;
  let error: string | null = null;

  onMount(async () => {
    try {
      const response = await fetch(`${PUBLIC_PHP_API_URL}/reservations.php`);
      if (!response.ok) throw new Error("Failed to fetch reservations");

      const data = await response.json();
      if (data.success && data.reservations) {
        reservations = data.reservations;
      } else {
        throw new Error("Invalid response format");
      }
    } catch (e) {
      error = e instanceof Error ? e.message : "Unknown error";
      console.error("Error fetching reservations:", e);
    } finally {
      isLoading = false;
    }
  });
</script>

<section id="rsvp" class="px-4 pb-20 pt-16 sm:px-6 lg:px-8 lg:pb-24 lg:pt-20">
  <div class="mx-auto max-w-5xl">
    <Card className="relative overflow-hidden p-8 sm:p-10">
      <div
        class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(251,191,36,.14),transparent_36%),linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02))]"
      ></div>
      <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-start">
        <div>
          <p class="text-sm uppercase tracking-[0.22em] text-amber-200">
            RSVP, Contact & QR Access
          </p>
          <h2
            class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl"
          >
            Every invited guest can have a unique QR reservation.
          </h2>
          <p
            class="mt-4 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg"
          >
            Use a personalized page per guest, so the invitation displays their
            name, reservation code, seat, and a QR for entry validation.
          </p>

          <div class="mt-6 space-y-3 text-sm text-slate-300">
            <div class="flex items-center gap-3">
              <Phone size={16} class="text-amber-200" /> +62 812-3456-7890
            </div>
            <div class="flex items-center gap-3">
              <Mail size={16} class="text-amber-200" /> umrohthesultansmg@gmail.com
            </div>
          </div>
        </div>
      </div>

      <div class="relative mt-8 grid gap-4 lg:grid-cols-3">
        {#if isLoading}
          <div class="col-span-full text-center py-8 text-slate-400">
            Loading reservations...
          </div>
        {:else if error}
          <div class="col-span-full text-center py-8 text-red-400">
            Failed to load reservations: {error}
          </div>
        {:else if reservations.length === 0}
          <div class="col-span-full text-center py-8 text-slate-400">
            No reservations found
          </div>
        {:else}
          {#each reservations as guest}
            <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
              <div class="flex items-center gap-2 text-amber-200">
                <UserRound size={16} />
                <p class="text-sm font-medium">{guest.guestName}</p>
              </div>
              <p class="mt-2 text-sm text-slate-400">{guest.reservationCode}</p>
              <p class="mt-3 text-sm text-slate-300">{guest.seatLabel}</p>
              <a
                class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-amber-200 hover:text-amber-100"
                href={`/invitation/${guest.reservationCode}`}
              >
                <QrCode size={15} />
                Open personalized QR page
              </a>
            </div>
          {/each}
        {/if}
      </div>
    </Card>
  </div>
</section>
