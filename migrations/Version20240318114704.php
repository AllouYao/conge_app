<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240318114704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge_partiel DROP FOREIGN KEY FK_1EE74E53CAAC9A59');
        $this->addSql('DROP TABLE conge_partiel');
        $this->addSql('ALTER TABLE category_salarie DROP FOREIGN KEY FK_45567A6449FF82A0');
        $this->addSql('DROP INDEX IDX_45567A6449FF82A0 ON category_salarie');
        $this->addSql('ALTER TABLE category_salarie DROP smigs_id');
        $this->addSql('ALTER TABLE conge DROP type_payement_conge');
        $this->addSql('ALTER TABLE personal DROP active');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conge_partiel (id INT AUTO_INCREMENT NOT NULL, conge_id INT DEFAULT NULL, date_depart DATE NOT NULL, date_retour DATE NOT NULL, INDEX IDX_1EE74E53CAAC9A59 (conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE conge_partiel ADD CONSTRAINT FK_1EE74E53CAAC9A59 FOREIGN KEY (conge_id) REFERENCES conge (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE category_salarie ADD smigs_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category_salarie ADD CONSTRAINT FK_45567A6449FF82A0 FOREIGN KEY (smigs_id) REFERENCES smig (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_45567A6449FF82A0 ON category_salarie (smigs_id)');
        $this->addSql('ALTER TABLE conge ADD type_payement_conge VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE personal ADD active TINYINT(1) NOT NULL');
    }
}
