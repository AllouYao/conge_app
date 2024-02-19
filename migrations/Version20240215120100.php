<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240215120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detail_retenue_forfetaire (id INT AUTO_INCREMENT NOT NULL, retenu_forfetaire_id INT NOT NULL, salary_id INT NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_21636309E38ABA12 (retenu_forfetaire_id), INDEX IDX_21636309B0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309E38ABA12 FOREIGN KEY (retenu_forfetaire_id) REFERENCES retenue_forfetaire (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309E38ABA12');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309B0FDF16E');
        $this->addSql('DROP TABLE detail_retenue_forfetaire');
    }
}
