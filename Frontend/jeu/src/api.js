/* =========================================================
   Appels à l'API du jeu (Backend/jeu/api.php)
   - jeton CSRF lu dans <meta name="csrf-token"> et envoyé en en-tête
   - session expirée (401) : retour à la connexion, puis au jeu
   ========================================================= */
const ADRESSE_API = '../Backend/jeu/api.php';

function jetonCsrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export class ErreurApi extends Error {}

async function lire(reponse) {
  if (reponse.status === 401) {
    window.location.href = 'connexion_citoyen.php?session=expiree&retour=' + encodeURIComponent('Jeu-enigme.php');
    throw new ErreurApi('Votre session a expiré.');
  }
  let donnees;
  try {
    donnees = await reponse.json();
  } catch {
    throw new ErreurApi('Le serveur ne répond pas correctement. Réessayez dans un instant.');
  }
  if (!donnees.success) {
    throw new ErreurApi(donnees.message || 'Une erreur est survenue.');
  }
  return donnees;
}

/** État complet du jeu : parcours, score, classement. */
export async function chargerEtat() {
  const reponse = await fetch(`${ADRESSE_API}?action=etat`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
  });
  return lire(reponse);
}

/** Action du joueur : repondre, indice, reveler, pseudo. */
export async function envoyerAction(action, champs = {}) {
  const corps = new FormData();
  corps.append('action', action);
  Object.entries(champs).forEach(([cle, valeur]) => corps.append(cle, valeur));
  const reponse = await fetch(ADRESSE_API, {
    method: 'POST',
    headers: { 'X-CSRF-Token': jetonCsrf(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    body: corps,
  });
  return lire(reponse);
}
