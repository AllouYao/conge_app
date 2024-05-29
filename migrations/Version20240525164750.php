<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240525164750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, personal_id INT DEFAULT NULL, user_id INT DEFAULT NULL, started_date DATETIME NOT NULL, ended_date DATETIME NOT NULL, description VARCHAR(255) DEFAULT NULL, justified TINYINT(1) NOT NULL, type VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_765AE0C95D430949 (personal_id), INDEX IDX_765AE0C9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conge (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, user_id INT DEFAULT NULL, date_depart DATE NOT NULL, date_retour DATE NOT NULL, date_reprise DATE NOT NULL, date_dernier_retour DATE DEFAULT NULL, salaire_moyen NUMERIC(20, 2) DEFAULT NULL, allocation_conge NUMERIC(20, 2) DEFAULT NULL, is_conge TINYINT(1) NOT NULL, complete TINYINT(1) NOT NULL, type_conge VARCHAR(255) DEFAULT NULL, type_payement_conge VARCHAR(255) DEFAULT NULL, gratification NUMERIC(20, 2) DEFAULT NULL, allocation_payer NUMERIC(20, 2) DEFAULT NULL, day_auth_on_year INT NOT NULL, days NUMERIC(8, 2) DEFAULT NULL, days_plus NUMERIC(8, 2) DEFAULT NULL, salary_due NUMERIC(20, 2) DEFAULT NULL, work_months NUMERIC(8, 2) DEFAULT NULL, total_days NUMERIC(8, 2) DEFAULT NULL, older_days NUMERIC(8, 2) DEFAULT NULL, remaining_vacation NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2ED893485D430949 (personal_id), INDEX IDX_2ED89348A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fonction (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, service_id INT DEFAULT NULL, matricule VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, genre VARCHAR(255) NOT NULL, birthday DATE DEFAULT NULL, lieu_naissance VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_F18A6D84BCF5E72D (categorie_id), INDEX IDX_F18A6D84ED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal_fonction (personal_id INT NOT NULL, fonction_id INT NOT NULL, INDEX IDX_A53F0BF45D430949 (personal_id), INDEX IDX_A53F0BF457889920 (fonction_id), PRIMARY KEY(personal_id, fonction_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE society (id INT AUTO_INCREMENT NOT NULL, raison_social VARCHAR(255) NOT NULL, forme VARCHAR(255) DEFAULT NULL, activity VARCHAR(255) DEFAULT NULL, numero_cc VARCHAR(255) DEFAULT NULL, siege VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_role (user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3D60322AC (role_id), PRIMARY KEY(user_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C95D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE conge ADD CONSTRAINT FK_2ED893485D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE conge ADD CONSTRAINT FK_2ED89348A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D84BCF5E72D FOREIGN KEY (categorie_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D84ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE personal_fonction ADD CONSTRAINT FK_A53F0BF45D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_fonction ADD CONSTRAINT FK_A53F0BF457889920 FOREIGN KEY (fonction_id) REFERENCES fonction (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C95D430949');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9A76ED395');
        $this->addSql('ALTER TABLE conge DROP FOREIGN KEY FK_2ED893485D430949');
        $this->addSql('ALTER TABLE conge DROP FOREIGN KEY FK_2ED89348A76ED395');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D84BCF5E72D');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D84ED5CA9E6');
        $this->addSql('ALTER TABLE personal_fonction DROP FOREIGN KEY FK_A53F0BF45D430949');
        $this->addSql('ALTER TABLE personal_fonction DROP FOREIGN KEY FK_A53F0BF457889920');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE conge');
        $this->addSql('DROP TABLE fonction');
        $this->addSql('DROP TABLE personal');
        $this->addSql('DROP TABLE personal_fonction');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE society');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_role');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
