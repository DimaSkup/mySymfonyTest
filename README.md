# My Symfony Test
This is my test task

## Installation
#### Download the project
    $ git clone https://github.com/DimaSkup/mySymfonyTest.git
    $ cd mySymfonyTest
    
#### Installing the dependencies using composer 
    $ composer install
    
#### Installation of a bootstrap
    $ npm install @symfony/webpack-encore --save-dev
    $ composer require encore
Now you need _to_ _comment_ _out_ the lines `./node_modules/bootstrap/dist/js/bootstrap.min.js` and `./node_modules/bootstrap/dist/css/bootstrap.min.css` in the file `webpack.config.js`

Execute the following commands:

    $ ./node_modules/.bin/encore dev
    $ npm install jquery
    $ npm install popper.js
    $ npm install bootstrap
Now you need _to_ _uncomment_ the lines `./node_modules/bootstrap/dist/js/bootstrap.min.js` and `./node_modules/bootstrap/dist/css/bootstrap.min.css` in the file `webpack.config.js`

    $ ./node_modules/.bin/encore dev
    
#### Prepare data for the site
* In MySQL create a database called `DB`
* Create a migration: `php bin/console make:migration`
* Let's update our table `DB`: `php bin/console doctrine:schema:up -f`
* Fill the database with fixtures:
    `$ php bin/console doctrine:fixtures:load`
    
#### Running the Symfony web server
    $ symfony server:start
Now you can go to the example: https://127.0.0.1:8000
