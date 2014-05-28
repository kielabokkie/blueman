# Blueman

Convert an [API Blueprint](http://apiblueprint.org) JSON file into a [Postman](http://www.getpostman.com) collection.

[![Build Status](https://travis-ci.org/pixelfusion/blueman.svg?branch=master)](https://travis-ci.org/pixelfusion/blueman)

## Install Blueman as a Phar (recommended)

Use the installer to download Blueman:

```sh
$ curl -sS http://blueman.pixelfusion.co.nz/installer.php | php
```

This will grab the latest version of Blueman and copy it to your current directory. We recommend moving it to the bin directory so you can run the Blueman from anywhere:

```sh
$ mv blueman.phar /usr/local/bin/blueman
```

Whenever there is a new version of Blueman you can run `self-update` to update to the latest version:

```sh
blueman self-update
```

## Install using Composer

Blueman can also be installed using Composer if you prefer that:

```sh
$ composer create-project pixelfusion/blueman your-project-name
```

## Usage

To generate the Postman collection you run the `convert` command. For example, if the API Blueprint JSON file you generated is called `api.json` you would execute the following command:

```sh
$ blueman convert api.json
```

Note: If you installed Blueman using Composer you have to replace `blueman` with `./bin/console` in all the example commands, e.g.:

```sh
$ ./bin/console convert api.json
```

This command will generate a file called `collection.json`, which you can import in Postman.

By default Blueman will look for the JSON file in the same location as where you are running the command. If your file is in another directory, you need to specify the path:

```sh
$ blueman convert api.json --path=/Users/wouter/Desktop
```

### Setting the host

The base host of your API can be set in a couple of different ways.

First of all you can specify it in your API Blueprint as metadata by adding the following line to the top of your API Blueprint Markdown file:

    HOST: https://api.example.com/v1

If your Markdown file doesn't have the host metadata or if you want to overwrite it, you can specify the host when executing the `convert` command:

```sh
$ blueman convert api.json --host=https://api.example.com/v1
```

Lastly, if you don't do either of the above you'll be prompted to set the host when you execute the `convert` command.

**TIP:** If you use environments in Postman to test your API on different servers (sandbox, user acceptance testing, etc.) you can use the host option to specify your placeholder keys that you've setup in Postman's environment config:

```sh
$ blueman convert api.json --host=https://api.{{host}}/v1
```
