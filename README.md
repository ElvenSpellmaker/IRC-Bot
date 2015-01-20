# WildBot - IRC Bot

***NOTE: This version of the bot is experimental and has been working for two years fine. I am in the process of re-writing the bot for a newer, better, version.***

### Commands

Commands start with a command prefix (default !) and get triggered when someone uses the command prefix with the name of the command.

### Listeners

Listeners listen on specific events from the server and then get triggered when those are fulfilled, such as PRIVMSG.

## Install & Run

### Dependecy

Currently *only* *nix like systems are supported, this is due to UNIX sockets being used as the communication method for the bot's two halves. This includes Cygwin and Cygwin's PHP.

proctitle (optional) - Changes the process title when running as service.

    pecl install proctitle-alpha

### Config

Copy configuration file and customize its content.

    cp config.php config.local.php

### Run

Run as PHP

    php wildbot.php - For the front-end.
	php pWorkhorse.php - For the back-end.

If you want to re-load plug-ins only the back-end needs to be killed and restarted, this will keep your bot in the channels responding to PING requests.

Sample Usage and Output
-------
    <random-user> !say #wildbot hello there
    <wildbot> hello there
    <random-user> !poke #wildbot random-user
    * wildbot pokes random-user
