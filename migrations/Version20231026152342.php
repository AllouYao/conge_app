<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231026152342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aventage DROP FOREIGN KEY FK_32CC33F3FC6334B1');
        $this->addSql('ALTER TABLE detail_salary_avantage DROP FOREIGN KEY FK_365F6E52B0FDF16E');
        $this->addSql('ALTER TABLE detail_salary_avantage DROP FOREIGN KEY FK_365F6E52EA96B22C');
        $this->addSql('DROP TABLE aventage');
        $this->addSql('DROP TABLE detail_salary_avantage');
        $this->addSql('DROP TABLE type_aventage');
        $this->addSql('ALTER TABLE salary DROP indemnite_fonction, DROP indemnite_logement');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aventage (id INT AUTO_INCREMENT NOT NULL, type_aventage_id INT NOT NULL, numb_piece INT NOT NULL, amount_aventage NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_32CC33F3FC6334B1 (type_aventage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE detail_salary_avantage (id INT AUTO_INCREMENT NOT NULL, avantage_id INT NOT NULL, salary_id INT NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_365F6E52EA96B22C (avantage_id), INDEX IDX_365F6E52B0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_aventage (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE aventage ADD CONSTRAINT FK_32CC33F3FC6334B1 FOREIGN KEY (type_aventage_id) REFERENCES type_aventage (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE detail_salary_avantage ADD CONSTRAINT FK_365F6E52B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE detail_salary_avantage ADD CONSTRAINT FK_365F6E52EA96B22C FOREIGN KEY (avantage_id) REFERENCES aventage (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE salary ADD indemnite_fonction NUMERIC(20, 2) DEFAULT NULL, ADD indemnite_logement NUMERIC(20, 2) DEFAULT NULL');
    }
}
