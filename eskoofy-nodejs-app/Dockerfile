# Eskoofy Node variant — dev image (Next.js dev server).
#
# Build context = the app directory (see docker/node-dev/docker-compose.yml).
# The source tree is bind-mounted at /app at runtime with `node_modules` and
# `.next` in named volumes seeded from this image, so the container's deps stay
# independent of the host's (platform-specific Prisma engines are generated
# here for alpine/musl).

FROM node:20-alpine

WORKDIR /app

# Install deps first (postinstall runs `prisma generate`, so have the schema
# in place before npm ci).
COPY package.json package-lock.json ./
COPY prisma ./prisma
RUN npm ci

# Bake the sources in just so generate + volume seeding are reproducible.
COPY . .

# Generate the Prisma client for this platform (musl).
RUN npx prisma generate

# .next and node_modules become named volumes seeded from this image; let the
# default (non-root) `node` user write to them.
RUN mkdir -p /app/.next && chown -R node:node /app/.next /app/node_modules

USER node

EXPOSE 3000

CMD ["npm", "run", "dev", "--", "--hostname", "0.0.0.0"]