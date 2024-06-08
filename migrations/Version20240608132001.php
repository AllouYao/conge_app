<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240608132001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge DROP salaire_moyen, DROP allocation_conge, DROP complete, DROP type_conge, DROP type_payement_conge, DROP gratification, DROP allocation_payer, DROP day_auth_on_year, DROP days, DROP days_plus, DROP salary_due, DROP work_months, DROP older_days, DROP remaining_vacation');
        $this->addSql('ALTER TABLE personal ADD date_embauche DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge ADD salaire_moyen NUMERIC(20, 2) DEFAULT NULL, ADD allocation_conge NUMERIC(20, 2) DEFAULT NULL, ADD complete TINYINT(1) NOT NULL, ADD type_conge VARCHAR(255) DEFAULT NULL, ADD type_payement_conge VARCHAR(255) DEFAULT NULL, ADD gratification NUMERIC(20, 2) DEFAULT NULL, ADD allocation_payer NUMERIC(20, 2) DEFAULT NULL, ADD day_auth_on_year INT NOT NULL, ADD days NUMERIC(8, 2) DEFAULT NULL, ADD days_plus NUMERIC(8, 2) DEFAULT NULL, ADD salary_due NUMERIC(20, 2) DEFAULT NULL, ADD work_months NUMERIC(8, 2) DEFAULT NULL, ADD older_days NUMERIC(8, 2) DEFAULT NULL, ADD remaining_vacation NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE personal DROP date_embauche');
    }
}
