<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240424105704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_category_salarie (user_id INT NOT NULL, category_salarie_id INT NOT NULL, INDEX IDX_384338B9A76ED395 (user_id), INDEX IDX_384338B97E47C334 (category_salarie_id), PRIMARY KEY(user_id, category_salarie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_category_salarie ADD CONSTRAINT FK_384338B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_category_salarie ADD CONSTRAINT FK_384338B97E47C334 FOREIGN KEY (category_salarie_id) REFERENCES category_salarie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal ADD job_id INT DEFAULT NULL, ADD workplace_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D84BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D84AC25FB46 FOREIGN KEY (workplace_id) REFERENCES service (id)');
        $this->addSql('CREATE INDEX IDX_F18A6D84BE04EA9 ON personal (job_id)');
        $this->addSql('CREATE INDEX IDX_F18A6D84AC25FB46 ON personal (workplace_id)');
        $this->addSql('ALTER TABLE user ADD last_name VARCHAR(255) DEFAULT NULL, ADD first_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D84BE04EA9');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D84AC25FB46');
        $this->addSql('ALTER TABLE user_category_salarie DROP FOREIGN KEY FK_384338B9A76ED395');
        $this->addSql('ALTER TABLE user_category_salarie DROP FOREIGN KEY FK_384338B97E47C334');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE user_category_salarie');
        $this->addSql('DROP INDEX IDX_F18A6D84BE04EA9 ON personal');
        $this->addSql('DROP INDEX IDX_F18A6D84AC25FB46 ON personal');
        $this->addSql('ALTER TABLE personal DROP job_id, DROP workplace_id');
        $this->addSql('ALTER TABLE user DROP last_name, DROP first_name');
    }
}
