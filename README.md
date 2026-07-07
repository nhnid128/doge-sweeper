# 🐕 Doge Sweeper

Un outil PHP 8.3 pour balayer les portefeuilles Dogecoin à partir de clés WIF et envoyer tous les fonds vers une adresse unique.

## 📋 Caractéristiques

- ✅ Lecture de clés WIF depuis un fichier texte
- ✅ Calcul automatique des adresses Dogecoin
- ✅ Filtrage des adresses avec solde zéro
- ✅ Envoi des fonds vers une adresse Binance unique
- ✅ Envoi du maximum disponible (solde - frais)
- ✅ Taux de frais configurable (défaut: 0,01 DOGE/KB)
- ✅ Architecture PSR-4 orientée objet
- ✅ Bien documenté et maintenable

## 🔧 Prérequis

- **Ubuntu 24.04**
- **PHP 8.3** avec support FFI
- **Composer**
- **Git**
- **libdogecoin 0.1.5-pre** installé

### Installation de libdogecoin

```bash
# Cloner et compiler libdogecoin
git clone https://github.com/dogecoinfoundation/libdogecoin.git
cd libdogecoin
./autogen.sh
./configure
make
sudo make install
sudo ldconfig
```

## 📦 Installation

```bash
git clone https://github.com/nhnid128/doge-sweeper.git
cd doge-sweeper
composer install
```

## 🚀 Utilisation

### 1. Configuration

Créer `config/local.php` :

```php
<?php
return [
    'binance_address' => 'your_binance_dogecoin_address_here',
    'fee_rate' => 0.01, // DOGE/KB
    'network' => 'mainnet', // ou 'testnet',
];
```

### 2. Préparation des clés WIF

Créer `data/wif.txt` avec une clé WIF par ligne :

```
QVDj2wfyULZzBX4JczAYzYXchVnQfnVqvGYWh5bV8JKBypfG5DAH
QUvQ9LZGYYVDj2wfyULZzBX4JczAYzYXchVnQfnVqvGYWh5bV8JK
...
```

### 3. Exécution

```bash
php bin/doge-sweeper
```

Ou avec composer :

```bash
composer start
```

## 📁 Structure du projet

```
doge-sweeper/
├── composer.json              # Configuration Composer
├── README.md                  # Documentation
├── ARCHITECTURE.md            # Architecture détaillée
├── .gitignore                # Fichiers à ignorer
├── src/
│   ├── Application/          # Application principale
│   │   └── DogeSweeper.php
│   ├── Wallet/               # Gestion des portefeuilles
│   │   ├── WifReader.php
│   │   ├── WalletManager.php
│   │   └── WalletAddress.php
│   ├── Transaction/          # Construction et envoi de transactions
│   │   ├── TransactionBuilder.php
│   │   ├── FeeCalculator.php
│   │   └── TransactionSender.php
│   ├── Integration/          # Intégration libdogecoin
│   │   └── LibDogecoinFFI.php
│   ├── Exception/            # Exceptions personnalisées
│   │   ├── InvalidWifException.php
│   │   ├── InsufficientFundsException.php
│   │   └── TransactionException.php
│   └── Config/               # Configuration
│       └── Config.php
├── config/
│   └── default.php           # Configuration par défaut
├── data/
│   └── wif.txt              # Clés WIF (exemple)
└── bin/
    └── doge-sweeper         # Point d'entrée CLI
```

## 🔐 Sécurité

- Les clés privées sont traitées exclusivement en mémoire
- Pas de stockage persistant des clés
- Validation rigoureuse des adresses et WIF
- Gestion d'erreurs complète

## 📄 Licence

MIT License

## 👨‍💻 Auteur

nhnid128

## 🤝 Support

Pour les problèmes ou suggestions, ouvrez une issue sur GitHub.
