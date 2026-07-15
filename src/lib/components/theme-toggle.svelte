<script lang="ts">
  import { onMount } from "svelte";
  import { Moon, Sun } from "lucide-svelte";

  type Theme = "light" | "dark";

  let theme: Theme = "dark";

  function applyTheme(nextTheme: Theme) {
    theme = nextTheme;
    document.documentElement.dataset.theme = nextTheme;
    document.documentElement.style.colorScheme = nextTheme;
    try {
      localStorage.setItem("theme", nextTheme);
    } catch {
      // Theme switching still works when browser storage is unavailable.
    }
  }

  function toggleTheme() {
    applyTheme(theme === "dark" ? "light" : "dark");
  }

  onMount(() => {
    theme = document.documentElement.dataset.theme === "light" ? "light" : "dark";
  });
</script>

<button
  type="button"
  class="theme-toggle"
  onclick={toggleTheme}
  aria-label={theme === "dark" ? "Gunakan tema terang" : "Gunakan tema gelap"}
  title={theme === "dark" ? "Tema terang" : "Tema gelap"}
>
  {#if theme === "dark"}
    <Sun size={19} aria-hidden="true" />
    <span>Light</span>
  {:else}
    <Moon size={19} aria-hidden="true" />
    <span>Dark</span>
  {/if}
</button>
