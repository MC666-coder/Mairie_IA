/* Le parcours : une étape par énigme (verrouillée, en cours, trouvée, réponse vue) */
import { Cadenas, Coche, Oeil, IconeType, LIBELLES_TYPE } from './Icones.jsx';

const STYLES_PASTILLE = {
  resolue: 'bg-emerald-500 text-white',
  revelee: 'bg-slate-300 text-slate-700',
  disponible: 'bg-white text-[#003b8e] ring-2 ring-[#003b8e]',
  verrouillee: 'bg-slate-100 text-slate-400',
};

export default function Parcours({ parcours, enigmeActive, onChoisir }) {
  return (
    <section className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5" aria-labelledby="titre-parcours">
      <h2 id="titre-parcours" className="m-0 text-xs font-bold tracking-wider text-slate-500 uppercase">Le parcours</h2>
      <ol className="m-0 mt-4 grid list-none gap-1 p-0">
        {parcours.map((e, i) => {
          const verrouillee = e.statut === 'verrouillee';
          const active = e.id === enigmeActive;
          return (
            <li key={e.id} className="relative">
              {/* Trait vertical entre les étapes */}
              {i < parcours.length - 1 && (
                <span className={`absolute top-10 left-[1.15rem] h-[calc(100%-1.5rem)] w-0.5 ${e.statut === 'resolue' || e.statut === 'revelee' ? 'bg-emerald-300' : 'bg-slate-200'}`} aria-hidden="true" />
              )}
              <button type="button" disabled={verrouillee} onClick={() => onChoisir(e.id)}
                      aria-current={active ? 'step' : undefined}
                      className={`relative flex w-full cursor-pointer items-center gap-3 rounded-2xl border-0 p-1.5 text-left transition disabled:cursor-not-allowed ${active ? 'bg-[#e6eef9]' : 'bg-transparent hover:bg-slate-50'}`}>
                <span className={`grid size-9 shrink-0 place-items-center rounded-full text-sm font-bold ${STYLES_PASTILLE[e.statut]}`}>
                  {e.statut === 'resolue' ? <Coche /> : e.statut === 'revelee' ? <Oeil /> : verrouillee ? <Cadenas /> : i + 1}
                </span>
                <span className="min-w-0 flex-1">
                  <span className={`block truncate text-sm font-semibold ${verrouillee ? 'text-slate-400' : 'text-slate-900'}`}>
                    {verrouillee ? 'Énigme verrouillée' : e.lieu || e.titre}
                  </span>
                  <span className="flex items-center gap-1 text-xs text-slate-500">
                    <IconeType type={e.type} /> {LIBELLES_TYPE[e.type]}
                    {e.statut === 'resolue' && <> · +{e.points} pts</>}
                    {e.statut === 'revelee' && <> · réponse vue</>}
                    {e.statut === 'disponible' && <> · {e.points_max} pts</>}
                  </span>
                </span>
              </button>
            </li>
          );
        })}
      </ol>
    </section>
  );
}
