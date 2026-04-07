# Module Culture & Parcelle

## Vue d'ensemble
Module autonome pour la gestion des cultures et parcelles agricoles, développé dans une structure de dossiers dédiée `src/CultureParcelle/`.

## Structure du module

```
src/CultureParcelle/
├── Controller/
│   ├── CultureController.php
│   └── ParcelleController.php
├── Repository/
│   ├── CultureRepository.php
│   └── ParcelleRepository.php
└── templates/
    ├── culture/
    │   ├── index.html.twig
    │   ├── new.html.twig
    │   └── edit.html.twig
    └── parcelle/
        ├── index.html.twig
        ├── new.html.twig
        └── edit.html.twig
```

## Fonctionnalités implémentées

### 1. Gestion des Cultures

#### Entité Culture
- `idCulture` : Identifiant unique
- `nomCulture` : Nom de la culture (validation : lettres uniquement, 2-100 caractères)
- `typeCulture` : Type de culture (validation : 2-50 caractères)
- `dateSemis` : Date de semis (validation : ne peut pas être dans le futur)
- `dateRecolte` : Date de récolte (validation : doit être après la date de semis)
- `user_id` : Référence utilisateur

#### Fonctionnalités Culture
- **Liste** : Affichage en grille de cartes modernes avec design gradient
- **Recherche** : Par nom de culture
- **Filtre** : Par type de culture (dropdown dynamique depuis la base de données)
- **Ajout** : Formulaire avec validation côté serveur (Symfony Assert)
- **Modification** : Édition des cultures existantes
- **Suppression** : Avec modal de confirmation stylisé

#### Routes Culture
- `front_culture_index` : `/culture` (GET)
- `front_culture_new` : `/culture/new` (GET/POST)
- `front_culture_edit` : `/culture/{idCulture}/edit` (GET/POST)
- `front_culture_delete` : `/culture/{idCulture}/delete` (POST)

### 2. Gestion des Parcelles

#### Entité Parcelle
- `idParcelle` : Identifiant unique
- `surface` : Surface en hectares (validation : positif, max 10000 ha)
- `localisation` : Localisation (validation : 2-255 caractères)
- `latitude` : Coordonnée GPS (nullable)
- `longitude` : Coordonnée GPS (nullable)
- `user_id` : Référence utilisateur

#### Fonctionnalités Parcelle
- **Liste** : Affichage en grille avec carte individuelle pour chaque parcelle
- **Recherche** : Par localisation
- **Filtre** : Par surface (min/max en hectares)
- **Ajout** : Formulaire avec carte interactive Leaflet.js
- **Modification** : Édition avec mise à jour de position sur carte
- **Suppression** : Avec modal de confirmation stylisé
- **Géolocalisation** : Intégration Leaflet.js avec reverse geocoding (Nominatim API)

#### Routes Parcelle
- `front_parcelle_index` : `/parcelle` (GET)
- `front_parcelle_new` : `/parcelle/new` (GET/POST)
- `front_parcelle_edit` : `/parcelle/{idParcelle}/edit` (GET/POST)
- `front_parcelle_delete` : `/parcelle/{idParcelle}/delete` (POST)

### 3. Intégration Leaflet.js

#### Fonctionnalités carte
- **Ajout/Édition** : 
  - Clic sur la carte pour placer un marqueur
  - Marqueur déplaçable (drag & drop)
  - Reverse geocoding automatique pour remplir la localisation
  - Affichage des coordonnées en temps réel
  - Champs cachés pour latitude/longitude
  
- **Liste des parcelles** :
  - Carte individuelle pour chaque parcelle (180px de hauteur)
  - Marqueur vert personnalisé
  - Zoom niveau 14 centré sur la parcelle
  - Placeholder pour parcelles sans coordonnées

#### Configuration Leaflet
- Tuiles : OpenStreetMap
- API Geocoding : Nominatim (reverse geocoding)
- Icône personnalisée : Marqueur vert circulaire avec ombre

## Validation des données

