<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240305150615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_salarie ADD smigs_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category_salarie ADD CONSTRAINT FK_45567A6449FF82A0 FOREIGN KEY (smigs_id) REFERENCES smig (id)');
        $this->addSql('CREATE INDEX IDX_45567A6449FF82A0 ON category_salarie (smigs_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_salarie DROP FOREIGN KEY FK_45567A6449FF82A0');
        $this->addSql('DROP INDEX IDX_45567A6449FF82A0 ON category_salarie');
        $this->addSql('ALTER TABLE category_salarie DROP smigs_id');
    }
}
