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
These commands require a version of node.js of at least 10.13.0
    $ npm install
    $ npm run dev
    
#### Setting .env  
I. Rename .env.example: `mv .env.example .env`

II. Configure `.env`:

    # Changing the development environment to prod
    APP_ENV=dev
    # Build connecting
    DATABASE_URL=mysql://db_user:db_password@db_ip:3306/db_name

    # Configure Swift Mailer
    # For Gmail as a transport, use: "gmail://username:password@localhost"
    # For a generic SMTP server, use: "smtp://localhost:25?encryption=&auth_mode="
    MAILER_URL=gmail://gmail_username:gmail_password@localhost
    
#### Prepare data for the site
* Start the migration: `php bin/console doctrine:migration:migrate`
* Fill the database with fixtures:
    `$ php bin/console doctrine:fixtures:load`
    
#### Running the Symfony web server
    $ symfony server:start
Now you can go to the example: https://127.0.0.1:8000 and log in in three different ways:
1. As a user `1@gmail.com` with a password `12345`
2. Log in with Google
3. Log in with Github
4. Log in as administrator with e-mail `Admin@gmail.com` and password `admin`

