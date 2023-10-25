<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231025155822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB715C54EE5F');
        $this->addSql('DROP TABLE aventage_nature');
        $this->addSql('DROP INDEX IDX_9413BB715C54EE5F ON salary');
        $this->addSql('ALTER TABLE salary DROP avantage_nature_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aventage_nature (id INT AUTO_INCREMENT NOT NULL, nombre_piece INT NOT NULL, logement NUMERIC(20, 2) NOT NULL, mobilier NUMERIC(20, 2) NOT NULL, electricite NUMERIC(20, 2) NOT NULL, eaux NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE salary ADD avantage_nature_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB715C54EE5F FOREIGN KEY (avantage_nature_id) REFERENCES aventage_nature (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_9413BB715C54EE5F ON salary (avantage_nature_id)');
    }
}
