# API_CONFIGURATION.md

## Fournisseurs d'API disponibles

### ✅ dogechain.info (RECOMMANDÉ)
- **Coût**: 100% Gratuit
- **Limites**: Aucune
- **Configuration**: Aucune (pas d'API key)
- **Documentation**: https://dogechain.info/api

### ✅ BlockCypher (Optionnel)
- **Coût**: Gratuit (200 requêtes/heure)
- **Limites**: 200 req/heure (gratuit)
- **Configuration**: Token API (gratuit)
- **Documentation**: https://www.blockcypher.com/dev/dogecoin/

---

## Configuration rapide

### Option 1: 100% Gratuit (Recommandé)

```php
<?php
// config/local.php
return [
    'binance_address' => 'votre_adresse',
    'fee_rate' => 0.01,
    // Utilise dogechain.info par défaut
];
```

### Option 2: Avec BlockCypher

```php
<?php
return [
    'binance_address' => 'votre_adresse',
    'balance_provider' => 'BlockCypher',
    'transaction_broadcaster' => 'BlockCypher',
    'blockcypher_token' => 'votre_token_ici',
];
```

### Option 3: Fallback intelligent

```php
<?php
return [
    'binance_address' => 'votre_adresse',
    'balance_provider' => 'dogechain.info',
    'transaction_broadcaster' => 'dogechain.info',
    'blockcypher_token' => 'votre_token_ici', // optionnel, en fallback
    'api_fallback_enabled' => true,
];
```

---

## Obtenir un token BlockCypher

Voir le guide complet: [BLOCKCYPHER_SETUP.md](BLOCKCYPHER_SETUP.md)

---

## Comparaison

| Critère | dogechain.info | BlockCypher |
|---------|---|---|
| **Prix** | Gratuit ✅ | Gratuit (200/h) ✅ |
| **Limite** | Aucune ✅ | 200 req/h |
| **Config** | Aucune ✅ | Token requis |
| **Stabilité** | Très bonne ✅ | Excellente ✅ |
| **Recommandé** | OUI ✅ | Optionnel |

---

## Note importante

⚠️ **SoChain est maintenant payant - N'utilisez plus SoChain**

✅ **Utilisez dogechain.info à la place**
