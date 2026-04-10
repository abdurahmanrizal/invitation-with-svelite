export const handle = async ({ event, resolve }) => {
  return resolve(event, {
    transformPageChunk: ({ html }) => {
      // Enable Svelte DevTools support in development
      if (import.meta.env.DEV) {
        return html.replace(
          '</body>',
          `
          <script>
            window.__SVELTE_DEV__ = true;
          </script>
          </body>`
        );
      }
      return html;
    }
  });
};
