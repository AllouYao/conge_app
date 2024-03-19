<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240311180853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conge_partiel (id INT AUTO_INCREMENT NOT NULL, conge_id INT DEFAULT NULL, date_depart DATE NOT NULL, date_retour DATE NOT NULL, INDEX IDX_1EE74E53CAAC9A59 (conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conge_partiel ADD CONSTRAINT FK_1EE74E53CAAC9A59 FOREIGN KEY (conge_id) REFERENCES conge (id)');
        $this->addSql('ALTER TABLE conge ADD type_payement_conge VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge_partiel DROP FOREIGN KEY FK_1EE74E53CAAC9A59');
        $this->addSql('DROP TABLE conge_partiel');
        $this->addSql('ALTER TABLE conge DROP type_payement_conge');
    }
}
