/* Classement (pseudonymes uniquement) + choix du pseudonyme pour y apparaître */
import { useState } from 'react';
import { Trophee } from './Icones.jsx';

const MEDAILLES = { 1: 'bg-amber-400 text-amber-950', 2: 'bg-slate-300 text-slate-800', 3: 'bg-orange-300 text-orange-950' };

export default function Classement({ classement, pseudo, agir }) {
  const [saisie, setSaisie] = useState('');
  const [modifier, setModifier] = useState(false);
  const [erreur, setErreur] = useState('');

  async function enregistrer(evenement) {
    evenement.preventDefault();
    const r = await agir('pseudo', { pseudo: saisie.trim() });
    if (r) {
      setModifier(false);
      setErreur('');
    } else {
      setErreur('Pseudonyme refusé : il est peut-être déjà pris, ou trop court.');
    }
  }

  const formulaireVisible = modifier; // le pseudonyme est choisi au début de la première partie

  return (
    <section className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6" aria-labelledby="titre-classement">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h2 id="titre-classement" className="m-0 flex items-center gap-2 text-lg font-bold text-slate-900">
          <Trophee className="text-amber-500" /> Classement de la commune
        </h2>
        {classement.mon_rang && (
          <span className="rounded-full bg-[#e6eef9] px-3 py-1 text-xs font-bold text-[#003b8e]">
            Vous êtes {classement.mon_rang}{classement.mon_rang === 1 ? 'er' : 'e'} sur {classement.joueurs}
          </span>
        )}
      </div>

      {/* Pseudonyme : on n'apparaît jamais sous son vrai nom */}
      {formulaireVisible ? (
        <form onSubmit={enregistrer} className="mt-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-900/5">
          <label htmlFor="pseudo-jeu" className="text-sm font-semibold text-slate-800">
            {pseudo ? 'Nouveau pseudonyme' : 'Choisissez un pseudonyme pour apparaître dans le classement'}
          </label>
          <p className="m-0 mt-0.5 text-xs text-slate-500">Votre vrai nom n’est jamais affiché. 3 à 20 caractères.</p>
          <div className="mt-2 flex flex-col gap-2 sm:flex-row">
            <input id="pseudo-jeu" value={saisie} onChange={(e) => setSaisie(e.target.value)} minLength={3} maxLength={20} required
                   placeholder="Ex. : Moulin_85" autoComplete="off"
                   className="min-w-0 flex-1 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#003b8e] focus:ring-4 focus:ring-[#003b8e]/15" />
            <button type="submit" className="cursor-pointer rounded-full border-0 bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
              Enregistrer
            </button>
          </div>
          {erreur && <p className="m-0 mt-2 text-xs font-semibold text-red-700" role="alert">{erreur}</p>}
        </form>
      ) : (
        <p className="m-0 mt-2 text-sm text-slate-500">
          Vous jouez sous le nom <strong className="text-slate-800">{pseudo}</strong>.{' '}
          <button type="button" onClick={() => { setSaisie(pseudo); setModifier(true); }}
                  className="cursor-pointer border-0 bg-transparent p-0 text-sm font-semibold text-[#003b8e] underline">Modifier</button>
        </p>
      )}

      {/* Top 10 */}
      {classement.top.length === 0 ? (
        <p className="m-0 mt-4 text-sm text-slate-500">Personne n’est encore classé : soyez le premier !</p>
      ) : (
        <ol className="m-0 mt-4 grid list-none gap-1.5 p-0">
          {classement.top.map((j) => (
            <li key={j.rang} className={`flex items-center gap-3 rounded-xl px-3 py-2 ${j.moi ? 'bg-[#e6eef9] ring-1 ring-[#003b8e]/30' : 'bg-slate-50'}`}>
              <span className={`grid size-7 shrink-0 place-items-center rounded-full text-xs font-extrabold ${MEDAILLES[j.rang] ?? 'bg-white text-slate-600 ring-1 ring-slate-900/10'}`}>{j.rang}</span>
              <span className="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900">{j.pseudo}{j.moi && ' (vous)'}</span>
              <span className="text-xs text-slate-500">{j.trouvees} trouvée{j.trouvees > 1 ? 's' : ''}</span>
              <span className="w-16 text-right text-sm font-bold text-slate-900">{j.score} pts</span>
            </li>
          ))}
        </ol>
      )}
    </section>
  );
}
