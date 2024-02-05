<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240123103348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE variable_paie ADD personal_id INT NOT NULL');
        $this->addSql('ALTER TABLE variable_paie ADD CONSTRAINT FK_F15997C55D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('CREATE INDEX IDX_F15997C55D430949 ON variable_paie (personal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE variable_paie DROP FOREIGN KEY FK_F15997C55D430949');
        $this->addSql('DROP INDEX IDX_F15997C55D430949 ON variable_paie');
        $this->addSql('ALTER TABLE variable_paie DROP personal_id');
    }
}
