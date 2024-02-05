<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240123194155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll ADD preavis_amount NUMERIC(20, 2) DEFAULT NULL, ADD licemciement_imposable NUMERIC(20, 2) DEFAULT NULL, ADD licenciement_no_impo NUMERIC(20, 2) DEFAULT NULL, ADD gratification_d NUMERIC(20, 2) DEFAULT NULL, ADD allocation_conge_d NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll DROP preavis_amount, DROP licemciement_imposable, DROP licenciement_no_impo, DROP gratification_d, DROP allocation_conge_d');
    }
}
