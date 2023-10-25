<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016164212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE primes (id INT AUTO_INCREMENT NOT NULL, intitule VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salary (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, base_amount NUMERIC(20, 2) NOT NULL, sursalaire NUMERIC(20, 2) NOT NULL, brut_amount NUMERIC(20, 2) NOT NULL, prime_transport NUMERIC(20, 10) DEFAULT NULL, indemnite_fonction NUMERIC(20, 2) DEFAULT NULL, indemnite_logement NUMERIC(20, 2) DEFAULT NULL, prime_logement NUMERIC(20, 2) DEFAULT NULL, prime_fonction NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9413BB715D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB715D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB715D430949');
        $this->addSql('DROP TABLE primes');
        $this->addSql('DROP TABLE salary');
    }
}
