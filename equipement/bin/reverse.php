<?php
// bin/reverse_all.php

require __DIR__ . '/../vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=farmvision_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Reverse Engineering complet de FarmVision DB ===\n\n";
    
    // Récupérer toutes les tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Tables trouvées : " . implode(', ', $tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "📦 Traitement de : $table\n";
        
        // Récupérer la structure de la table
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les clés étrangères
        $fkStmt = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'farmvision_db'
            AND TABLE_NAME = '$table'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $className = ucfirst(preg_replace('/^id_/', '', $table));
        $className = str_replace('_', '', ucwords($className, '_'));
        
        // Générer l'entité
        $entity = generateEntity($table, $className, $columns, $foreignKeys);
        file_put_contents(__DIR__ . "/../src/Entity/$className.php", $entity);
        echo "   ✅ Entité : src/Entity/$className.php\n";
        
        // Générer le repository
        $repo = generateRepository($className);
        file_put_contents(__DIR__ . "/../src/Repository/{$className}Repository.php", $repo);
        echo "   ✅ Repository : src/Repository/{$className}Repository.php\n\n";
    }
    
    echo "=== Reverse engineering terminé ! ===\n";
    echo "\nTotal : " . count($tables) . " entités générées.\n";
    echo "\nExécutez : php bin/console cache:clear\n";
    echo "Puis : php bin/console doctrine:schema:validate\n";
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "   Vérifiez que MySQL est démarré\n";
}

function generateEntity($table, $className, $columns, $foreignKeys)
{
    $entity = "<?php\n\nnamespace App\Entity;\n\nuse Doctrine\ORM\Mapping as ORM;\n";
    
    // Ajouter les use pour les collections si nécessaire
    $hasCollection = false;
    foreach ($foreignKeys as $fk) {
        if ($fk['COLUMN_NAME'] !== 'equipement_id') {
            $hasCollection = true;
        }
    }
    if ($hasCollection) {
        $entity .= "use Doctrine\Common\Collections\Collection;\n";
        $entity .= "use Doctrine\Common\Collections\ArrayCollection;\n";
    }
    
    $entity .= "\n#[ORM\Entity(repositoryClass: {$className}Repository::class)]\n";
    $entity .= "#[ORM\Table(name: '$table')]\n";
    $entity .= "class $className\n{\n";
    
    // Ajouter l'ID
    $entity .= "    #[ORM\Id]\n";
    $entity .= "    #[ORM\GeneratedValue]\n";
    $entity .= "    #[ORM\Column(type: 'integer')]\n";
    $entity .= "    private ?int \$id = null;\n\n";
    
    // Ajouter les colonnes
    foreach ($columns as $col) {
        $name = $col['Field'];
        if ($name === 'id') continue;
        
        $type = mapSqlTypeToDoctrine($col['Type']);
        $nullable = $col['Null'] === 'YES';
        $length = preg_match('/\((\d+)\)/', $col['Type'], $m) ? $m[1] : null;
        
        $phpType = mapDoctrineTypeToPhp($type);
        
        $entity .= "    #[ORM\Column(name: '$name', type: '$type'";
        if ($nullable) $entity .= ", nullable: true";
        if ($length && in_array($type, ['string', 'integer'])) $entity .= ", length: $length";
        $entity .= ")]\n";
        $entity .= "    private $phpType \$$name;\n\n";
    }
    
    // Ajouter les relations ManyToOne (clés étrangères)
    foreach ($foreignKeys as $fk) {
        $fkColumn = $fk['COLUMN_NAME'];
        $refTable = ucfirst(preg_replace('/^id_/', '', $fk['REFERENCED_TABLE_NAME']));
        $refTable = str_replace('_', '', ucwords($refTable, '_'));
        
        if ($fkColumn === 'equipement_id') {
            $entity .= "    #[ORM\ManyToOne(inversedBy: 'maintenances')]\n";
            $entity .= "    #[ORM\JoinColumn(name: '$fkColumn', nullable: false)]\n";
            $entity .= "    private ?$refTable \$$refTable = null;\n\n";
        } elseif ($fkColumn === 'user_id') {
            $entity .= "    #[ORM\ManyToOne]\n";
            $entity .= "    #[ORM\JoinColumn(name: '$fkColumn', nullable: true)]\n";
            $entity .= "    private ?$refTable \$$refTable = null;\n\n";
        } elseif ($fkColumn === 'id_stock') {
            $entity .= "    #[ORM\ManyToOne]\n";
            $entity .= "    #[ORM\JoinColumn(name: '$fkColumn', nullable: false)]\n";
            $entity .= "    private ?$refTable \$$refTable = null;\n\n";
        } elseif ($fkColumn === 'id_utilisateur') {
            $entity .= "    #[ORM\ManyToOne]\n";
            $entity .= "    #[ORM\JoinColumn(name: '$fkColumn', nullable: false)]\n";
            $entity .= "    private ?$refTable \$$refTable = null;\n\n";
        } else {
            $entity .= "    #[ORM\ManyToOne]\n";
            $entity .= "    #[ORM\JoinColumn(name: '$fkColumn', nullable: true)]\n";
            $entity .= "    private ?$refTable \$$refTable = null;\n\n";
        }
    }
    
    // Ajouter le constructeur pour les collections
    if ($table === 'equipement') {
        $entity .= "    public function __construct()\n    {\n";
        $entity .= "        \$this->maintenances = new ArrayCollection();\n";
        $entity .= "    }\n\n";
    } elseif ($table === 'utilisateur') {
        $entity .= "    public function __construct()\n    {\n";
        $entity .= "        \$this->parcelles = new ArrayCollection();\n";
        $entity .= "        \$this->cultures = new ArrayCollection();\n";
        $entity .= "    }\n\n";
    }
    
    // Ajouter les getters et setters pour l'ID
    $entity .= "    public function getId(): ?int\n    {\n        return \$this->id;\n    }\n\n";
    
    // Ajouter les getters et setters pour les colonnes
    foreach ($columns as $col) {
        $name = $col['Field'];
        if ($name === 'id') continue;
        
        $camelName = str_replace('_', '', ucwords($name, '_'));
        $type = mapSqlTypeToPhpType($col['Type']);
        
        $entity .= "    public function get$camelName(): $type\n    {\n        return \$this->$name;\n    }\n\n";
        $entity .= "    public function set$camelName($type \$$name): self\n    {\n        \$this->$name = \$$name;\n        return \$this;\n    }\n\n";
    }
    
    // Ajouter les getters et setters pour les relations
    foreach ($foreignKeys as $fk) {
        $fkColumn = $fk['COLUMN_NAME'];
        $refTable = ucfirst(preg_replace('/^id_/', '', $fk['REFERENCED_TABLE_NAME']));
        $refTable = str_replace('_', '', ucwords($refTable, '_'));
        $propName = lcfirst($refTable);
        
        $entity .= "    public function get$refTable(): ?$refTable\n    {\n        return \$this->$propName;\n    }\n\n";
        $entity .= "    public function set$refTable(?$refTable \$$propName): self\n    {\n        \$this->$propName = \$$propName;\n        return \$this;\n    }\n\n";
    }
    
    $entity .= "}\n";
    
    return $entity;
}

