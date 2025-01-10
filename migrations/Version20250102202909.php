<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250102202909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE methode_paiement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande ADD methode_paiement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D474F4E47 FOREIGN KEY (methode_paiement_id) REFERENCES methode_paiement (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D474F4E47 ON commande (methode_paiement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D474F4E47');
        $this->addSql('DROP TABLE methode_paiement');
        $this->addSql('DROP INDEX IDX_6EEAA67D474F4E47 ON commande');
        $this->addSql('ALTER TABLE commande DROP methode_paiement_id');
    }
}
