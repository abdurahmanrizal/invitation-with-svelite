<script lang="ts">
  import "../app.css";
  import { Home, ScanLine, Settings } from "lucide-svelte";

  let currentPage = "";

  // Simple navigation
  function navigate(path: string) {
    window.location.href = path;
  }
</script>

<nav
  class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/80 backdrop-blur-md"
>
  <div
    class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8"
  >
    <a
      href="/"
      class="flex items-center gap-2 text-amber-200 hover:text-amber-100"
    >
      <!-- <span class="font-semibold">🕋 Umrah Gathering</span> -->
      <img
        src="https://thesultanumroh.co.id/logo.png"
        alt="The Sultan Umrah - Travel Umrah &amp; Haji Terpercaya"
        class="h-12 md:h-14 w-auto"
        width="auto"
        height="56"
      />
    </a>

    <div class="flex items-center gap-2">
      <button
        onclick={() => navigate("/")}
        class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white transition-colors"
        title="Home"
      >
        <Home size={20} />
      </button>
      <button
        onclick={() => navigate("/scan")}
        class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white transition-colors"
        title="Scanner"
      >
        <ScanLine size={20} />
      </button>
      <button
        onclick={() => navigate("/admin")}
        class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white transition-colors"
        title="Admin"
      >
        <Settings size={20} />
      </button>
    </div>
  </div>
</nav>

<slot />
