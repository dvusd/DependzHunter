<?php

declare(strict_types=1);

namespace DependzHunter\Db\Mysql\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190508213735 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Increase column size for dependency and version.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
ALTER TABLE `asset` 
CHANGE COLUMN `dependency` `dependency` VARCHAR(255) NOT NULL COMMENT \'The name of the dependency\' ,
CHANGE COLUMN `version` `version` VARCHAR(255) NULL DEFAULT NULL COMMENT \'The semantic version rule in defined in the asset\' ;
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
