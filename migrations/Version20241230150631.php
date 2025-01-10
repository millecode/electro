<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241230150631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandeproduits DROP FOREIGN KEY FK_3CDE4C5182EA2E54');
        $this->addSql('ALTER TABLE commandeproduits DROP FOREIGN KEY FK_3CDE4C51CD11A2CF');
        $this->addSql('DROP TABLE commandeproduits');
        $this->addSql('ALTER TABLE commande_produits DROP FOREIGN KEY FK_680DC716CD11A2CF');
        $this->addSql('ALTER TABLE commande_produits DROP FOREIGN KEY FK_680DC71682EA2E54');
        $this->addSql('DROP INDEX IDX_680DC716CD11A2CF ON commande_produits');
        $this->addSql('ALTER TABLE commande_produits ADD id INT AUTO_INCREMENT NOT NULL, ADD quantity INT NOT NULL, CHANGE produits_id produit_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE commande_produits ADD CONSTRAINT FK_680DC716F347EFB FOREIGN KEY (produit_id) REFERENCES produits (id)');
        $this->addSql('ALTER TABLE commande_produits ADD CONSTRAINT FK_680DC71682EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('CREATE INDEX IDX_680DC716F347EFB ON commande_produits (produit_id)');
        $this->addSql('ALTER TABLE produits DROP updated_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commandeproduits (commande_id INT NOT NULL, produits_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_3CDE4C5182EA2E54 (commande_id), INDEX IDX_3CDE4C51CD11A2CF (produits_id), PRIMARY KEY(commande_id, produits_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE commandeproduits ADD CONSTRAINT FK_3CDE4C5182EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE commandeproduits ADD CONSTRAINT FK_3CDE4C51CD11A2CF FOREIGN KEY (produits_id) REFERENCES produits (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE produits ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commande_produits MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_produits DROP FOREIGN KEY FK_680DC716F347EFB');
        $this->addSql('ALTER TABLE commande_produits DROP FOREIGN KEY FK_680DC71682EA2E54');
        $this->addSql('DROP INDEX IDX_680DC716F347EFB ON commande_produits');
        $this->addSql('DROP INDEX `PRIMARY` ON commande_produits');
        $this->addSql('ALTER TABLE commande_produits ADD produits_id INT NOT NULL, DROP id, DROP produit_id, DROP quantity');
        $this->addSql('ALTER TABLE commande_produits ADD CONSTRAINT FK_680DC716CD11A2CF FOREIGN KEY (produits_id) REFERENCES produits (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_produits ADD CONSTRAINT FK_680DC71682EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_680DC716CD11A2CF ON commande_produits (produits_id)');
        $this->addSql('ALTER TABLE commande_produits ADD PRIMARY KEY (commande_id, produits_id)');
    }
}
