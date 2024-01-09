<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240108161110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detail_prime_salary (id INT AUTO_INCREMENT NOT NULL, prime_id INT NOT NULL, salary_id INT NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D4BA2BC69247986 (prime_id), INDEX IDX_D4BA2BCB0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detail_prime_salary ADD CONSTRAINT FK_D4BA2BC69247986 FOREIGN KEY (prime_id) REFERENCES primes (id)');
        $this->addSql('ALTER TABLE detail_prime_salary ADD CONSTRAINT FK_D4BA2BCB0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_prime_salary DROP FOREIGN KEY FK_D4BA2BC69247986');
        $this->addSql('ALTER TABLE detail_prime_salary DROP FOREIGN KEY FK_D4BA2BCB0FDF16E');
        $this->addSql('DROP TABLE detail_prime_salary');
    }
}
