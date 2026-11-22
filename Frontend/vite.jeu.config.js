/* =========================================================
   Construction de l'application React du jeu d'énigmes
   ---------------------------------------------------------
   npm run jeu         → assets/jeu/jeu.js (une fois)
   npm run jeu:watch   → reconstruit à chaque modification
   Le code source est dans jeu/src/. Un seul fichier JS est produit,
   servi par le site lui-même : compatible avec la CSP « script-src 'self' ».
   Les styles viennent de assets/css/tailwind.css (npm run css).
   ========================================================= */
import react from '@vitejs/plugin-react';

export default {
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'assets/jeu',
    emptyOutDir: true,
    sourcemap: false,
    rollupOptions: {
      input: 'jeu/src/main.jsx',
      output: {
        entryFileNames: 'jeu.js',
        chunkFileNames: 'jeu-[name].js',
        assetFileNames: 'jeu-[name][extname]',
      },
    },
  },
};
