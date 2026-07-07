# BlockCypher - Guide de Configuration Complet

## 📋 Table des matières

1. [Créer un compte BlockCypher](#créer-un-compte)
2. [Obtenir votre token API](#obtenir-votre-token)
3. [Configurer dans Doge Sweeper](#configuration)
4. [Limites et tarification](#limites)
5. [Dépannage](#dépannage)

---

## Créer un compte BlockCypher

### Étape 1: Aller sur le site

1. Ouvrez votre navigateur et allez sur: **https://www.blockcypher.com/**

2. Cliquez sur **"Sign Up"** ou **"Get Started"** en haut à droite

### Étape 2: Créer un compte gratuit

1. Vous verrez le formulaire d'inscription avec deux options:
   - **Gratuit** (Free) ← Recommandé pour débuter
   - **Payant** (Pro)

2. Pour l'option gratuite, cliquez sur **"Sign Up"**

3. Remplissez le formulaire:
   ```
   Email:        votre_email@gmail.com
   Password:     mot_de_passe_sécurisé
   Confirm Pwd:  répétez_le_mot_de_passe
   ```

4. Acceptez les conditions d'utilisation

5. Cliquez sur **"Create Account"**

### Étape 3: Vérifier votre email

1. Allez dans votre boîte mail
2. Vous recevrez un email de BlockCypher
3. Cliquez sur le lien de vérification
4. Vous êtes maintenant connecté! ✅

---

## Obtenir votre token API

### Localisez votre token

1. Après la connexion, allez sur: **https://www.blockcypher.com/dev/dogecoin/**
   (Ou cliquez sur "Dogecoin" dans le menu)

2. Vous devriez voir:
   ```
   Dogecoin Mainnet
   Token: [VOTRE_TOKEN_ICI]
   ```

3. **Copiez votre token** (c'est une longue chaîne de caractères)

   Exemple:
   ```
   a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```

### Alternative: Dashboard

1. Allez sur: https://www.blockcypher.com/dev/
2. Vous verrez une liste de vos tokens par blockchain
3. Cherchez "Dogecoin Mainnet"
4. Copiez le token

---

## Configuration dans Doge Sweeper

### Option 1: Configuration avec BlockCypher (RECOMMANDÉ)

#### Créer le fichier `config/local.php`:

```php
<?php
return [
    // Votre adresse Binance
    'binance_address' => 'DBu3jQogbkghM5fYZQVaoCyBLwK4gzGfyP',

    // Utiliser BlockCypher (gratuit, 200 req/heure)
    'balance_provider' => 'BlockCypher',
    'transaction_broadcaster' => 'BlockCypher',

    // Votre token BlockCypher
    'blockcypher_token' => 'COLLEZ_VOTRE_TOKEN_ICI',

    // Taux de frais
    'fee_rate' => 0.01,

    // Réseau
    'network' => 'mainnet',
];
```

#### Remplacer le token:

```bash
# Exemple: votre token BlockCypher
sed -i "s/COLLEZ_VOTRE_TOKEN_ICI/a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6/g" config/local.php
```

### Option 2: Configuration avec dogechain.info (100% GRATUIT)

```php
<?php
return [
    'binance_address' => 'DBu3jQogbkghM5fYZQVaoCyBLwK4gzGfyP',

    // Utiliser dogechain.info (100% gratuit, communautaire)
    'balance_provider' => 'dogechain.info',
    'transaction_broadcaster' => 'dogechain.info',

    'fee_rate' => 0.01,
    'network' => 'mainnet',
];
```

### Option 3: Configuration avec fallback intelligent (MEILLEURE)

```php
<?php
return [
    'binance_address' => 'DBu3jQogbkghM5fYZQVaoCyBLwK4gzGfyP',

    // Primaire: dogechain.info (100% gratuit)
    'balance_provider' => 'dogechain.info',
    'transaction_broadcaster' => 'dogechain.info',

    // BlockCypher disponible en fallback (au cas où)
    'blockcypher_token' => 'votre_token_ici',

    // Fallback: essayer d'autres APIs si celle par défaut échoue
    'api_fallback_enabled' => true,

    'fee_rate' => 0.01,
    'network' => 'mainnet',
];
```

---

## Limites et tarification

### Comparaison des fournisseurs

| Fournisseur | Gratuit | Limite | Support | URL |
|------------|---------|--------|---------|-----|
| **BlockCypher** | ✅ | 200 req/h | Bon | https://blockcypher.com |
| **dogechain.info** | ✅ | Illimité | Communautaire | https://dogechain.info |

### BlockCypher - Plan Gratuit

| Limite | Valeur |
|--------|--------|
| **Requêtes/heure** | 200 |
| **Adresses Dogecoin** | Illimité |
| **Transactions** | Illimité |
| **Token** | 1 token |

**Parfait pour**: 
- Tester le projet
- Petits portefeuilles (< 100 adresses)
- Balayages occasionnels

### dogechain.info - Gratuit

| Limite | Valeur |
|--------|--------|
| **Requ êtes/heure** | Illimité |
| **Adresses** | Illimité |
| **Transactions** | Illimité |
| **Token** | Non requis |

**Parfait pour**: 
- Production
- Gros portefeuilles
- Balayages fréquents

### Calcul du nombre de requêtes

**Nombre d'adresses = Nombre de requêtes**

```
Exemple BlockCypher:
- 100 adresses = ~1 requête pour tout charger
- 200 adresses = ~2 requêtes
- 1000 adresses = ~5 requêtes (BlockCypher supporte 200 adresses/requête)

Exemple dogechain.info:
- Illimité! ✅
```

---

## Dépannage

### Erreur: "Invalid token"

```
BlockCypher API error: Invalid token
```

**Solutions**:
1. ✅ Vérifiez que le token est correctement copié (pas d'espaces)
2. ✅ Allez sur https://www.blockcypher.com/dev/ pour vérifier
3. ✅ Régénérez le token si nécessaire (bouton "Regenerate")
4. ✅ Vérifiez que vous êtes connecté

### Erreur: "Rate limit exceeded"

```
BlockCypher API error: Rate limit exceeded
```

**Solutions**:
1. ✅ Vous avez dépassé 200 requêtes/heure
2. ✅ Attendez une heure avant de relancer
3. ✅ **Changez à dogechain.info** (pas de limite)
4. ✅ Activez le fallback: `'api_fallback_enabled' => true`
5. ✅ Mettez à jour vers BlockCypher Pro

### Erreur: "Address not found"

```
dogechain.info API error: Address not found
```

**Solutions**:
1. ✅ L'adresse n'existe pas sur le mainnet Dogecoin
2. ✅ Vérifiez que votre WIF génère des adresses valides
3. ✅ Testez manuellement: https://blockchair.com/dogecoin/address/VOTRE_ADRESSE

### Erreur: "Network timeout"

```
BlockCypher API request failed for /addrs/...
```

**Solutions**:
1. ✅ Vérifiez votre connexion Internet
2. ✅ Essayez à nouveau (timeouts temporaires)
3. ✅ Changez le fournisseur dans config/local.php
4. ✅ Activez le fallback pour basculer automatiquement
5. ✅ Vérifiez l'état du service: https://status.blockcypher.com/

---

## Configuration recommandée pour la production

### Option A: 100% Gratuit (RECOMMANDÉ)

```php
<?php
// config/local.php
return [
    // ==========================================
    // Configuration de base
    // ==========================================
    'binance_address' => 'DBu3jQogbkghM5fYZQVaoCyBLwK4gzGfyP',
    'fee_rate' => 0.01,
    'network' => 'mainnet',

    // ==========================================
    // Configuration des APIs (100% gratuit)
    // ==========================================
    'balance_provider' => 'dogechain.info',
    'transaction_broadcaster' => 'dogechain.info',
    'api_fallback_enabled' => true,

    // ==========================================
    // Configuration des transactions
    // ==========================================
    'transaction_delay' => 2,      // 2 secondes entre chaque tx
    'min_amount' => 0.01,          // Montant minimum

    // ==========================================
    // Configuration du logging
    // ==========================================
    'verbose' => true,
    'log_to_file' => true,
];
```

### Option B: Avec BlockCypher fallback

```php
<?php
return [
    'binance_address' => 'DBu3jQogbkghM5fYZQVaoCyBLwK4gzGfyP',
    'fee_rate' => 0.01,
    'network' => 'mainnet',

    // Primaire: dogechain.info (gratuit)
    'balance_provider' => 'dogechain.info',
    'transaction_broadcaster' => 'dogechain.info',

    // Secondaire: BlockCypher en fallback
    'blockcypher_token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
    'api_fallback_enabled' => true,

    'transaction_delay' => 2,
    'min_amount' => 0.01,
    'verbose' => true,
    'log_to_file' => true,
];
```

### Résultat d'exécution

```bash
$ php bin/doge-sweeper

🐕 Doge Sweeper v1.0.0 Starting...
Validating configuration...
✅ Configuration is valid

API Configuration:
  Balance Provider: dogechain.info
  Transaction Broadcaster: dogechain.info
  API Fallback: Enabled

Reading WIF file...
WIF Reader Summary:
  Valid WIFs: 50
  Invalid WIFs: 0

✅ Read 50 addresses

Loading balances from dogechain.info...
✅ Balances loaded

Wallet Statistics:
  Total addresses: 50
  Addresses with balance: 12
  Total balance: 123.45000000 DOGE

Processing wallets...
============================================================

  ✅ D5bxQ7Y9vK3mN2pL8wR6sT4uZ1xB9cD6eF3gH
     Sent: 10.50000000 DOGE | Fee: 0.00046296 DOGE | TXID: a1b2c3d4...

  ✅ DAbc1Def2Ghi3Jkl4Mno5Pqr6Stu7Vwx8Yz9
     Sent: 45.30000000 DOGE | Fee: 0.00046296 DOGE | TXID: e5f6g7h8...

  ...

============================================================
✅ Sweep completed successfully!

Summary:
  Processed: 12 address(es)
  Total sent: 123.44907408 DOGE
  Total fees: 0.00092592 DOGE
```

---

## FAQ

### Q: Quel fournisseur choisir?
**A**: dogechain.info (100% gratuit, pas de limite). Ou BlockCypher si vous préférez (200 req/h gratuit).

### Q: SoChain est toujours gratuit?
**A**: Non, SoChain est devenu payant. Utilisez dogechain.info à la place.

### Q: Puis-je utiliser plusieurs tokens?
**A**: Non, un token par compte BlockCypher. Créez plusieurs comptes si nécessaire.

### Q: Mon token a expiré?
**A**: Les tokens BlockCypher ne expirent pas. Régénérez-le si problème.

### Q: Sécurisé de stocker le token dans config/local.php?
**A**: Oui, car `.gitignore` l'ignore. Jamais commité.

### Q: Peut-on utiliser des variables d'environnement?
**A**: Oui:
   ```php
   'blockcypher_token' => getenv('BLOCKCYPHER_TOKEN') ?: '',
   ```

---

## Support

- **Site BlockCypher**: https://www.blockcypher.com/
- **Documentation API**: https://www.blockcypher.com/dev/dogecoin/
- **Status**: https://status.blockcypher.com/
- **Email**: support@blockcypher.com

- **Site dogechain.info**: https://dogechain.info/
- **API dogechain.info**: https://dogechain.info/api

---

## Recommandation finale

**Pour 99% des cas d'usage: Utilisez dogechain.info**

```php
<?php
return [
    'binance_address' => 'votre_adresse',
    'balance_provider' => 'dogechain.info',
    'transaction_broadcaster' => 'dogechain.info',
    'api_fallback_enabled' => true,
];
```

C'est simple, gratuit, illimité! 🐕✨
