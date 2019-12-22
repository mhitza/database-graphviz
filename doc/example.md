Using the employees database schema available at [datacharmer/test_db](https://github.com/datacharmer/test_db). While the
current implementation does not explicitly support views, in the simple section you will see two additional tables as
opposed to the record section. Those are views that are shown by default by the `SHOW TABLES` command.

## Simple
### Using the default `dot` layout engine:

```
$ database-graphviz generate simple --dbname=employees ... > schema.gv
$ dot -Tpng schema.gv > schema.png
```

![simple generation with dot layout filter](schema-simple-dot.png)

### Using the `sfdp` layout engine

```
$ database-graphviz generate simple --dbname=employees ... > schema.gv
$ dot -Tpng -Ksfdp schema.gv > schema.png
```

![simple generation with sfdp layout filter](schema-simple-sfdp.png)

## Record
### Using the default `dot` layout engine:

```
$ database-graphviz generate record --dbname=employees ... > schema.gv
$ dot -Tpng schema.gv > schema.png
```

![record generation with dot layout filter](schema-record-dot.png)

### Using the `sfdp` layout engine

```
$ database-graphviz generate record --dbname=employees ... > schema.gv
$ dot -Tpng -Ksfdp schema.gv > schema.png
```

![record generation with sfdp layout filter](schema-record-sfdp.png)
