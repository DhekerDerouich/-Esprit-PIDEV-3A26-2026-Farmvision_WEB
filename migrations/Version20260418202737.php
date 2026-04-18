<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates cart, cart_item, and invoice tables.
 */
final class Version20260418202737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cart, cart_item and invoice tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            status VARCHAR(50) DEFAULT NULL,
            UNIQUE INDEX UNIQ_BA388B7613FECDF (session_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE IF NOT EXISTS cart_item (
            id INT AUTO_INCREMENT NOT NULL,
            marketplace_id INT NOT NULL,
            cart_id INT NOT NULL,
            quantity DOUBLE PRECISION NOT NULL,
            price DOUBLE PRECISION NOT NULL,
            added_at DATETIME NOT NULL,
            INDEX IDX_F0FE25277078ABE4 (marketplace_id),
            INDEX IDX_F0FE25271AD5CDBF (cart_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE IF NOT EXISTS invoice (
            id INT AUTO_INCREMENT NOT NULL,
            invoice_number VARCHAR(50) NOT NULL,
            amount DOUBLE PRECISION NOT NULL,
            currency VARCHAR(50) NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_name VARCHAR(100) DEFAULT NULL,
            items LONGTEXT DEFAULT NULL,
            pdf_path VARCHAR(255) DEFAULT NULL,
            stripe_session_id VARCHAR(100) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            status VARCHAR(50) NOT NULL,
            UNIQUE INDEX UNIQ_906517442DA68207 (invoice_number),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        // Add FK only if cart_item was just created (IF NOT EXISTS guard above handles idempotency)
        $this->addSql('ALTER TABLE cart_item
            ADD CONSTRAINT FK_F0FE25277078ABE4 FOREIGN KEY IF NOT EXISTS (marketplace_id) REFERENCES marketplace (id_marketplace),
            ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY IF NOT EXISTS (cart_id) REFERENCES cart (id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY IF EXISTS FK_F0FE25277078ABE4');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY IF EXISTS FK_F0FE25271AD5CDBF');
        $this->addSql('DROP TABLE IF EXISTS cart_item');
        $this->addSql('DROP TABLE IF EXISTS cart');
        $this->addSql('DROP TABLE IF EXISTS invoice');
    }
}
