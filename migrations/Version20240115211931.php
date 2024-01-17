<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240115211931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll ADD matricule VARCHAR(255) DEFAULT NULL, ADD service VARCHAR(255) DEFAULT NULL, ADD departement VARCHAR(255) DEFAULT NULL, ADD categories VARCHAR(255) DEFAULT NULL, ADD date_embauche DATE DEFAULT NULL, ADD num_cnps VARCHAR(255) DEFAULT NULL, ADD majoration_amount NUMERIC(20, 2) DEFAULT NULL, ADD anciennete_amount NUMERIC(20, 2) DEFAULT NULL, ADD conges_payes_amount NUMERIC(20, 2) DEFAULT NULL, ADD prime_fonction_amount NUMERIC(20, 2) DEFAULT NULL, ADD prime_logement_amount NUMERIC(20, 2) DEFAULT NULL, ADD indemnite_fonction_amount NUMERIC(20, 2) DEFAULT NULL, ADD indemnite_logement_amount NUMERIC(20, 2) DEFAULT NULL, ADD amount_ta NUMERIC(20, 2) DEFAULT NULL, ADD amount_annuel_fpc NUMERIC(20, 2) DEFAULT NULL, ADD amount_fpc NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll DROP matricule, DROP service, DROP departement, DROP categories, DROP date_embauche, DROP num_cnps, DROP majoration_amount, DROP anciennete_amount, DROP conges_payes_amount, DROP prime_fonction_amount, DROP prime_logement_amount, DROP indemnite_fonction_amount, DROP indemnite_logement_amount, DROP amount_ta, DROP amount_annuel_fpc, DROP amount_fpc');
    }
}
