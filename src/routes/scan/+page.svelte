<script lang="ts">
  import { onMount, onDestroy, tick } from "svelte";
  import { PUBLIC_PHP_API_URL } from "$env/static/public";
  import QrScanner from "qr-scanner";
  import {
    Camera,
    CameraOff,
    ScanLine,
    CheckCircle,
    XCircle,
    Loader2,
  } from "lucide-svelte";
  import Card from "$lib/components/ui/card.svelte";
  import Button from "$lib/components/ui/button.svelte";
  import Badge from "$lib/components/ui/badge.svelte";

  let videoElement: HTMLVideoElement;
  let qrScanner: QrScanner | null = null;
  let videoKey = 0;

  let scanResult: "success" | "error" | "scanning" = "scanning";
  let resultData: any = null;
  let errorMessage = "";
  let isCameraActive = false;
  let isCheckedIn = false;
  let checkInLoading = false;

  onMount(async () => {
    await tick(); // Ensure videoElement is bound before creating scanner
    try {
      qrScanner = new QrScanner(
        videoElement,
        handleQrResult,
        {
          highlightScanRegion: true,
          highlightCodeOutline: true,
        },
      );

      await startScanner();
    } catch (error) {
      scanResult = "error";
      errorMessage =
        "Failed to access camera. Please allow camera permissions.";
    }
  });

  onDestroy(() => {
    if (qrScanner) {
      qrScanner.destroy();
      qrScanner = null;
    }
  });

  // QR scan callback handler
  async function handleQrResult(result: any) {
    if (checkInLoading) return;

    try {
      const parsedData = JSON.parse(result.data);
      if (parsedData.type !== "umrah-gathering-reservation") {
        scanResult = "error";
        errorMessage =
          "Invalid QR code type. Please scan a valid reservation QR code.";
        stopScanner();
        return;
      }

      const { reservationCode, checkInUrl } = parsedData;
      console.info(checkInUrl);
      // Fetch reservation details from PHP API
      const response = await fetch(
        `${PUBLIC_PHP_API_URL}/reservation.php?code=${reservationCode}`,
      );
      const data = await response.json();

      if (!data.valid) {
        scanResult = "error";
        errorMessage = data.message || "Reservation not found";
        stopScanner();
        return;
      }

      scanResult = "success";
      resultData = { ...data, checkInUrl };
      isCheckedIn = data.status === "checked_in" ? true : false;
      stopScanner();
    } catch (e) {
      scanResult = "error";
      errorMessage =
        "Invalid QR code format. Please scan a valid reservation QR code.";
      stopScanner();
    }
  }

  async function startScanner() {
    try {
      // Destroy existing instance
      if (qrScanner) {
        try {
          qrScanner.destroy();
        } catch {
          // Ignore destroy errors
        }
        qrScanner = null;
      }

      // Wait for video element to be available
      await tick();
      if (!videoElement) return;

      qrScanner = new QrScanner(
        videoElement,
        handleQrResult,
        {
          highlightScanRegion: true,
          highlightCodeOutline: true,
        },
      );

      await qrScanner.start();
      isCameraActive = true;
      scanResult = "scanning";
      errorMessage = "";
      resultData = null;
    } catch (error) {
      scanResult = "error";
      errorMessage = "Failed to start camera. Please check camera permissions.";
    }
  }

  function stopScanner() {
    if (!qrScanner) return;
    qrScanner.stop();
    isCameraActive = false;
  }

  async function handleCheckIn() {
    if (scanResult !== "success" || !resultData?.checkInUrl) return;

    checkInLoading = true;
    try {
      const response = await fetch(resultData.checkInUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
      });

      const result = await response.json();

      if (response.ok) {
        isCheckedIn = true;
        resultData.status = "checked_in";
        resultData.checkedInAt = result.checkedInAt;
      } else {
        scanResult = "error";
        errorMessage = result.message || "Check-in failed. Please try again.";
      }
    } catch (error) {
      scanResult = "error";
      errorMessage = "Network error. Please try again.";
    } finally {
      checkInLoading = false;
    }
  }

  async function resetScan() {
    videoKey += 1; // Force video element recreation
    isCheckedIn = false;
    errorMessage = "";
    resultData = null;
    scanResult = "scanning";

    // Wait for video element to be recreated and bound
    await tick();
    await tick(); // Double tick to ensure video is ready
    await startScanner();
  }
</script>

<svelte:head>
  <title>QR Check-in Scanner</title>
  <meta name="description" content="Scan QR codes for guest check-in" />
</svelte:head>

