<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204180929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE food_allergen (food_id INT NOT NULL, allergen_id INT NOT NULL, PRIMARY KEY (food_id, allergen_id))');
        $this->addSql('CREATE INDEX IDX_DF22F8CBA8E87C4 ON food_allergen (food_id)');
        $this->addSql('CREATE INDEX IDX_DF22F8C6E775A4A ON food_allergen (allergen_id)');
        $this->addSql('ALTER TABLE food_allergen ADD CONSTRAINT FK_DF22F8CBA8E87C4 FOREIGN KEY (food_id) REFERENCES food (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE food_allergen ADD CONSTRAINT FK_DF22F8C6E775A4A FOREIGN KEY (allergen_id) REFERENCES allergen (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE food_allergen DROP CONSTRAINT FK_DF22F8CBA8E87C4');
        $this->addSql('ALTER TABLE food_allergen DROP CONSTRAINT FK_DF22F8C6E775A4A');
        $this->addSql('DROP TABLE food_allergen');
    }
}
