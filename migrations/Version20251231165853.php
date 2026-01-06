<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231165853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS education (id INT AUTO_INCREMENT NOT NULL, school VARCHAR(255) NOT NULL, degree VARCHAR(255) DEFAULT NULL, field_of_study VARCHAR(255) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS work_experience (id INT AUTO_INCREMENT NOT NULL, company VARCHAR(255) NOT NULL, position VARCHAR(255) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_current TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `admin` CHANGE roles roles JSON NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE avatar_color avatar_color VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact_messages CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE read_at read_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE personal_details CHANGE job_title job_title VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE phone2 phone2 VARCHAR(50) DEFAULT NULL, CHANGE location location VARCHAR(255) DEFAULT NULL, CHANGE years_of_experience years_of_experience VARCHAR(50) DEFAULT NULL, CHANGE linkedin_url linkedin_url VARCHAR(500) DEFAULT NULL, CHANGE github_url github_url VARCHAR(500) DEFAULT NULL, CHANGE twitter_url twitter_url VARCHAR(500) DEFAULT NULL, CHANGE website_url website_url VARCHAR(500) DEFAULT NULL, CHANGE cv_path cv_path VARCHAR(255) DEFAULT NULL, CHANGE profile_image profile_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project CHANGE image_url image_url VARCHAR(500) DEFAULT NULL, CHANGE live_url live_url VARCHAR(500) DEFAULT NULL, CHANGE github_url github_url VARCHAR(500) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE skill CHANGE icon icon VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE education');
        $this->addSql('DROP TABLE work_experience');
        $this->addSql('ALTER TABLE `admin` CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE avatar_color avatar_color VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE contact_messages CHANGE phone phone VARCHAR(255) DEFAULT \'NULL\', CHANGE read_at read_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE personal_details CHANGE job_title job_title VARCHAR(255) DEFAULT \'NULL\', CHANGE phone phone VARCHAR(50) DEFAULT \'NULL\', CHANGE phone2 phone2 VARCHAR(50) DEFAULT \'NULL\', CHANGE location location VARCHAR(255) DEFAULT \'NULL\', CHANGE years_of_experience years_of_experience VARCHAR(50) DEFAULT \'NULL\', CHANGE linkedin_url linkedin_url VARCHAR(500) DEFAULT \'NULL\', CHANGE github_url github_url VARCHAR(500) DEFAULT \'NULL\', CHANGE twitter_url twitter_url VARCHAR(500) DEFAULT \'NULL\', CHANGE website_url website_url VARCHAR(500) DEFAULT \'NULL\', CHANGE cv_path cv_path VARCHAR(255) DEFAULT \'NULL\', CHANGE profile_image profile_image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE project CHANGE image_url image_url VARCHAR(500) DEFAULT \'NULL\', CHANGE live_url live_url VARCHAR(500) DEFAULT \'NULL\', CHANGE github_url github_url VARCHAR(500) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE skill CHANGE icon icon VARCHAR(100) DEFAULT \'NULL\'');
    }
}
