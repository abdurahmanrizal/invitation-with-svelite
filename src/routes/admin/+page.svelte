<script lang="ts">
    import { PUBLIC_PHP_API_URL } from "$env/static/public";
    import { invitation, invitationManasik } from "$lib/data";
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
        ChevronLeft,
        ChevronRight,
        Filter,
        MessageCircleOff,
        Handshake,
        UserRound,
    } from "lucide-svelte";
    import Card from "$lib/components/ui/card.svelte";
    import Button from "$lib/components/ui/button.svelte";
    import Badge from "$lib/components/ui/badge.svelte";
    import ImportReservations from "$lib/components/admin/import-reservations.svelte";
    import clsx from "clsx";

    const invitationEmoji = {
        prayer: String.fromCodePoint(0x1f64f),
        calendar: String.fromCodePoint(0x1f4c5),
        location: String.fromCodePoint(0x1f4cd),
        clock: String.fromCodePoint(0x1f552),
        clothing: String.fromCodePoint(0x1f455),
        warning: String.fromCodePoint(0x26a0, 0xfe0f),
        link: String.fromCodePoint(0x1f517),
        sparkle: String.fromCodePoint(0x2728),
    };

    function createWhatsAppUrl(phone: string, text: string) {
        const replacementCharacter = String.fromCodePoint(0xfffd);
        const sanitizedText = text
            .normalize("NFC")
            .replaceAll(replacementCharacter, "");
        const params = new URLSearchParams({
            phone,
            text: sanitizedText,
        });
        const url = `https://api.whatsapp.com/send?${params.toString()}`;

        if (url.toUpperCase().includes("%EF%BF%BD")) {
            throw new Error("WhatsApp message contains invalid UTF-8 text");
        }

        return url;
    }

    let reservations = $state<any[]>([]);
    let isLoading = $state(true);
    let showForm = $state(false);
    let editingId = $state<number | null>(null);
    let isSaving = $state(false);
    let message = $state({ type: "", text: "" });
    let copiedCode = $state<string | null>(null);
    let searchQuery = $state("");
    let statusFilter = $state("all");
    let activeTab = $state<"mitra" | "jamaah">("jamaah");
    let currentPage = $state(1);
    let itemsPerPage = 10;
    let confirmationModal = $state<{
        isOpen: boolean;
        action: "check_in" | "undo_check_in" | "delete" | null;
        reservation: any | null;
        isProcessing: boolean;
    }>({
        isOpen: false,
        action: null,
        reservation: null,
        isProcessing: false,
    });

    let filteredReservations = $derived(
        reservations.filter((r) => {
            const matchesCategory = (r.category || "jamaah") === activeTab;
            const matchesSearch = searchQuery
                ? r.guestName
                      ?.toLowerCase()
                      .includes(searchQuery.toLowerCase()) ||
                  r.reservationCode
                      ?.toLowerCase()
                      .includes(searchQuery.toLowerCase()) ||
                  r.phone?.includes(searchQuery)
                : true;

            const matchesStatus =
                statusFilter === "all" || r.status === statusFilter;

            return matchesCategory && matchesSearch && matchesStatus;
        }),
    );

    let totalPages = $derived(
        Math.ceil(filteredReservations.length / itemsPerPage),
    );

    let paginatedReservations = $derived(
        filteredReservations.slice(
            (currentPage - 1) * itemsPerPage,
            currentPage * itemsPerPage,
        ),
    );

    let categoryCounts = $derived({
        jamaah: reservations.filter(
            (r) => (r.category || "jamaah") === "jamaah",
        ).length,
        mitra: reservations.filter((r) => r.category === "mitra").length,
    });

    let formData = $state({
        id: null,
        reservationCode: "",
        guestName: "",
        seatLabel: null,
        allowedGuests: null,
        phone: "",
        status: "pending",
        category: "jamaah",
    });

    async function loadReservations() {
        isLoading = true;
        try {
            const response = await fetch(
                `${PUBLIC_PHP_API_URL}/reservations.php`,
            );
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
                message = {
                    type: "error",
                    text: result.message || "Failed to save",
                };
            }
        } catch (error) {
            message = { type: "error", text: "Network error" };
        } finally {
            isSaving = false;
        }
    }

    async function deleteReservation(id: number) {
        try {
            const response = await fetch(
                `${PUBLIC_PHP_API_URL}/reservations.php`,
                {
                    method: "DELETE",
                    body: JSON.stringify({ id }),
                },
            );

            if (response.ok) {
                message = { type: "success", text: "Reservation deleted!" };
                await loadReservations();
            }
        } catch (error) {
            message = { type: "error", text: "Failed to delete" };
        }
    }

    function openConfirmationModal(
        action: "check_in" | "undo_check_in" | "delete",
        reservation: any,
    ) {
        confirmationModal = {
            isOpen: true,
            action,
            reservation,
            isProcessing: false,
        };
    }

    function closeConfirmationModal() {
        if (confirmationModal.isProcessing) return;

        confirmationModal = {
            isOpen: false,
            action: null,
            reservation: null,
            isProcessing: false,
        };
    }

    function getConfirmationContent() {
        switch (confirmationModal.action) {
            case "check_in":
                return {
                    title: "Konfirmasi Check-in",
                    description: `Apakah Anda yakin ingin melakukan check-in untuk ${confirmationModal.reservation?.guestName}?`,
                    confirmLabel: "Ya, Check-in",
                    confirmClass: "bg-emerald-500 hover:bg-emerald-400",
                };
            case "undo_check_in":
                return {
                    title: "Batalkan Check-in",
                    description: `Apakah Anda yakin ingin membatalkan check-in ${confirmationModal.reservation?.guestName}?`,
                    confirmLabel: "Ya, Batalkan",
                    confirmClass: "bg-orange-500 hover:bg-orange-400",
                };
            case "delete":
                return {
                    title: "Hapus Pengguna",
                    description: `Apakah Anda yakin ingin menghapus ${confirmationModal.reservation?.guestName}? Data yang dihapus tidak dapat dipulihkan.`,
                    confirmLabel: "Ya, Hapus",
                    confirmClass: "bg-red-500 hover:bg-red-400",
                };
            default:
                return {
                    title: "Konfirmasi",
                    description: "Apakah Anda yakin ingin melanjutkan?",
                    confirmLabel: "Ya, Lanjutkan",
                    confirmClass: "bg-amber-500 hover:bg-amber-400",
                };
        }
    }

    async function confirmReservationAction() {
        const { action, reservation } = confirmationModal;
        if (!action || !reservation || confirmationModal.isProcessing) return;

        confirmationModal.isProcessing = true;

        try {
            if (action === "check_in") {
                await checkInGuest(reservation);
            } else if (action === "undo_check_in") {
                await undoCheckIn(reservation);
            } else {
                await deleteReservation(reservation.id);
            }
        } finally {
            confirmationModal = {
                isOpen: false,
                action: null,
                reservation: null,
                isProcessing: false,
            };
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
            category: reservation.category || "jamaah",
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
            category: activeTab,
        };
        editingId = null;
        showForm = false;
        message = { type: "", text: "" };
    }

    async function incrementCopyCount(id: number) {
        try {
            const response = await fetch(
                `${PUBLIC_PHP_API_URL}/copy-count.php`,
                {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id }),
                },
            );

            const result = await response.json();
            if (result.success) {
                // Update local copy count
                reservations = reservations.map((r) =>
                    r.id === id ? { ...r, copyCount: result.copyCount } : r,
                );
            }
        } catch (error) {
            console.error("Failed to increment copy count:", error);
        }
    }

    async function copyInvitationUrl(reservation: any) {
        try {
            const url = `${window.location.origin}/invitation/${reservation.reservationCode}`;
            const isManasik = activeTab === "jamaah";
            const agendaLabel = isManasik
                ? "*Manasik Umroh The Sultan Umroh*"
                : "*Gathering & Halal Bihalal The Sultan Umroh*";
            const agendaDescription = isManasik
                ? "Kegiatan manasik ini diselenggarakan sebagai pembekalan agar Bapak/Ibu dapat memahami rangkaian ibadah umroh, tata cara pelaksanaannya, serta berbagai persiapan yang diperlukan. Mengingat pentingnya materi yang akan disampaikan, kami sangat mengharapkan kehadiran Bapak/Ibu."
                : "Acara ini menjadi momen silaturahmi sekaligus mempererat kebersamaan keluarga besar The Sultan Umroh. Kehadiran Bapak/Ibu akan menjadi kehormatan dan kebahagiaan bagi kami.";
            const confirmationText = `\n\nUntuk melakukan konfirmasi kehadiran, silakan mengunjungi tautan berikut:\n${invitationEmoji.link} ${url}`;
            const invitationDate = isManasik
                ? invitationManasik.date
                : invitation.date;
            const invitationVenue = isManasik
                ? invitationManasik.venue
                : invitation.venue;
            const invitationTime = isManasik
                ? invitationManasik.time
                : invitation.time;
            const dressCodeText = isManasik
                ? `\n${invitationEmoji.clothing} *Busana:* Busana muslim berwarna putih`
                : "";
            copiedCode = `Assalamu'alaikum Warahmatullahi Wabarakatuh ${invitationEmoji.prayer}\n\nKepada Yth.\n*Bapak/Ibu ${reservation.guestName}*\n\nDengan hormat, kami mengundang Bapak/Ibu untuk menghadiri acara ${agendaLabel} yang insyaallah akan diselenggarakan pada:\n\n${invitationEmoji.calendar} *Tanggal:* ${invitationDate}\n${invitationEmoji.location} *Tempat:* ${invitationVenue}\n${invitationEmoji.clock} *Waktu:* ${invitationTime}${dressCodeText}\n\n${invitationEmoji.warning} Undangan ini berlaku untuk 1 orang.\n\n${agendaDescription}${confirmationText}\n\nAtas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh ${invitationEmoji.sparkle}`;
            await navigator.clipboard.writeText(copiedCode);

            // Increment copy count in database
            await incrementCopyCount(reservation.id);

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
            message = {
                type: "error",
                text: "No phone number for this reservation",
            };
            return;
        }

        const invitationUrl = `${window.location.origin}/invitation/${reservation.reservationCode}`;
        const formattedPhone = reservation.phone
            .replace(/^0/, "62")
            .replace(/\+/g, "")
            .replace(/[^0-9]/g, "");

        const isManasik = activeTab === "jamaah";
        const agendaLabel = isManasik
            ? "*Manasik Umroh The Sultan Umroh*"
            : "*Gathering & Halal Bihalal The Sultan Umroh*";
        const agendaDescription = isManasik
            ? "Kegiatan manasik ini diselenggarakan sebagai pembekalan agar Bapak/Ibu dapat memahami rangkaian ibadah umroh, tata cara pelaksanaannya, serta berbagai persiapan yang diperlukan. Mengingat pentingnya materi yang akan disampaikan, kami sangat mengharapkan kehadiran Bapak/Ibu."
            : "Acara ini menjadi momen silaturahmi sekaligus mempererat kebersamaan keluarga besar The Sultan Umroh. Kehadiran Bapak/Ibu akan menjadi kehormatan dan kebahagiaan bagi kami.";
        const confirmationText = `\n\nUntuk melakukan konfirmasi kehadiran, silakan mengunjungi tautan berikut:\n${invitationEmoji.link} ${invitationUrl}`;
        const invitationDate = isManasik
            ? invitationManasik.date
            : invitation.date;
        const invitationVenue = isManasik
            ? invitationManasik.venue
            : invitation.venue;
        const invitationTime = isManasik
            ? invitationManasik.time
            : invitation.time;
        const dressCodeText = isManasik
            ? `\n${invitationEmoji.clothing} *Busana:* Busana muslim berwarna putih`
            : "";
        const messageText = `Assalamu'alaikum Warahmatullahi Wabarakatuh ${invitationEmoji.prayer}\n\nKepada Yth.\n*Bapak/Ibu ${reservation.guestName}*\n\nDengan hormat, kami mengundang Bapak/Ibu untuk menghadiri acara ${agendaLabel} yang insyaallah akan diselenggarakan pada:\n\n${invitationEmoji.calendar} *Tanggal:* ${invitationDate}\n${invitationEmoji.location} *Tempat:* ${invitationVenue}\n${invitationEmoji.clock} *Waktu:* ${invitationTime}${dressCodeText}\n\n${invitationEmoji.warning} Undangan ini berlaku untuk 1 orang.\n\n${agendaDescription}${confirmationText}\n\nAtas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.\n\nWassalamu'alaikum Warahmatullahi Wabarakatuh ${invitationEmoji.sparkle}`;

        const whatsappUrl = createWhatsAppUrl(formattedPhone, messageText);
        window.open(whatsappUrl, "_blank", "noopener,noreferrer");
    }

    async function checkInGuest(reservation: any) {
        try {
            const response = await fetch(
                `${PUBLIC_PHP_API_URL}/reservation-id.php`,
                {
                    method: "PUT",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        id: reservation.id,
                        guestName: reservation.guestName,
                        seatLabel: reservation.seatLabel || "",
                        allowedGuests: reservation.allowedGuests,
                        phone: reservation.phone || "",
                        status: "checked_in",
                        category: reservation.category || "jamaah",
                        checkedInAt: new Date().toISOString(),
                    }),
                },
            );

            const result = await response.json();
            if (result.success) {
                message = {
                    type: "success",
                    text: `${reservation.guestName} checked in successfully!`,
                };
                await loadReservations();
            } else {
                message = {
                    type: "error",
                    text: result.message || "Failed to check in",
                };
            }
        } catch (error) {
            message = { type: "error", text: "Network error" };
        }
    }

    async function undoCheckIn(reservation: any) {
        try {
            const response = await fetch(
                `${PUBLIC_PHP_API_URL}/reservation-id.php`,
                {
                    method: "PUT",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        id: reservation.id,
                        guestName: reservation.guestName,
                        seatLabel: reservation.seatLabel || "",
                        allowedGuests: reservation.allowedGuests,
                        phone: reservation.phone || "",
                        status: "confirmed",
                        category: reservation.category || "jamaah",
                        checkedInAt: null,
                    }),
                },
            );

            const result = await response.json();
            if (result.success) {
                message = {
                    type: "success",
                    text: `Check in undone for ${reservation.guestName}`,
                };
                await loadReservations();
            } else {
                message = {
                    type: "error",
                    text: result.message || "Failed to undo check in",
                };
            }
        } catch (error) {
            message = { type: "error", text: "Network error" };
        }
    }

    loadReservations();
