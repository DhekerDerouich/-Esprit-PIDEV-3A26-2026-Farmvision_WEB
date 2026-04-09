#!/bin/bash

echo "=== Installation de FarmVision (Équipements & Maintenances) ==="
echo ""

# 1. Installation des dépendances
echo "1. Installation des dépendances Composer..."
composer install
echo ""

# 2. Configuration de la base de données
echo "2. Configuration de la base de données..."
echo "   Vérifiez que votre .env contient la bonne DATABASE_URL"
echo "   Exemple: DATABASE_URL=\"mysql://root:@127.0.0.1:3306/farmvision_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4\""
echo ""

# 3. Reverse engineering
echo "3. Reverse engineering de la base existante..."
php bin/console doctrine:mapping:import "App\Entity" annotation --path=src/Entity
php bin/console doctrine:annotations:convert --namespace="App\Entity" src/Entity/
php bin/console make:entity --regenerate App\Entity\Equipement
php bin/console make:entity --regenerate App\Entity\Maintenance
echo ""

# 4. Vérification
echo "4. Validation du mapping Doctrine..."
php bin/console doctrine:schema:validate
echo ""

# 5. Nettoyage du cache
echo "5. Nettoyage du cache..."
php bin/console cache:clear
echo ""

echo "=== Installation terminée ! ==="
echo ""
echo "Pour lancer l'application :"
echo "  symfony server:start"
echo ""
echo "Accès :"
echo "  - Front-office : http://localhost:8000"
echo "  - Back-office  : http://localhost:8000/admin"