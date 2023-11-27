<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231122103657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campagne_personal (campagne_id INT NOT NULL, personal_id INT NOT NULL, INDEX IDX_A966AD9716227374 (campagne_id), INDEX IDX_A966AD975D430949 (personal_id), PRIMARY KEY(campagne_id, personal_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campagne_personal ADD CONSTRAINT FK_A966AD9716227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campagne_personal ADD CONSTRAINT FK_A966AD975D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campagne_personal DROP FOREIGN KEY FK_A966AD9716227374');
        $this->addSql('ALTER TABLE campagne_personal DROP FOREIGN KEY FK_A966AD975D430949');
        $this->addSql('DROP TABLE campagne_personal');
    }
}
