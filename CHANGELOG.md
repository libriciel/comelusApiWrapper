# Changelog
All notable changes to this project will be documented in this file.

## [4.0.0] - 2026-XX-XX

### Evolutions
- Catégorie des fichiers (`File`) facultative : `null`, `""` ou un texte blanc sont envoyés vides et Comélus crée le dossier sans catégorie ; `getCategory()` renvoie alors `""`

### Modifications
- **Changement incompatible** : nom et rang des fichiers (`File`) et des annexes (`Annexe`) contrôlés comme le fait l'API v2 de Comélus, pour échouer avant l'envoi plutôt que sur un refus 400 : nom obligatoire (`""`, `"0"` et un texte composé d'espaces refusés, « … name must be not empty »), rang d'annexe supérieur ou égal à 1 (« annexe rank must be greater than 0 ») ; le rang d'un fichier peut toujours valoir 0
- **Changement incompatible** : dossier (`Dossier`) contrôlé comme le fait l'API v2 de Comélus : nom limité à 511 octets (Comélus refuse à partir de 512), caractères autorisés restreints à ceux de Comélus (lettres, chiffres, espaces, ponctuation, symboles monétaires, `°` et `+` ; « dossier name contains forbidden characters ») ; nom, description et identifiant de liste de diffusion `""` ou `"0"` refusés. Deux contrôles vont volontairement plus loin que Comélus, qui accepte ces valeurs sans qu'elles aient de sens : un texte composé d'espaces (nom, description, noms des fichiers et annexes) et un nom de dossier terminé par un retour à la ligne sont refusés
- Identifiant de liste de diffusion : tout UUID RFC 4122 accepté quelle que soit sa version, comme par Comélus ; un retour à la ligne final est refusé
- **Changement incompatible** : `setUrl()` n'accepte plus que les URL en `https://` (message « malformed url : https required »), pour que la clé d'API ne transite jamais en clair ; vérifier avant la mise à jour qu'aucune URL Comélus enregistrée n'utilise `http://`

### Corrections
- Réponse de l'API vide ou non JSON : `ComelusException` (code 500) au lieu d'une `TypeError` (`check()`, `getMailingLists()`…) ou d'un `null` renvoyé sans erreur (`createDossier()`)
- Réponse d'erreur de l'API dont le `message` n'est pas un texte (liste de messages de validation par exemple) : `ComelusException` avec la raison HTTP au lieu d'une `TypeError` ; un `message` vide est aussi remplacé par la raison HTTP
- Clé d'API terminée par un retour à la ligne acceptée par `setApiKey()` (puis envoyée telle quelle dans l'en-tête `Authorization`)
- URL terminée par un retour à la ligne acceptée par `setUrl()`
- URL terminée par `/` : les requêtes partaient vers `https://…//api/v2/…` ; la barre finale est désormais retirée par `setUrl()`
- Fichier ou annexe supprimé (ou devenu illisible) entre sa création et `createDossier()` : `ComelusException` (code 400, « … file not readable ») au lieu d'un warning PHP suivi d'une exception de `symfony/mime`
- Type MIME d'un fichier non reconnu : un en-tête `Content-Type` vide était envoyé ; il est désormais déduit du nom du fichier

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
