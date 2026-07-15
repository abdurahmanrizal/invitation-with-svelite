<script lang="ts">
  import { PUBLIC_PHP_API_URL } from "$env/static/public";
  import { read, utils, writeFile } from "xlsx";
  import {
    AlertCircle,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    Loader2,
    Upload,
    X,
  } from "lucide-svelte";
  import Button from "$lib/components/ui/button.svelte";
  import Card from "$lib/components/ui/card.svelte";

  export let category: "jamaah" | "mitra";
  export let onImported: () => void;

  type ImportRow = {
    guestName: string;
    phone: string;
    status: "pending" | "confirmed" | "checked_in";
  };

  let isOpen = false;
  let isParsing = false;
  let isImporting = false;
  let fileName = "";
  let rows: ImportRow[] = [];
  let error = "";
  let result: { imported: number; failed: number; errors: any[] } | null = null;

  const headerAliases: Record<string, keyof ImportRow> = {
    nama: "guestName",
    namalengkap: "guestName",
    namajamaah: "guestName",
    namamitra: "guestName",
    guestname: "guestName",
    name: "guestName",
    nohp: "phone",
    nomorhp: "phone",
    nomortelepon: "phone",
    telepon: "phone",
    phone: "phone",
    whatsapp: "phone",
    nowhatsapp: "phone",
    status: "status",
  };

  function normalizeHeader(value: unknown) {
    return String(value ?? "")
      .toLowerCase()
      .replace(/[^a-z0-9]/g, "");
  }

  function close() {
    isOpen = false;
    fileName = "";
    rows = [];
    error = "";
    result = null;
  }

  function templateBaseName() {
    return `template-import-${category}`;
  }

  function downloadXlsxTemplate() {
    const workbook = utils.book_new();
    const dataSheet = utils.aoa_to_sheet([
      ["Nama", "No HP", "Status"],
      ["", "", "pending"],
    ]);
    dataSheet["!cols"] = [{ wch: 32 }, { wch: 20 }, { wch: 16 }];

    const guideSheet = utils.aoa_to_sheet([
      ["Panduan Import Reservasi"],
      ["Kolom", "Ketentuan"],
      ["Nama", "Wajib diisi. Maksimal 255 karakter."],
      ["No HP", "Opsional. Gunakan format teks agar angka 0 di depan tidak hilang."],
      ["Status", "Opsional: pending, confirmed, atau checked_in. Default: pending."],
      ["Batas import", "Maksimal 500 baris dalam satu file."],
      ["Kategori", "Kategori ditentukan oleh tab Jamaah/Mitra yang aktif saat file diunggah."],
    ]);
    guideSheet["!cols"] = [{ wch: 20 }, { wch: 70 }];

    utils.book_append_sheet(workbook, dataSheet, "Data");
    utils.book_append_sheet(workbook, guideSheet, "Panduan");
    writeFile(workbook, `${templateBaseName()}.xlsx`);
  }

  function downloadCsvTemplate() {
    const csv = '\uFEFF"Nama","No HP","Status"\r\n"","","pending"\r\n';
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");
    anchor.href = url;
    anchor.download = `${templateBaseName()}.csv`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
  }

  async function parseFile(event: Event) {
    const input = event.currentTarget as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    isParsing = true;
    error = "";
    result = null;
    rows = [];
    fileName = file.name;

    try {
      const workbook = read(await file.arrayBuffer(), { type: "array" });
      const sheet = workbook.Sheets[workbook.SheetNames[0]];
      const table = utils.sheet_to_json<unknown[]>(sheet, {
        header: 1,
        defval: "",
        raw: false,
      });

      if (table.length < 2) throw new Error("File tidak memiliki data.");

      const headers = table[0].map((header) => headerAliases[normalizeHeader(header)]);
      if (!headers.includes("guestName")) {
        throw new Error('Kolom "Nama" tidak ditemukan.');
      }

      rows = table
        .slice(1)
        .filter((row) => row.some((cell) => String(cell).trim() !== ""))
        .map((row) => {
          const mapped: Record<string, string> = {};
          headers.forEach((header, index) => {
            if (header) mapped[header] = String(row[index] ?? "").trim();
          });
          const rawStatus = mapped.status?.toLowerCase().replace(/\s+/g, "_");
          return {
            guestName: mapped.guestName || "",
            phone: mapped.phone || "",
            status: (["pending", "confirmed", "checked_in"].includes(rawStatus)
              ? rawStatus
              : "pending") as ImportRow["status"],
          };
        });

      if (rows.length === 0) throw new Error("Tidak ada baris data untuk diimpor.");
      if (rows.length > 500) throw new Error("Maksimal 500 baris dalam satu import.");
    } catch (e) {
      error = e instanceof Error ? e.message : "Gagal membaca file.";
    } finally {
      isParsing = false;
      input.value = "";
    }
  }

  async function importRows() {
    isImporting = true;
    error = "";
    result = null;

    try {
      const response = await fetch(`${PUBLIC_PHP_API_URL}/import-reservations.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ category, rows }),
      });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || "Import gagal.");
      result = data;
      rows = [];
      await onImported();
    } catch (e) {
      error = e instanceof Error ? e.message : "Import gagal.";
    } finally {
      isImporting = false;
    }
  }
</script>

<Button on:click={() => (isOpen = true)} variant="outline" className="w-full gap-2 sm:w-auto">
  <Upload size={17} />
  Import Excel
</Button>

{#if isOpen}
  <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 p-4 backdrop-blur-sm">
    <div class="flex min-h-full items-center justify-center py-6">
      <Card className="w-full max-w-3xl border-white/15 bg-slate-900 p-0">
        <div class="flex items-start justify-between border-b border-white/10 p-5 sm:p-6">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-400">Import data</p>
            <h2 class="mt-2 text-xl font-semibold">Import {category === "mitra" ? "Mitra" : "Jamaah"}</h2>
            <p class="mt-1 text-sm text-slate-400">Unggah file Excel atau CSV, lalu periksa data sebelum disimpan.</p>
          </div>
          <button onclick={close} class="rounded-full p-2 text-slate-400 transition hover:bg-white/10 hover:text-white" aria-label="Tutup">
            <X size={20} />
          </button>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
          <div class="flex flex-col gap-4 rounded-2xl border border-amber-400/15 bg-amber-400/[0.06] p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-sm font-medium text-amber-100">Belum punya format data?</p>
              <p class="mt-1 text-xs leading-5 text-slate-400">Download template, isi data mulai baris kedua, lalu unggah kembali.</p>
            </div>
            <div class="grid shrink-0 grid-cols-2 gap-2">
              <button
                type="button"
                onclick={downloadXlsxTemplate}
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-xs font-semibold text-white transition hover:border-amber-400/30 hover:bg-white/10"
              >
                <Download size={15} /> .XLSX
              </button>
              <button
                type="button"
                onclick={downloadCsvTemplate}
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-xs font-semibold text-white transition hover:border-amber-400/30 hover:bg-white/10"
              >
                <Download size={15} /> .CSV
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-dashed border-white/15 bg-white/[0.03] p-6 text-center">
            <FileSpreadsheet size={32} class="mx-auto text-amber-400" />
            <p class="mt-3 font-medium">Pilih file data</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Kolom wajib: Nama. Opsional: No HP, Status. Maksimal 500 baris.</p>
            <label class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-full bg-amber-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">
              {#if isParsing}<Loader2 size={17} class="animate-spin" />{:else}<Upload size={17} />{/if}
              Pilih Excel / CSV
              <input type="file" accept=".xlsx,.xls,.csv" onchange={parseFile} class="sr-only" disabled={isParsing || isImporting} />
            </label>
          </div>

          {#if error}
            <div class="flex gap-3 rounded-xl border border-red-400/20 bg-red-400/10 p-4 text-sm text-red-200">
              <AlertCircle size={18} class="mt-0.5 shrink-0" /><span>{error}</span>
            </div>
          {/if}

          {#if result}
            <div class="flex gap-3 rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-200">
              <CheckCircle2 size={18} class="mt-0.5 shrink-0" />
              <span>{result.imported} data berhasil diimpor{result.failed ? `, ${result.failed} baris dilewati.` : "."}</span>
            </div>
          {/if}

          {#if rows.length > 0}
            <div>
              <div class="mb-3 flex items-center justify-between gap-3">
                <div><p class="font-medium">Preview data</p><p class="text-xs text-slate-500">{fileName} · {rows.length} baris</p></div>
                <span class="rounded-full bg-amber-400/10 px-3 py-1 text-xs font-medium text-amber-300">{category === "mitra" ? "Mitra" : "Jamaah"}</span>
              </div>
              <div class="overflow-x-auto rounded-xl border border-white/10">
                <table class="w-full min-w-[520px] text-sm">
                  <thead class="bg-white/5 text-left text-xs uppercase tracking-wider text-slate-500">
                    <tr><th class="px-4 py-3">No.</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">No. HP</th><th class="px-4 py-3">Status</th></tr>
                  </thead>
                  <tbody>
                    {#each rows.slice(0, 5) as row, index}
                      <tr class="border-t border-white/5"><td class="px-4 py-3 text-slate-500">{index + 1}</td><td class="px-4 py-3 font-medium">{row.guestName || "-"}</td><td class="px-4 py-3 text-slate-400">{row.phone || "-"}</td><td class="px-4 py-3 capitalize text-slate-400">{row.status.replace("_", " ")}</td></tr>
                    {/each}
                  </tbody>
                </table>
              </div>
              {#if rows.length > 5}<p class="mt-2 text-center text-xs text-slate-500">Dan {rows.length - 5} baris lainnya</p>{/if}
            </div>
          {/if}
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-white/10 p-5 sm:flex-row sm:justify-end sm:p-6">
          <Button on:click={close} variant="ghost" className="w-full sm:w-auto">Tutup</Button>
          {#if rows.length > 0}
            <Button on:click={importRows} disabled={isImporting} className="w-full gap-2 sm:w-auto">
              {#if isImporting}<Loader2 size={17} class="animate-spin" />{:else}<Upload size={17} />{/if}
              Import {rows.length} Data
            </Button>
          {/if}
        </div>
      </Card>
    </div>
  </div>
{/if}
