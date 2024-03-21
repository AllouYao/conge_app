<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240320113708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation ADD campagne_id INT NOT NULL');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D16227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id)');
        $this->addSql('CREATE INDEX IDX_1981A66D16227374 ON operation (campagne_id)');
        $this->addSql('ALTER TABLE payroll ADD rembours_net NUMERIC(20, 2) DEFAULT NULL, ADD rembours_brut NUMERIC(20, 2) DEFAULT NULL, ADD retenue_net NUMERIC(20, 2) DEFAULT NULL, ADD retenue_brut NUMERIC(20, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66D16227374');
        $this->addSql('DROP INDEX IDX_1981A66D16227374 ON operation');
        $this->addSql('ALTER TABLE operation DROP campagne_id');
        $this->addSql('ALTER TABLE payroll DROP rembours_net, DROP rembours_brut, DROP retenue_net, DROP retenue_brut');
    }
}
