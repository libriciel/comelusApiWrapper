# Changelog
All notable changes to this project will be documented in this file.

## [4.0.0] - 2026-XX-XX

Version majeure : voir la section « Mise à jour depuis la version 3.x » du README.

### Changements incompatibles
- `setUrl()` n'accepte plus que les URL en `https://` (message « malformed url : https required », au lieu de « malformed url : missing http|https »), pour que la clé d'API ne transite jamais en clair ; vérifier avant la mise à jour qu'aucune URL Comélus enregistrée n'utilise `http://`
- Fichiers (`File`) et annexes (`Annexe`) contrôlés comme le fait l'API v2 de Comélus, pour échouer avant l'envoi plutôt que sur un refus 400 : nom obligatoire (`""`, `"0"` et un texte composé d'espaces refusés, « … name must be not empty »), rang d'annexe supérieur ou égal à 1 (« annexe rank must be greater than 0 ») ; le rang d'un fichier peut toujours valoir 0
- Dossier (`Dossier`) contrôlé comme le fait l'API v2 de Comélus : nom limité à 511 octets (Comélus refuse à partir de 512), caractères autorisés restreints à ceux de Comélus (lettres, chiffres, espaces, ponctuation, symboles monétaires, `°` et `+` ; « dossier name contains forbidden characters ») ; nom, description et identifiant de liste de diffusion `""` ou `"0"` refusés ; message « dossier mailingListId is not a valid UUID » (au lieu de « … is not id valid »)
- Deux contrôles vont volontairement plus loin que Comélus, qui accepte ces valeurs sans qu'elles aient de sens : un texte composé d'espaces (nom et description du dossier, noms des fichiers et annexes) et un nom de dossier terminé par un retour à la ligne sont refusés
- `Dossier`, `File`, `Annexe` et `ComelusException` sont `final` (ni héritage ni double de test possibles) ; `ComelusWrapper` ne l'est pas, pour que les consommateurs puissent le simuler dans leurs tests
- Arguments nommés : le paramètre `$uploadedfile` des constructeurs de `File` et `Annexe` devient `$uploadedFile` (le getter devient `getUploadedFile()`, sans effet pour les appelants car PHP ne distingue pas la casse des noms de méthodes)

### Evolutions socle technique
- Mise à jour de l'environnement : image Docker PHP 8.3, Makefile, PHPUnit 12, PHPStan (niveau 10), ECS (PSR-12), couverture de code à 100 %
- CI : image par version de PHP et tests sur PHP 8.3, 8.4 et 8.5, PHPStan, ECS, `composer validate` et `composer audit`, analyse Sonar, contrôle des standards Libriciel

### Evolutions
- Catégorie des fichiers (`File`) facultative : `null`, `""` ou un texte blanc sont envoyés vides et Comélus crée le dossier sans catégorie ; `getCategory()` renvoie alors `""`
- Annexes d'un fichier facultatives (`[]` par défaut) ; `File::getAnnexes()` renvoie toujours un tableau
- Les exceptions `ComelusException` conservent l'exception d'origine (`getPrevious()`), par exemple l'erreur réseau du client HTTP ou l'erreur de décodage JSON
- Identifiant de liste de diffusion : tout UUID RFC 4122 accepté quelle que soit sa version, comme par Comélus ; un retour à la ligne final est refusé
- Typage complet de l'API publique (types natifs et annotations, dont le type de retour `array` de `createDossier()`), exploitable par l'analyse statique des projets qui utilisent la lib

### Modifications
- Modernisation du code (syntaxe PHP 8, `declare(strict_types=1)`)
- Contrainte PHP `^8.3` (au lieu de `>=8.3`) et dépendances de développement mises à jour
- README réécrit : exemples, contrôles avant l'envoi, gestion des erreurs, mise à jour depuis la 3.x

### Corrections
- Réponse de l'API vide ou non JSON : `ComelusException` (code 500) au lieu d'une `TypeError` (`check()`, `getMailingLists()`…) ou d'un `null` renvoyé sans erreur (`createDossier()`)
- Réponse d'erreur de l'API dont le `message` n'est pas un texte (liste de messages de validation par exemple) : `ComelusException` avec la raison HTTP au lieu d'une `TypeError` ; un `message` vide est aussi remplacé par la raison HTTP
- Clé d'API terminée par un retour à la ligne acceptée par `setApiKey()` (puis envoyée telle quelle dans l'en-tête `Authorization`)
- URL terminée par un retour à la ligne acceptée par `setUrl()`
- URL terminée par `/` : les requêtes partaient vers `https://…//api/v2/…` ; la barre finale est désormais retirée par `setUrl()`
- Fichier ou annexe supprimé (ou devenu illisible) entre sa création et `createDossier()` : `ComelusException` (code 400, « … file not readable ») au lieu d'un warning PHP suivi d'une exception de `symfony/mime`
- Type MIME d'un fichier non reconnu : un en-tête `Content-Type` vide était envoyé ; il est désormais déduit du nom du fichier

### Suppressions
- Suppression du champ `version` du `composer.json` : la version est désormais déterminée par les tags git
- Les classes de test ne sont plus chargées par l'autoload des projets qui utilisent la lib (`autoload-dev`)

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
