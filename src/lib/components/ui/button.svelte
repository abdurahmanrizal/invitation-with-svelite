<script lang="ts">
  import { cva, type VariantProps } from "class-variance-authority";
  import { cn } from "$lib/utils/cn";

  const buttonVariants = cva(
    "inline-flex items-center justify-center gap-2 rounded-full text-sm font-medium transition-all outline-none disabled:pointer-events-none disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-amber-400/70",
    {
      variants: {
        variant: {
          default:
            "bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20 hover:bg-amber-400",
          outline:
            "border border-white/20 bg-white/5 text-white backdrop-blur hover:bg-white/10",
          ghost: "text-white hover:bg-white/10",
        },
        size: {
          default: "h-11 px-6",
          lg: "h-12 px-8 text-base",
          sm: "h-9 px-4 text-xs",
        },
      },
      defaultVariants: {
        variant: "default",
        size: "default",
      },
    },
  );

  type Variant = VariantProps<typeof buttonVariants>["variant"];
  type Size = VariantProps<typeof buttonVariants>["size"];

  export let variant: Variant = "default";
  export let size: Size = "default";
  export let href: string | undefined = undefined;
  export let type: "button" | "submit" | "reset" = "button";
  export let className = "";
  export let disabled = false;
</script>

{#if href}
  <a {href} class={cn(buttonVariants({ variant, size }), className)}>
    <slot />
  </a>
{:else}
  <button {type} {disabled} class={cn(buttonVariants({ variant, size }), className)} on:click>
    <slot />
  </button>
{/if}
