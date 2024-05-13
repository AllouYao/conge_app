<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240508153802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure ADD nb_part NUMERIC(10, 2) DEFAULT NULL, ADD impot_brut NUMERIC(20, 2) DEFAULT NULL, ADD credit_impot NUMERIC(20, 2) DEFAULT NULL, ADD impot_net NUMERIC(20, 2) DEFAULT NULL, ADD amount_cmu NUMERIC(20, 2) DEFAULT NULL, ADD amount_cnps NUMERIC(20, 2) DEFAULT NULL, ADD totat_charge_personal NUMERIC(20, 2) DEFAULT NULL, ADD amount_is NUMERIC(20, 2) DEFAULT NULL, ADD amount_cr NUMERIC(20, 2) DEFAULT NULL, ADD amount_pf NUMERIC(20, 2) DEFAULT NULL, ADD amount_at NUMERIC(20, 2) DEFAULT NULL, ADD amount_ta NUMERIC(20, 2) DEFAULT NULL, ADD amountfpc NUMERIC(20, 2) DEFAULT NULL, ADD amount_fpc_year NUMERIC(20, 2) DEFAULT NULL, ADD amount_cmu_e NUMERIC(20, 2) DEFAULT NULL, ADD total_charge_employer NUMERIC(20, 2) DEFAULT NULL, ADD net_payer NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure DROP nb_part, DROP impot_brut, DROP credit_impot, DROP impot_net, DROP amount_cmu, DROP amount_cnps, DROP totat_charge_personal, DROP amount_is, DROP amount_cr, DROP amount_pf, DROP amount_at, DROP amount_ta, DROP amountfpc, DROP amount_fpc_year, DROP amount_cmu_e, DROP total_charge_employer, DROP net_payer');
    }
}
