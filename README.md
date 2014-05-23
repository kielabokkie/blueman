# Blueman

Convert a [API Blueprint](http://apiblueprint.org) JSON file into a [Postman](http://www.getpostman.com) collection.

## Installation

### Phar (recommended)

    curl -sS https://blueman.pixelfusion.co.nz/installer | php

### Composer:

    composer create-project pixelfusion/blueman your-project-name

## Usage

To generate the Postman collection you run the `convert` command. For example, if the API Blueprint JSON file you generated is called `api.json` you would execute the following command:

    ./bin/console convert api.json

This command will generate a file called `collection.json`, which is the file you can import in Postman.

By default it will look for the JSON file in the same location as where you are running the command. If your file is somewhere else on your computer you need to specify the path:

    ./bin/console convert api.json --path='/Users/wouter/Desktop'
