# CONTENTS OF THIS FILE

 - About Entityqueue
 - Entityqueue API
 - Incompatible modules

## About Entityqueue

The Entityqueue module allows users to create lists of any entity type (content,
users, taxonomy terms, etc). Each queue is implemented as an entity reference
that can hold a single entity type. Items in each list can be manually
reordered.

Entityqueue provides Views integration, by adding an entity queue relationship
to a view, and adding a sort by entity queue position.

## Entityqueue API

Entityqueue uses EntityQueueHandler plugins for each queue. When creating a
queue, the user selects the handler to use for that queue. Entityqueue provides
two handlers by default, "Simple queue" and "Multiple subqueues". Other modules
can provide their own handlers to alter the queue behavior.

## Incompatible modules

Entityqueue is incompatible with the
[Draggable Views](https://www.drupal.org/project/draggableviews) module, which
provides similar functionality.
