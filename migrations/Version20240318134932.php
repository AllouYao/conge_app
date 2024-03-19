<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240318134932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation ADD campagne_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D16227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id)');
        $this->addSql('CREATE INDEX IDX_1981A66D16227374 ON operation (campagne_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66D16227374');
        $this->addSql('DROP INDEX IDX_1981A66D16227374 ON operation');
        $this->addSql('ALTER TABLE operation DROP campagne_id');
    }
}
