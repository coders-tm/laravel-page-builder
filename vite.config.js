import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";
import fs from "fs";

function hotFilePlugin() {
  return {
    name: "hot-file",
    configureServer(server) {
      server.httpServer?.once("listening", () => {
        const address = server.httpServer?.address();
        const isAddressInfo = (x) => typeof x === "object";
        if (isAddressInfo(address)) {
          let host = address.address;
          if (host === "::" || host === "::1") {
            host = "localhost";
          } else if (host.includes(":")) {
            host = `[${host}]`;
          }
          if (!fs.existsSync("dist")) {
            fs.mkdirSync("dist");
          }
          fs.writeFileSync("dist/hot", `http://${host}:${address.port}`);
        }
      });
    },
    buildStart() {
      if (fs.existsSync("dist/hot")) {
        fs.rmSync("dist/hot");
      }
    },
  };
}

export default defineConfig({
  plugins: [react(), hotFilePlugin()],
  server: {
    cors: true,
  },
  define: {
    "process.env.NODE_ENV": JSON.stringify(
      process.env.NODE_ENV || "development"
    ),
  },
  test: {
    environment: "jsdom",
    globals: true,
    setupFiles: ["./resources/js/__tests__/setup.ts"],
    alias: {
      "@": path.resolve(__dirname, "./resources/js"),
    },
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      exclude: [
        "resources/js/__tests__/**",
        "resources/js/main.tsx",
        "resources/js/components/settings/fields/icon-data/**",
      ],
      thresholds: {
        lines: 80,
        branches: 80,
        functions: 80,
        statements: 80,
      },
    },
  },
  build: {
    outDir: "dist",
    copyPublicDir: false,
    assetsDir: "",
    lib: {
      entry: path.resolve(__dirname, "resources/js/main.tsx"),
      name: "PageBuilder",
      formats: ["es", "umd"],
      fileName: (format) => (format === "es" ? "app.js" : `app.${format}.js`),
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo && assetInfo.name && assetInfo.name.endsWith(".css")) {
            return "app.css";
          }
          return "[name].[ext]";
        },
      },
    },
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./resources/js"),
    },
  },
});
