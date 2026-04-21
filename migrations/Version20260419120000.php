<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix utilisateur table:
 *  - Adds `id` INT AUTO_INCREMENT as primary key (replaces original PK)
 *  - Adds ban columns if not already present
 *  - Adds date_naissance and genre columns if not already present
 */
final class Version20260419120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add id AUTO_INCREMENT PK to utilisateur + ban fields + stats fields';
    }

    public function up(Schema $schema): void
    {
        // ── 1. Vérifie si la colonne `id` existe déjà ──
        // Si elle existe, on ne fait rien sur la PK.
        $columns = $this->connection->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'utilisateur'
               AND COLUMN_NAME = 'id'"
        );

        if (empty($columns)) {
            // La colonne id n'existe pas : on l'ajoute comme nouvelle PK AUTO_INCREMENT
            // D'abord supprimer l'ancienne clé primaire si elle existe
            $pks = $this->connection->fetchAllAssociative(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'utilisateur'
                   AND CONSTRAINT_NAME = 'PRIMARY'"
            );

            if (!empty($pks)) {
                $this->addSql('ALTER TABLE utilisateur DROP PRIMARY KEY');
            }

            $this->addSql(
                'ALTER TABLE utilisateur
                 ADD id INT NOT NULL AUTO_INCREMENT FIRST,
                 ADD PRIMARY KEY (id)'
            );
        } else {
            // La colonne id existe — vérifie qu'elle est bien AUTO_INCREMENT
            $info = $this->connection->fetchAllAssociative(
                "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'utilisateur'
                   AND COLUMN_NAME = 'id'"
            );
            if (!empty($info) && stripos($info[0]['EXTRA'] ?? '', 'auto_increment') === false) {
                $this->addSql(
                    'ALTER TABLE utilisateur MODIFY id INT NOT NULL AUTO_INCREMENT'
                );
            }
        }

        // ── 2. Colonnes ban (ajout si absentes) ──
        $this->addColumnIfMissing('ban_status',    "VARCHAR(20)  DEFAULT NULL");
        $this->addColumnIfMissing('ban_reason',    "VARCHAR(500) DEFAULT NULL");
        $this->addColumnIfMissing('ban_expires_at',"DATETIME     DEFAULT NULL");
        $this->addColumnIfMissing('banned_at',     "DATETIME     DEFAULT NULL");

        // ── 3. Colonnes stats IA (ajout si absentes) ──
        $this->addColumnIfMissing('date_naissance', "DATE         DEFAULT NULL");
        $this->addColumnIfMissing('genre',          "VARCHAR(1)   DEFAULT NULL");

        // ── 4. Colonnes profil / auth (ajout si absentes) ──
        $this->addColumnIfMissing('matricule',                  "VARCHAR(50)  DEFAULT NULL");
        $this->addColumnIfMissing('telephone',                  "VARCHAR(20)  DEFAULT NULL");
        $this->addColumnIfMissing('adresse',                    "VARCHAR(255) DEFAULT NULL");
        $this->addColumnIfMissing('remarques',                  "VARCHAR(1000) DEFAULT NULL");
        $this->addColumnIfMissing('photo_profil',               "VARCHAR(255) DEFAULT NULL");
        $this->addColumnIfMissing('google_id',                  "VARCHAR(255) DEFAULT NULL");
        $this->addColumnIfMissing('reset_password_token',       "VARCHAR(255) DEFAULT NULL");
        $this->addColumnIfMissing('reset_password_expires_at',  "DATETIME     DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        // Ne pas supprimer la colonne id en rollback — trop destructeur
        $this->addSql('SELECT 1 -- rollback intentionnellement vide pour la colonne id');
    }

    // ── Helper : ajoute une colonne seulement si elle n'existe pas encore ──
    private function addColumnIfMissing(string $column, string $definition): void
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'utilisateur'
               AND COLUMN_NAME = :col",
            ['col' => $column]
        );

        if (empty($rows)) {
            $this->addSql("ALTER TABLE utilisateur ADD {$column} {$definition}");
        }
    }
}