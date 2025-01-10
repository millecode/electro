<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241213225322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reparation ADD service_id INT DEFAULT NULL, ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reparation ADD CONSTRAINT FK_8FDF219DED5CA9E6 FOREIGN KEY (service_id) REFERENCES servicess (id)');
        $this->addSql('ALTER TABLE reparation ADD CONSTRAINT FK_8FDF219DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8FDF219DED5CA9E6 ON reparation (service_id)');
        $this->addSql('CREATE INDEX IDX_8FDF219DA76ED395 ON reparation (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reparation DROP FOREIGN KEY FK_8FDF219DED5CA9E6');
        $this->addSql('ALTER TABLE reparation DROP FOREIGN KEY FK_8FDF219DA76ED395');
        $this->addSql('DROP INDEX IDX_8FDF219DED5CA9E6 ON reparation');
        $this->addSql('DROP INDEX IDX_8FDF219DA76ED395 ON reparation');
        $this->addSql('ALTER TABLE reparation DROP service_id, DROP user_id');
    }
}
