import type { NextConfig } from "next";
import { fileURLToPath } from "node:url";

const projectRoot = fileURLToPath(new URL(".", import.meta.url));

/**
 * Eskoofy Node.js variant — Next.js config.
 *
 * Single-architecture app: the public site (RSC/SSR), the dashboard, and the
 * `/api/v1/*` route handlers all live in this one project. There is deliberately
 * no separate API server and no SPA client.
 */
const nextConfig: NextConfig = {
  reactStrictMode: true,
  poweredByHeader: false,
  // Pin output file tracing to this project so a lockfile higher up the tree
  // (monorepo root / home dir) cannot widen the build root.
  outputFileTracingRoot: projectRoot,
  // Prisma and bcryptjs are Node-only; keep them server-external so the bundler
  // does not try to inline native/engine binaries.
  serverExternalPackages: ["@prisma/client", "bcryptjs"],
  eslint: {
    // Linting is a separate, explicit gate (`npm run lint`); do not fail builds on it.
    ignoreDuringBuilds: true,
  },
};

export default nextConfig;