function generateRepository($className)
{
    return "<?php\n\nnamespace App\Repository;\n\nuse App\Entity\\$className;\nuse Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;\nuse Doctrine\Persistence\ManagerRegistry;\n\n/**\n * @extends ServiceEntityRepository<$className>\n */\nclass {$className}Repository extends ServiceEntityRepository\n{\n    public function __construct(ManagerRegistry \$registry)\n    {\n        parent::__construct(\$registry, $className::class);\n    }\n}\n";
}

function mapSqlTypeToDoctrine($sqlType)
{
    $sqlType = strtolower($sqlType);
    if (str_contains($sqlType, 'int')) return 'integer';
    if (str_contains($sqlType, 'varchar')) return 'string';
    if (str_contains($sqlType, 'text')) return 'text';
    if (str_contains($sqlType, 'date')) return 'date';
    if (str_contains($sqlType, 'datetime')) return 'datetime';
    if (str_contains($sqlType, 'decimal')) return 'decimal';
    if (str_contains($sqlType, 'float')) return 'float';
    if (str_contains($sqlType, 'boolean') || str_contains($sqlType, 'tinyint(1)')) return 'boolean';
    if (str_contains($sqlType, 'enum')) return 'string';
    return 'string';
}

function mapSqlTypeToPhpType($sqlType)
{
    $sqlType = strtolower($sqlType);
    if (str_contains($sqlType, 'int')) return '?int';
    if (str_contains($sqlType, 'varchar')) return '?string';
    if (str_contains($sqlType, 'text')) return '?string';
    if (str_contains($sqlType, 'date')) return '?\DateTimeInterface';
    if (str_contains($sqlType, 'datetime')) return '?\DateTimeInterface';
    if (str_contains($sqlType, 'decimal')) return '?float';
    if (str_contains($sqlType, 'float')) return '?float';
    if (str_contains($sqlType, 'boolean') || str_contains($sqlType, 'tinyint(1)')) return '?bool';
    if (str_contains($sqlType, 'enum')) return '?string';
    return '?string';
}

function mapDoctrineTypeToPhp($doctrineType)
{
    $map = [
        'integer' => '?int',
        'bigint' => '?int',
        'smallint' => '?int',
        'string' => '?string',
        'text' => '?string',
        'datetime' => '?\DateTimeInterface',
        'date' => '?\DateTimeInterface',
        'decimal' => '?float',
        'float' => '?float',
        'boolean' => '?bool',
    ];
    return $map[$doctrineType] ?? '?string';
}