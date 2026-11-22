/* Point d'entrée de l'application React du jeu d'énigmes (Frontend/Jeu-enigme.php) */
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';

const racine = document.getElementById('jeu-enigmes');
if (racine) {
  createRoot(racine).render(
    <StrictMode>
      <App />
    </StrictMode>
  );
}
