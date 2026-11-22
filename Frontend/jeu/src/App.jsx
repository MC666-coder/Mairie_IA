/* =========================================================
   JEU D'ÉNIGMES DU PATRIMOINE — application principale
   Le parcours (à gauche) se débloque énigme après énigme ;
   l'énigme choisie s'affiche à droite ; classement en dessous.
   Toute la logique (réponses, points, déblocage) est sur le serveur :
   l'application ne fait qu'afficher l'état renvoyé par l'API.
   ========================================================= */
import { useCallback, useEffect, useState } from 'react';
import { chargerEtat, envoyerAction } from './api.js';
import Parcours from './composants/Parcours.jsx';
import CarteEnigme from './composants/CarteEnigme.jsx';
import Classement from './composants/Classement.jsx';
import ChoixPseudo from './composants/ChoixPseudo.jsx';
import { Croix, Trophee } from './composants/Icones.jsx';

export default function App() {
  const [etat, setEtat] = useState(null);
  const [erreur, setErreur] = useState('');
  const [enigmeChoisie, setEnigmeChoisie] = useState(null);

  // Énigme à afficher : celle choisie, sinon celle en cours (la première disponible)
  const enCours = etat?.parcours.find((e) => e.statut === 'disponible') ?? null;
  const enigme = etat?.parcours.find((e) => e.id === enigmeChoisie) ?? enCours ?? etat?.parcours.at(-1) ?? null;

  const rafraichir = useCallback(async () => {
    try {
      setEtat(await chargerEtat());
      setErreur('');
    } catch (e) {
      setErreur(e.message);
    }
  }, []);

  useEffect(() => {
    rafraichir();
  }, [rafraichir]);

  /** Envoie une action ; met l'état à jour. Renvoie la réponse du serveur (ou null en cas d'erreur). */
  async function agir(action, champs) {
    try {
      const reponse = await envoyerAction(action, champs);
      if (reponse.etat) setEtat(reponse.etat);
      setErreur('');
      return reponse;
    } catch (e) {
      setErreur(e.message);
      return null;
    }
  }

  /** Passe à l'énigme suivante débloquée. */
  function suivante() {
    setEnigmeChoisie(null);
  }

  if (!etat) {
    return (
      <div className="rounded-3xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-900/5">
        {erreur ? (
          <p className="m-0 inline-flex items-center gap-2 text-red-700"><Croix /> {erreur}</p>
        ) : (
          <p className="m-0">Chargement du parcours…</p>
        )}
      </div>
    );
  }

  // Première partie : le pseudonyme du classement est choisi avant de jouer
  if (!etat.pseudo) {
    return <ChoixPseudo agir={agir} nombreEnigmes={etat.parcours.length} erreurServeur={erreur} />;
  }

  const terminees = etat.parcours.filter((e) => e.statut === 'resolue' || e.statut === 'revelee').length;
  const total = etat.parcours.length;
  const fini = total > 0 && terminees === total;

  return (
    <div className="grid items-start gap-6 text-left lg:grid-cols-[320px_minmax(0,1fr)]">
      {/* Message d'erreur (réseau, action refusée…) */}
      {erreur && (
        <p role="alert" className="m-0 flex items-start gap-2 rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-600/20 lg:col-span-2">
          <Croix className="mt-0.5" /> {erreur}
        </p>
      )}

      <aside className="flex min-w-0 flex-col gap-6 lg:sticky lg:top-24">
        {/* Score et avancement */}
        <section className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5" aria-label="Votre score">
          <div className="flex items-end justify-between gap-3">
            <div>
              <p className="m-0 text-xs font-bold tracking-wider text-slate-500 uppercase">Votre score</p>
              <p className="m-0 mt-1 text-3xl font-extrabold text-slate-900">
                {etat.score} <span className="text-base font-semibold text-slate-400">/ {etat.score_max} pts</span>
              </p>
            </div>
            <span className="grid size-11 place-items-center rounded-2xl bg-amber-50 text-xl text-amber-600"><Trophee /></span>
          </div>
          <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar"
               aria-valuemin={0} aria-valuemax={total} aria-valuenow={terminees} aria-label="Énigmes terminées">
            <div className="h-full rounded-full bg-gradient-to-r from-[#003b8e] to-indigo-500 transition-all duration-500"
                 style={{ width: `${total ? (terminees / total) * 100 : 0}%` }} />
          </div>
          <p className="m-0 mt-2 text-xs text-slate-500">{terminees} énigme{terminees > 1 ? 's' : ''} terminée{terminees > 1 ? 's' : ''} sur {total}</p>
        </section>

        <Parcours parcours={etat.parcours} enigmeActive={enigme?.id} onChoisir={setEnigmeChoisie} />
      </aside>

      <div className="flex min-w-0 flex-col gap-6">
        {fini && (
          <section className="rounded-3xl bg-gradient-to-br from-[#003b8e] via-[#1e3a9e] to-indigo-600 p-6 text-white shadow-lg">
            <p className="m-0 text-sm text-blue-100">Parcours terminé</p>
            <p className="m-0 mt-1 text-2xl font-extrabold">Bravo, vous connaissez votre commune !</p>
            <p className="m-0 mt-2 text-sm text-blue-100">
              Score final : {etat.score} points sur {etat.score_max}. De nouvelles énigmes arriveront bientôt.
            </p>
          </section>
        )}

        {enigme && (
          <CarteEnigme key={enigme.id} enigme={enigme} agir={agir} onSuivante={suivante}
                       aUneSuivante={Boolean(enCours && enCours.id !== enigme.id)} />
        )}

        <Classement classement={etat.classement} pseudo={etat.pseudo} agir={agir} />
      </div>
    </div>
  );
}
