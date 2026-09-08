import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    nodeResolve({
      browser: true,
      preferBuiltins: true,
      extensions: ['.js', '.jsx', '.ts', '.tsx', '.json'],
    }),
    commonjs({
      include: [
        /node_modules[\\/]hoist-non-react-statics/,
        /node_modules[\\/]formik/,
        /node_modules[\\/]tiny-warning/,
      ],
      transformMixedEsModules: true,
      dynamicRequireTargets: [
        'node_modules/hoist-non-react-statics/*.js',
        'node_modules/formik/*.js',
      ],
    }),
  ],
  resolve: {
    alias: [
      {
        find: '@',
        replacement: path.resolve(__dirname, './src'),
      },
      {
        find: 'hoist-non-react-statics',
        replacement: 'hoist-non-react-statics/dist/hoist-non-react-statics.cjs.js'
      },
      {
        find: 'formik',
        replacement: 'formik/dist/formik.esm.js'
      }
    ]
  },
  optimizeDeps: {
    include: ['hoist-non-react-statics', 'formik'],
    esbuildOptions: {
      // Node.js global to browser globalThis
      define: {
        global: 'globalThis',
      },
    },
  },
  server: {
    host: true, // Allow access from outside the container
    proxy: {
      '/api': {
        target: 'http://localhost:8001',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});