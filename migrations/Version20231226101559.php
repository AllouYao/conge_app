<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231226101559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge ADD gratification NUMERIC(20, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE heure_sup ADD amount NUMERIC(20, 2) DEFAULT NULL, ADD taux_horaire NUMERIC(20, 2) NOT NULL');
        $this->addSql('ALTER TABLE personal ADD older NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE salary DROP taux_horaire, DROP gratification, DROP heursupplementaire, DROP conge_payer');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge DROP gratification');
        $this->addSql('ALTER TABLE heure_sup DROP amount, DROP taux_horaire');
        $this->addSql('ALTER TABLE personal DROP older');
        $this->addSql('ALTER TABLE salary ADD taux_horaire NUMERIC(20, 2) NOT NULL, ADD gratification NUMERIC(20, 2) DEFAULT NULL, ADD heursupplementaire NUMERIC(20, 2) DEFAULT NULL, ADD conge_payer NUMERIC(20, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`');
    }
}
