<?php

declare(strict_types=1);

namespace DependzHunter\Db\Mysql\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190430202849 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Initial DB creation';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('
  CREATE TABLE `asset` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(2000) NOT NULL COMMENT \'The source path of the asset.\',
  `asset_type` VARCHAR(45) NOT NULL COMMENT \'The type of file that was parsed.\',
  `section` VARCHAR(45) NOT NULL COMMENT \'The section of the file that was captured.\',
  `dependency` VARCHAR(200) NOT NULL COMMENT \'The name of the dependency\',
  `version` VARCHAR(45) NULL COMMENT \'The semantic version rule in defined in the asset\',
  `created_ts` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`));
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `asset`');
    }
}
