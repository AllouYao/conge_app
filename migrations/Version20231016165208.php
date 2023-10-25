<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016165208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE salary_prime (id INT AUTO_INCREMENT NOT NULL, prime_id INT DEFAULT NULL, salary_id INT DEFAULT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_C1ACECF769247986 (prime_id), INDEX IDX_C1ACECF7B0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salary_prime ADD CONSTRAINT FK_C1ACECF769247986 FOREIGN KEY (prime_id) REFERENCES primes (id)');
        $this->addSql('ALTER TABLE salary_prime ADD CONSTRAINT FK_C1ACECF7B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salary_prime DROP FOREIGN KEY FK_C1ACECF769247986');
        $this->addSql('ALTER TABLE salary_prime DROP FOREIGN KEY FK_C1ACECF7B0FDF16E');
        $this->addSql('DROP TABLE salary_prime');
    }
}
