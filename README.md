# Blueman

Convert an [API Blueprint](http://apiblueprint.org) JSON file into a [Postman](http://www.getpostman.com) collection.

## Install Blueman as a Phar (recommended)

Use the installer to download Blueman:

    ``` sh
    $ curl -sS http://blueman.pixelfusion.co.nz/installer.php | php
    ```

This will grab the latest version of Blueman and copy it to your current directory. We recommend moving it to the bin directory so you can run the Blueman from anywhere:

    ``` sh
    $ mv blueman.phar /usr/local/bin/blueman
    ````

## Install using Composer

Blueman can also be installed using Composer for you prefer that:

	``` sh
    $ composer create-project pixelfusion/blueman your-project-name
    ```

## Usage

To generate the Postman collection you run the `convert` command. For example, if the API Blueprint JSON file you generated is called `api.json` you would execute the following command:

    ``` sh
    $ blueman convert api.json
    ```

Or if you installed Blueman using Composer:

	``` sh
    $ ./bin/console convert api.json
    ```

This command will generate a file called `collection.json`, which you can import in Postman.

By default Blueman will look for the JSON file in the same location as where you are running the command. If your file is in another directory, you need to specify the path:

	``` sh
    $ blueman convert api.json --path='/Users/wouter/Desktop'
    ```

Or for a Composer install:

	``` sh
    $ ./bin/console convert api.json --path='/Users/wouter/Desktop'
    ```
