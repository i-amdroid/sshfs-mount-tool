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
* PHP CLI >= 8.4
* SSHFS

Installation
------------

### Install SSHFS

Ubuntu:

```bash
sudo apt install sshfs
```

macOS ([macFUSE](https://osxfuse.github.io/)):

```bash
brew install --cask macfuse
brew install gromgit/fuse/sshfs-mac
```

### Install SSHFS Mount Tool

**Option 1: Install with Composer**

```bash
composer global require i-amdroid/sshfs-mount-tool
```

Check that the Composer bin directory exists in your PATH.
Depending on your OS, Composer bin directory can be `$HOME/.composer/vendor/bin` or `$HOME/.config/composer/vendor/bin` ([read more](https://getcomposer.org/doc/03-cli.md#composer-home)).
Depending on your shell, it can be set in `~/.bash_profile`, `~/.bashrc`, `~/.zshrc` etc.

If the Composer bin directory does not exist in PATH, add it like this:

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

or

```bash
export PATH="$PATH:$HOME/.config/composer/vendor/bin"
```

**Option 2: Manual installation**

Download the latest phar from [Releases](https://github.com/i-amdroid/sshfs-mount-tool/releases).

```bash
chmod 755 smt.phar
sudo mv smt.phar /usr/local/bin/smt
```

Usage
-----

**Add connection**

```bash
smt add -v
```

Connection properties:

| Property      | Description           |
|---------------|-----------------------|
| `id`          | Connection ID         |
| `title`       | Title                 |
| `server`      | Server                |
| `port`        | Port                  |
| `user`        | Username              |
| `password`    | Password              |
| `key`         | Path to key file      |
| `mount`       | Mount directory       |
| `remote`      | Remote directory      |
| `options`     | List of SSHFS options |
| `ssh_options` | List of SSH options   |

Connections can be stored in YAML file in `~/.config/smt/smt.yml` (global) or in `smt.yml` in current directory.
Files are created with `0600` permissions so passwords stored in them are only readable by you.

`ssh_options` are not prompted during `add` command, so they need to be added to a config file manually.

Example of the config file:

```yaml
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
    options:
      - some_option
      - SomeAnotherOption=yes
    ssh_options:
      - '-o SomeOption=100'
      - '-f SomeFlag 200'
```

**Mount connection**

```bash
smt <connection id>
```

or just:

```bash
smt
````

It will show saved connections to choose one or automatically mount if only one connection exist in a config file.

**Unmount connection**

```bash
smt um <connection id>
```

or:

```bash
smt um
```

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
`smt completion [<shell>]` — Dump the shell completion script (supports connection ID completion)  
`smt shell-init <shell>` — Print a shell wrapper enabling in-place `smt cd`  

**SSH & cd — same tab vs new tab**

`smt ssh [<connection id>, -p <password>, -t/--new-tab]` — Launch SSH session  
`smt cd [<connection id>, -e/--eval]` — Change directory to the connection's mount directory  

By default `smt ssh` attaches to the **current** terminal tab (ssh owns the TTY until you exit it). Pass `-t`/`--new-tab` to open a new tab instead.
New-tab mode is only supported in default Ubuntu terminal (gnome-terminal), default macOS terminal (Terminal.app) and iTerm.

A child process cannot change its parent shell's working directory, so `smt cd` defaults to spawning a new tab. Install the shell wrapper once and `smt cd <id>` will change the **current** tab instead:

```bash
# bash or zsh — add to ~/.bashrc / ~/.zshrc
eval "$(smt shell-init bash)"      # or: zsh

# fish — add to ~/.config/fish/config.fish
smt shell-init fish | source
```

After that, `smt cd msrv` changes your shell's directory in-place.
Under the hood the wrapper calls `smt cd --eval`, which prints a quoted `cd '…'` command to stdout for `eval`. All other subcommands (`mount`, `ssh`, `add`, …) pass through the wrapper unchanged.

Launching SSH sessions with password authentication requires `sshpass`.
Passwords are fed via a 0600 temporary file (`sshpass -f`) instead of the command line, so they are not visible in `ps`.

Installing on Ubuntu:

```bash
sudo apt install sshpass
```

Installing on macOS:

```bash
brew install hudochenkov/sshpass/sshpass
```

**Config files**

Global config file `~/.config/smt/smt.yml` useful for storing multiple often used connections.

Config file `smt.yml` in current directory useful for storing per-project connections in project folder. If only one connection exist in a config file, it is automatically used as `<connection id>` argument for commands. For example:

`smt` — Mount connection  
`smt um` — Unmount connection  

If SMT run from folder which contain `smt.yml` file, this file is used as a config file. Otherwise, the global config file is used. For using a global config file from folder which contains `smt.yml` file, use a global option (`-g, --global`).

SMT also supports user preferences in `~/.config/smt/config.yml` file. Preferences allow customizing SSHFS commands, default options, add new terminals, choose editor, default mount folder, default global option state.

Development
-----------

SMT initially has been written on pure PHP.

V2 has been completely rewritten with the Symfony Console component.

V3 has been upgraded to use Symfony 6.2.

V4 has been upgraded to use Symfony 6.3.

V4.1 has been upgraded to use Symfony 6.4.

V4.2 has been upgraded to use Symfony 7.

V5 is a full rewrite targeting PHP 8.4 and Symfony 8 with a proper layered architecture:

- Procedural `includes/bootstrap.inc` replaced with typed, testable services (`Config`, `Connection`, `Mount`, `Ssh`, `Terminal`, `Process` namespaces).
- All shell concatenation replaced with `Process` argv invocations. Passwords are passed to `sshfs` via stdin and to `ssh` via an ephemeral 0600 tempfile (`sshpass -f`) — never via the command line.
- Commands use the `#[AsCommand]` attribute, `SymfonyStyle`, `ConfirmationQuestion`, and expose shell-completion suggestions for `connection_id`.
- Config/preferences files are now written with `0600` perms and directories with `0700`.
- PHPUnit tests (unit + integration with `CommandTester`) and Mago for formatting / linting / analysis.

Any contributions are welcome.

**Build**

Dev tools (mago, phpunit) come from the main `require-dev`; [Box](https://github.com/box-project/box) lives in an isolated `vendor-bin/box/` sandbox managed by [bamarni/composer-bin-plugin](https://github.com/bamarni/composer-bin-plugin), so its dependency tree is decoupled from the app's.

1. Clone project
2. Install dependencies
    ```bash
    composer install
    ```

3. Install Box (runs once, then cached)
    ```bash
    composer bin box install
    ```

4. Build the phar
    ```bash
    composer box
    ```

**Development tasks**

```bash
composer lint        # mago lint
composer format      # mago format
composer test        # phpunit
```