# Changelog
All notable changes to this project will be documented in this file.

## [4.0.0] - 2026-XX-XX

### Evolutions
- Catégorie des fichiers (`File`) facultative : `null`, `""` ou un texte blanc sont envoyés vides et Comélus crée le dossier sans catégorie ; `getCategory()` renvoie alors `""`

### Modifications
- **Changement incompatible** : nom et rang des fichiers (`File`) et des annexes (`Annexe`) contrôlés comme le fait l'API v2 de Comélus, pour échouer avant l'envoi plutôt que sur un refus 400 : nom obligatoire (`""` et `"0"` refusés, « … name must be not empty »), rang d'annexe supérieur ou égal à 1 (« annexe rank must be greater than 0 ») ; le rang d'un fichier peut toujours valoir 0
- **Changement incompatible** : `setUrl()` n'accepte plus que les URL en `https://` (message « malformed url : https required »), pour que la clé d'API ne transite jamais en clair ; vérifier avant la mise à jour qu'aucune URL Comélus enregistrée n'utilise `http://`

### Corrections
- Réponse de l'API vide ou non JSON : `ComelusException` (code 500) au lieu d'une `TypeError` (`check()`, `getMailingLists()`…) ou d'un `null` renvoyé sans erreur (`createDossier()`)
- Réponse d'erreur de l'API dont le `message` n'est pas un texte (liste de messages de validation par exemple) : `ComelusException` avec la raison HTTP au lieu d'une `TypeError` ; un `message` vide est aussi remplacé par la raison HTTP
- Clé d'API terminée par un retour à la ligne acceptée par `setApiKey()` (puis envoyée telle quelle dans l'en-tête `Authorization`)
- URL terminée par un retour à la ligne acceptée par `setUrl()`
- URL terminée par `/` : les requêtes partaient vers `https://…//api/v2/…` ; la barre finale est désormais retirée par `setUrl()`
- Dossier dont le nom ou la description vaut `"0"` refusé (« must be not empty »)
- Fichier ou annexe supprimé (ou devenu illisible) entre sa création et `createDossier()` : `ComelusException` (code 400, « … file not readable ») au lieu d'un warning PHP suivi d'une exception de `symfony/mime`

### Suppressions

## [3.0.0] - 2025-09-19

### Evolutions
- Mise à jour de sécurité (composer et npm)
- PHP >= 8.3
- Ajout des nouvelles entrées d’API de ComElus (api/v2)

### Modifications
- De la préparation de l'envoie pour la création des dossiers

### Suppressions
- Suppression des appels API de ComElus v1

## [2.0.0] - 2024-01-10

## [1.0.1] - 2020-06-15

## [1.0.0] - 2020-06-02
