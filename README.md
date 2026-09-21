# comelusApiWrapper

Wrapper PHP de l'API v2 de Comélus. Avant l'envoi, la lib applique aux dossiers, fichiers et annexes les mêmes contrôles
que Comélus : une donnée invalide est refusée tout de suite, avec le message de l'API, au lieu d'une erreur 400 du
serveur.

### Prérequis

- PHP 8.3 ou supérieur, avec l'extension `json`
- Un client HTTP [PSR-18](https://www.php-fig.org/psr/psr-18/) et des fabriques de requêtes et de flux
[PSR-17](https://www.php-fig.org/psr/psr-17/)
- Comélus avec l'API v2 (contrôles alignés sur Comélus 1.3.13)

### Installation

```bash
composer require libriciel/comelus-api-wrapper
```

### Mise à jour depuis la version 3.x

```bash
composer require libriciel/comelus-api-wrapper:^4.0
```

La version 4.0 introduit des changements incompatibles :

- **URL en `https://` uniquement** : `setUrl()` refuse désormais les URL en `http://`, pour que la clé d'API ne
transite jamais en clair. Avant de mettre à jour, vérifier qu'aucune URL Comélus enregistrée n'utilise `http://`.
- **Contrôles alignés sur l'API v2 de Comélus** (voir [Contrôles avant l'envoi](#contrôles-avant-lenvoi)) : nom de
fichier et d'annexe obligatoire, rang d'annexe à partir de 1, nom de dossier de 511 octets au plus et limité aux
caractères acceptés par Comélus. Ces valeurs étaient déjà refusées par le serveur : seul le moment de l'erreur change.
- **`Dossier`, `File`, `Annexe` et `ComelusException` sont `final`** et ne peuvent plus être étendues ni simulées dans
les tests. `ComelusWrapper` reste simulable.
- **Arguments nommés** : le paramètre `$uploadedfile` de `File` et `Annexe` devient `$uploadedFile`.

Autres évolutions :

- la catégorie d'un fichier est facultative (`null` ou `''`) : Comélus crée alors le dossier sans catégorie ;
- les annexes d'un `File` sont facultatives (`[]` par défaut) et `File::getAnnexes()` renvoie toujours un tableau.

## Utilisation

### Initialisation

Dans un projet Symfony, le plus simple est de déclarer `ComelusWrapper` comme service et de l'injecter. Sinon, par
exemple avec Guzzle :

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Libriciel\ComelusApiWrapper\ComelusWrapper;

$httpFactory = new HttpFactory();
$comelus = new ComelusWrapper(new Client(), $httpFactory, $httpFactory);
$comelus->setUrl('https://comelus.example.org');
$comelus->setApiKey('structure_api_key');
```

### Vérification de la connexion

```php
$comelus->check(); // ['success' => true]
```

### Listes de diffusion

```php
$mailingLists = $comelus->getMailingLists();
```

### Création d'un dossier

```php
use Libriciel\ComelusApiWrapper\Model\Annexe;
use Libriciel\ComelusApiWrapper\Model\Dossier;
use Libriciel\ComelusApiWrapper\Model\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

$deliberation = new UploadedFile('/chemin/deliberation.pdf', 'deliberation.pdf');
$annexe = new UploadedFile('/chemin/annexe.pdf', 'annexe.pdf');

// catégorie (facultative), nom, rang, fichier, annexes
$file = new File('Projets', 'Budget primitif', 1, $deliberation);
// nom, rang (à partir de 1), fichier
$file->addAnnexe(new Annexe('Annexe budgétaire', 1, $annexe));

// nom, description, identifiant de la liste de diffusion, fichiers (au moins un)
$dossier = new Dossier('Séance du 21/09/2026', 'Conseil municipal', $mailingListId, [$file]);

$result = $comelus->createDossier($dossier); // ['success' => true, 'id' => '…']
```

### Envoi d'un dossier et suivi

```php
$comelus->sendDocument($result['id']);

$status = $comelus->getDossierStatus($result['id']);
$dossiers = $comelus->getDossiers();
```

### Contrôles avant l'envoi

Les constructeurs de `Dossier`, `File` et `Annexe` lèvent une `ComelusException` (code 400) si :

| Champ | Règle |
|---|---|
| Nom et description du dossier | obligatoires : `''`, `'0'` et un texte composé d'espaces sont refusés |
| Nom du dossier | 511 octets au plus ; lettres, chiffres, espaces, ponctuation, symboles monétaires, `°` et `+` uniquement (pas de retour à la ligne ni de `<`, `>`, `=`, `\|`, emoji…) |
| Liste de diffusion | UUID |
| Fichiers du dossier | au moins un |
| Nom d'un fichier ou d'une annexe | obligatoire : `''`, `'0'` et un texte composé d'espaces sont refusés ; 512 octets au plus |
| Catégorie d'un fichier | facultative, 255 octets au plus |
| Rang d'un fichier | entier positif ou nul |
| Rang d'une annexe | entier à partir de 1 |
| Fichier ou annexe | doit exister ; il est relu au moment de l'envoi |

Deux contrôles vont volontairement plus loin que Comélus, qui accepte ces valeurs sans qu'elles aient de sens : un texte
composé d'espaces et un nom de dossier terminé par un retour à la ligne.

Le nombre, la taille, le format et l'analyse antivirale des fichiers restent contrôlés par Comélus, selon la
configuration de chaque plateforme.

### Gestion des erreurs

Toutes les erreurs sont levées sous forme de `ComelusException`. Son code vaut :

- le statut HTTP renvoyé par l'API en cas d'erreur de celle-ci (400 donnée refusée, 403 clé d'API invalide, 503
antivirus indisponible…), avec le message de l'API ;
- 400 si les données fournies sont invalides, ou si l'URL ou la clé d'API n'est pas renseignée ;
- 500 si la réponse de l'API n'est pas un JSON valide ;
- le code de l'exception du client HTTP en cas d'erreur réseau, conservée dans `getPrevious()`.

```php
use Libriciel\ComelusApiWrapper\ComelusException;

try {
    $comelus->createDossier($dossier);
} catch (ComelusException $e) {
    // $e->getMessage(), $e->getCode(), $e->getPrevious()
}
```

## Développement

L'environnement de développement tourne dans Docker (PHP 8.3, la version minimale supportée) :

| Commande | Rôle |
|---|---|
| `make build` | Construit l'image |
| `make composer-install` | Installe les dépendances |
| `make composer-update` | Met à jour les dépendances |
| `make test` | Lance les tests (PHPUnit) |
| `make phpstan` | Analyse statique (PHPStan, niveau 10) |
| `make ecs` | Vérifie le style de code (ECS, PSR-12) |
| `make ecs-fix` | Corrige le style de code |
| `make shell` | Ouvre un terminal dans le conteneur |

La CI exécute les tests sur PHP 8.3, 8.4 et 8.5.
