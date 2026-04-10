<script lang="ts">
  import { PUBLIC_PHP_API_URL } from "$env/static/public";
  import { invitation } from "$lib/data";
  import {
    Plus,
    Trash2,
    Edit,
    Save,
    X,
    Check,
    Loader2,
    Users,
    Ticket,
    Calendar,
    Copy,
    Search,
    MessageCircle,
  } from "lucide-svelte";
  import Card from "$lib/components/ui/card.svelte";
  import Button from "$lib/components/ui/button.svelte";
  import Badge from "$lib/components/ui/badge.svelte";

  let reservations: any[] = [];
  let isLoading = true;
  let showForm = false;
  let editingId: number | null = null;
  let isSaving = false;
  let message = { type: "", text: "" };
  let copiedCode: string | null = null;
  let searchQuery = "";

  $: filteredReservations = searchQuery
    ? reservations.filter(
        (r) =>
          r.guestName?.toLowerCase().includes(searchQuery.toLowerCase()) ||
          r.reservationCode
            ?.toLowerCase()
            .includes(searchQuery.toLowerCase()) ||
          r.phone?.includes(searchQuery),
      )
    : reservations;

  let formData = {
    id: null,
    reservationCode: "",
    guestName: "",
    seatLabel: null,
    allowedGuests: null,
    phone: "",
    status: "pending",
  };

  async function loadReservations() {
    isLoading = true;
    try {
      const response = await fetch(`${PUBLIC_PHP_API_URL}/reservations.php`);
      const data = await response.json();
      if (data.success) {
        reservations = data.reservations;
      }
    } catch (error) {
      message = { type: "error", text: "Failed to load reservations" };
    } finally {
      isLoading = false;
    }
  }

  async function saveReservation() {
    isSaving = true;
    message = { type: "", text: "" };

    try {
      let newReservation = {
        ...(editingId && { id: editingId }),
        ...formData,
      };

      const url = editingId
        ? `${PUBLIC_PHP_API_URL}/reservation-id.php`
        : `${PUBLIC_PHP_API_URL}/reservations.php`;

      const response = await fetch(url, {
        method: editingId ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newReservation),
      });

      const result = await response.json();

      if (response.ok) {
        message = {
          type: "success",
          text: result.message || "Reservation saved!",
        };
        resetForm();
        await loadReservations();
      } else {
        message = { type: "error", text: result.message || "Failed to save" };
      }
    } catch (error) {
      message = { type: "error", text: "Network error" };
    } finally {
      isSaving = false;
    }
  }

  async function deleteReservation(id: number) {
    if (!confirm("Are you sure you want to delete this reservation?")) return;

    try {
      const response = await fetch(`${PUBLIC_PHP_API_URL}/reservations/${id}`, {
        method: "DELETE",
      });

      if (response.ok) {
        message = { type: "success", text: "Reservation deleted!" };
        await loadReservations();
      }
    } catch (error) {
      message = { type: "error", text: "Failed to delete" };
    }
  }

  function editReservation(reservation: any) {
    editingId = reservation.id;
    formData = {
      id: reservation.id,
      reservationCode: reservation.reservationCode,
      guestName: reservation.guestName,
      seatLabel: reservation.seatLabel || "",
      allowedGuests: reservation.allowedGuests,
      phone: reservation.phone || "",
      status: reservation.status,
    };
    showForm = true;
  }

  function resetForm() {
    formData = {
      id: null,
      reservationCode: "",
      guestName: "",
      seatLabel: null,
      allowedGuests: null,
      phone: "",
      status: "pending",
    };
    editingId = null;
    showForm = false;
    message = { type: "", text: "" };
  }

  async function copyInvitationUrl(reservation: any) {
    try {
      const url = `${window.location.origin}/invitation/${reservation.reservationCode}`;
      copiedCode = `Assalamu'alaikum Warahmatullahi Wabarakatuh 🙏\n\nKepada Yth. *${reservation.guestName}*\n\nDengan hormat, kami mengundang Anda untuk hadir dalam acara *Gathering & Halal Bihalal The Sultan Umroh* yang akan diselenggarakan pada:\n\n📅 *Tanggal:* ${invitation.date}\n📍 *Tempat:* ${invitation.venue}\n🕒 *Waktu:* ${invitation.time}\n\n⚠️ Undangan ini berlaku hanya untuk 1 orang.\n\nAcara ini menjadi momen silaturahmi sekaligus mempererat kebersamaan keluarga besar The Sultan Umroh. Kehadiran Anda akan menjadi kehormatan bagi kami.\n\nUntuk konfirmasi kehadiran dan reservasi, silakan kunjungi link berikut:\n🔗 ${url}\n\nTerima kasih atas perhatian dan kehadirannya.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh ✨`;
      await navigator.clipboard.writeText(copiedCode);
      // copiedCode = reservationCode;
      message = { type: "success", text: "Invitation URL copied!" };
      setTimeout(() => {
        copiedCode = null;
        message = { type: "", text: "" };
      }, 2000);
    } catch (error) {
      message = { type: "error", text: "Failed to copy URL" };
    }
  }

  function sendWhatsApp(reservation: any) {
    if (!reservation.phone) {
      message = { type: "error", text: "No phone number for this reservation" };
      return;
    }

    const invitationUrl = `${window.location.origin}/invitation/${reservation.reservationCode}`;
    const formattedPhone = reservation.phone
      .replace(/^0/, "62")
      .replace(/\+/g, "")
      .replace(/[^0-9]/g, "");

    const messageText = `Assalamu'alaikum Warahmatullahi Wabarakatuh 🙏\n\nKepada Yth. *${reservation.guestName}*\n\nDengan hormat, kami mengundang Anda untuk hadir dalam acara *Gathering & Halal Bihalal The Sultan Umroh* yang akan diselenggarakan pada:\n\n📅 *Tanggal:* ${invitation.date}\n📍 *Tempat:* ${invitation.venue}\n🕒 *Waktu:* ${invitation.time}\n\n⚠️ Undangan ini berlaku hanya untuk 1 orang.\n\nAcara ini menjadi momen silaturahmi sekaligus mempererat kebersamaan keluarga besar The Sultan Umroh. Kehadiran Anda akan menjadi kehormatan bagi kami.\n\nUntuk konfirmasi kehadiran dan reservasi, silakan kunjungi link berikut:\n🔗 ${invitationUrl}\n\nTerima kasih atas perhatian dan kehadirannya.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh ✨`;

    const whatsappUrl = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(messageText)}`;
    window.open(whatsappUrl, "_blank");
  }

  loadReservations();
</script>

<svelte:head>
  <title>Admin - Reservations</title>
  <meta name="description" content="Manage guest reservations" />
</svelte:head>

<div class="min-h-screen bg-slate-950 px-4 py-8 text-white sm:px-6 lg:px-8">
  <div class="mx-auto max-w-6xl">
    <!-- Header -->
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
      <div>
        <Badge variant="outline">Admin Panel</Badge>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
          Manajemen Reservasi
        </h1>
      </div>
      <Button on:click={() => (showForm = true)} className="gap-2">
        <Plus size={18} />
        Tambah Reservasi
      </Button>
    </div>

    <!-- Message Alert -->
    {#if message.text}
      <div
        class="mb-6 rounded-2xl p-4 {message.type === 'success'
          ? 'bg-emerald-400/10 border border-emerald-400/20 text-emerald-200'
          : 'bg-red-400/10 border border-red-400/20 text-red-200'}"
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

    <!-- Form -->
    {#if showForm}
      <Card className="mb-6">
        <div
          class="mb-6 flex items-center justify-between border-b border-white/10 pb-4"
        >
          <h2 class="text-xl font-semibold">
            {editingId ? "Edit Reservation" : "New Reservation"}
          </h2>
          <button
            onclick={resetForm}
            class="rounded-full p-2 hover:bg-white/10 transition-colors"
          >
            <X size={20} />
          </button>
        </div>

        <form
          onsubmit={(e) => {
            e.preventDefault();
            saveReservation();
          }}
          class="space-y-6"
        >
          <div class="grid gap-6 sm:grid-cols-2">
            <!-- Guest Name -->
            <div>
              <label
                for="guestName"
                class="mb-2 block text-sm font-medium text-slate-300"
              >
                Guest Name *
              </label>
              <input
                id="guestName"
                type="text"
                bind:value={formData.guestName}
                required
                placeholder="Full name"
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-500 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"
              />
            </div>

            <!-- Phone -->
            <div>
              <label
                for="phone"
                class="mb-2 block text-sm font-medium text-slate-300"
              >
                Phone Number
              </label>
              <input
                id="phone"
                type="tel"
                bind:value={formData.phone}
                placeholder="+62..."
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-500 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"
              />
            </div>

            <!-- Status -->
            {#if editingId}
              <div>
                <label
                  for="status"
                  class="mb-2 block text-sm font-medium text-slate-300"
                >
                  Status *
                </label>
                <select
                  id="status"
                  bind:value={formData.status}
                  class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"
                >
                  <option value="pending">Pending</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="checked_in">Checked In</option>
                </select>
              </div>
            {/if}
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <Button type="button" on:click={resetForm} variant="outline">
              Cancel
            </Button>
            <Button type="submit" disabled={isSaving} className="gap-2">
              {#if isSaving}
                <Loader2 size={18} class="animate-spin" />
              {:else}
                <Save size={18} />
              {/if}
              {editingId ? "Update" : "Create"} Reservation
            </Button>
          </div>
        </form>
      </Card>
    {/if}

    <!-- Reservations List -->
    <Card>
      <div
        class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-4"
      >
        <h2 class="text-xl font-semibold">
          All Reservations {searchQuery
            ? `(${filteredReservations.length} of ${reservations.length})`
            : `(${reservations.length})`}
        </h2>
        <div class="relative">
          <Search
            size={16}
            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
          />
          <input
            type="text"
            bind:value={searchQuery}
            placeholder="Search by name, code, or phone..."
            class="w-64 rounded-xl border border-white/10 bg-white/5 py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"
          />
        </div>
      </div>

      {#if isLoading}
        <div class="flex items-center justify-center py-12">
          <Loader2 size={32} class="animate-spin text-amber-400" />
        </div>
      {:else if filteredReservations.length === 0}
        <div class="py-12 text-center text-slate-400">
          <p>
            {searchQuery
              ? "No reservations found matching your search."
              : "No reservations yet. Create your first one!"}
          </p>
        </div>
      {:else}
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr
                class="border-b border-white/10 text-left text-sm text-slate-400"
              >
                <th class="pb-4 font-medium">Kode</th>
                <th class="pb-4 font-medium">Tamu</th>
                <th class="pb-4 font-medium">Kontak</th>
                <th class="pb-4 font-medium">Status</th>
                <th class="pb-4 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {#each filteredReservations as reservation}
                <tr class="border-b border-white/5">
                  <td class="py-4">
                    <span class="font-mono text-sm text-amber-200"
                      >{reservation.reservationCode}</span
                    >
                  </td>
                  <td class="py-4 font-medium">{reservation.guestName}</td>
                  <td class="py-4 text-sm text-slate-300">
                    {reservation.phone || "-"}
                  </td>
                  <td class="py-4">
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium {reservation.status ===
                      'checked_in'
                        ? 'bg-emerald-400/10 text-emerald-200'
                        : reservation.status === 'confirmed'
                          ? 'bg-blue-400/10 text-blue-200'
                          : 'bg-amber-400/10 text-amber-200'}"
                    >
                      {reservation.status.replace("_", " ")}
                    </span>
                  </td>
                  <td class="py-4">
                    <div class="flex gap-2">
                      <button
                        onclick={() => sendWhatsApp(reservation)}
                        class="rounded-lg p-2 hover:bg-green-400/10 transition-colors"
                        title="Send via WhatsApp"
                      >
                        <MessageCircle size={16} class="text-green-400" />
                      </button>
                      <button
                        onclick={() => copyInvitationUrl(reservation)}
                        class="rounded-lg p-2 hover:bg-amber-400/10 transition-colors"
                        title="Copy Invitation URL"
                      >
                        <Copy
                          size={16}
                          class={copiedCode === reservation.reservationCode
                            ? "text-amber-400"
                            : "text-slate-400"}
                        />
                      </button>
                      <button
                        onclick={() => editReservation(reservation)}
                        class="rounded-lg p-2 hover:bg-white/10 transition-colors"
                        title="Edit"
                      >
                        <Edit size={16} class="text-slate-400" />
                      </button>
                      <button
                        onclick={() => deleteReservation(reservation.id)}
                        class="rounded-lg p-2 hover:bg-red-400/10 transition-colors"
                        title="Delete"
                      >
                        <Trash2 size={16} class="text-red-400" />
                      </button>
                    </div>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {/if}
    </Card>
  </div>
</div>
