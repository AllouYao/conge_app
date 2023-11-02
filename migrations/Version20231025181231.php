<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231025181231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detail_salary_avantage (id INT AUTO_INCREMENT NOT NULL, avantage_id INT NOT NULL, salary_id INT NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_365F6E52EA96B22C (avantage_id), INDEX IDX_365F6E52B0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detail_salary_avantage ADD CONSTRAINT FK_365F6E52EA96B22C FOREIGN KEY (avantage_id) REFERENCES aventage (id)');
        $this->addSql('ALTER TABLE detail_salary_avantage ADD CONSTRAINT FK_365F6E52B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_salary_avantage DROP FOREIGN KEY FK_365F6E52EA96B22C');
        $this->addSql('ALTER TABLE detail_salary_avantage DROP FOREIGN KEY FK_365F6E52B0FDF16E');
        $this->addSql('DROP TABLE detail_salary_avantage');
    }
}
