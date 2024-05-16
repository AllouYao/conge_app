<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240430115947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /*$this->addSql('ALTER TABLE account_bank CHANGE bank_id bank_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payroll ADD status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payroll ADD CONSTRAINT FK_499FBCC65D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE payroll ADD CONSTRAINT FK_499FBCC616227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id)');*/
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_bank CHANGE bank_id bank_id INT NOT NULL');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC65D430949');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC616227374');
        $this->addSql('ALTER TABLE payroll DROP status');
    }
}
