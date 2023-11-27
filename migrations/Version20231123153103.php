<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231123153103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payroll (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, campagne_id INT NOT NULL, number_part NUMERIC(10, 2) DEFAULT NULL, base_amount NUMERIC(20, 2) DEFAULT NULL, sursalaire NUMERIC(20, 2) DEFAULT NULL, brut_amount NUMERIC(20, 2) DEFAULT NULL, imposable_amount NUMERIC(20, 2) DEFAULT NULL, salary_its NUMERIC(20, 2) DEFAULT NULL, salary_cnps NUMERIC(20, 2) DEFAULT NULL, salary_cmu NUMERIC(20, 2) DEFAULT NULL, fixcal_amount NUMERIC(20, 2) DEFAULT NULL, salary_transport NUMERIC(20, 2) DEFAULT NULL, net_payer NUMERIC(20, 2) DEFAULT NULL, salary_sante NUMERIC(20, 2) DEFAULT NULL, employeur_is NUMERIC(20, 2) DEFAULT NULL, employeur_fdfp NUMERIC(20, 2) DEFAULT NULL, employeur_cmu NUMERIC(20, 2) DEFAULT NULL, employeur_pf NUMERIC(20, 2) DEFAULT NULL, employeur_at NUMERIC(20, 2) DEFAULT NULL, employeur_cnps NUMERIC(20, 2) DEFAULT NULL, employeur_sante NUMERIC(20, 2) DEFAULT NULL, employeur_cr NUMERIC(20, 2) DEFAULT NULL, fixcal_amount_employeur NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_499FBCC65D430949 (personal_id), INDEX IDX_499FBCC616227374 (campagne_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payroll ADD CONSTRAINT FK_499FBCC65D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE payroll ADD CONSTRAINT FK_499FBCC616227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC65D430949');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC616227374');
        $this->addSql('DROP TABLE payroll');
    }
}
