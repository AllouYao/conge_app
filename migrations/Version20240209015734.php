<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240209015734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_personals ADD departure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE charge_personals ADD CONSTRAINT FK_988A281D7704ED06 FOREIGN KEY (departure_id) REFERENCES departure (id)');
        $this->addSql('CREATE INDEX IDX_988A281D7704ED06 ON charge_personals (departure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_personals DROP FOREIGN KEY FK_988A281D7704ED06');
        $this->addSql('DROP INDEX IDX_988A281D7704ED06 ON charge_personals');
        $this->addSql('ALTER TABLE charge_personals DROP departure_id');
    }
}
