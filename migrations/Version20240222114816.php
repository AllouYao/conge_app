<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240222114816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detail_retenue_forfetaire_charge_people (detail_retenue_forfetaire_id INT NOT NULL, charge_people_id INT NOT NULL, INDEX IDX_B75401C1F3FBF618 (detail_retenue_forfetaire_id), INDEX IDX_B75401C158E7E374 (charge_people_id), PRIMARY KEY(detail_retenue_forfetaire_id, charge_people_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people ADD CONSTRAINT FK_B75401C1F3FBF618 FOREIGN KEY (detail_retenue_forfetaire_id) REFERENCES detail_retenue_forfetaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people ADD CONSTRAINT FK_B75401C158E7E374 FOREIGN KEY (charge_people_id) REFERENCES charge_people (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people DROP FOREIGN KEY FK_B75401C1F3FBF618');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people DROP FOREIGN KEY FK_B75401C158E7E374');
        $this->addSql('DROP TABLE detail_retenue_forfetaire_charge_people');
    }
}
