<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240209022515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure ADD amount_lcmt_imposable NUMERIC(20, 2) DEFAULT NULL, ADD amount_lcmt_no_imposable NUMERIC(20, 2) DEFAULT NULL, ADD total_indemnite_imposable NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure DROP amount_lcmt_imposable, DROP amount_lcmt_no_imposable, DROP total_indemnite_imposable');
    }
}
