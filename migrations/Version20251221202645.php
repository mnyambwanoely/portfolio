<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251221202645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS admin (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, avatar_color VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
        // Unique index on admin.email already exists in database — skipping explicit creation.
        $this->addSql('CREATE TABLE IF NOT EXISTS contact_messages (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE IF NOT EXISTS personal_details (id INT AUTO_INCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, job_title VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, phone2 VARCHAR(50) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, professional_summary LONGTEXT DEFAULT NULL, years_of_experience VARCHAR(50) DEFAULT NULL, linkedin_url VARCHAR(500) DEFAULT NULL, github_url VARCHAR(500) DEFAULT NULL, twitter_url VARCHAR(500) DEFAULT NULL, website_url VARCHAR(500) DEFAULT NULL, cv_path VARCHAR(255) DEFAULT NULL, profile_image VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE IF NOT EXISTS project (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, image_url VARCHAR(500) DEFAULT NULL, category VARCHAR(50) NOT NULL, technologies LONGTEXT DEFAULT NULL, project_date DATETIME NOT NULL, live_url VARCHAR(500) DEFAULT NULL, github_url VARCHAR(500) DEFAULT NULL, status VARCHAR(20) NOT NULL, is_published TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, display_order INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE IF NOT EXISTS skill (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, percentage INT NOT NULL, category VARCHAR(50) NOT NULL, icon VARCHAR(100) DEFAULT NULL, display_order INT NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id INT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        // Indexes for messenger_messages are skipped if they already exist in database.
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE admin');
        $this->addSql('DROP TABLE contact_messages');
        $this->addSql('DROP TABLE personal_details');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE skill');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
