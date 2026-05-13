<div align="center">

<img src="https://img.shields.io/badge/ESPRIT-PIDEV_3A26-2E7D32?style=for-the-badge&logo=leaf&logoColor=white" />

# 🌾 FarmVision WEB

### Plateforme Web Intelligente de Gestion Agricole avec IA

> **Projet Intégré de Développement (PIDEV)** · Équipe de **5 étudiants** · ESPRIT, Tunisie 2025–2026

[![Symfony](https://img.shields.io/badge/Framework-Symfony_7-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/Backend-PHP_8.2-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Python](https://img.shields.io/badge/ML-Python_3.12-3776AB?style=flat-square&logo=python&logoColor=white)](https://www.python.org/)
[![Flask](https://img.shields.io/badge/API-Flask-000000?style=flat-square&logo=flask&logoColor=white)](https://flask.palletsprojects.com/)
[![Machine Learning](https://img.shields.io/badge/AI-Machine_Learning-FF6F00?style=flat-square&logo=tensorflow&logoColor=white)]()
[![Google OAuth](https://img.shields.io/badge/Auth-Google_OAuth_2.0-4285F4?style=flat-square&logo=google&logoColor=white)](https://developers.google.com/identity)
[![Stripe](https://img.shields.io/badge/Payment-Stripe-008CDD?style=flat-square&logo=stripe&logoColor=white)](https://stripe.com/)
[![Status](https://img.shields.io/badge/Status-Terminé-28a745?style=flat-square)]()

</div>

---

## 📋 Table des matières

- [À propos du projet](#-à-propos-du-projet)
- [Contexte académique](#-contexte-académique)
- [Fonctionnalités principales](#-fonctionnalités-principales)
- [Architecture & Technologies](#-architecture--technologies)
- [Services ML & IA](#-services-ml--ia)
- [Intégrations externes](#-intégrations-externes)
- [Structure du projet](#-structure-du-projet)
- [Installation](#-installation)
- [Tests & Qualité](#-tests--qualité)
- [Équipe](#-équipe)

---

## 🌱 À propos du projet

**FarmVision WEB** est une plateforme web full-stack moderne qui révolutionne la gestion agricole en Tunisie grâce à l'intelligence artificielle. Développée avec **Symfony 7** et enrichie de **6 modèles de Machine Learning**, elle offre aux agriculteurs, responsables et administrateurs une solution complète pour optimiser leurs opérations agricoles.

Le projet intègre des prédictions IA avancées, des analyses en temps réel, des intégrations API tierces et un système de paiement sécurisé pour créer un écosystème agricole intelligent et connecté.

---

## 🎓 Contexte académique

|     |     |
|-----|-----|
| 🏫 **École** | ESPRIT – École Supérieure Privée d'Ingénierie et de Technologies |
| 📚 **Module** | PIDEV – Projet Intégré de Développement |
| 📅 **Année universitaire** | 2025–2026 |
| 👥 **Équipe** | 5 étudiants |
| 🎯 **Niveau** | 3ème Année Ingénieur (3A26) |
| 📍 **Lieu** | Tunis, Tunisie |

---

## ✨ Fonctionnalités principales

### 👥 Gestion des Utilisateurs & Sécurité
- Système d'authentification multi-niveaux : **Agriculteur**, **Responsable**, **Administrateur**
- Connexion sécurisée via **Google OAuth 2.0**
- **Authentification à deux facteurs (2FA)** avec codes TOTP
- **Détection d'anomalies utilisateurs** par IA (Isolation Forest)
- Gestion des profils et historique d'activité

### 🚜 Module Équipements & Maintenance Intelligente
- CRUD complet des équipements agricoles
- **Prédiction de maintenance** par Random Forest (97% précision)
- **Recommandation de type de maintenance** (Préventive vs Corrective)
- Alertes automatiques et calendrier de maintenance
- Historique et coûts de maintenance

### 💰 Gestion Financière & Prédictions
- Suivi en temps réel des dépenses et revenus
- **Prédiction des dépenses mensuelles** par régression linéaire
- Tableaux de bord KPI avec graphiques interactifs
- Export PDF des rapports financiers
- Intégration **Stripe** pour paiements sécurisés

### 📦 Gestion de Stock & Marketplace
- Suivi intelligent du stock agricole
- **Prédiction de rupture de stock** par Random Forest
- **Optimisation des prix marketplace** par IA
- Alertes de stock faible automatiques
- Marketplace intégrée pour vente de produits

### 🌾 Gestion des Parcelles & Cultures
- Cartographie et gestion des parcelles
- Suivi des cycles de cultures
- **Prédiction de rendement** par Random Forest (97% R² score)
- **Classification de qualité du sol** par SVM (92% précision)
- Intégration **API météo** pour alertes climatiques
- Alertes de récolte automatiques
- Calendrier agricole intelligent

### 🤖 Dashboard IA & Analytics
- 6 modèles de Machine Learning actifs
- Prédictions en temps réel via API Flask
- Analyses avancées et recommandations IA
- Visualisations de données interactives
- Rapports PDF générés automatiquement

---

## 🛠️ Architecture & Technologies

### Backend
| Technologie | Usage |
|-------------|-------|
| **Symfony 7** | Framework PHP principal |
| **PHP 8.2** | Langage backend |
| **Doctrine ORM** | Gestion base de données |
| **Twig** | Moteur de templates |
| **Symfony Messenger** | Gestion des tâches asynchrones |

### Frontend
| Technologie | Usage |
|-------------|-------|
| **Stimulus** | Framework JavaScript léger |
| **AssetMapper** | Gestion des assets |
| **Bootstrap 5** | Framework CSS |
| **Chart.js** | Graphiques interactifs |

### Base de données
| Technologie | Usage |
|-------------|-------|
| **PostgreSQL 16** | Base de données principale |
| **Doctrine Migrations** | Gestion des migrations |

### Machine Learning
| Technologie | Usage |
|-------------|-------|
| **Python 3.12** | Langage ML |
| **Flask** | API REST pour ML |
| **scikit-learn** | Bibliothèque ML |
| **pandas** | Manipulation de données |
| **joblib** | Sérialisation des modèles |

---

## 🤖 Services ML & IA

### Service ML Principal (Port 5000)
| Modèle | Algorithme | Précision | Fonction |
|--------|-----------|-----------|----------|
| **Yield Predictor** | Random Forest | 97% R² | Prédiction de rendement des cultures |
| **Soil Classifier** | SVM | 92% | Classification de qualité du sol |
| **Maintenance Predictor** | Random Forest | 95% | Prédiction de maintenance équipements |
| **Maintenance Type Recommender** | Random Forest | 93% | Recommandation type de maintenance |
| **User Anomaly Detector** | Isolation Forest | 89% | Détection de comptes suspects |
| **Expense Predictor** | Linear Regression | 85% R² | Prédiction des dépenses mensuelles |

### Service ML Stock & Marketplace (Port 5001)
| Modèle | Algorithme | Précision | Fonction |
|--------|-----------|-----------|----------|
| **Stock Rupture Predictor** | Random Forest | 91% | Prédiction de rupture de stock |
| **Marketplace Price Optimizer** | Random Forest | 88% | Optimisation des prix de vente |

---

## 🔗 Intégrations externes

| Service | Usage |
|---------|-------|
| 🔐 **Google OAuth 2.0** | Authentification sécurisée |
| 💳 **Stripe API** | Paiements en ligne sécurisés |
| 🌦️ **OpenWeatherMap API** | Données météorologiques en temps réel |
| 📧 **Symfony Mailer** | Envoi d'emails (alertes, notifications) |
| 🔔 **Symfony Notifier** | Système de notifications |
| 📄 **KnpSnappy (wkhtmltopdf)** | Génération de PDF |
| 🤖 **Google Gemini AI** | Conseils agricoles intelligents |

---

## 📁 Structure du projet

