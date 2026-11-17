-- =========================================================
-- Migration 005 : anonymisation des anciennes décisions IA (RGPD)
-- ---------------------------------------------------------
-- L'ancien chatbot enregistrait dans ai_decisions.justification la
-- réponse complète de l'IA, qui pouvait reprendre le message du citoyen.
-- Les nouvelles lignes ne contiennent que type / priorité / service
-- (format JSON commençant par {"type":...}).
--
-- On garde les lignes (lien avec la demande, trace de l'implication de
-- l'IA) mais on efface leur contenu. Idempotente.
-- =========================================================

UPDATE ai_decisions
SET justification = '{"contenu":"effacé (RGPD, ancien format)"}'
WHERE justification IS NOT NULL
  AND justification NOT LIKE '{"type":%'
  AND justification <> '{"contenu":"effacé (RGPD, ancien format)"}';