</script>

<svelte:head>
    <title>Admin - Reservations</title>
    <meta name="description" content="Manage guest reservations" />
</svelte:head>

<div
    class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(251,191,36,0.08),_transparent_28rem)] bg-slate-950 px-4 py-6 text-white sm:px-6 sm:py-10 lg:px-8"
>
    <div class="mx-auto max-w-6xl">
        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <Badge>Admin Panel</Badge>
                <h1
                    class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                >
                    Manajemen Reservasi
                </h1>
                <p
                    class="mt-2 max-w-xl text-sm leading-6 text-slate-400 sm:text-base"
                >
                    Kelola undangan, konfirmasi kehadiran, dan check-in tamu.
                </p>
            </div>
            <div class="grid w-full gap-3 sm:flex sm:w-auto">
                <ImportReservations
                    category={activeTab}
                    onImported={loadReservations}
                />
                <Button
                    on:click={() => {
                        formData.category = activeTab;
                        showForm = true;
                    }}
                    className="w-full gap-2 sm:w-auto"
                >
                    <Plus size={18} />
                    Tambah {activeTab === "mitra" ? "Mitra" : "Jamaah"}
                </Button>
            </div>
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

                        <!-- Category -->
                        <div>
                            <label
                                for="category"
                                class="mb-2 block text-sm font-medium text-slate-300"
                            >
                                Kategori *
                            </label>
                            <select
                                id="category"
                                bind:value={formData.category}
                                required
                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"
                            >
                                <option value="jamaah">Jamaah</option>
                                <option value="mitra">Mitra</option>
                            </select>
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
                                    <option value="checked_in"
                                        >Checked In</option
                                    >
                                </select>
                            </div>
                        {/if}
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            on:click={resetForm}
                            variant="outline"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={isSaving}
                            className="gap-2"
                        >
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
        <Card className="overflow-hidden p-0">
            <div class="border-b border-white/10 bg-white/[0.025] p-4 sm:p-6">
                <p
                    class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"
                >
                    Kategori tamu
                </p>
                <div
                    class="grid grid-cols-2 gap-2 rounded-2xl border border-white/10 bg-slate-950/60 p-1.5 sm:inline-grid sm:min-w-[360px]"
                    role="tablist"
                    aria-label="Kategori reservasi"
                >
                    {#each [{ value: "jamaah", label: "Jamaah", icon: UserRound }, { value: "mitra", label: "Mitra", icon: Handshake }] as tab}
                        {@const TabIcon = tab.icon}
                        <button
                            type="button"
                            role="tab"
                            aria-selected={activeTab === tab.value}
                            onclick={() => {
                                activeTab = tab.value as "mitra" | "jamaah";
                                currentPage = 1;
                            }}
                            class={clsx(
                                "flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-all",
                                activeTab === tab.value
                                    ? "bg-amber-400 text-slate-950 shadow-lg shadow-amber-950/20"
                                    : "text-slate-400 hover:bg-white/5 hover:text-white",
                            )}
                        >
                            <TabIcon size={17} />
                            {tab.label}
                            <span
                                class={clsx(
                                    "rounded-full px-2 py-0.5 text-xs",
                                    activeTab === tab.value
                                        ? "bg-slate-950/15"
                                        : "bg-white/10 text-slate-400",
                                )}
                            >
                                {categoryCounts[
                                    tab.value as "mitra" | "jamaah"
                                ]}
                            </span>
                        </button>
                    {/each}
                </div>
            </div>

            <div
                class="flex flex-col gap-4 border-b border-white/10 p-4 sm:p-6 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <h2 class="text-xl font-semibold">
                        Daftar {activeTab === "mitra" ? "Mitra" : "Jamaah"}
                    </h2>
                    <p class="mt-1 text-sm text-slate-400">
                        {filteredReservations.length} dari {categoryCounts[
                            activeTab
                        ]} reservasi ditampilkan
                    </p>
                </div>
                <div
                    class="grid gap-3 sm:grid-cols-[auto_1fr] lg:flex lg:items-center"
                >
                    <!-- Status Filter -->

                    <div class="relative">
                        <Filter
                            size={16}
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"
                        />
                        <select
                            bind:value={statusFilter}
                            aria-label="Filter status"
                            class="w-full appearance-none rounded-xl border border-white/10 bg-white/5 py-2.5 pl-10 pr-8 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20 sm:w-auto"
                            onchange={() => {
                                currentPage = 1;
                            }}
                        >
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="checked_in">Checked In</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <Search
                            size={16}
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            type="text"
                            bind:value={searchQuery}
                            placeholder="Cari nama, kode, atau telepon..."
                            aria-label="Cari reservasi"
                            class="w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20 sm:w-72"
                            oninput={() => {
                                currentPage = 1;
                            }}
                        />
                    </div>
                </div>
            </div>

            {#if isLoading}
                <div class="flex items-center justify-center py-20">
                    <Loader2 size={32} class="animate-spin text-amber-400" />
                </div>
            {:else if filteredReservations.length === 0}
                <div class="px-6 py-20 text-center">
                    <div
                        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/5 text-slate-500"
                    >
                        <Users size={22} />
                    </div>
                    <p class="font-medium text-slate-300">
                        {searchQuery || statusFilter !== "all"
                            ? "Reservasi tidak ditemukan"
                            : `Belum ada data ${activeTab === "mitra" ? "mitra" : "jamaah"}`}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {searchQuery || statusFilter !== "all"
                            ? "Coba ubah kata pencarian atau filter status."
                            : "Tambahkan reservasi baru untuk mulai mengisi daftar."}
                    </p>
                </div>
            {:else}
                <!-- Mobile: Card Layout -->
                <div class="space-y-3 p-4 sm:hidden">
                    {#each paginatedReservations as reservation}
                        <div
                            class="rounded-xl border border-white/10 bg-white/5 p-4"
                        >
                            <div
                                class="mb-3 flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="font-mono text-sm text-amber-200">
                                        {reservation.reservationCode}
                                    </p>
                                    <p class="mt-1 font-medium">
                                        {reservation.guestName}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-400">
                                        {reservation.phone || "-"}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-3 py-1 text-xs font-medium {reservation.status ===
                                    'checked_in'
                                        ? 'bg-emerald-400/10 text-emerald-200'
                                        : reservation.status === 'confirmed'
                                          ? 'bg-blue-400/10 text-blue-200'
                                          : 'bg-amber-400/10 text-amber-200'}"
                                >
                                    {reservation.status.replace("_", " ")}
                                </span>
                            </div>
                            <div
                                class="flex justify-end gap-2 border-t border-white/10 pt-3"
                            >
                                <button
                                    onclick={() => sendWhatsApp(reservation)}
                                    class="rounded-lg bg-green-400/10 p-2.5 transition-colors hover:bg-green-400/20 disabled:opacity-50 disabled:pointer-events-none"
                                    title="Send via WhatsApp"
                                    disabled={!reservation.phone}
                                >
                                    {#if reservation.phone}
                                        <MessageCircle
                                            size={16}
                                            class="text-green-400"
                                        />
                                    {:else}
                                        <MessageCircleOff
                                            size={16}
                                            class="text-red-400"
                                        />
                                    {/if}
                                </button>
                                <button
                                    onclick={() =>
                                        copyInvitationUrl(reservation)}
                                    class="relative rounded-lg bg-amber-400/10 p-2.5 transition-colors hover:bg-amber-400/20"
                                    title="Copy Invitation URL"
                                >
                                    <Copy
                                        size={18}
                                        class={copiedCode ===
                                        reservation.reservationCode
                                            ? "text-amber-400"
                                            : "text-slate-400"}
                                    />
                                    {#if reservation.copyCount > 0}
                                        <span
                                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-slate-950"
                                        >
                                            {reservation.copyCount}
                                        </span>
                                    {/if}
                                </button>
                                {#if reservation.status !== "checked_in"}
                                    <button
                                        onclick={() =>
                                            openConfirmationModal(
                                                "check_in",
                                                reservation,
                                            )}
                                        class="rounded-lg bg-emerald-400/10 p-2.5 transition-colors hover:bg-emerald-400/20"
                                        title="Check In"
                                    >
                                        <Check
                                            size={18}
                                            class="text-emerald-400"
                                        />
                                    </button>
                                {:else}
                                    <button
                                        onclick={() =>
                                            openConfirmationModal(
                                                "undo_check_in",
                                                reservation,
                                            )}
                                        class="rounded-lg bg-orange-400/10 p-2.5 transition-colors hover:bg-orange-400/20"
                                        title="Undo Check In"
                                    >
                                        <X size={18} class="text-orange-400" />
                                    </button>
                                {/if}
                                <button
                                    onclick={() => editReservation(reservation)}
                                    class="rounded-lg bg-white/10 p-2.5 transition-colors hover:bg-white/20"
                                    title="Edit"
                                >
                                    <Edit size={18} class="text-slate-400" />
                                </button>
                                <button
                                    onclick={() =>
                                        openConfirmationModal(
                                            "delete",
                                            reservation,
                                        )}
                                    class="rounded-lg bg-red-400/10 p-2.5 transition-colors hover:bg-red-400/20"
                                    title="Delete"
                                >
                                    <Trash2 size={18} class="text-red-400" />
                                </button>
                            </div>
                        </div>
                    {/each}
                </div>

                <!-- Desktop: Table Layout -->
                <div class="hidden overflow-x-auto px-6 sm:block mt-3">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="border-b border-white/10 text-left text-sm text-slate-400"
                            >
                                <th class="pb-4 font-medium">Kode</th>
                                <th class="pb-4 font-medium">Nama</th>
                                <th class="pb-4 font-medium">Kontak</th>
                                <th class="pb-4 font-medium">Status</th>
                                <th class="pb-4 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each paginatedReservations as reservation}
                                <tr class="border-b border-white/5">
                                    <td class="py-4">
                                        <span
                                            class="font-mono text-sm text-amber-200"
                                            >{reservation.reservationCode}</span
                                        >
                                    </td>
                                    <td class="py-4 font-medium"
                                        >{reservation.guestName}</td
                                    >
                                    <td class="py-4 text-sm text-slate-300">
                                        {reservation.phone || "-"}
                                    </td>

                                    <td class="py-4">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium {reservation.status ===
                                            'checked_in'
                                                ? 'bg-emerald-400/10 text-emerald-200'
                                                : reservation.status ===
                                                    'confirmed'
                                                  ? 'bg-blue-400/10 text-blue-200'
                                                  : 'bg-amber-400/10 text-amber-200'}"
                                        >
                                            {reservation.status.replace(
                                                "_",
                                                " ",
                                            )}
                                        </span>
                                    </td>

                                    <td class="py-4">
                                        <div class="flex gap-2">
                                            <button
                                                onclick={() =>
                                                    sendWhatsApp(reservation)}
                                                class="rounded-lg p-2 hover:bg-green-400/10 transition-colors disabled:opacity-50 disabled:pointer-events-none disabled:cursor-not-allowed"
                                                title="Send via WhatsApp"
                                                disabled={!reservation.phone}
                                            >
                                                {#if reservation.phone}
                                                    <MessageCircle
                                                        size={16}
                                                        class="text-green-400"
                                                    />
                                                {:else}
                                                    <MessageCircleOff
                                                        size={16}
                                                        class="text-red-400"
                                                    />
                                                {/if}
                                            </button>
                                            <button
                                                onclick={() =>
                                                    copyInvitationUrl(
                                                        reservation,
                                                    )}
                                                class="relative rounded-lg p-2 hover:bg-amber-400/10 transition-colors"
                                                title="Copy Invitation URL"
                                            >
                                                <Copy
                                                    size={16}
                                                    class={copiedCode ===
                                                    reservation.reservationCode
                                                        ? "text-amber-400"
                                                        : "text-slate-400"}
                                                />
                                                {#if reservation.copyCount > 0}
                                                    <span
                                                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-[10px] font-bold text-slate-950"
                                                    >
                                                        {reservation.copyCount}
                                                    </span>
                                                {/if}
                                            </button>

                                            {#if reservation.status !== "checked_in"}
                                                <button
                                                    onclick={() =>
                                                        openConfirmationModal(
                                                            "check_in",
                                                            reservation,
                                                        )}
                                                    class="rounded-lg p-2 hover:bg-emerald-400/10 transition-colors"
                                                    title="Check In"
                                                >
                                                    <Check
                                                        size={16}
                                                        class="text-emerald-400"
                                                    />
                                                </button>
                                            {:else}
                                                <button
                                                    onclick={() =>
                                                        openConfirmationModal(
                                                            "undo_check_in",
                                                            reservation,
                                                        )}
                                                    class="rounded-lg p-2 hover:bg-orange-400/10 transition-colors"
                                                    title="Undo Check In"
                                                >
                                                    <X
                                                        size={16}
                                                        class="text-orange-400"
                                                    />
                                                </button>
                                            {/if}

                                            <button
                                                onclick={() =>
                                                    editReservation(
                                                        reservation,
                                                    )}
                                                class="rounded-lg p-2 hover:bg-white/10 transition-colors"
                                                title="Edit"
                                            >
                                                <Edit
                                                    size={16}
                                                    class="text-slate-400"
                                                />
                                            </button>
                                            <button
                                                onclick={() =>
                                                    openConfirmationModal(
                                                        "delete",
                                                        reservation,
                                                    )}
                                                class="rounded-lg p-2 hover:bg-red-400/10 transition-colors"
                                                title="Delete"
                                            >
                                                <Trash2
                                                    size={16}
                                                    class="text-red-400"
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                {#if totalPages > 1}
                    <div
                        class="mt-2 flex flex-col gap-4 border-t border-white/10 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <div
                            class="text-center text-xs text-slate-400 sm:text-sm"
                        >
                            <span class="hidden sm:inline">
                                Showing {(currentPage - 1) * itemsPerPage + 1} to
                                {Math.min(
                                    currentPage * itemsPerPage,
                                    filteredReservations.length,
                                )} of {filteredReservations.length} entries
                            </span>
                            <span class="sm:hidden">
                                {currentPage} / {totalPages}
                            </span>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-center gap-1 sm:gap-2"
                        >
                            <Button
                                variant="outline"
                                size="sm"
                                on:click={() => currentPage--}
                                disabled={currentPage === 1}
                                className="gap-1 px-2 sm:px-3"
                            >
                                <ChevronLeft size={16} />
                                <span class="hidden sm:inline">Previous</span>
                            </Button>

                            <!-- Page Numbers -->
                            <div class="hidden gap-1 sm:flex">
                                {#each Array(totalPages) as _, i}
                                    {#if i === 0 || i === totalPages - 1 || (i >= currentPage - 2 && i <= currentPage + 2)}
                                        <button
                                            onclick={() =>
                                                (currentPage = i + 1)}
                                            class="min-w-10 rounded-lg px-3 py-2 text-sm {currentPage ===
                                            i + 1
                                                ? 'bg-amber-400 text-slate-950 font-medium'
                                                : 'text-slate-300 hover:bg-white/10'}"
                                        >
                                            {i + 1}
                                        </button>
                                    {:else if i === currentPage - 3 || i === currentPage + 3}
                                        <span class="px-2 text-slate-500"
                                            >...</span
                                        >
                                    {/if}
                                {/each}
                            </div>

                            <!-- Mobile: Current Page Display -->
                            <div class="flex items-center gap-1 sm:hidden">
                                <button
                                    onclick={() => currentPage--}
                                    disabled={currentPage === 1}
                                    class="rounded-lg bg-amber-400 px-3 py-2 text-sm font-medium text-slate-950 disabled:opacity-50"
                                >
                                    {currentPage}
                                </button>
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                on:click={() => currentPage++}
                                disabled={currentPage === totalPages}
                                className="gap-1 px-2 sm:px-3"
                            >
                                <span class="hidden sm:inline">Next</span>
                                <ChevronRight size={16} />
                            </Button>
                        </div>
                    </div>
                {/if}
            {/if}
        </Card>
    </div>
</div>

{#if confirmationModal.isOpen}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <button
            type="button"
            class="absolute inset-0 cursor-default bg-slate-950/75 backdrop-blur-sm"
            aria-label="Tutup modal konfirmasi"
            onclick={closeConfirmationModal}
        ></button>

        <dialog
            open
            class="relative m-0 w-full max-w-md rounded-2xl border border-white/10 bg-slate-900 p-6 text-white shadow-2xl"
            aria-modal="true"
            aria-labelledby="confirmation-modal-title"
            aria-describedby="confirmation-modal-description"
        >
            <div
                class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full {confirmationModal.action ===
                'delete'
                    ? 'bg-red-400/10 text-red-400'
                    : confirmationModal.action === 'undo_check_in'
                      ? 'bg-orange-400/10 text-orange-400'
                      : 'bg-emerald-400/10 text-emerald-400'}"
            >
                {#if confirmationModal.action === "delete"}
                    <Trash2 size={22} />
                {:else if confirmationModal.action === "undo_check_in"}
                    <X size={22} />
                {:else}
                    <Check size={22} />
                {/if}
            </div>

            <h2
                id="confirmation-modal-title"
                class="text-center text-xl font-semibold text-white"
            >
                {getConfirmationContent().title}
            </h2>
            <p
                id="confirmation-modal-description"
                class="mt-2 text-center text-sm leading-6 text-slate-300"
            >
                {getConfirmationContent().description}
            </p>

            <div
                class="mt-6 flex flex-col-reverse justify-center gap-3 sm:flex-row"
            >
                <button
                    type="button"
                    class="w-full rounded-full border border-white/15 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-white/10 disabled:opacity-50 sm:w-auto"
                    disabled={confirmationModal.isProcessing}
                    onclick={closeConfirmationModal}
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-medium text-white transition-colors disabled:pointer-events-none disabled:opacity-60 sm:w-auto {getConfirmationContent()
                        .confirmClass}"
                    disabled={confirmationModal.isProcessing}
                    onclick={confirmReservationAction}
                >
                    {#if confirmationModal.isProcessing}
                        <Loader2 size={16} class="animate-spin" />
                        Memproses...
                    {:else}
                        {getConfirmationContent().confirmLabel}
                    {/if}
                </button>
            </div>
        </dialog>
    </div>
{/if}
