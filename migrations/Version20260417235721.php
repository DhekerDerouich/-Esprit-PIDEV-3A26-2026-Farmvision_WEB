<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250418000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes image_filename, barcode, updated_at pour stock';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes à la table stock
        $this->addSql('ALTER TABLE stock ADD image_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE stock ADD barcode VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE stock ADD updated_at DATETIME DEFAULT NULL');
        
        // Ajouter les colonnes pour les tables panier
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_BA388B7613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, marketplace_id INT NOT NULL, cart_id INT NOT NULL, quantity DOUBLE PRECISION NOT NULL, price DOUBLE PRECISION NOT NULL, added_at DATETIME NOT NULL, INDEX IDX_F0FE2527B729A95F (marketplace_id), INDEX IDX_F0FE25271AD5CDBF (cart_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(50) NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(50) NOT NULL, customer_email VARCHAR(100) NOT NULL, customer_name VARCHAR(100) DEFAULT NULL, items LONGTEXT DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, stripe_session_id VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_90651744C6939CCF (invoice_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Ajouter les clés étrangères
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527B729A95F FOREIGN KEY (marketplace_id) REFERENCES marketplace (id_marketplace)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les tables
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527B729A95F');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE invoice');
        
        // Supprimer les colonnes
        $this->addSql('ALTER TABLE stock DROP image_filename');
        $this->addSql('ALTER TABLE stock DROP barcode');
        $this->addSql('ALTER TABLE stock DROP updated_at');
    }
}