<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231026151740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account_bank (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, bank_id INT NOT NULL, code VARCHAR(255) NOT NULL, num_compte VARCHAR(255) NOT NULL, rib VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_54F338D05D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE charge_people (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birthday DATE NOT NULL, gender VARCHAR(255) NOT NULL, num_piece VARCHAR(255) NOT NULL, contact VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_1DB5E6D05D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_bank ADD CONSTRAINT FK_54F338D05D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE charge_people ADD CONSTRAINT FK_1DB5E6D05D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_bank DROP FOREIGN KEY FK_54F338D05D430949');
        $this->addSql('ALTER TABLE charge_people DROP FOREIGN KEY FK_1DB5E6D05D430949');
        $this->addSql('DROP TABLE account_bank');
        $this->addSql('DROP TABLE charge_people');
    }
}
