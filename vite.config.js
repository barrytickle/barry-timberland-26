import { defineConfig } from "vite";
import { resolve } from "path";
import { fileURLToPath } from "url";

const __dirname = fileURLToPath(new URL(".", import.meta.url));
const dest = "./theme/assets/dist";
const entries = [
  "./theme/assets/main.js",
  "./theme/assets/editor.js",
];

const cssEntries = [
  resolve(__dirname, "theme/assets/styles/main.scss"),
  resolve(__dirname, "theme/assets/styles/editor-style.scss"),
];

/** Re-run Tailwind when Twig templates change — they're not in Vite's module graph. */
function twigTailwindHmr() {
  return {
    name: "twig-tailwind-hmr",
    handleHotUpdate({ file, server }) {
      if (!file.endsWith(".twig")) {
        return;
      }

      const modules = cssEntries
        .map((id) => server.moduleGraph.getModuleById(id))
        .filter(Boolean);

      if (modules.length) {
        return modules;
      }
    },
  };
}

export default defineConfig(() => {
  return {
    base: "./",
    plugins: [twigTailwindHmr()],
    resolve: {
      alias: {
        "@": __dirname,
      },
    },
    server: {
      cors: true,
      strictPort: true,
      port: 3000,
      https: false,
      watch: {
        ignored: ["!**/theme/blocks/**", "!**/theme/views/**"],
      },
      hmr: {
        host: "localhost",
      },
    },
    build: {
      outDir: dest,
      emptyOutDir: true,
      manifest: true,
      target: "es2018",
      rollupOptions: {
        input: entries,
      },
      minify: true,
      write: true,
    },
  };
});
