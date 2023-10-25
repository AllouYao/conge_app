<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231025165340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aventage (id INT AUTO_INCREMENT NOT NULL, type_aventage_id INT NOT NULL, numb_piece INT NOT NULL, amount_aventage NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_32CC33F3FC6334B1 (type_aventage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aventage ADD CONSTRAINT FK_32CC33F3FC6334B1 FOREIGN KEY (type_aventage_id) REFERENCES type_aventage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aventage DROP FOREIGN KEY FK_32CC33F3FC6334B1');
        $this->addSql('DROP TABLE aventage');
    }
}
