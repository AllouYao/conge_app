<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231122103336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campagne DROP FOREIGN KEY FK_539B5D165D430949');
        $this->addSql('DROP INDEX IDX_539B5D165D430949 ON campagne');
        $this->addSql('ALTER TABLE campagne DROP personal_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campagne ADD personal_id INT NOT NULL');
        $this->addSql('ALTER TABLE campagne ADD CONSTRAINT FK_539B5D165D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_539B5D165D430949 ON campagne (personal_id)');
    }
}
