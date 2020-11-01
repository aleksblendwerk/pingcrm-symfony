<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201101135136 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE contacts (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, organization_id INT DEFAULT NULL, first_name VARCHAR(25) NOT NULL, last_name VARCHAR(25) NOT NULL, email VARCHAR(50) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, address VARCHAR(150) DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, region VARCHAR(50) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, postal_code VARCHAR(25) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_334015739B6B5FBA (account_id), INDEX IDX_3340157332C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_334015739B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_3340157332C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE contacts');
    }
}
