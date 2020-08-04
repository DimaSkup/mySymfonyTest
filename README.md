# My Symfony Test
This is my test task

## Installation
#### Download the project
    $ git clone https://github.com/DimaSkup/mySymfonyTest.git
    $ cd mySymfonyTest
    
#### Installing the dependencies using composer 
* Installing composer packages: `$ composer install`
* Clearing cache: `$ php bin/console cache:clear --env=prod --no-debug`
    
#### Installation of a bootstrap
    $ npm install
    $ npm run dev
    
#### Prepare data for the site
* In MySQL create a database called `DB`
* Create a migration: `php bin/console make:migration`
* Let's update our table `DB`: `php bin/console doctrine:schema:up -f`
* Fill the database with fixtures:
    `$ php bin/console doctrine:fixtures:load`
    
#### Running the Symfony web server
    $ symfony server:start
Now you can go to the example: https://127.0.0.1:8000 and log in in three different ways:
1. As a user `1@gmail.com` with a password `12345`
2. Log in with Google
3. Log in with Github
