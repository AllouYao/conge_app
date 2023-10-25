<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231018112647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE salary_primes (salary_id INT NOT NULL, primes_id INT NOT NULL, INDEX IDX_38160458B0FDF16E (salary_id), INDEX IDX_38160458E3C3BF49 (primes_id), PRIMARY KEY(salary_id, primes_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salary_primes ADD CONSTRAINT FK_38160458B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salary_primes ADD CONSTRAINT FK_38160458E3C3BF49 FOREIGN KEY (primes_id) REFERENCES primes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salary_prime DROP FOREIGN KEY FK_C1ACECF769247986');
        $this->addSql('ALTER TABLE salary_prime DROP FOREIGN KEY FK_C1ACECF7B0FDF16E');
        $this->addSql('DROP TABLE salary_prime');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE salary_prime (id INT AUTO_INCREMENT NOT NULL, prime_id INT DEFAULT NULL, salary_id INT DEFAULT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_C1ACECF769247986 (prime_id), INDEX IDX_C1ACECF7B0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE salary_prime ADD CONSTRAINT FK_C1ACECF769247986 FOREIGN KEY (prime_id) REFERENCES primes (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE salary_prime ADD CONSTRAINT FK_C1ACECF7B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE salary_primes DROP FOREIGN KEY FK_38160458B0FDF16E');
        $this->addSql('ALTER TABLE salary_primes DROP FOREIGN KEY FK_38160458E3C3BF49');
        $this->addSql('DROP TABLE salary_primes');
    }
}
