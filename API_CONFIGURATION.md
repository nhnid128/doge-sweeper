# 🐕 Doge Sweeper - API Configuration Guide

## Configuration des APIs

Doge Sweeper supporte 3 fournisseurs d'API tiers pour récupérer les soldes et envoyer les transactions.

### Fournisseurs disponibles

#### 1. **SoChain** (Recommandé) ⭐
- **URL**: https://sochain.com/api
- **Statut**: Gratuit, fiable
- **Limites**: ~3 requêtes/seconde
- **Avantages**:
  - Pas d'API key requis
  - Très rapide
  - Excellente stabilité
  - Support pour mainnet et testnet

#### 2. **dogechain.info**
- **URL**: https://dogechain.info/api
- **Statut**: Gratuit, communautaire
- **Limites**: Pas de limite connue
- **Avantages**:
  - Très simple
  - Pas d'API key requis
  - Maintenu par la communauté Dogecoin

#### 3. **BlockCypher**
- **URL**: https://www.blockcypher.com/dev/dogecoin/
- **Statut**: Freemium
- **Limites gratuites**: 200 requêtes/heure
- **Avantages**:
  - Support des transactions signées
  - Token optionnel pour augmenter les limites
  - API riche et documentée

## Configuration

### Fichier `config/local.php`

```php
<?php
return [
    // Adresse Binance pour les transferts
    'binance_address' => 'DBu3jQogbkghM5fYZQVaoCyBLwK4gzGfyP',

    // Fournisseur de solde (SoChain, dogechain.info, BlockCypher)
    'balance_provider' => 'SoChain',

    // Broadcaster de transaction
    'transaction_broadcaster' => 'SoChain',

    // Token BlockCypher (optionnel)
    'blockcypher_token' => '',

    // Activer le fallback automatique en cas d'erreur API
    'api_fallback_enabled' => true,

    // Taux de frais
    'fee_rate' => 0.01,

    // Réseau
    'network' => 'mainnet',
];
```

## Utilisation

### 1. Configuration minimale (recommandée)

```php
<?php
return [
    'binance_address' => 'votre_adresse_binance',
    'fee_rate' => 0.01,
    'network' => 'mainnet',
    // Les APIs par défaut (SoChain) seront utilisées
];
```

### 2. Avec BlockCypher Token

```php
<?php
return [
    'binance_address' => 'votre_adresse_binance',
    'balance_provider' => 'BlockCypher',
    'transaction_broadcaster' => 'BlockCypher',
    'blockcypher_token' => 'your_blockcypher_token_here',
];
```

### 3. Avec fallback désactivé (moins recommandé)

```php
<?php
return [
    'binance_address' => 'votre_adresse_binance',
    'balance_provider' => 'SoChain',
    'api_fallback_enabled' => false,
];
```

## Flux de récupération des soldes

```
DogeSweeper démarre
        ↓
  Charger config
        ↓
  Lire clés WIF
        ↓
  Générer adresses
        ↓
  Récupérer soldes via API
  ┌─────────────────────────┐
  │ balance_provider (cfg)  │
  └────────┬────────────────┘
           ↓
   Succès? ┌────────────┐
           │    OUI     │
           └────────────┘
                      │
        Traiter les transactions

           NON (erreur)
           ├────────────┐
           │            │
      api_fallback      NON
      enabled?
           │
          OUI
           │
     Essayer les autres
     providers en ordre
           ↓
     Une réussit?
           ├─ OUI → Continuer
           └─ NON → Erreur fatale
```

## Performance et recommandations

### Recommandé pour la plupart des cas
**SoChain** ✅
- Aucune configuration nécessaire
- Excellent rapport vitesse/fiabilité
- Pas de limites pratiques pour un sweeper

### Pour les petits portefeuilles
**dogechain.info** ✅
- Même plus simple que SoChain
- Parfait pour tester

### Pour les gros volumes
**BlockCypher avec token** ⭐
- Plus de contrôle
- Limites augmentées
- Requiert une clé API

## Obtenir les API Keys

### BlockCypher
1. Aller sur https://www.blockcypher.com/dev/
2. Créer un compte (gratuit)
3. Copier votre token
4. Ajouter dans `config/local.php`:
   ```php
   'blockcypher_token' => 'your_token_here',
   ```

### SoChain & dogechain.info
- ❌ Pas de token requis
- Gratuit pour tous

## Gestion des erreurs

Quand le `api_fallback_enabled` est `true` (défaut):

1. Essayer le provider configuré
2. Si erreur, essayer les autres dans cet ordre:
   - SoChain
   - dogechain.info
   - BlockCypher
3. Si tous échouent → Erreur fatale

## Exemple d'exécution

```bash
$ php bin/doge-sweeper

🐕 Doge Sweeper v1.0.0 Starting...
Validating configuration...
✅ Configuration is valid

API Configuration:
  Balance Provider: SoChain
  Transaction Broadcaster: SoChain
  API Fallback: Enabled

Reading WIF file...
WIF Reader Summary:
  Valid WIFs: 5
  Invalid WIFs: 0

✅ Read 5 addresses

Loading balances from SoChain...
✅ Balances loaded

Wallet Statistics:
  Total addresses: 5
  Addresses with balance: 3
  Total balance: 45.50000000 DOGE

...
```
