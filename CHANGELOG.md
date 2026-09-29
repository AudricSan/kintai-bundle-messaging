# Changelog

Tous les changements notables de ce bundle sont documentés dans ce fichier.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).
Le schéma de version (X.Y.Z, canaux alpha/beta/main) est décrit dans
`.github/workflows/release.yml`.

## [Unreleased]

## [1.1.0] - 2026-09-29

### Changed

- Aucun changement fonctionnel — bump de version pour aligner ce bundle sur la ligne 1.1.0 commune à tous les bundles officiels.
- Les notifications "nouveau message" ne disaient pas qui l'avait envoyé et ne menaient nulle part au clic. Le corps précise désormais l'expéditeur (`notif_message_received_body`/`notif_thread_message_received_body`, côté Kintai Core, gagnent le placeholder `:sender`), et le clic renvoie vers `/employee/messages/{id}` — cette route n'étant gatée par aucune permission RBAC (seule la participation au fil compte, vérifiée par le contrôleur), elle fonctionne pour tout destinataire quel que soit son rôle, évitant d'avoir à déterminer côté serveur si chacun est admin/manager/employé. **Nécessite** la version de Kintai Core introduisant le paramètre `$link` sur `notify()`/`notifyMany()`.
- Le CSS (`.msg-*`/`.link-subject*`) et le JS temps réel (`message-stream.js`, SSE + envoi AJAX) vivaient dans Kintai Core, pas dans ce dépôt. Ils vivent maintenant dans `public/css/messaging.css`/`public/js/message-stream.js`, fournis par le bundle lui-même via `Bundle::loadAssetsFrom()`/`bundle_asset()`. **Nécessite** `kintai_core.min: "0.2.0"`.

## [1.0.0] - 2026-09-19

### Added

- Extraction initiale depuis Kintai (`src/Bundles/Messaging`).
