# Changelog
All notable changes to this project will be documented in this file.

## [4.0.0] - 2026-XX-XX

### Evolutions

### Modifications

### Corrections
- Réponse de l'API vide ou non JSON : `ComelusException` (code 500) au lieu d'une `TypeError` (`check()`, `getMailingLists()`…) ou d'un `null` renvoyé sans erreur (`createDossier()`)
- Réponse d'erreur de l'API dont le `message` n'est pas un texte (liste de messages de validation par exemple) : `ComelusException` avec la raison HTTP au lieu d'une `TypeError` ; un `message` vide est aussi remplacé par la raison HTTP
- Clé d'API terminée par un retour à la ligne acceptée par `setApiKey()` (puis envoyée telle quelle dans l'en-tête `Authorization`)

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
