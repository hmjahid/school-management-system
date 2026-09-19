// Custom Vite plugin to fix Formik ESM imports
import { createRequire } from 'module';
const require = createRequire(import.meta.url);

export default function formikFix() {
  return {
    name: 'formik-fix',
    config() {
      return {
        optimizeDeps: {
          include: ['formik'],
          esbuildOptions: {
            target: 'es2020',
            define: {
              global: 'globalThis',
            },
          },
        },
        resolve: {
          alias: [
            {
              find: /^formik(\/.*)?$/,
              replacement: 'formik/dist/formik.esm.js',
            },
          ],
        },
      };
    },
  };
}
