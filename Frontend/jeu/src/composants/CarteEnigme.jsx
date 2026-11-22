/* =========================================================
   Une énigme : question (texte, photo ou date), réponse, indice,
   « voir la réponse » ; une fois terminée, la réponse et l'explication.
   ========================================================= */
import { useState } from 'react';
import { Ampoule, Coche, Croix, FlecheBas, FlecheHaut, IconeType, LIBELLES_NIVEAU, LIBELLES_TYPE, Oeil } from './Icones.jsx';

const STYLES_NIVEAU = {
  facile: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  moyen: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  difficile: 'bg-rose-50 text-rose-700 ring-rose-600/20',
};

export default function CarteEnigme({ enigme, agir, onSuivante, aUneSuivante }) {
  const [reponse, setReponse] = useState('');
  const [retour, setRetour] = useState(null); // { type: 'succes' | 'erreur', texte, indication }
  const [envoi, setEnvoi] = useState(false);
  const [confirmerReveler, setConfirmerReveler] = useState(false);

  const terminee = enigme.statut === 'resolue' || enigme.statut === 'revelee';
  const estDate = enigme.type === 'date';

  async function valider(evenement) {
    evenement.preventDefault();
    if (!reponse.trim() || envoi) return;
    setEnvoi(true);
    const r = await agir('repondre', { enigme_id: enigme.id, reponse: reponse.trim() });
    setEnvoi(false);
    if (!r) return;
    setRetour({ type: r.correct ? 'succes' : 'erreur', texte: r.message, indication: r.indication });
    if (!r.correct) setReponse('');
  }

  async function demanderIndice() {
    await agir('indice', { enigme_id: enigme.id });
  }

  async function reveler() {
    setConfirmerReveler(false);
    await agir('reveler', { enigme_id: enigme.id });
  }

  return (
    <article className="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-900/5" aria-labelledby={`titre-enigme-${enigme.id}`}>
      {/* En-tête de l'énigme */}
      <header className="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-4 sm:px-6">
        <span className="inline-flex items-center gap-1.5 rounded-full bg-[#e6eef9] px-3 py-1 text-xs font-bold text-[#003b8e]">
          <IconeType type={enigme.type} /> {LIBELLES_TYPE[enigme.type]}
        </span>
        <span className={`rounded-full px-3 py-1 text-xs font-bold ring-1 ${STYLES_NIVEAU[enigme.niveau]}`}>
          {LIBELLES_NIVEAU[enigme.niveau]} · {enigme.points_max} pts
        </span>
        {enigme.lieu && <span className="ml-auto text-xs font-medium text-slate-500">{enigme.lieu}</span>}
      </header>

      <div className="p-5 sm:p-6">
        <h2 id={`titre-enigme-${enigme.id}`} className="m-0 text-xl font-extrabold tracking-tight text-slate-900">{enigme.titre}</h2>

        {/* Image (énigmes « photo ») */}
        {enigme.image && (
          <figure className="m-0 mt-4 overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-900/5">
            <img src={enigme.image} alt="Image à identifier" className="mx-auto block max-h-80 w-full object-contain" />
          </figure>
        )}

        <p className="m-0 mt-4 text-base leading-relaxed text-slate-700">{enigme.question}</p>

        {/* Énigme terminée : réponse + explication */}
        {terminee && (
          <div className={`mt-5 rounded-2xl p-4 ring-1 ${enigme.statut === 'resolue' ? 'bg-emerald-50 ring-emerald-600/20' : 'bg-slate-50 ring-slate-900/10'}`}>
            <p className={`m-0 flex items-center gap-2 text-sm font-bold ${enigme.statut === 'resolue' ? 'text-emerald-800' : 'text-slate-700'}`}>
              {enigme.statut === 'resolue' ? <><Coche /> Trouvé ! +{enigme.points} points</> : <><Oeil /> Réponse affichée (0 point)</>}
            </p>
            <p className="m-0 mt-2 text-sm text-slate-800">Réponse : <strong>{enigme.reponse}</strong></p>
            {enigme.explication && <p className="m-0 mt-2 text-sm text-slate-600">{enigme.explication}</p>}
            {aUneSuivante && (
              <button type="button" onClick={onSuivante}
                      className="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-full border-0 bg-gradient-to-r from-[#003b8e] to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:brightness-110">
                Énigme suivante →
              </button>
            )}
          </div>
        )}

        {/* Énigme en cours : formulaire de réponse */}
        {!terminee && (
          <>
            {enigme.indice && (
              <p className="m-0 mt-4 flex items-start gap-2 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-600/20">
                <Ampoule className="mt-0.5" /> <span><strong>Indice :</strong> {enigme.indice}</span>
              </p>
            )}

            <form onSubmit={valider} className="mt-5 flex flex-col gap-2 sm:flex-row">
              <label htmlFor={`reponse-${enigme.id}`} className="sr-only">Votre réponse</label>
              <input id={`reponse-${enigme.id}`} value={reponse} onChange={(e) => setReponse(e.target.value)}
                     type={estDate ? 'number' : 'text'} inputMode={estDate ? 'numeric' : undefined}
                     min={estDate ? 1 : undefined} max={estDate ? 2100 : undefined}
                     maxLength={100} autoComplete="off" required
                     placeholder={estDate ? 'Une année, par exemple 1850' : 'Votre réponse…'}
                     className="min-w-0 flex-1 rounded-full border border-slate-300 bg-white px-5 py-3 text-base text-slate-900 outline-none transition focus:border-[#003b8e] focus:ring-4 focus:ring-[#003b8e]/15" />
              <button type="submit" disabled={envoi}
                      className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-full border-0 bg-gradient-to-r from-[#003b8e] to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:brightness-110 disabled:cursor-wait disabled:opacity-60">
                Valider
              </button>
            </form>

            {/* Retour sur la dernière réponse */}
            <div aria-live="polite">
              {retour && (
                <p className={`m-0 mt-3 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold ${retour.type === 'succes' ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'}`}>
                  {retour.type === 'succes' ? <Coche /> : retour.indication === 'plus_ancien' ? <FlecheBas /> : retour.indication === 'plus_recent' ? <FlecheHaut /> : <Croix />}
                  {retour.texte}
                </p>
              )}
            </div>

            <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
              <span className="text-slate-500">
                Essais : {enigme.tentatives} / {enigme.essais_max} · <strong className="text-slate-800">{enigme.points_en_jeu} pts</strong> en jeu
              </span>
              <div className="flex flex-wrap gap-2">
                {enigme.indice_existe && !enigme.indice && (
                  <button type="button" onClick={demanderIndice}
                          className="inline-flex cursor-pointer items-center gap-1.5 rounded-full border-0 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 ring-1 ring-amber-600/20 hover:bg-amber-100">
                    <Ampoule /> Indice (points ÷ 2)
                  </button>
                )}
                {!confirmerReveler ? (
                  <button type="button" onClick={() => setConfirmerReveler(true)}
                          className="inline-flex cursor-pointer items-center gap-1.5 rounded-full border-0 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200">
                    <Oeil /> Voir la réponse
                  </button>
                ) : (
                  <span className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2 py-1">
                    <span className="pl-2 text-xs text-slate-600">0 point pour cette énigme ?</span>
                    <button type="button" onClick={reveler} className="cursor-pointer rounded-full border-0 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white">Oui</button>
                    <button type="button" onClick={() => setConfirmerReveler(false)} className="cursor-pointer rounded-full border-0 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">Non</button>
                  </span>
                )}
              </div>
            </div>
          </>
        )}
      </div>
    </article>
  );
}
