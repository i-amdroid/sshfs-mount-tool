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
* PHP CLI >= 8.1.0
* SSHFS

Installation
------------

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

It will show saved connections to choose one or automatically mount, if only one connection exist in config file. 

**Unmount connection**

    smt um <connection id>

or: 

    smt um

**All commands**

`smt (mount) [<connection id>, -p <password>]` — Mount connection  
`smt unmount (um) [<connection id>]` — Unmount connection  
`smt add` — Add connection  
`smt remove (rm) [<connection id>]` — Remove connection  
`smt list (ls) [<connection id>]` — List connection properties  
`smt status (st)` — Show status of connections  
`smt config (cfg)` — Open config file  
`smt help (-h) [<command>]` — Show help  
`smt --version (-V)` — Show version  
`smt info (--info, -i)` — Show information about dependencies  
`smt completion [<shell>]` — Dump the shell completion script  

**Limited support commands**

`smt cd [<connection id>]` — Change directory to connection mount directory  
`smt ssh [<connection id>]` — Launch SSH session  

Currently supported only in default Ubuntu terminal (gnome-terminal), default macOS terminal (Terminal.app) and iTerm.

Launching SSH sessions with password authentication require `sshpass`.

Installing on Ubuntu:

    sudo apt install sshpass

Installing on macOS:

    brew install https://raw.githubusercontent.com/kadwanev/bigboybrew/master/Library/Formula/sshpass.rb

**Config files**

Global config file `~/.config/smt/stm.yml` useful for storing multiple often used connections.

Config file `stm.yml` in current directory useful for storing per project connections in project folder. If only one connection exist in config file, it will be automatically used as `<connection id>` argument for commands. For example:

`smt` — Mount connection  
`smt um` — Unmount connection  

If SMT run from folder which contain `stm.yml` file, this file will be used as config file. Otherwise, global config file will be used. For using global config file from folder which contains `stm.yml` file, use global option (`-g, --global`).

Since v2.1, SMT supports user preferences in `~/.config/smt/config.yml` file. Preferences allow to customize SSHFS commands, default options, add new terminals, choose editor, default mount folder, default global option state.

Development
-----------

SMT initially has been written on pure PHP.

V2 has been completely rewritten with Symfony Console component.

V3 has been upgraded to use Symfony 6.2.

V4 has been upgraded to use Symfony 6.3.

Any contributions are welcome.

**Future plans**

* Add tests
* Prettify info command
* Install with Homebrew

**Build**

1. Clone project
2. Get dependencies via [Composer](https://getcomposer.org/)
  
  ~~~
  composer install
  ~~~

3. Run build process via [Box](https://github.com/box-project/box)

  ~~~
  box compile
  ~~~

