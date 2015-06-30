# Blueman

[![Author](http://img.shields.io/badge/by-@kielabokkie-lightgrey.svg?style=flat-square)](https://twitter.com/kielabokkie)
[![Build Status](http://img.shields.io/travis/pixelfusion/blueman/master.svg?style=flat-square)](https://travis-ci.org/pixelfusion/blueman)
[![Code Coverage](https://img.shields.io/codecov/c/github/pixelfusion/blueman.svg?style=flat-square)](https://codecov.io/github/pixelfusion/blueman)
[![Codacy Badge](https://img.shields.io/codacy/a3d1afc3e17b4af3adf9b5543cb81959.svg?style=flat-square)](https://www.codacy.com/public/pixelfusion/blueman)
[![Packagist Version](https://img.shields.io/packagist/v/pixelfusion/blueman.svg?style=flat-square)](https://packagist.org/packages/pixelfusion/blueman)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Gitter](https://img.shields.io/badge/gitter-join%20chat-2DCD76.svg?style=flat-square)](https://gitter.im/pixelfusion/blueman)

Convert an [API Blueprint](http://apiblueprint.org) JSON file into a [Postman](http://www.getpostman.com) collection.

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
$ blueman self-update
```

## Install using Composer

Blueman can also be installed using Composer if you prefer that:

```sh
$ composer create-project pixelfusion/blueman your-project-name
```

## Usage

To generate a Postman collection you run the `convert` command. For example, if the API Blueprint JSON file you generated is called `api.json` you would execute the following command:

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

### Specify output file

By default Blueman will create a `collection.json` file in the current directory. You can save the file to a different folder and change the output filename by passing the output parameter:

```sh
$ blueman convert api.json --output=/Users/wouter/Desktop/postman_collection.json
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
