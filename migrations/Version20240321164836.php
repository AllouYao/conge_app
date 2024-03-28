<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240321164836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation ADD amount NUMERIC(10, 3) DEFAULT NULL, ADD amount_mensualite NUMERIC(10, 3) DEFAULT NULL, ADD nb_mensualite INT DEFAULT NULL, ADD remaining NUMERIC(10, 3) DEFAULT NULL, ADD status_pay VARCHAR(255) DEFAULT NULL, ADD amount_refund NUMERIC(10, 3) DEFAULT NULL, CHANGE campagne_id campagne_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation DROP amount, DROP amount_mensualite, DROP nb_mensualite, DROP remaining, DROP status_pay, DROP amount_refund, CHANGE campagne_id campagne_id INT NOT NULL');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC65D430949');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC616227374');
    }
}
