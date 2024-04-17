<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240416085111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campagne ADD campagne_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campagne ADD CONSTRAINT FK_539B5D1616227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_539B5D1616227374 ON campagne (campagne_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campagne DROP FOREIGN KEY FK_539B5D1616227374');
        $this->addSql('DROP INDEX UNIQ_539B5D1616227374 ON campagne');
        $this->addSql('ALTER TABLE campagne DROP campagne_id');
    }
}
