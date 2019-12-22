# database-graphviz
A simple command line tool that can generate a Graphviz graph based on your MySQL/MariaDB schema relationships

## Motivation

I just needed a simple tool to dump a Graphviz graph for a database on a project I was working on. The existing doctrine
bundle for symfony didn't register as a command, and I'd rather have a tool not dependant on any other integration for this
simple task.

## Installation

Either add it as dev dependency to your project and execute it with `./vendor/bin/database-graphviz`
```
$ composer require --dev mhitza/database-graphviz
```

Or download the packaged executable from the [GitHub release tab](https://github.com/mhitza/database-graphviz/releases).

## Usage

See the [example page](doc/example.md) for some steps to get you started.
