# Architecture de Doge Sweeper

## Vue d'ensemble

Doge Sweeper suit une architecture en couches PSR-4 avec séparation claire des responsabilités.

```
┌─────────────────────────────────────┐
│     Application Layer               │
│   (DogeSweeper.php - CLI Entry)    │
└──────────────┬──────────────────────┘
               │
       ┌───────┴────────┐
       │                │
┌──────▼─────┐  ┌───────▼──────┐
│  Wallet    │  │ Transaction  │
│  Layer     │  │  Layer       │
└──────┬─────┘  └───────┬──────┘
       │                │
       └───────┬────────┘
               │
       ┌───────▼──────────┐
       │  Integration     │
       │  Layer (FFI)     │
       └───────┬──────────┘
               │
       ┌───────▼──────────┐
       │  libdogecoin     │
       │  (C Library)     │
       └──────────────────┘
```

## Structure des fichiers

### src/Application/
- **DogeSweeper.php**: Orchestrateur principal
  - Charge la configuration
  - Coordonne le flux principal
  - Gère les erreurs

### src/Config/
- **Config.php**: Gestionnaire de configuration centralisé
  - Fusion config défaut + config locale
  - Validation
  - Accès sécurisé aux paramètres

### src/Wallet/
- **WifReader.php**: Lecteur de fichier WIF
  - Lecture et validation des clés
  - Filtrage (commentaires, lignes vides)
  
- **WalletManager.php**: Gestionnaire de portefeuilles
  - Gestion des adresses
  - Statistiques
  
- **WalletAddress.php**: Entité adresse
  - Données d'une adresse
  - Solde et informations associées

### src/Transaction/
- **FeeCalculator.php**: Calcul des frais
  - Estimation de taille de transaction
  - Calcul automatique des frais
  
- **TransactionBuilder.php**: Construction de transaction
  - Préparation des données
  - Validation
  
- **TransactionSender.php**: Envoi de transactions
  - Broadcast vers le réseau
  - Historique des envois

### src/Integration/
- **LibDogecoinFFI.php**: Interface libdogecoin
  - Bindings FFI C
  - Conversion WIF → Adresse
  - Validation

### src/Exception/
- **InvalidWifException.php**: Clés WIF invalides
- **InsufficientFundsException.php**: Soldes insuffisants
- **TransactionException.php**: Erreurs de transaction

## Flux d'exécution

```
1. Initialisation
   └─ Charger Config
   └─ Initialiser FFI
   └─ Initialiser WalletManager
   └─ Initialiser FeeCalculator

2. Lecture des WIF
   └─ WifReader::read()
   └─ Valider format
   └─ WalletManager::addFromWifs()

3. Génération des adresses
   └─ Pour chaque WIF:
      ├─ LibDogecoinFFI::addressFromWif()
      ├─ Créer WalletAddress
      └─ Stocker dans WalletManager

4. Chargement des soldes
   └─ Pour chaque adresse:
      └─ Récupérer solde (via API/RPC)
      └─ Mettre à jour WalletAddress

5. Traitement des transactions
   └─ Pour chaque adresse avec solde:
      ├─ Créer TransactionBuilder
      ├─ Calculer montant max (FeeCalculator)
      ├─ Construire tx (build())
      └─ Envoyer via TransactionSender

6. Résumé et logs
   └─ Afficher statistiques
   └─ Écrire logs
```

## Points d'intégration clés

### LibDogecoinFFI
- Convertit WIF → Clé privée
- Génère clé publique
- Hash public key → HASH160
- Génère adresse finale

### Configuration
Fichiers en cascade:
1. `config/default.php` (défauts)
2. `config/local.php` (custom - gitignored)

### Logging
- Console: si `verbose: true`
- Fichier: chemin configurable dans `log_file`

## Sécurité

### Gestion des clés
- Clés privées jamais écrites sur disque
- Manipulées exclusivement en mémoire
- Allocation FFI sécurisée pour données sensibles

### Validation
- WIF validé avant traitement
- Montants vérifiés
- Adresses destination validées

## Extensibilité

### Pour ajouter une source de solde externe:
1. Créer classe `BalanceProvider` (interface)
2. Implémenter pour chaque source (API, RPC, etc.)
3. Injecter dans DogeSweeper

### Pour ajouter support multi-blockchain:
1. Créer interface `NetworkAdapter`
2. Implémenter pour Dogecoin, Litecoin, etc.
3. Factory pattern pour sélection runtime

## Dépendances externes

- **PHP 8.3+**: Types stricts, match expressions
- **FFI**: Extension PHP pour appels C
- **libdogecoin 0.1.5+**: Bibliothèque C
- **Composer**: Autoloading PSR-4

## Testabilité

Tous les composants sont indépendants et testables:
- Config injectée
- WalletManager accepte WIF en tableau
- FeeCalculator standalone
- TransactionSender découplé
