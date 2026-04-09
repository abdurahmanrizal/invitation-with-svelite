<script lang="ts">
  import { onMount } from "svelte";
  import { PUBLIC_PHP_API_URL } from '$env/static/public';
  import {
    CalendarDays,
    CircleCheckBig,
    MapPin,
    Loader2,
  } from "lucide-svelte";
  import Badge from "$lib/components/ui/badge.svelte";
  import Card from "$lib/components/ui/card.svelte";
  import QRCode from "qrcode";
  import { invitation } from "$lib/data";

  let reservation = $state<any>(null);
  let qrCodeDataUrl = $state("");
  let checkInUrl = $state("");
  let isLoading = $state(true);
  let error = $state("");

  const status = $derived(reservation?.status);

  onMount(async () => {
    // Get reservationCode from URL
    const pathParts = window.location.pathname.split("/");
    const reservationCode = pathParts[pathParts.length - 1];

    try {
      // Fetch reservation from PHP API
      const response = await fetch(`${PUBLIC_PHP_API_URL}/reservations.php`);
      const result = await response.json();

      if (result.success && result.reservations) {
        const found = result.reservations.find(
          (r: any) => r.reservationCode.toLowerCase() === reservationCode.toLowerCase()
        );

        if (found) {
          reservation = found;
          // Generate check-in URL pointing to PHP API
          checkInUrl = `${PUBLIC_PHP_API_URL}/reservation.php?code=${reservation.reservationCode}`;

          // Generate QR code
          const qrPayload = JSON.stringify({
            type: "umrah-gathering-reservation",
            reservationCode: reservation.reservationCode,
            guestName: reservation.guestName,
            checkInUrl
          });

          qrCodeDataUrl = await QRCode.toDataURL(qrPayload, {
            errorCorrectionLevel: "H",
            margin: 1,
            width: 320
          });
        } else {
          error = "Reservation not found";
        }
      } else {
        error = "Failed to load reservation";
      }
    } catch (e) {
      error = "Network error. Please try again.";
    } finally {
      isLoading = false;
    }
  });
</script>

<svelte:head>
  {#if reservation}
    <title>{reservation.guestName} · Reservation Invitation</title>
    <meta
      name="description"
      content={`Private invitation and QR reservation for ${reservation.guestName}.`}
    />
  {/if}
</svelte:head>

{#if isLoading}
  <div class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8 flex items-center justify-center">
    <div class="text-center">
      <Loader2 size={48} class="animate-spin text-amber-400 mx-auto mb-4" />
      <p class="text-slate-300">Loading invitation...</p>
    </div>
  </div>
{:else if error}
  <div class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8 flex items-center justify-center">
    <Card className="text-center">
      <h1 class="text-2xl font-semibold text-red-400 mb-4">Error</h1>
      <p class="text-slate-300">{error}</p>
    </Card>
  </div>
{:else}
  <div class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">
      <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
        <div>
          <Badge>Unique Guest Invitation</Badge>
          <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
            Assalamu'alaikum, {reservation.guestName}
          </h1>
          <p class="mt-3 max-w-2xl text-slate-300">
            This page is personalized for one reservation only. Please show this
            QR code during guest check-in.
          </p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <Card className="space-y-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm uppercase tracking-[0.24em] text-amber-200">
                Reservation Holder
              </p>
              <h2 class="mt-3 text-2xl font-semibold text-white">
                {reservation.guestName}
              </h2>
              <p class="mt-2 text-slate-300">
                {reservation.reservationCode}
              </p>
            </div>

            <div
              class="flex gap-2 rounded-full border px-4 py-2 text-sm font-medium whitespace-nowrap {[
                'confirmed',
                'checked_in',
              ].includes(status)
                ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200'
                : 'border-amber-400/20 bg-amber-400/10 text-amber-200'}"
            >
              {#if status === "checked_in" || status === "confirmed"}<div
                  class="flex items-center"
                >
                  <CircleCheckBig size={16} />
                </div>{/if}
              {#if status === "confirmed"}
                Confirmed
              {:else if status === "checked_in"}
                Checked In
              {:else}
                Pending
              {/if}
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-1">
            <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
              <div class="flex items-center gap-3 text-slate-200">
                <CalendarDays size={18} class="text-amber-200" />
                <span class="text-sm uppercase tracking-[0.2em] text-slate-400"
                  >Schedule</span
                >
              </div>
              <p class="mt-3 font-medium text-white">{invitation.date}</p>
              <p class="text-slate-300">{invitation.time}</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
              <div class="flex items-center gap-3 text-slate-200">
                <MapPin size={18} class="text-amber-200" />
                <span class="text-sm uppercase tracking-[0.2em] text-slate-400"
                  >Venue</span
                >
              </div>
              <p class="mt-3 font-medium text-white">{invitation.venue}</p>
              <p class="text-slate-300">{invitation.address}</p>
            </div>
          </div>

          <div
            class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-4 text-sm leading-6 text-amber-100"
          >
            Each invitation page is mapped to a single reservation code. You can
            later replace the mock data with a database, spreadsheet import, or
            admin CMS.
          </div>
        </Card>

        <Card
          className="flex flex-col items-center justify-center gap-6 text-center"
        >
          <div>
            <p class="text-sm uppercase tracking-[0.24em] text-amber-200">
              Entry QR Code
            </p>
            <h2 class="mt-3 text-2xl font-semibold text-white">
              Scan for reservation validation
            </h2>
            <p class="mt-3 max-w-md text-slate-300">
              The QR payload contains the reservation code, guest name, and a
              validation URL.
            </p>
          </div>

          <div class="rounded-[2rem] bg-white p-5 shadow-2xl shadow-black/30">
            <img
              src={qrCodeDataUrl}
              alt={`QR code for ${reservation.guestName}`}
              class="h-72 w-72 rounded-2xl"
            />
          </div>
        </Card>
      </div>
    </div>
  </div>
{/if}
