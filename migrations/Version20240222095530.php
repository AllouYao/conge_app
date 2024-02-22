<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240222095530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD personal_id INT NOT NULL');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_216363095D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('CREATE INDEX IDX_216363095D430949 ON detail_retenue_forfetaire (personal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_216363095D430949');
        $this->addSql('DROP INDEX IDX_216363095D430949 ON detail_retenue_forfetaire');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP personal_id');
    }
}
