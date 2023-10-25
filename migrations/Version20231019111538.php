<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231019111538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB71EA96B22C');
        $this->addSql('DROP INDEX UNIQ_9413BB71EA96B22C ON salary');
        $this->addSql('ALTER TABLE salary CHANGE avantage_id avantage_nature_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB715C54EE5F FOREIGN KEY (avantage_nature_id) REFERENCES aventage_nature (id)');
        $this->addSql('CREATE INDEX IDX_9413BB715C54EE5F ON salary (avantage_nature_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB715C54EE5F');
        $this->addSql('DROP INDEX IDX_9413BB715C54EE5F ON salary');
        $this->addSql('ALTER TABLE salary CHANGE avantage_nature_id avantage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB71EA96B22C FOREIGN KEY (avantage_id) REFERENCES aventage_nature (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9413BB71EA96B22C ON salary (avantage_id)');
    }
}
