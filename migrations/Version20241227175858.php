<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241227175858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commandeproduits (commande_id INT NOT NULL, produits_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_3CDE4C5182EA2E54 (commande_id), INDEX IDX_3CDE4C51CD11A2CF (produits_id), PRIMARY KEY(commande_id, produits_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commandeproduits ADD CONSTRAINT FK_3CDE4C5182EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE commandeproduits ADD CONSTRAINT FK_3CDE4C51CD11A2CF FOREIGN KEY (produits_id) REFERENCES produits (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandeproduits DROP FOREIGN KEY FK_3CDE4C5182EA2E54');
        $this->addSql('ALTER TABLE commandeproduits DROP FOREIGN KEY FK_3CDE4C51CD11A2CF');
        $this->addSql('DROP TABLE commandeproduits');
    }
}
