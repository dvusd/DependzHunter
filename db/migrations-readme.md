All examples are run from project root.  

Create a new migration (on linux, needs db connection)
--- 
```
./vendor/bin/doctrine-migrations generate --configuration ./db/mysql/migrations.php --db-configuration ./db/mysql/migrations-db.php
```

If a migration cannot be reverted, use throwIrreversibleMigrationException in down
```
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException('not supported');
    }
```

Test the migration first
---
```
./vendor/bin/doctrine-migrations migrate --dry-run --configuration ./db/mysql/migrations.php --db-configuration ./db/mysql/migrations-db.php
```

Apply migrations to machine
---
```
./vendor/bin/doctrine-migrations migrate --configuration ./db/mysql/migrations.php --db-configuration ./db/mysql/migrations-db.php
```

Notes
---
mysql variables have to be used in a single addSql() call
```php
    public function up(Schema $schema) : void
    {
        $this->addSql('
        CALL spCreateApp(\'tacos\', \'tacos\', \'tacos\', \'tacos\', \'tacos\', 0, @resourceId); 
        CALL spCreatePerm(\'tacos\', \'tacos\', 1, @resourceId, @permId);
        CALL spAclGrant(\'Administrators\', @resourceId);
        ');

    }
```

Check the status of migrations 
---
```
./vendor/bin/doctrine-migrations status --show-versions --configuration ./db/mysql/migrations.php --db-configuration ./db/mysql/migrations-db.php
```

[Full Docs](https://www.doctrine-project.org/projects/doctrine-migrations/en/2.0/index.html)