<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth, reset password and profile photo fields to utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD photo_profil VARCHAR(255) DEFAULT NULL, ADD google_id VARCHAR(255) DEFAULT NULL, ADD reset_password_token VARCHAR(255) DEFAULT NULL, ADD reset_password_expires_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_UTILISATEUR_GOOGLE_ID ON utilisateur (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_UTILISATEUR_RESET_PASSWORD_TOKEN ON utilisateur (reset_password_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_UTILISATEUR_GOOGLE_ID ON utilisateur');
        $this->addSql('DROP INDEX UNIQ_UTILISATEUR_RESET_PASSWORD_TOKEN ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP photo_profil, DROP google_id, DROP reset_password_token, DROP reset_password_expires_at');
    }
}
