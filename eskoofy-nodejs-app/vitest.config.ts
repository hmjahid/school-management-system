import { defineConfig } from "vitest/config";
import { fileURLToPath } from "node:url";

export default defineConfig({
  test: {
    environment: "node",
    globals: true,
    include: ["tests/**/*.test.ts"],
    env: {
      // Prisma validates the datasource URL when the client is constructed;
      // unit tests never query, so a placeholder keeps imports safe.
      DATABASE_URL: "mysql://root:password@127.0.0.1:3306/eskoofy_test",
    },
  },
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./", import.meta.url)),
    },
  },
});
