<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240516134912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge_partiel DROP FOREIGN KEY FK_1EE74E53CAAC9A59');
        $this->addSql('DROP INDEX IDX_1EE74E53CAAC9A59 ON conge_partiel');
        $this->addSql('ALTER TABLE conge_partiel DROP conge_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge_partiel ADD conge_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conge_partiel ADD CONSTRAINT FK_1EE74E53CAAC9A59 FOREIGN KEY (conge_id) REFERENCES conge (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1EE74E53CAAC9A59 ON conge_partiel (conge_id)');
    }
}
