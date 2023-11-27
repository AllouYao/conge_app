<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231121104420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campagne (id INT AUTO_INCREMENT NOT NULL, last_campagne_id INT NOT NULL, personal_id INT NOT NULL, started_at DATE NOT NULL, closed_at DATE DEFAULT NULL, active TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_539B5D1656140C78 (last_campagne_id), INDEX IDX_539B5D165D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campagne ADD CONSTRAINT FK_539B5D1656140C78 FOREIGN KEY (last_campagne_id) REFERENCES campagne (id)');
        $this->addSql('ALTER TABLE campagne ADD CONSTRAINT FK_539B5D165D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campagne DROP FOREIGN KEY FK_539B5D1656140C78');
        $this->addSql('ALTER TABLE campagne DROP FOREIGN KEY FK_539B5D165D430949');
        $this->addSql('DROP TABLE campagne');
    }
}
