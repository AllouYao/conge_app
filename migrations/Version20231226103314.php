<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231226103314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE departure (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, date DATE NOT NULL, is_paied TINYINT(1) DEFAULT NULL, conge_amount NUMERIC(20, 2) DEFAULT NULL, dissmissal_amount NUMERIC(20, 2) DEFAULT NULL, notice_amount NUMERIC(20, 2) DEFAULT NULL, reason VARCHAR(255) NOT NULL, salary_due NUMERIC(20, 2) DEFAULT NULL, gratification NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_45E9C6715D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE departure ADD CONSTRAINT FK_45E9C6715D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure DROP FOREIGN KEY FK_45E9C6715D430949');
        $this->addSql('DROP TABLE departure');
    }
}
