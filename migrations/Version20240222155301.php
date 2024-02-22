<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240222155301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal CHANGE birthday birthday DATE DEFAULT NULL, CHANGE piece piece VARCHAR(255) DEFAULT NULL, CHANGE ref_piece ref_piece VARCHAR(255) DEFAULT NULL, CHANGE fonction fonction VARCHAR(255) DEFAULT NULL, CHANGE service service VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal CHANGE birthday birthday DATE NOT NULL, CHANGE piece piece VARCHAR(255) NOT NULL, CHANGE ref_piece ref_piece VARCHAR(255) NOT NULL, CHANGE fonction fonction VARCHAR(255) NOT NULL, CHANGE service service VARCHAR(255) NOT NULL');
    }
}
