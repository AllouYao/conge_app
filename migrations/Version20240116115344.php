<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240116115344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll ADD amount_trans_imposable NUMERIC(20, 2) DEFAULT NULL, ADD amount_avantage_imposable NUMERIC(20, 2) DEFAULT NULL, ADD aventage_non_imposable NUMERIC(20, 2) DEFAULT NULL, ADD amount_prime_panier NUMERIC(20, 2) DEFAULT NULL, ADD amount_prime_salissure NUMERIC(20, 2) DEFAULT NULL, ADD amount_prime_outillage NUMERIC(20, 2) DEFAULT NULL, ADD amount_prime_tenue_trav NUMERIC(20, 2) DEFAULT NULL, ADD amount_prime_rendement NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll DROP amount_trans_imposable, DROP amount_avantage_imposable, DROP aventage_non_imposable, DROP amount_prime_panier, DROP amount_prime_salissure, DROP amount_prime_outillage, DROP amount_prime_tenue_trav, DROP amount_prime_rendement');
    }
}
