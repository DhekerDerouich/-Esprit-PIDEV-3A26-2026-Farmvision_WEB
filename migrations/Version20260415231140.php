<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415231140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE culture CHANGE nomCulture nomCulture VARCHAR(100) NOT NULL, CHANGE typeCulture typeCulture VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE depense CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE equipement CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE etat etat enum(\'Fonctionnel\',\'En panne\',\'Maintenance\') DEFAULT \'Fonctionnel\'');
        $this->addSql('ALTER TABLE maintenance CHANGE equipement_id equipement_id INT NOT NULL, CHANGE type_maintenance type_maintenance enum(\'Préventive\',\'Corrective\') NOT NULL, CHANGE statut statut enum(\'Planifiée\',\'Réalisée\') DEFAULT \'Planifiée\'');
        $this->addSql('ALTER TABLE marketplace DROP FOREIGN KEY fk_marketplace_stock');
        $this->addSql('ALTER TABLE marketplace CHANGE id_stock id_stock INT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE rapport_personnalise ADD CONSTRAINT FK_AAAA0A49DE12AB56 FOREIGN KEY (created_by) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE revenu CHANGE source source VARCHAR(100) NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE stock CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE nom_produit nom_produit VARCHAR(100) NOT NULL, CHANGE type_produit type_produit VARCHAR(50) DEFAULT NULL, CHANGE unite unite VARCHAR(20) DEFAULT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(150) NOT NULL, CHANGE activated activated INT DEFAULT NULL, CHANGE matricule matricule VARCHAR(50) DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE remarques remarques VARCHAR(1000) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('DROP INDEX uniq_utilisateur_google_id ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B376F5C865 ON utilisateur (google_id)');
        $this->addSql('DROP INDEX uniq_utilisateur_reset_password_token ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3452C9EC5 ON utilisateur (reset_password_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE culture CHANGE nomCulture nomCulture VARCHAR(255) NOT NULL, CHANGE typeCulture typeCulture VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE depense CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE equipement CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE etat etat INT DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance CHANGE type_maintenance type_maintenance VARCHAR(50) NOT NULL, CHANGE statut statut VARCHAR(20) DEFAULT NULL, CHANGE equipement_id equipement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE marketplace CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE id_stock id_stock INT DEFAULT NULL');
        $this->addSql('ALTER TABLE marketplace ADD CONSTRAINT fk_marketplace_stock FOREIGN KEY (id_stock) REFERENCES stock (id_stock) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rapport_personnalise DROP FOREIGN KEY FK_AAAA0A49DE12AB56');
        $this->addSql('ALTER TABLE revenu CHANGE source source VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE stock CHANGE id_utilisateur id_utilisateur INT NOT NULL, CHANGE nom_produit nom_produit VARCHAR(255) NOT NULL, CHANGE type_produit type_produit VARCHAR(255) DEFAULT NULL, CHANGE unite unite VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE activated activated TINYINT(1) DEFAULT NULL, CHANGE matricule matricule VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(255) DEFAULT NULL, CHANGE remarques remarques VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_1d1c63b3452c9ec5 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_UTILISATEUR_RESET_PASSWORD_TOKEN ON utilisateur (reset_password_token)');
        $this->addSql('DROP INDEX uniq_1d1c63b376f5c865 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_UTILISATEUR_GOOGLE_ID ON utilisateur (google_id)');
    }
}
