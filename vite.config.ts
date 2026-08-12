import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: resolve(__dirname, 'plugins/edulume-core/assets/admin'),
    emptyOutDir: true,
    sourcemap: true,
    lib: {
      entry: resolve(__dirname, 'apps/admin/src/index.ts'),
      name: 'edulumeAdmin',
      fileName: 'edulume-admin',
      formats: ['iife'],
    },
  },
  test: {
    include: ['apps/admin/tests/**/*.test.ts'],
    environment: 'node',
  },
});
