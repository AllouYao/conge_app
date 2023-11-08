<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231108155431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_personals DROP FOREIGN KEY FK_988A281DF8774613');
        $this->addSql('DROP INDEX IDX_988A281DF8774613 ON charge_personals');
        $this->addSql('ALTER TABLE charge_personals DROP categorie_charge_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_personals ADD categorie_charge_id INT NOT NULL');
        $this->addSql('ALTER TABLE charge_personals ADD CONSTRAINT FK_988A281DF8774613 FOREIGN KEY (categorie_charge_id) REFERENCES category_charge (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_988A281DF8774613 ON charge_personals (categorie_charge_id)');
    }
}
