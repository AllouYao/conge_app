<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231113134706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE charge_employeur (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, amount_is NUMERIC(20, 2) DEFAULT NULL, amount_fdfp NUMERIC(20, 2) DEFAULT NULL, amount_cr NUMERIC(20, 2) DEFAULT NULL, amount_pf NUMERIC(20, 2) DEFAULT NULL, amount_at NUMERIC(20, 2) DEFAULT NULL, total_charge_employeur NUMERIC(20, 2) DEFAULT NULL, total_retenu_cnps NUMERIC(20, 2) DEFAULT NULL, amount_cmu NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_EEE7CB855D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE charge_employeur ADD CONSTRAINT FK_EEE7CB855D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_employeur DROP FOREIGN KEY FK_EEE7CB855D430949');
        $this->addSql('DROP TABLE charge_employeur');
    }
}
