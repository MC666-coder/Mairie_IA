/* Petites icônes SVG (style « trait fin », d'après Lucide, licence ISC) */
function Icone({ children, className = '' }) {
  return (
    <svg className={`inline-block h-[1.1em] w-[1.1em] shrink-0 ${className}`} viewBox="0 0 24 24" fill="none"
         stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
      {children}
    </svg>
  );
}

export const Cadenas = (p) => <Icone {...p}><rect width="18" height="11" x="3" y="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></Icone>;
export const Coche = (p) => <Icone {...p}><path d="M20 6 9 17l-5-5" /></Icone>;
export const Oeil = (p) => <Icone {...p}><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" /></Icone>;
export const Ampoule = (p) => <Icone {...p}><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" /><path d="M9 18h6" /><path d="M10 22h4" /></Icone>;
export const Trophee = (p) => <Icone {...p}><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" /><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" /><path d="M4 22h16" /><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" /><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" /><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" /></Icone>;
export const Photo = (p) => <Icone {...p}><rect width="18" height="18" x="3" y="3" rx="2" /><circle cx="9" cy="9" r="2" /><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21" /></Icone>;
export const Calendrier = (p) => <Icone {...p}><rect width="18" height="18" x="3" y="4" rx="2" /><path d="M16 2v4" /><path d="M8 2v4" /><path d="M3 10h18" /></Icone>;
export const Bulle = (p) => <Icone {...p}><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></Icone>;
export const FlecheHaut = (p) => <Icone {...p}><path d="m5 12 7-7 7 7" /><path d="M12 19V5" /></Icone>;
export const FlecheBas = (p) => <Icone {...p}><path d="M12 5v14" /><path d="m19 12-7 7-7-7" /></Icone>;
export const Croix = (p) => <Icone {...p}><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></Icone>;

/** Icône du type d'énigme */
export function IconeType({ type, className }) {
  if (type === 'photo') return <Photo className={className} />;
  if (type === 'date') return <Calendrier className={className} />;
  return <Bulle className={className} />;
}

export const LIBELLES_TYPE = { texte: 'Devinette', photo: 'Photo', date: 'Date' };
export const LIBELLES_NIVEAU = { facile: 'Facile', moyen: 'Moyen', difficile: 'Difficile' };
