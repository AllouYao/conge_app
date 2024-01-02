<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240102113452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure ADD personal_id INT NOT NULL');
        $this->addSql('ALTER TABLE departure ADD CONSTRAINT FK_45E9C6715D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_45E9C6715D430949 ON departure (personal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departure DROP FOREIGN KEY FK_45E9C6715D430949');
        $this->addSql('DROP INDEX UNIQ_45E9C6715D430949 ON departure');
        $this->addSql('ALTER TABLE departure DROP personal_id');
    }
}
