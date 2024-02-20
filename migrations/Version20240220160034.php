<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240220160034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_765AE0C9A76ED395 ON absence (user_id)');
        $this->addSql('ALTER TABLE account_bank ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE account_bank ADD CONSTRAINT FK_54F338D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_54F338D0A76ED395 ON account_bank (user_id)');
        $this->addSql('ALTER TABLE charge_people ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE charge_people ADD CONSTRAINT FK_1DB5E6D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1DB5E6D0A76ED395 ON charge_people (user_id)');
        $this->addSql('ALTER TABLE conge ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conge ADD CONSTRAINT FK_2ED89348A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2ED89348A76ED395 ON conge (user_id)');
        $this->addSql('ALTER TABLE departure ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE departure ADD CONSTRAINT FK_45E9C671A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_45E9C671A76ED395 ON departure (user_id)');
        $this->addSql('ALTER TABLE heure_sup ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE heure_sup ADD CONSTRAINT FK_F74507C4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_F74507C4A76ED395 ON heure_sup (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9A76ED395');
        $this->addSql('DROP INDEX IDX_765AE0C9A76ED395 ON absence');
        $this->addSql('ALTER TABLE absence DROP user_id');
        $this->addSql('ALTER TABLE account_bank DROP FOREIGN KEY FK_54F338D0A76ED395');
        $this->addSql('DROP INDEX IDX_54F338D0A76ED395 ON account_bank');
        $this->addSql('ALTER TABLE account_bank DROP user_id');
        $this->addSql('ALTER TABLE charge_people DROP FOREIGN KEY FK_1DB5E6D0A76ED395');
        $this->addSql('DROP INDEX IDX_1DB5E6D0A76ED395 ON charge_people');
        $this->addSql('ALTER TABLE charge_people DROP user_id');
        $this->addSql('ALTER TABLE conge DROP FOREIGN KEY FK_2ED89348A76ED395');
        $this->addSql('DROP INDEX IDX_2ED89348A76ED395 ON conge');
        $this->addSql('ALTER TABLE conge DROP user_id');
        $this->addSql('ALTER TABLE departure DROP FOREIGN KEY FK_45E9C671A76ED395');
        $this->addSql('DROP INDEX IDX_45E9C671A76ED395 ON departure');
        $this->addSql('ALTER TABLE departure DROP user_id');
        $this->addSql('ALTER TABLE heure_sup DROP FOREIGN KEY FK_F74507C4A76ED395');
        $this->addSql('DROP INDEX IDX_F74507C4A76ED395 ON heure_sup');
        $this->addSql('ALTER TABLE heure_sup DROP user_id');
    }
}
