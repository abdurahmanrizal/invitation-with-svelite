<script lang="ts">
  import { onMount } from "svelte";
  import { fly } from "svelte/transition";
  import { PUBLIC_PHP_API_URL } from "$env/static/public";
  import {
    CalendarDays,
    CircleCheckBig,
    MapPin,
    Loader2,
    Check,
    X,
  } from "lucide-svelte";
  import Card from "$lib/components/ui/card.svelte";
  import QRCode from "qrcode";
  import { invitation } from "$lib/data";

  let reservation = $state<any>(null);
  let qrCodeDataUrl = $state("");
  let checkInUrl = $state("");
  let isLoading = $state(true);
  let error = $state("");
  let showContent = $state(false);
  let message = $state({ type: "", text: "" });
  let isSaving = $state(false);
  const status = $derived(reservation?.status);
  // Custom easing function
  const quintOut = (t: number) => 1 - Math.pow(1 - t, 5);

  async function loadReservation() {
    // Get reservationCode from URL
    const pathParts = window.location.pathname.split("/");
    const reservationCode = pathParts[pathParts.length - 1];

    try {
      // Fetch reservation from PHP API
      const response = await fetch(`${PUBLIC_PHP_API_URL}/reservations.php`);
      const result = await response.json();

      if (result.success && result.reservations) {
        const found = result.reservations.find(
          (r: any) =>
            r.reservationCode.toLowerCase() === reservationCode.toLowerCase(),
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
            checkInUrl,
          });

          qrCodeDataUrl = await QRCode.toDataURL(qrPayload, {
            errorCorrectionLevel: "H",
            margin: 1,
            width: 320,
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
      // Trigger content animation
      requestAnimationFrame(() => {
        showContent = true;
      });
    }
  }

  onMount(async () => {
    await loadReservation();
  });

  $effect(() => {
    if (message.text) {
      const timeout = setTimeout(() => {
        message = { type: "", text: "" };
      }, 5000);
      return () => clearTimeout(timeout);
    }
  });

  async function handleConfirm() {
    isSaving = true;
    try {
      const payload = {
        ...reservation,
        status: "confirmed",
      };
      const url = `${PUBLIC_PHP_API_URL}/reservation-id.php`;
      const response = await fetch(url, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await response.json();

      if (response.ok) {
        message = {
          type: "success",
          text: result.message || "Reservation saved!",
        };
        await loadReservation();
      } else {
        message = { type: "error", text: result.message || "Failed to save" };
      }
    } catch (error) {
      message = { type: "error", text: "Network error" };
    } finally {
      isSaving = false;
    }
  }
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
  <div
    class="page-wrapper min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8 flex items-center justify-center before-overlay"
  >
    <div class="text-center">
      <Loader2
        size={48}
        class="animate-spin mx-auto mb-4"
        style="color: #AB8713;"
      />
      <p class="text-gold-light">Loading invitation...</p>
    </div>
  </div>
{:else if error}
  <div
    class="page-wrapper min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8 flex items-center justify-center before-overlay"
  >
    <Card className="text-center">
      <h1 class="text-2xl font-semibold text-red-400 mb-4">Error</h1>
      <p class="text-gold-light">{error}</p>
    </Card>
  </div>
{:else}
  <div
    class="page-wrapper min-h-screen flex flex-col items-center justify-center py-8 sm:px-6 lg:px-8 before-overlay"
  >
    <div
      class="relative mx-auto max-w-md sm:max-w-xl lg:max-w-6xl w-full flex flex-col gap-3 mt-10"
      in:fly={{ y: -20, duration: 600, easing: quintOut }}
    >
      <div class="grid gap-6 lg:grid-cols-1">
        <div in:fly={{ x: -30, duration: 700, delay: 400, easing: quintOut }}>
          <!-- Message Alert -->

          <div class="p-3">
            <div class="flex justify-center">
              <img
                src="https://thesultanumroh.co.id/logo.png"
                alt="The Sultan Umrah - Travel Umrah &amp; Haji Terpercaya"
                class="h-14 w-auto"
                width="auto"
                height="56"
              />
            </div>
            <Card className="space-y-6 ">
              {#if message.text}
                <div
                  class="mb-6 rounded-2xl p-4 {message.type === 'success'
                    ? 'bg-slate-950 border border-emerald-400/20 text-white'
                    : 'bg-red-400/10 border border-red-400/20 text-red-600'}"
                >
                  <div class="flex items-center gap-3">
                    {#if message.type === "success"}
                      <Check size={20} />
                    {:else}
                      <X size={20} />
                    {/if}
                    <span>{message.text}</span>
                  </div>
                </div>
              {/if}
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p
                    class="text-sm uppercase tracking-[0.24em] text-gold-light font-semibold"
                  >
                    Konfirmasi Kehadiran
                  </p>
                  <h2
                    class="mt-3 text-2xl font-semibold text-gold whitespace-nowrap"
                  >
                    {reservation.guestName}
                  </h2>
                  <p class="mt-2 text-gold-light font-semibold">
                    {reservation.reservationCode}
                  </p>
                </div>

                <button
                  class="flex gap-2 rounded-full border px-4 py-2 text-sm font-medium whitespace-nowrap cursor-pointer disabled:cursor-not-allowed disabled:border-[rgba(171,135,19,0.2)] disabled:bg-[rgba(171,135,19,0.1)] disabled:text-[#D4AF37]"
                  class:border-[rgba(52,211,153,0.2)]={[
                    "confirmed",
                    "checked_in",
                  ].includes(status)}
                  class:bg-[rgba(52,211,153,0.1)]={[
                    "confirmed",
                    "checked_in",
                  ].includes(status)}
                  class:text-[#6ee7b7]={["confirmed", "checked_in"].includes(
                    status,
                  )}
                  class:border-[rgba(171,135,19,0.2)]={![
                    "confirmed",
                    "checked_in",
                  ].includes(status)}
                  class:bg-[rgba(171,135,19,0.1)]={![
                    "confirmed",
                    "checked_in",
                  ].includes(status)}
                  class:text-[#D4AF37]={!["confirmed", "checked_in"].includes(
                    status,
                  )}
                  disabled={["confirmed", "checked_in"].includes(status) ||
                    isSaving}
                  onclick={() => handleConfirm()}
                >
                  {#if isSaving}
                    <div class="flex gap-2">
                      <div class="flex items-center">
                        <Loader2 size={16} />
                      </div>
                      <span>Menyimpan...</span>
                    </div>
                  {:else}
                    {#if status === "checked_in" || status === "confirmed"}<div
                        class="flex items-center"
                      >
                        <CircleCheckBig size={16} />
                      </div>{/if}
                    {#if status === "confirmed"}
                      Hadir
                    {:else if status === "checked_in"}
                      Telah Hadir
                    {:else}
                      Hadir?
                    {/if}
                  {/if}
                </button>
              </div>

              <div class="grid gap-4 sm:grid-cols-1">
                <div
                  class="rounded-2xl border p-4"
                  style="border-color: rgba(171, 135, 19, 0.3); background: rgba(251, 243, 219, 0.15);"
                >
                  <div class="flex items-center gap-3 text-gold-light">
                    <CalendarDays size={18} class="text-gold" />
                    <span
                      class="text-xl md:text-2xl uppercase tracking-[0.2em] text-gold-light font-semibold"
                      >Tanggal</span
                    >
                  </div>
                  <p class="mt-3 font-medium text-gold text-xl md:text-2xl">
                    {invitation.date}
                  </p>
                  <p class="text-gold-light text-xl md:text-2xl">
                    {invitation.time}
                  </p>
                </div>

                <a
                  href={invitation.mapUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="rounded-2xl border p-4 block transition-opacity hover:opacity-80"
                  style="border-color: rgba(171, 135, 19, 0.3); background: rgba(251, 243, 219, 0.15);"
                >
                  <div class="flex items-center gap-3 text-gold-light">
                    <MapPin size={18} class="text-gold" />
                    <span
                      class="text-xl md:text-2xl uppercase tracking-[0.2em] text-gold-light font-semibold"
                      >Tempat</span
                    >
                  </div>
                  <p class="mt-3 font-medium text-gold text-xl md:text-2xl">
                    {invitation.venue}
                  </p>
                  <p class="text-gold-light text-xl md:text-2xl">
                    {invitation.address}
                  </p>
                </a>

                <a
                  href={invitation.mapUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  class="flex items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-medium transition-opacity hover:opacity-80"
                  style="border-color: rgba(171, 135, 19, 0.3); background: rgba(171, 135, 19, 0.2);"
                >
                  <MapPin size={28} class="fill-[#AB8713]" />
                  <span class="text-gold text-xl md:text-2xl font-semibold"
                    >Buka di Maps</span
                  >
                </a>
              </div>
            </Card>
          </div>
        </div>

        <div
          in:fly={{ x: 30, duration: 700, delay: 500, easing: quintOut }}
          class="p-3"
        >
          <Card
            className="flex flex-col items-center justify-center gap-6 text-center"
          >
            <div>
              <p
                class="text-sm uppercase tracking-[0.24em] text-gold-light font-bold"
              >
                QR Code Wajib Ditunjukan Saat Hadir
              </p>
            </div>

            <div
              class="rounded-[2rem] bg-white p-5 shadow-2xl shadow-black/30 flex flex-col gap-2"
            >
              <img
                src={qrCodeDataUrl}
                alt={`QR code for ${reservation.guestName}`}
                class="h-60 w-60 rounded-2xl"
              />
              <p class="text-gold font-semibold">
                {reservation.reservationCode}
              </p>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  .page-wrapper {
    position: relative;
  }

  .page-wrapper::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url("/bg-small.webp");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: -2;
    image-rendering: -webkit-optimize-contrast;
  }

  .before-overlay::after {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    pointer-events: none;
  }

  .text-gold {
    color: #ab8713;
  }

  .text-gold-light {
    color: #d4af37;
  }

  :global(.card) {
    background: rgba(251, 243, 219, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(171, 135, 19, 0.3);
  }

  @media (min-width: 640px) {
    .page-wrapper::before {
      background-image: url("/bg-medium.webp");
    }
  }

  @media (min-width: 1024px) {
    .page-wrapper::before {
      background-image: url("/bg-large.webp");
    }
  }
</style>
