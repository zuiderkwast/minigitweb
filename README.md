minigitweb: Single file web UI for git
======================================

A single PHP file web interface to git repositories.

Features
--------

* Side-by-side diff of committed and uncommited changes
* Very similar look to command line git
* Git blame annotations when hovering a line in the side-by-side diffs
* Git log, diff, branch, stash, cherry, help.
* Show differences between branches and arbitrary commits

Usage
-----

Make a symlink to this directory inside an existing git repository, in a
directory accessible from the web.

``` bash
$ cd my_project/www_root
$ ln -s /path/to/minigitweb .
```

Then point your browser to it.

The web server must be able to handle PHP files.

The web server needs read permission to the `.git` directory in the repo. For
`git status`, the web server also needs write access to create a lock file.

This interface is intended as readonly git client. It only executes commands for
listing and showing content. Never git add, commit, push and such things.
Nevertheless, don't use this in a production environment.

How it works
------------

Command line git commands are executed and the output is displayed on a web
page. Links and checkboxes are added without breaking the layout of the command
line output.
