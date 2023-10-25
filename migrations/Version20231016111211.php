<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016111211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal ADD conjoint VARCHAR(255) DEFAULT NULL, ADD num_certificat VARCHAR(255) DEFAULT NULL, ADD num_extrait_acte VARCHAR(255) DEFAULT NULL, ADD etat_civil VARCHAR(255) NOT NULL, ADD niveau_formation VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal DROP conjoint, DROP num_certificat, DROP num_extrait_acte, DROP etat_civil, DROP niveau_formation');
    }
}
