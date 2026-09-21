# Changelog
All notable changes to this project will be documented in this file.

## [4.0.0] - 2026-XX-XX

### Evolutions
- Nom, catégorie et rang des fichiers (`File`) et nom et rang des annexes (`Annexe`) facultatifs : `null`, `""` ou un texte blanc sont envoyés à l'API comme champ vide ; `getName()` et `getCategory()` renvoient alors `""`

### Modifications
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
