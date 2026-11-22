/* =========================================================
   Première partie : le citoyen choisit son pseudonyme avant de jouer.
   C'est ce nom (et jamais son vrai nom) qui apparaît dans le classement
   de la commune. Il pourra le modifier plus tard.
   ========================================================= */
import { useState } from 'react';
import { Ampoule, Calendrier, Croix, Photo, Trophee } from './Icones.jsx';

export default function ChoixPseudo({ agir, nombreEnigmes, erreurServeur }) {
  const [pseudo, setPseudo] = useState('');
  const [erreur, setErreur] = useState('');
  const [envoi, setEnvoi] = useState(false);

  async function commencer(evenement) {
    evenement.preventDefault();
    const choisi = pseudo.trim();
    if (choisi.length < 3) {
      setErreur('Votre pseudonyme doit faire au moins 3 caractères.');
      return;
    }
    setEnvoi(true);
    await agir('pseudo', { pseudo: choisi }); // si refusé, le message du serveur arrive dans erreurServeur
    setEnvoi(false);
  }

  const message = erreur || erreurServeur;

  return (
    <section className="mx-auto grid max-w-3xl overflow-hidden rounded-3xl bg-white text-left shadow-sm ring-1 ring-slate-900/5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]"
             aria-labelledby="titre-bienvenue">
      {/* Règles du jeu */}
      <div className="bg-slate-50 p-6 sm:p-8">
        <p className="m-0 text-xs font-bold tracking-wider text-slate-500 uppercase">Comment jouer</p>
        <ul className="m-0 mt-4 grid list-none gap-3 p-0 text-sm text-slate-700">
          <li className="flex items-start gap-3"><span className="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-[#003b8e] ring-1 ring-slate-900/5"><Trophee /></span>
            {nombreEnigmes} énigmes à résoudre dans l’ordre : chacune débloque la suivante.</li>
          <li className="flex items-start gap-3"><span className="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-[#003b8e] ring-1 ring-slate-900/5"><Photo /></span>
            Devinettes, photos à reconnaître et dates à retrouver.</li>
          <li className="flex items-start gap-3"><span className="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-[#003b8e] ring-1 ring-slate-900/5"><Ampoule /></span>
            Un indice si vous bloquez, mais il divise vos points par deux.</li>
          <li className="flex items-start gap-3"><span className="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-[#003b8e] ring-1 ring-slate-900/5"><Calendrier /></span>
            Pour les dates, on vous dit si c’est plus ancien ou plus récent.</li>
        </ul>
      </div>

      {/* Choix du pseudonyme */}
      <form onSubmit={commencer} className="flex flex-col justify-center p-6 sm:p-8">
        <h2 id="titre-bienvenue" className="m-0 text-2xl font-extrabold tracking-tight text-slate-900">Bienvenue dans le jeu !</h2>
        <p className="m-0 mt-2 text-sm text-slate-600">
          Choisissez d’abord votre <strong>pseudonyme</strong> : c’est le nom qui apparaîtra dans le classement de la commune.
        </p>

        <label htmlFor="pseudo-depart" className="mt-6 text-sm font-semibold text-slate-800">Votre pseudonyme</label>
        <input id="pseudo-depart" value={pseudo} onChange={(e) => { setPseudo(e.target.value); setErreur(''); }}
               minLength={3} maxLength={20} required autoComplete="off" autoFocus
               placeholder="Ex. : Moulin_85" aria-describedby="aide-pseudo"
               className="mt-1.5 rounded-full border border-slate-300 bg-white px-5 py-3 text-base text-slate-900 outline-none transition focus:border-[#003b8e] focus:ring-4 focus:ring-[#003b8e]/15" />
        <p id="aide-pseudo" className="m-0 mt-1.5 text-xs text-slate-500">
          3 à 20 caractères : lettres, chiffres, espace, point ou tiret. N’utilisez pas votre vrai nom.
        </p>

        {message && (
          <p className="m-0 mt-3 flex items-start gap-2 rounded-xl bg-red-50 px-3 py-2 text-sm font-medium text-red-800 ring-1 ring-red-600/20" role="alert">
            <Croix className="mt-0.5" /> {message}
          </p>
        )}

        <button type="submit" disabled={envoi}
                className="mt-5 inline-flex cursor-pointer items-center justify-center gap-2 rounded-full border-0 bg-gradient-to-r from-[#003b8e] to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:brightness-110 disabled:cursor-wait disabled:opacity-60">
          Commencer à jouer →
        </button>
        <p className="m-0 mt-3 text-center text-xs text-slate-400">Vous pourrez le modifier plus tard.</p>
      </form>
    </section>
  );
}
