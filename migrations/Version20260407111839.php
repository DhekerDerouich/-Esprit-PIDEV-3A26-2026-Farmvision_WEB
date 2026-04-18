<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407111839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE depense ADD id INT AUTO_INCREMENT NOT NULL, CHANGE idDepense idDepense INT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX idx_parcelle ON equipement');
        $this->addSql('DROP INDEX idx_etat ON equipement');
        $this->addSql('ALTER TABLE equipement CHANGE etat etat enum(\'Fonctionnel\',\'En panne\',\'Maintenance\') DEFAULT \'Fonctionnel\', CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_date ON maintenance');
        $this->addSql('DROP INDEX idx_statut ON maintenance');
        $this->addSql('ALTER TABLE maintenance CHANGE type_maintenance type_maintenance enum(\'Préventive\',\'Corrective\') NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE statut statut enum(\'Planifiée\',\'Réalisée\') DEFAULT \'Planifiée\', CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id)');
        $this->addSql('DROP INDEX idx_equipement ON maintenance');
        $this->addSql('CREATE INDEX IDX_2F84F8E9806F0F5C ON maintenance (equipement_id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT maintenance_ibfk_1 FOREIGN KEY (equipement_id) REFERENCES equipement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY fk_marketplace_stock');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY fk_marketplace_stock');
        $this->addSql('ALTER TABLE marketplace ADD id INT AUTO_INCREMENT NOT NULL, CHANGE id_marketplace id_marketplace INT NOT NULL, CHANGE prix_unitaire prix_unitaire VARCHAR(255) NOT NULL, CHANGE quantite_en_vente quantite_en_vente VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT FK_6634DA66A5B31750 FOREIGN KEY (id_stock) REFERENCES stock (id)');
        $this->addSql('DROP INDEX fk_marketplace_stock ON marketplace');
        $this->addSql('CREATE INDEX IDX_6634DA66A5B31750 ON marketplace (id_stock)');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT fk_marketplace_stock FOREIGN KEY (id_stock) REFERENCES stock (id_stock) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parcelle ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, CHANGE surface surface DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE revenu ADD id INT AUTO_INCREMENT NOT NULL, CHANGE idRevenu idRevenu INT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE stock ADD id INT AUTO_INCREMENT NOT NULL, CHANGE id_stock id_stock INT NOT NULL, CHANGE quantite quantite VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX idx_activated ON utilisateur');
        $this->addSql('DROP INDEX idx_email ON utilisateur');
        $this->addSql('DROP INDEX idx_type_role ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE type_role type_role VARCHAR(255) NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT NULL, CHANGE activated activated INT DEFAULT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE depense DROP id, CHANGE idDepense idDepense INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idDepense)');
        $this->addSql('ALTER TABLE equipement CHANGE etat etat ENUM(\'Fonctionnel\', \'En panne\', \'Maintenance\') DEFAULT \'Fonctionnel\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_parcelle ON equipement (parcelle_id)');
        $this->addSql('CREATE INDEX idx_etat ON equipement (etat)');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E9806F0F5C');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E9806F0F5C');
        $this->addSql('ALTER TABLE maintenance CHANGE type_maintenance type_maintenance ENUM(\'Préventive\', \'Corrective\') NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE statut statut ENUM(\'Planifiée\', \'Réalisée\') DEFAULT \'Planifiée\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT maintenance_ibfk_1 FOREIGN KEY (equipement_id) REFERENCES equipement (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_date ON maintenance (date_maintenance)');
        $this->addSql('CREATE INDEX idx_statut ON maintenance (statut)');
        $this->addSql('DROP INDEX idx_2f84f8e9806f0f5c ON maintenance');
        $this->addSql('CREATE INDEX idx_equipement ON maintenance (equipement_id)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E9806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id)');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY FK_6634DA66A5B31750');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY FK_6634DA66A5B31750');
        $this->addSql('ALTER TABLE marketplace DROP id, CHANGE id_marketplace id_marketplace INT AUTO_INCREMENT NOT NULL, CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION NOT NULL, CHANGE quantite_en_vente quantite_en_vente DOUBLE PRECISION NOT NULL, CHANGE statut statut VARCHAR(30) DEFAULT \'En vente\', CHANGE description description TEXT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_marketplace)');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT fk_marketplace_stock FOREIGN KEY (id_stock) REFERENCES stock (id_stock) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_6634da66a5b31750 ON marketplace');
        $this->addSql('CREATE INDEX fk_marketplace_stock ON marketplace (id_stock)');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT FK_6634DA66A5B31750 FOREIGN KEY (id_stock) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE parcelle DROP latitude, DROP longitude, CHANGE surface surface FLOAT NOT NULL');
        $this->addSql('ALTER TABLE revenu DROP id, CHANGE idRevenu idRevenu INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idRevenu)');
        $this->addSql('ALTER TABLE stock DROP id, CHANGE id_stock id_stock INT AUTO_INCREMENT NOT NULL, CHANGE quantite quantite DOUBLE PRECISION NOT NULL, CHANGE statut statut VARCHAR(30) DEFAULT \'Disponible\', DROP PRIMARY KEY, ADD PRIMARY KEY (id_stock)');
        $this->addSql('ALTER TABLE utilisateur CHANGE type_role type_role ENUM(\'ADMINISTRATEUR\', \'AGRICULTEUR\', \'RESPONSABLE_EXPLOITATION\') NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE activated activated TINYINT(1) DEFAULT 0');
        $this->addSql('CREATE INDEX idx_activated ON utilisateur (activated)');
        $this->addSql('CREATE INDEX idx_email ON utilisateur (email)');
        $this->addSql('CREATE INDEX idx_type_role ON utilisateur (type_role)');
        $this->addSql('DROP INDEX uniq_1d1c63b3e7927c74 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
    }
}
