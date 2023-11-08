<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231106102001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE charge_personals (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, categorie_charge_id INT NOT NULL, amount_its NUMERIC(20, 2) DEFAULT NULL, amount_cnps NUMERIC(20, 2) DEFAULT NULL, amount_cmu NUMERIC(20, 2) DEFAULT NULL, amount_total_charge_personal NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_988A281D5D430949 (personal_id), INDEX IDX_988A281DF8774613 (categorie_charge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE charge_personals ADD CONSTRAINT FK_988A281D5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE charge_personals ADD CONSTRAINT FK_988A281DF8774613 FOREIGN KEY (categorie_charge_id) REFERENCES category_charge (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_personals DROP FOREIGN KEY FK_988A281D5D430949');
        $this->addSql('ALTER TABLE charge_personals DROP FOREIGN KEY FK_988A281DF8774613');
        $this->addSql('DROP TABLE charge_personals');
    }
}
