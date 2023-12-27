<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231226151722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge ADD days NUMERIC(8, 2) DEFAULT NULL, ADD days_plus NUMERIC(8, 2) DEFAULT NULL, ADD salary_due NUMERIC(20, 2) DEFAULT NULL, ADD work_months NUMERIC(8, 2) DEFAULT NULL, ADD total_days NUMERIC(8, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conge DROP days, DROP days_plus, DROP salary_due, DROP work_months, DROP total_days');
    }
}
