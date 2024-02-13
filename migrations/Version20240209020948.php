<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240209020948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_employeur ADD departure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE charge_employeur ADD CONSTRAINT FK_EEE7CB857704ED06 FOREIGN KEY (departure_id) REFERENCES departure (id)');
        $this->addSql('CREATE INDEX IDX_EEE7CB857704ED06 ON charge_employeur (departure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_employeur DROP FOREIGN KEY FK_EEE7CB857704ED06');
        $this->addSql('DROP INDEX IDX_EEE7CB857704ED06 ON charge_employeur');
        $this->addSql('ALTER TABLE charge_employeur DROP departure_id');
    }
}
