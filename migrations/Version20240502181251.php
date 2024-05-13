<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240502181251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal ADD conjoint_num_ss VARCHAR(255) DEFAULT NULL, ADD num_ss VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE charge_people ADD num_ss VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE charge_people ADD CONSTRAINT FK_1DB5E6D05D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_people DROP FOREIGN KEY FK_1DB5E6D05D430949');
        $this->addSql('ALTER TABLE charge_people DROP FOREIGN KEY FK_1DB5E6D0A76ED395');
        $this->addSql('ALTER TABLE charge_people DROP num_ss');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F28595D430949');
        $this->addSql('ALTER TABLE personal DROP conjoint_num_ss, DROP num_ss');
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB715D430949');
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB71EA96B22C');
    }
}