### Contraintes Culture
```php
#[Assert\NotBlank(message: 'Le nom est obligatoire')]
#[Assert\Length(min: 2, max: 100)]
#[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\s]+$/', message: 'Lettres uniquement')]
#[Assert\LessThanOrEqual('today', message: 'Date de semis ne peut pas être dans le futur')]
#[Assert\GreaterThan(propertyPath: 'dateSemis', message: 'Date de récolte doit être après semis')]
```

### Contraintes Parcelle
```php
#[Assert\NotBlank(message: 'La localisation est obligatoire')]
#[Assert\Length(min: 2, max: 255)]
#[Assert\Positive(message: 'La surface doit être un nombre positif')]
#[Assert\LessThanOrEqual(value: 10000, message: 'Surface max 10 000 ha')]
```

## Interface utilisateur

### Design moderne
- Cartes avec gradient de couleur
- Badges de surface/type avec style pill
- Barre de filtres avec inputs stylisés
- Compteur de résultats
- Grille responsive (auto-fill, min 300px)
- Effets hover avec élévation
- Modal de suppression personnalisé (pas d'alert navigateur)

### Navigation
- **Navbar principale** : Dropdown "🌱 Cultures & Parcelles"
- **Sub-navbar** : Onglets "🌿 Cultures" et "🗺️ Parcelles" (apparaît uniquement sur les pages du module)
- Padding automatique du contenu quand sub-navbar visible

### Palette de couleurs
- Utilise les variables CSS du template de base
- `--green-mid` : Couleur principale
- `--green-deep` : Couleur foncée
- `--green-mist` : Fond clair
- `--green-light` : Accents

## Configuration Symfony

### Twig namespace
```yaml
# config/packages/twig.yaml
twig:
    paths:
        '%kernel.project_dir%/src/CultureParcelle/templates': CultureParcelle
```

### Routes
```yaml
# config/routes.yaml
culture_parcelle_controllers:
    resource: ../src/CultureParcelle/Controller/
    type: attribute
```

## Base de données

### Migration appliquée
- Ajout des colonnes `latitude` et `longitude` à la table `parcelle`
- Type : `DOUBLE PRECISION`
- Nullable : `true`

### Commande SQL exécutée
```sql
ALTER TABLE parcelle 
ADD COLUMN latitude DOUBLE PRECISION DEFAULT NULL, 
ADD COLUMN longitude DOUBLE PRECISION DEFAULT NULL;
```

## Dépendances externes

### Leaflet.js
```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

### API utilisées
- **OpenStreetMap** : Tuiles de carte
- **Nominatim** : Reverse geocoding (https://nominatim.openstreetmap.org/reverse)

## Sécurité

### Protection CSRF
- Tous les formulaires de suppression incluent un token CSRF
- Validation : `csrf_token('delete_culture_' ~ idCulture)`
- Validation : `csrf_token('delete_parcelle_' ~ idParcelle)`

### Validation serveur
- Utilisation de `ValidatorInterface` dans les contrôleurs
- Vérification des contraintes Assert avant persist
- Messages d'erreur flash en cas d'échec

## Points techniques

### Repositories personnalisés
- `CultureRepository::search($nom, $type)` : Recherche et filtre
- `CultureRepository::findAllTypes()` : Liste des types uniques
- `ParcelleRepository::search($localisation, $surfaceMin, $surfaceMax)` : Recherche et filtre

### Gestion des formulaires
- Formulaires Symfony natifs avec `createFormBuilder()`
- Validation automatique via contraintes d'entité
- Gestion des erreurs avec messages flash

### JavaScript
- Pas de framework externe (vanilla JS)
- Gestion des modals avec événements DOM
- Intégration Leaflet avec callbacks
- Reverse geocoding asynchrone (fetch API)

## Améliorations futures possibles

1. Export des parcelles en GeoJSON
2. Calcul automatique de surface depuis polygone
3. Historique des modifications
4. Photos des parcelles
5. Prévisions météo par parcelle
6. Rotation des cultures
7. Gestion des stocks par parcelle
8. Rapports et statistiques
