# Migrate Run

_The Migrate Drush Runner_

## Credits

This is a fork of
[Migrate Tools](https://www.drupal.org/project/migrate_tools). All credits goes
to [Migrate Tools](https://www.drupal.org/project/migrate_tools) team.

## Description

Unlike [Migrate Tools](https://www.drupal.org/project/migrate_tools), this
module has no dependency on
[Migrate Plus](https://www.drupal.org/project/migrate_plus) and is using the
core discovery, by discovering only migrations stored in `migrations/`
directory.
   
The module provides only the Drush runner, without any other UI features
included in [Migrate Tools](https://www.drupal.org/project/migrate_tools). The
runner doesn't support migration groups, which are part of
[Migrate Plus](https://www.drupal.org/project/migrate_plus), but with
`drush migrate-status`, by using the `--tag` option, migrations can be grouped
and filtered by tags.

Comparing to [Migrate drush](https://www.drupal.org/project/migrate_drush),
another thin runner, Migrate Run, offers all the benefits of
[Migrate Tools](https://www.drupal.org/project/migrate_tools) like
`drush migrate-status` and the very useful filters on import `--idlist` or
`--limit`.

## Install

Make sure [Migrate Tools](https://www.drupal.org/project/migrate_tools) is
uninstalled because this module uses the same Drush commands. Install it like
any other Drupal module.

## Usage

Show the status of all migrations:
```bash
$ drush migrate-status
 Migration          Status  Total  Imported  Unprocessed
 article            Idle    1250   1101      149
 image              Idle    3402   0         3402
 user               Idle    44     42        2
 user_picture       Idle    44     33        11
```

Show the status of all migrations, grouped by tag:
```bash
$ drush ms --tag
 Tag: content       Status  Total  Imported  Unprocessed
 article            Idle    1250   1101      149

 Tag: file
 image              Idle    3402   0         3402
 user_picture       Idle    44     33        11

 Tag: user
 user               Idle    44     42        2
 user_picture       Idle    44     33        11
```

Show the status of migrations tagged with `file` and `content`:
```bash
$ drush ms --tag=file,content
 Tag: content       Status  Total  Imported  Unprocessed
 article            Idle    1250   1101      149

 Tag: file
 user_picture       Idle    44     33        11
```

Run migrations `user_picture` and `user`:
```bash
$ drush migrate-import user_picture,user
Processed 44 items (33 created, 0 updated, 11 failed, 0 ignored) - done with 'user_picture'
Processed 44 items (42 created, 0 updated, 2 failed, 0 ignored) - done with 'user'
```
Run migration `user` by executing all its dependencies (`user` depends on
`user_picture`):
```bash
$ drush mi user --execute-dependencies
Processed 44 items (33 created, 0 updated, 11 failed, 0 ignored) - done with 'user_picture'
Processed 44 items (42 created, 0 updated, 2 failed, 0 ignored) - done with 'user'
```

Run migrations tagged with `user`:
```bash
$ drush mi--tag=user,content
Processed 44 items (33 created, 0 updated, 11 failed, 0 ignored) - done with 'user_picture'
Processed 44 items (42 created, 0 updated, 2 failed, 0 ignored) - done with 'user'
Processed 1250 items (1101 created, 0 updated, 149 failed, 0 ignored) - done with 'article'
```

Import only first 10 records (useful during migration development):
```bash
$ drush mi user --limit=10
Processed 10 items (10 created, 0 updated, 0 failed, 0 ignored) - done with 'user'
```

Import only records having the source ID 123, 23 or 3 (useful during migration
development):
```bash
$ drush mi user --idlist=123,23,3
Processed 3 items (3 created, 0 updated, 0 failed, 0 ignored) - done with 'user'
```

Use Drush help to get all usage options:
```bash
$ drush migrate-rollback --help
$ drush migrate-status --help
```
