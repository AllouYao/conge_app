<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240229152401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, personal_id INT DEFAULT NULL, user_id INT DEFAULT NULL, started_date DATETIME NOT NULL, ended_date DATETIME NOT NULL, description VARCHAR(255) DEFAULT NULL, justified TINYINT(1) NOT NULL, type VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_765AE0C95D430949 (personal_id), INDEX IDX_765AE0C9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE account_bank (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, user_id INT DEFAULT NULL, bank_id INT NOT NULL, code VARCHAR(255) NOT NULL, num_compte VARCHAR(255) NOT NULL, rib VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, code_agence VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_54F338D05D430949 (personal_id), INDEX IDX_54F338D0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE affectation (id INT AUTO_INCREMENT NOT NULL, personal_id INT DEFAULT NULL, date_effet DATE DEFAULT NULL, groupe_travail VARCHAR(255) DEFAULT NULL, poste VARCHAR(255) DEFAULT NULL, taux_affectation VARCHAR(255) DEFAULT NULL, lieu VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F4DD61D35D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE avantage (id INT AUTO_INCREMENT NOT NULL, num_piece INT NOT NULL, amount_logement NUMERIC(20, 2) NOT NULL, amount_mobilier NUMERIC(20, 2) NOT NULL, amount_electricite NUMERIC(10, 2) NOT NULL, amount_eaux NUMERIC(20, 2) NOT NULL, total_avantage NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campagne (id INT AUTO_INCREMENT NOT NULL, last_campagne_id INT DEFAULT NULL, started_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, active TINYINT(1) NOT NULL, ordinary TINYINT(1) NOT NULL, status VARCHAR(255) DEFAULT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_539B5D1656140C78 (last_campagne_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campagne_personal (campagne_id INT NOT NULL, personal_id INT NOT NULL, INDEX IDX_A966AD9716227374 (campagne_id), INDEX IDX_A966AD975D430949 (personal_id), PRIMARY KEY(campagne_id, personal_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, category_salarie_id INT NOT NULL, intitule VARCHAR(255) NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_64C19C17E47C334 (category_salarie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_charge (id INT AUTO_INCREMENT NOT NULL, codification VARCHAR(255) NOT NULL, intitule VARCHAR(255) NOT NULL, value NUMERIC(10, 2) DEFAULT NULL, type_charge VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, category VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_salarie (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE charge_employeur (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, departure_id INT DEFAULT NULL, amount_is NUMERIC(20, 2) DEFAULT NULL, amount_cr NUMERIC(20, 2) DEFAULT NULL, amount_pf NUMERIC(20, 2) DEFAULT NULL, amount_at NUMERIC(20, 2) DEFAULT NULL, total_charge_employeur NUMERIC(20, 2) DEFAULT NULL, total_retenu_cnps NUMERIC(20, 2) DEFAULT NULL, amount_cmu NUMERIC(20, 2) DEFAULT NULL, amount_ta NUMERIC(20, 2) DEFAULT NULL, amount_fpc NUMERIC(20, 2) DEFAULT NULL, amount_annuel_fpc NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_EEE7CB855D430949 (personal_id), INDEX IDX_EEE7CB857704ED06 (departure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE charge_people (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birthday DATE NOT NULL, gender VARCHAR(255) NOT NULL, num_piece VARCHAR(255) NOT NULL, contact VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_1DB5E6D05D430949 (personal_id), INDEX IDX_1DB5E6D0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE charge_personals (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, departure_id INT DEFAULT NULL, amount_its NUMERIC(20, 2) DEFAULT NULL, amount_cnps NUMERIC(20, 2) DEFAULT NULL, amount_cmu NUMERIC(20, 2) DEFAULT NULL, amount_total_charge_personal NUMERIC(20, 2) DEFAULT NULL, num_part NUMERIC(10, 2) NOT NULL, amount_impot_brut NUMERIC(20, 2) DEFAULT NULL, amount_credit_impot NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_988A281D5D430949 (personal_id), INDEX IDX_988A281D7704ED06 (departure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conge (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, user_id INT DEFAULT NULL, date_depart DATE NOT NULL, date_retour DATE NOT NULL, date_dernier_retour DATE DEFAULT NULL, salaire_moyen NUMERIC(20, 2) DEFAULT NULL, allocation_conge NUMERIC(20, 2) DEFAULT NULL, is_conge TINYINT(1) NOT NULL, type_conge VARCHAR(255) DEFAULT NULL, gratification NUMERIC(20, 2) DEFAULT NULL, days NUMERIC(8, 2) DEFAULT NULL, days_plus NUMERIC(8, 2) DEFAULT NULL, salary_due NUMERIC(20, 2) DEFAULT NULL, work_months NUMERIC(8, 2) DEFAULT NULL, total_days NUMERIC(8, 2) DEFAULT NULL, older_days NUMERIC(8, 2) DEFAULT NULL, remaining_vacation NUMERIC(10, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2ED893485D430949 (personal_id), INDEX IDX_2ED89348A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contract (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, date_embauche DATE NOT NULL, date_effet DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, temps_contractuel VARCHAR(255) DEFAULT NULL, type_contrat VARCHAR(255) NOT NULL, ref_contract VARCHAR(255) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_E98F28595D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cron_job (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL, command VARCHAR(1024) NOT NULL, schedule VARCHAR(191) NOT NULL, description VARCHAR(191) NOT NULL, enabled TINYINT(1) NOT NULL, UNIQUE INDEX un_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cron_report (id INT AUTO_INCREMENT NOT NULL, job_id INT DEFAULT NULL, run_at DATETIME NOT NULL, run_time DOUBLE PRECISION NOT NULL, exit_code INT NOT NULL, output LONGTEXT NOT NULL, error LONGTEXT NOT NULL, INDEX IDX_B6C6A7F5BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE departure (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, user_id INT DEFAULT NULL, date DATE NOT NULL, is_paied TINYINT(1) DEFAULT NULL, conge_amount NUMERIC(20, 2) DEFAULT NULL, dissmissal_amount NUMERIC(20, 2) DEFAULT NULL, notice_amount NUMERIC(20, 2) DEFAULT NULL, reason VARCHAR(255) NOT NULL, salary_due NUMERIC(20, 2) DEFAULT NULL, gratification NUMERIC(20, 2) DEFAULT NULL, frais_funeraire NUMERIC(20, 2) DEFAULT NULL, amount_lcmt_imposable NUMERIC(20, 2) DEFAULT NULL, amount_lcmt_no_imposable NUMERIC(20, 2) DEFAULT NULL, total_indemnite_imposable NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_45E9C6715D430949 (personal_id), INDEX IDX_45E9C671A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE detail_prime_salary (id INT AUTO_INCREMENT NOT NULL, prime_id INT NOT NULL, salary_id INT NOT NULL, amount NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D4BA2BC69247986 (prime_id), INDEX IDX_D4BA2BCB0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE detail_retenue_forfetaire (id INT AUTO_INCREMENT NOT NULL, retenu_forfetaire_id INT NOT NULL, salary_id INT NOT NULL, personal_id INT DEFAULT NULL, user_id INT DEFAULT NULL, amount NUMERIC(20, 2) NOT NULL, amount_emp NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_21636309E38ABA12 (retenu_forfetaire_id), INDEX IDX_21636309B0FDF16E (salary_id), INDEX IDX_216363095D430949 (personal_id), INDEX IDX_21636309A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE detail_retenue_forfetaire_charge_people (detail_retenue_forfetaire_id INT NOT NULL, charge_people_id INT NOT NULL, INDEX IDX_B75401C1F3FBF618 (detail_retenue_forfetaire_id), INDEX IDX_B75401C158E7E374 (charge_people_id), PRIMARY KEY(detail_retenue_forfetaire_id, charge_people_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE detail_salary (id INT AUTO_INCREMENT NOT NULL, prime_id INT DEFAULT NULL, salary_id INT NOT NULL, smig_horaire NUMERIC(20, 2) DEFAULT NULL, amount_prime NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_86D9A2EA69247986 (prime_id), INDEX IDX_86D9A2EAB0FDF16E (salary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE heure_sup (id INT AUTO_INCREMENT NOT NULL, personal_id INT DEFAULT NULL, user_id INT DEFAULT NULL, started_hour DATETIME NOT NULL, ended_hour DATETIME NOT NULL, type_day VARCHAR(255) NOT NULL, type_jour_or_nuit VARCHAR(255) NOT NULL, started_date DATETIME NOT NULL, ended_date DATETIME NOT NULL, amount NUMERIC(20, 2) DEFAULT NULL, taux_horaire NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_F74507C45D430949 (personal_id), INDEX IDX_F74507C4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payroll (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, campagne_id INT NOT NULL, number_part NUMERIC(10, 2) DEFAULT NULL, base_amount NUMERIC(20, 2) DEFAULT NULL, sursalaire NUMERIC(20, 2) DEFAULT NULL, brut_amount NUMERIC(20, 2) DEFAULT NULL, imposable_amount NUMERIC(20, 2) DEFAULT NULL, salary_its NUMERIC(20, 2) DEFAULT NULL, salary_cnps NUMERIC(20, 2) DEFAULT NULL, salary_cmu NUMERIC(20, 2) DEFAULT NULL, fixcal_amount NUMERIC(20, 2) DEFAULT NULL, salary_transport NUMERIC(20, 2) DEFAULT NULL, net_payer NUMERIC(20, 2) DEFAULT NULL, salary_sante NUMERIC(20, 2) DEFAULT NULL, employeur_is NUMERIC(20, 2) DEFAULT NULL, employeur_fdfp NUMERIC(20, 2) DEFAULT NULL, employeur_cmu NUMERIC(20, 2) DEFAULT NULL, employeur_pf NUMERIC(20, 2) DEFAULT NULL, employeur_at NUMERIC(20, 2) DEFAULT NULL, employeur_cnps NUMERIC(20, 2) DEFAULT NULL, employeur_sante NUMERIC(20, 2) DEFAULT NULL, employeur_cr NUMERIC(20, 2) DEFAULT NULL, fixcal_amount_employeur NUMERIC(20, 2) DEFAULT NULL, masse_salary NUMERIC(20, 2) DEFAULT NULL, matricule VARCHAR(255) DEFAULT NULL, service VARCHAR(255) DEFAULT NULL, departement VARCHAR(255) DEFAULT NULL, categories VARCHAR(255) DEFAULT NULL, date_embauche DATE DEFAULT NULL, num_cnps VARCHAR(255) DEFAULT NULL, majoration_amount NUMERIC(20, 2) DEFAULT NULL, anciennete_amount NUMERIC(20, 2) DEFAULT NULL, conges_payes_amount NUMERIC(20, 2) DEFAULT NULL, prime_fonction_amount NUMERIC(20, 2) DEFAULT NULL, prime_logement_amount NUMERIC(20, 2) DEFAULT NULL, indemnite_fonction_amount NUMERIC(20, 2) DEFAULT NULL, indemnite_logement_amount NUMERIC(20, 2) DEFAULT NULL, amount_ta NUMERIC(20, 2) DEFAULT NULL, amount_annuel_fpc NUMERIC(20, 2) DEFAULT NULL, amount_fpc NUMERIC(20, 2) DEFAULT NULL, amount_trans_imposable NUMERIC(20, 2) DEFAULT NULL, amount_avantage_imposable NUMERIC(20, 2) DEFAULT NULL, aventage_non_imposable NUMERIC(20, 2) DEFAULT NULL, amount_prime_panier NUMERIC(20, 2) DEFAULT NULL, amount_prime_salissure NUMERIC(20, 2) DEFAULT NULL, amount_prime_outillage NUMERIC(20, 2) DEFAULT NULL, amount_prime_tenue_trav NUMERIC(20, 2) DEFAULT NULL, amount_prime_rendement NUMERIC(20, 2) DEFAULT NULL, preavis_amount NUMERIC(20, 2) DEFAULT NULL, licemciement_imposable NUMERIC(20, 2) DEFAULT NULL, licenciement_no_impo NUMERIC(20, 2) DEFAULT NULL, gratification_d NUMERIC(20, 2) DEFAULT NULL, allocation_conge_d NUMERIC(20, 2) DEFAULT NULL, total_indemnite_brut NUMERIC(20, 2) DEFAULT NULL, total_indemnite_imposable NUMERIC(20, 2) DEFAULT NULL, date_created DATE DEFAULT NULL, social_amount NUMERIC(20, 2) DEFAULT NULL, social_amount_employeur NUMERIC(20, 2) DEFAULT NULL, total_retenue_salarie NUMERIC(20, 2) DEFAULT NULL, total_retenue_patronal NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_499FBCC65D430949 (personal_id), INDEX IDX_499FBCC616227374 (campagne_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, matricule VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, genre VARCHAR(255) NOT NULL, birthday DATE DEFAULT NULL, lieu_naissance VARCHAR(255) DEFAULT NULL, ref_cnps VARCHAR(255) DEFAULT NULL, piece VARCHAR(255) DEFAULT NULL, ref_piece VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, conjoint VARCHAR(255) DEFAULT NULL, num_certificat VARCHAR(255) DEFAULT NULL, num_extrait_acte VARCHAR(255) DEFAULT NULL, etat_civil VARCHAR(255) DEFAULT NULL, niveau_formation VARCHAR(255) DEFAULT NULL, mode_paiement VARCHAR(255) DEFAULT NULL, fonction VARCHAR(255) DEFAULT NULL, service VARCHAR(255) DEFAULT NULL, older NUMERIC(10, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_F18A6D84BCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE primes (id INT AUTO_INCREMENT NOT NULL, intitule VARCHAR(255) NOT NULL, taux NUMERIC(10, 2) DEFAULT NULL, code VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE retenue_forfetaire (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, value NUMERIC(20, 2) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salary (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, avantage_id INT DEFAULT NULL, base_amount NUMERIC(20, 2) NOT NULL, sursalaire NUMERIC(20, 2) NOT NULL, brut_amount NUMERIC(20, 2) NOT NULL, prime_transport NUMERIC(20, 2) DEFAULT NULL, brut_imposable NUMERIC(20, 2) NOT NULL, smig NUMERIC(20, 2) NOT NULL, total_prime_juridique NUMERIC(20, 2) DEFAULT NULL, prime_aciennete NUMERIC(20, 2) DEFAULT NULL, transport_imposable NUMERIC(20, 2) DEFAULT NULL, amount_aventage NUMERIC(20, 2) DEFAULT NULL, total_autre_primes NUMERIC(20, 2) DEFAULT NULL, gratification NUMERIC(20, 2) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9413BB715D430949 (personal_id), INDEX IDX_9413BB71EA96B22C (avantage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smig (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, amount NUMERIC(20, 2) NOT NULL, is_active TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE society (id INT AUTO_INCREMENT NOT NULL, raison_social VARCHAR(255) NOT NULL, forme VARCHAR(255) DEFAULT NULL, activity VARCHAR(255) DEFAULT NULL, numero_cc VARCHAR(255) DEFAULT NULL, siege VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE taux_horaire (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, amount NUMERIC(20, 2) NOT NULL, is_active TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_role (user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3D60322AC (role_id), PRIMARY KEY(user_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE variable_paie (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, date_validation DATE DEFAULT NULL, smig NUMERIC(20, 2) DEFAULT NULL, embauche DATE DEFAULT NULL, etat_civil VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, active TINYINT(1) DEFAULT NULL, INDEX IDX_F15997C55D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C95D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE account_bank ADD CONSTRAINT FK_54F338D05D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE account_bank ADD CONSTRAINT FK_54F338D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT FK_F4DD61D35D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE campagne ADD CONSTRAINT FK_539B5D1656140C78 FOREIGN KEY (last_campagne_id) REFERENCES campagne (id)');
        $this->addSql('ALTER TABLE campagne_personal ADD CONSTRAINT FK_A966AD9716227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campagne_personal ADD CONSTRAINT FK_A966AD975D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C17E47C334 FOREIGN KEY (category_salarie_id) REFERENCES category_salarie (id)');
        $this->addSql('ALTER TABLE charge_employeur ADD CONSTRAINT FK_EEE7CB855D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE charge_employeur ADD CONSTRAINT FK_EEE7CB857704ED06 FOREIGN KEY (departure_id) REFERENCES departure (id)');
        $this->addSql('ALTER TABLE charge_people ADD CONSTRAINT FK_1DB5E6D05D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE charge_people ADD CONSTRAINT FK_1DB5E6D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE charge_personals ADD CONSTRAINT FK_988A281D5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE charge_personals ADD CONSTRAINT FK_988A281D7704ED06 FOREIGN KEY (departure_id) REFERENCES departure (id)');
        $this->addSql('ALTER TABLE conge ADD CONSTRAINT FK_2ED893485D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE conge ADD CONSTRAINT FK_2ED89348A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F28595D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE cron_report ADD CONSTRAINT FK_B6C6A7F5BE04EA9 FOREIGN KEY (job_id) REFERENCES cron_job (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE departure ADD CONSTRAINT FK_45E9C6715D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE departure ADD CONSTRAINT FK_45E9C671A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE detail_prime_salary ADD CONSTRAINT FK_D4BA2BC69247986 FOREIGN KEY (prime_id) REFERENCES primes (id)');
        $this->addSql('ALTER TABLE detail_prime_salary ADD CONSTRAINT FK_D4BA2BCB0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309E38ABA12 FOREIGN KEY (retenu_forfetaire_id) REFERENCES retenue_forfetaire (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309B0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_216363095D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire ADD CONSTRAINT FK_21636309A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people ADD CONSTRAINT FK_B75401C1F3FBF618 FOREIGN KEY (detail_retenue_forfetaire_id) REFERENCES detail_retenue_forfetaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people ADD CONSTRAINT FK_B75401C158E7E374 FOREIGN KEY (charge_people_id) REFERENCES charge_people (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE detail_salary ADD CONSTRAINT FK_86D9A2EA69247986 FOREIGN KEY (prime_id) REFERENCES primes (id)');
        $this->addSql('ALTER TABLE detail_salary ADD CONSTRAINT FK_86D9A2EAB0FDF16E FOREIGN KEY (salary_id) REFERENCES salary (id)');
        $this->addSql('ALTER TABLE heure_sup ADD CONSTRAINT FK_F74507C45D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE heure_sup ADD CONSTRAINT FK_F74507C4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payroll ADD CONSTRAINT FK_499FBCC65D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE payroll ADD CONSTRAINT FK_499FBCC616227374 FOREIGN KEY (campagne_id) REFERENCES campagne (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D84BCF5E72D FOREIGN KEY (categorie_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB715D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE salary ADD CONSTRAINT FK_9413BB71EA96B22C FOREIGN KEY (avantage_id) REFERENCES avantage (id)');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variable_paie ADD CONSTRAINT FK_F15997C55D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C95D430949');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9A76ED395');
        $this->addSql('ALTER TABLE account_bank DROP FOREIGN KEY FK_54F338D05D430949');
        $this->addSql('ALTER TABLE account_bank DROP FOREIGN KEY FK_54F338D0A76ED395');
        $this->addSql('ALTER TABLE affectation DROP FOREIGN KEY FK_F4DD61D35D430949');
        $this->addSql('ALTER TABLE campagne DROP FOREIGN KEY FK_539B5D1656140C78');
        $this->addSql('ALTER TABLE campagne_personal DROP FOREIGN KEY FK_A966AD9716227374');
        $this->addSql('ALTER TABLE campagne_personal DROP FOREIGN KEY FK_A966AD975D430949');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C17E47C334');
        $this->addSql('ALTER TABLE charge_employeur DROP FOREIGN KEY FK_EEE7CB855D430949');
        $this->addSql('ALTER TABLE charge_employeur DROP FOREIGN KEY FK_EEE7CB857704ED06');
        $this->addSql('ALTER TABLE charge_people DROP FOREIGN KEY FK_1DB5E6D05D430949');
        $this->addSql('ALTER TABLE charge_people DROP FOREIGN KEY FK_1DB5E6D0A76ED395');
        $this->addSql('ALTER TABLE charge_personals DROP FOREIGN KEY FK_988A281D5D430949');
        $this->addSql('ALTER TABLE charge_personals DROP FOREIGN KEY FK_988A281D7704ED06');
        $this->addSql('ALTER TABLE conge DROP FOREIGN KEY FK_2ED893485D430949');
        $this->addSql('ALTER TABLE conge DROP FOREIGN KEY FK_2ED89348A76ED395');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F28595D430949');
        $this->addSql('ALTER TABLE cron_report DROP FOREIGN KEY FK_B6C6A7F5BE04EA9');
        $this->addSql('ALTER TABLE departure DROP FOREIGN KEY FK_45E9C6715D430949');
        $this->addSql('ALTER TABLE departure DROP FOREIGN KEY FK_45E9C671A76ED395');
        $this->addSql('ALTER TABLE detail_prime_salary DROP FOREIGN KEY FK_D4BA2BC69247986');
        $this->addSql('ALTER TABLE detail_prime_salary DROP FOREIGN KEY FK_D4BA2BCB0FDF16E');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309E38ABA12');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309B0FDF16E');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_216363095D430949');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire DROP FOREIGN KEY FK_21636309A76ED395');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people DROP FOREIGN KEY FK_B75401C1F3FBF618');
        $this->addSql('ALTER TABLE detail_retenue_forfetaire_charge_people DROP FOREIGN KEY FK_B75401C158E7E374');
        $this->addSql('ALTER TABLE detail_salary DROP FOREIGN KEY FK_86D9A2EA69247986');
        $this->addSql('ALTER TABLE detail_salary DROP FOREIGN KEY FK_86D9A2EAB0FDF16E');
        $this->addSql('ALTER TABLE heure_sup DROP FOREIGN KEY FK_F74507C45D430949');
        $this->addSql('ALTER TABLE heure_sup DROP FOREIGN KEY FK_F74507C4A76ED395');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC65D430949');
        $this->addSql('ALTER TABLE payroll DROP FOREIGN KEY FK_499FBCC616227374');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D84BCF5E72D');
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB715D430949');
        $this->addSql('ALTER TABLE salary DROP FOREIGN KEY FK_9413BB71EA96B22C');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('ALTER TABLE variable_paie DROP FOREIGN KEY FK_F15997C55D430949');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE account_bank');
        $this->addSql('DROP TABLE affectation');
        $this->addSql('DROP TABLE avantage');
        $this->addSql('DROP TABLE campagne');
        $this->addSql('DROP TABLE campagne_personal');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_charge');
        $this->addSql('DROP TABLE category_salarie');
        $this->addSql('DROP TABLE charge_employeur');
        $this->addSql('DROP TABLE charge_people');
        $this->addSql('DROP TABLE charge_personals');
        $this->addSql('DROP TABLE conge');
        $this->addSql('DROP TABLE contract');
        $this->addSql('DROP TABLE cron_job');
        $this->addSql('DROP TABLE cron_report');
        $this->addSql('DROP TABLE departure');
        $this->addSql('DROP TABLE detail_prime_salary');
        $this->addSql('DROP TABLE detail_retenue_forfetaire');
        $this->addSql('DROP TABLE detail_retenue_forfetaire_charge_people');
        $this->addSql('DROP TABLE detail_salary');
        $this->addSql('DROP TABLE heure_sup');
        $this->addSql('DROP TABLE payroll');
        $this->addSql('DROP TABLE personal');
        $this->addSql('DROP TABLE primes');
        $this->addSql('DROP TABLE retenue_forfetaire');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE salary');
        $this->addSql('DROP TABLE smig');
        $this->addSql('DROP TABLE society');
        $this->addSql('DROP TABLE taux_horaire');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_role');
        $this->addSql('DROP TABLE variable_paie');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
