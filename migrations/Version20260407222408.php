<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407222408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipement CHANGE etat etat enum(\'Fonctionnel\',\'En panne\',\'Maintenance\') DEFAULT \'Fonctionnel\'');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E9806F0F5C');
        $this->addSql('ALTER TABLE maintenance CHANGE type_maintenance type_maintenance enum(\'Préventive\',\'Corrective\') NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE statut statut enum(\'Planifiée\',\'Réalisée\') DEFAULT \'Planifiée\', CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_equipement ON maintenance');
        $this->addSql('CREATE INDEX IDX_2F84F8E9806F0F5C ON maintenance (equipement_id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id)');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY fk_marketplace_stock');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY fk_marketplace_stock');
        $this->addSql('ALTER TABLE marketplace ADD created_at DATETIME NOT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT FK_6634DA66A5B31750 FOREIGN KEY (id_stock) REFERENCES stock (id)');
        $this->addSql('DROP INDEX fk_marketplace_stock ON marketplace');
        $this->addSql('CREATE INDEX IDX_6634DA66A5B31750 ON marketplace (id_stock)');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT fk_marketplace_stock FOREIGN KEY (id_stock) REFERENCES stock (id_stock) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parcelle CHANGE surface surface DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE revenu ADD id INT AUTO_INCREMENT NOT NULL, CHANGE idRevenu idRevenu INT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE stock ADD created_at DATETIME NOT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_type_role ON utilisateur');
        $this->addSql('DROP INDEX idx_activated ON utilisateur');
        $this->addSql('DROP INDEX idx_email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE type_role type_role VARCHAR(255) NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT NULL, CHANGE activated activated INT DEFAULT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipement CHANGE etat etat ENUM(\'Fonctionnel\', \'En panne\', \'Maintenance\') DEFAULT \'Fonctionnel\'');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E9806F0F5C');
        $this->addSql('ALTER TABLE maintenance CHANGE type_maintenance type_maintenance ENUM(\'Préventive\', \'Corrective\') NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE statut statut ENUM(\'Planifiée\', \'Réalisée\') DEFAULT \'Planifiée\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_2f84f8e9806f0f5c ON maintenance');
        $this->addSql('CREATE INDEX idx_equipement ON maintenance (equipement_id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id)');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY FK_6634DA66A5B31750');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY FK_6634DA66A5B31750');
        $this->addSql('ALTER TABLE marketplace DROP created_at, CHANGE statut statut VARCHAR(30) DEFAULT \'En vente\', CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT fk_marketplace_stock FOREIGN KEY (id_stock) REFERENCES stock (id_stock) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_6634da66a5b31750 ON marketplace');
        $this->addSql('CREATE INDEX fk_marketplace_stock ON marketplace (id_stock)');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT FK_6634DA66A5B31750 FOREIGN KEY (id_stock) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE parcelle CHANGE surface surface FLOAT NOT NULL');
        $this->addSql('ALTER TABLE revenu DROP id, CHANGE idRevenu idRevenu INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idRevenu)');
        $this->addSql('ALTER TABLE stock DROP created_at, CHANGE statut statut VARCHAR(30) DEFAULT \'Disponible\'');
        $this->addSql('ALTER TABLE utilisateur CHANGE type_role type_role ENUM(\'ADMINISTRATEUR\', \'AGRICULTEUR\', \'RESPONSABLE_EXPLOITATION\') NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE activated activated TINYINT(1) DEFAULT 0');
        $this->addSql('CREATE INDEX idx_type_role ON utilisateur (type_role)');
        $this->addSql('CREATE INDEX idx_activated ON utilisateur (activated)');
        $this->addSql('CREATE INDEX idx_email ON utilisateur (email)');
        $this->addSql('DROP INDEX uniq_1d1c63b3e7927c74 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
    }
}
