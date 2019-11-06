# Dependz Hunter
Scan projects for composer and NPM dependencies.  Record the results into a database.

## Setup
* Run `composer install`
* Create db schema `dependz_hunter`
* Create new user/password with full privileges to the database.
* Update `config/db.php` as necessary.    
* Run migrations (refer to `db/migrations-readme.md`) 

## Usage
* Run `php bin/cli.php scan --dir=/somedir --exclude=/vendor/i`

## Help
`php bin/cli.php help scan`

## Package
* Package the project for easier usage: `php build/phar.php dependzhunter`

## Credit
Based upon this console skeleton project: https://github.com/slaff/ZendCliSkeleton
