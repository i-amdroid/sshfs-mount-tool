SSHFS Mount Tool
================

SSHFS Mount Tool (SMT) — CLI tool for manage and mount SSH connections as file system volumes.  
SMT is a wrapper around SSHFS but designed to work with minimal typing.

<p align="center">
  <img src="https://cdn.rawgit.com/i-amdroid/sshfs-mount-tool/2.x/smt.svg?v=2.1.0" width="792">
</p>

Requirements
------------

* Linux/macOS
* PHP CLI >= 7.1
* SSHFS

Instalation
-----------

### Install SSHFS

Ubuntu:

    sudo apt install sshfs

macOS ([osxfuse.github.io](https://osxfuse.github.io/)):

    brew install sshfs

### Install SSHFS Mount Tool

**Option 1: Install with Composer**

    composer global require i-amdroid/sshfs-mount-tool

Check that Composer bin directory exists in your PATH.
Depending on your OS, Composer bin directory can be `$HOME/.composer/vendor/bin` or `$HOME/.config/composer/vendor/bin` ([read more](https://getcomposer.org/doc/03-cli.md#composer-home)).
Depending on your shell, it can be set in `~/.bash_profile`, `~/.bashrc`, `~/.zshrc` etc.

If Composer bin directory does not exist in PATH, add it like this:

    export PATH="$PATH:$HOME/.composer/vendor/bin"

or

    export PATH="$PATH:$HOME/.config/composer/vendor/bin"

**Option 2: Manual installation**

Download latest phar from [Releases](https://github.com/i-amdroid/sshfs-mount-tool/releases).

    chmod 755 smt.phar
    sudo mv smt.phar /usr/local/bin/smt

Usage
-----

**Add connection**

    smt add -v

Connection properties:

| Property   | Description           |
| ---------- | --------------------- |
| `id`       | Connection ID         |
| `title`    | Title                 |
| `server`   | Server                |
| `port`     | Port                  |
| `user`     | Username              |
| `password` | Password              |
| `key`      | Path to key file      |
| `mount`    | Mount directory       |
| `remote`   | Remote directory      |
| `options`  | List of SSHFS options |

Connections can be stored in YAML file in `~/.config/smt/stm.yml` (global) or in `stm.yml` in current directory.

Example of config file:

~~~language-yaml
connections:
  msrv:
    title: myserver
    server: server.com
    port: null
    user: iam
    password: null
    key: ~/.ssh/id_rsa
    mount: ~/mnt/msrv
    remote: /var/www
    options: {  }
~~~

**Mount connection**

    smt <connection id>

or just:

    smt

It will show saved connetcions to choose one or automatically mount, if only one connection exist in config file. 

**Unmount connection**

    smt um <connection id>

or: 

    smt um

**All commands**

`smt [<connection id>, -p <password>]` — Mount connection  
`smt um [<connection id>]` — Unmount connection  
`smt add` — Add connection  
`smt rm [<connection id>]` — Remove connection  
`smt ls [<connection id>]` — List connection properties  
`smt st` — Show status of connections  
`smt config` — Open config file  
`smt -h [<command>]` — Show help  
`smt -V` — Show version  
`smt -i` — Show information about dependencies  

**Limited support commands**

`smt cd [<connection id>]` — Change directory to connection mount directory  
`smt ssh [<connection id>]` — Launch SSH session  

Currently supported only in default Ubuntu terminal (gnome-terminal) and default macOS terminal (Terminal.app).

Launching SSH sessions with password authentification require `sshpass`.

Installing on Ubuntu:

    sudo apt install sshfs

Installing on macOS:

    brew install https://raw.githubusercontent.com/kadwanev/bigboybrew/master/Library/Formula/sshpass.rb

**Config files**

Global config file `~/.config/smt/stm.yml` usefull for storing multiple often used connections.

Config file `stm.yml` in current directory useful for storing per project connections in project folder. If only one connection exist in config file, it will be automatically used as `<connection id>` argument for commands. For example:

`smt` — Mount connection  
`smt um` — Unmount connection  

If SMT run from folder which contain `stm.yml` file, this file will be used as config file. In other case global config file will be used. For using global config file from folder which contain `stm.yml` file, use global option (`-g, --global`).

Since v2.1, SMT supports user preferences in `~/.config/smt/config.yml` file. Preferences allow to customize SSHFS commands, default options, add new terminals, choose editor, default mount folder, default global option state.

Development
-----------

SMT has written on PHP, any contributions welcome.

SMT v2 completely rewriten with Symfony Console component and development still in progres. 

**Future plans**

* More terminals support for `cd` and `ssh` commands (currently can be added in preferences)
* Create autocomplete suggestions
* Create tests
* Pretify info command
* Install with Homebrew

**Build**

1. Clone project
2. Get dependencies via [Composer](https://getcomposer.org/)
  
  ~~~
  composer install
  ~~~

3. Run build process via [Box](https://box-project.github.io/box2/)

  ~~~
  box build -v
  ~~~