<div class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8">
  <div class="mx-auto max-w-2xl">
    <!-- Header -->
    <div class="mb-8 text-center">
      <Badge>Check-in Scanner</Badge>
      <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
        Scan Guest QR Code
      </h1>
      <p class="mt-3 text-slate-300">
        Point the camera at the guest's invitation QR code to check them in
      </p>
    </div>

    <!-- Scanner Card -->
    <Card className="overflow-hidden">
      {#if scanResult === "scanning"}
        <!-- Camera View -->
        <div class="relative aspect-square bg-black">
          {#key videoKey}
            <video bind:this={videoElement} class="h-full w-full object-cover"
            ></video>
          {/key}

          <!-- Scan Overlay -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div
              class="relative flex h-48 w-48 items-center justify-center rounded-2xl border-2 border-amber-400 bg-amber-400/10"
            >
              <ScanLine
                size={32}
                class="absolute text-amber-400 animate-pulse"
              />
            </div>
          </div>

          <!-- Status Bar -->
          <div
            class="absolute bottom-0 left-0 right-0 rounded-b-3xl bg-gradient-to-t from-black/80 to-transparent p-6"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div
                  class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"
                ></div>
                <span class="text-sm font-medium text-emerald-200"
                  >Waiting for QR code...</span
                >
              </div>
              <button
                on:click={isCameraActive ? stopScanner : startScanner}
                class="rounded-full bg-white/10 p-2 hover:bg-white/20 transition-colors"
              >
                {#if isCameraActive}
                  <CameraOff size={20} class="text-white" />
                {:else}
                  <Camera size={20} class="text-white" />
                {/if}
              </button>
            </div>
          </div>
        </div>

        <!-- Instructions -->
        <div class="p-6">
          <div
            class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-4 text-sm leading-6 text-amber-100"
          >
            <p class="font-medium mb-2">Scanning Tips:</p>
            <ul class="list-disc list-inside space-y-1 text-amber-200/80">
              <li>Hold the device steady</li>
              <li>Ensure good lighting</li>
              <li>Position QR code within the frame</li>
              <li>Keep 10-20cm distance from the code</li>
            </ul>
          </div>
        </div>
      {:else if scanResult === "success"}
        <!-- Success Result -->
        <div class="p-6">
          <div class="mb-6 flex items-center justify-center">
            <div
              class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-400/20"
            >
              {#if checkInLoading}
                <Loader2 size={32} class="text-emerald-400 animate-spin" />
              {:else if isCheckedIn}
                <CheckCircle size={32} class="text-emerald-400" />
              {:else}
                <CheckCircle size={32} class="text-emerald-400" />
              {/if}
            </div>
          </div>

          <h2 class="mb-6 text-center text-2xl font-semibold text-white">
            {isCheckedIn ? "Guest Checked In!" : resultData.guestName}
          </h2>

          <!-- Reservation Details -->
          <div class="mb-6 space-y-4">
            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                Reservation Code
              </p>
              <p class="mt-1 font-mono text-lg text-white">
                {resultData.reservationCode}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                  Guests
                </p>
                <p class="mt-1 text-lg text-white">
                  {resultData.allowedGuests}
                </p>
              </div>

              <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                  Status
                </p>
                <p class="mt-1 text-lg text-white">
                  {resultData.status.replace("_", " ")?.toUpperCase()}
                </p>
              </div>
            </div>

            <!-- {#if resultData.seatLabel}
              <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">
                  Seat Assignment
                </p>
                <p class="mt-1 text-lg text-white">
                  {resultData.seatLabel}
                </p>
              </div>
            {/if} -->

            {#if isCheckedIn && resultData.checkedInAt}
              <div
                class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4"
              >
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-400">
                  Checked In At
                </p>
                <p class="mt-1 text-sm text-emerald-200">
                  {new Date(resultData.checkedInAt).toLocaleString()}
                </p>
              </div>
            {/if}
          </div>

          <!-- Actions -->
          <div class="flex flex-col md:flex-row gap-3">
            {#if !isCheckedIn}
              <Button
                on:click={handleCheckIn}
                disabled={checkInLoading}
                className="flex-1 whitespace-nowrap py-2"
              >
                {#if checkInLoading}
                  <Loader2 size={18} class="mr-2 animate-spin" />
                {:else}
                  <CheckCircle size={18} class="mr-2" />
                {/if}
                Confirm Check-in
              </Button>
            {/if}
            <Button
              on:click={resetScan}
              variant="outline"
              className="flex-1 whitespace-nowrap py-2"
            >
              <!-- <Button
              on:click={() => alert("Not implemented yet")}
              variant="outline"
              className="flex-1"
            > -->
              <ScanLine size={18} class="mr-2" />
              Scan Next
            </Button>
          </div>
        </div>
      {:else}
        <!-- Error State -->
        <div class="p-6">
          <div class="mb-6 flex items-center justify-center">
            <div
              class="flex h-16 w-16 items-center justify-center rounded-full bg-red-400/20"
            >
              <XCircle size={32} class="text-red-400" />
            </div>
          </div>

          <h2 class="mb-2 text-center text-2xl font-semibold text-white">
            Scan Failed
          </h2>
          <p class="mb-6 text-center text-slate-300">
            {errorMessage}
          </p>

          <div class="flex gap-3">
            <Button on:click={resetScan} className="flex-1">
              <Camera size={18} class="mr-2" />
              Try Again
            </Button>
          </div>
        </div>
      {/if}
    </Card>
  </div>
</div>
