<?php

declare(strict_types=1);

namespace DependzHunter\Db\Mysql\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190509230206 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
ALTER TABLE `asset` 
ADD COLUMN `group_id` VARCHAR(25) NULL DEFAULT NULL COMMENT \'A unique ID that groups all results from this script execution.\' ;
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
