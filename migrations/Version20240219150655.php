<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240219150655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE retenue_forfetaire (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, value NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309E38ABA12 FOREIGN KEY (retenu_forfetaire_id) REFERENCES retenue_forfetaire (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
        $this->addSql('ALTER TABLE payroll ADD date_created DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309E38ABA12');
        $this->addSql('DROP TABLE retenue_forfetaire');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309B0FDF16E');
        $this->addSql('ALTER TABLE payroll DROP date_created');
    }
}