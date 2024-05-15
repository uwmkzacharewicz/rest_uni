<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240515174927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher DROP FOREIGN KEY FK_B0F6A6D55CB2E05D');
        $this->addSql('ALTER TABLE teacher ADD CONSTRAINT FK_B0F6A6D55CB2E05D FOREIGN KEY (login_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE teacher DROP FOREIGN KEY FK_B0F6A6D55CB2E05D');
        $this->addSql('ALTER TABLE teacher ADD CONSTRAINT FK_B0F6A6D55CB2E05D FOREIGN KEY (login_id) REFERENCES user (id)');
    }
}
