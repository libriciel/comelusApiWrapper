# ComelusApiWrapper

Wrapper php de l'api de comelus.

### Prérequis
php >= 7.2

### Installation

```
composer require libriciel/comelus-wrapper
```

### Initialisation
```
// Le plus simple reste d'injecter le service
$client = new ClientImplementation();
$requestFactory = new RequestFactoryImplementation()
$streamFactory = new StreamFactoryImplementation()
$ceWrapper = new ComelusWrapper($client, $requestFactory, $streamFactory);

$ceWrapper->setKey("structure_api_key");
$ceWrapper->setUrl("https://comelus.fr");

```

#### Verification de la connexion

```
$ceWrapper->check();
```

#### Lister les listes de diffusions

```
$ceWrapper->getMailingLists();
```


#### Lister les dossiers

```
$ceWrapper->getDocuments();
```


#### Récuperer les status d'un dossier

```
$ceWrapper->getDocumentStatus('documentId');;
```


#### Creer un dossier

```
$ceWrapper->createDocument('name', 'mailingListId', 'description', files[]);;
```


#### Envoyer un dossier

```
$ceWrapper->sendDocument('documentId');
```

