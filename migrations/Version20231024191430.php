<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231024191430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detail_salary (id INT AUTO_INCREMENT NOT NULL, prime_id INT DEFAULT NULL, salary_id INT NOT NULL, smig_horaire NUMERIC(20, 2) DEFAULT NULL, amount_prime NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_86D9A2EA69247986 (prime_id), INDEX IDX_86D9A2EAB0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detail_salary ADD CONSTRAINT FK_86D9A2EA69247986 FOREIGN KEY (prime_id) REFERENCES primes (id)');
        $this->addSql('ALTER TABLE detail_salary ADD CONSTRAINT FK_86D9A2EAB0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
        $this->addSql('ALTER TABLE category DROP grade');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C17E47C334 FOREIGN KEY (category_salarie_id) REFERENCES category_salarie (id)');
        $this->addSql('CREATE INDEX IDX_64C19C17E47C334 ON category (category_salarie_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_salary DROP FOREIGN KEY FK_86D9A2EA69247986');
        $this->addSql('ALTER TABLE detail_salary DROP FOREIGN KEY FK_86D9A2EAB0FDF16E');
        $this->addSql('DROP TABLE detail_salary');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C17E47C334');
        $this->addSql('DROP INDEX IDX_64C19C17E47C334 ON category');
        $this->addSql('ALTER TABLE category ADD grade VARCHAR(255) DEFAULT NULL');
    }
}
