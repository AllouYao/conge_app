<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231212162601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE heure_sup (id INT AUTO_INCREMENT NOT NULL, personal_id INT DEFAULT NULL, due_date DATETIME NOT NULL, started_hour DATETIME NOT NULL, ended_hour DATETIME NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', type_day VARCHAR(255) NOT NULL, type_jour_or_nuit VARCHAR(255) NOT NULL, INDEX IDX_F74507C45D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE heure_sup ADD CONSTRAINT FK_F74507C45D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE heure_sup DROP FOREIGN KEY FK_F74507C45D430949');
        $this->addSql('DROP TABLE heure_sup');
    }
}
