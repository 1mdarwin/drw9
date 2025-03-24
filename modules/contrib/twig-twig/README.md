# Twig input filter

Provides the Twig template engine as an input filter.

## Usage

* Go to admin/config/content/formats and activate Twig as an input filter on a
  text format.
* Create a node using that Twig enabled text format
* Write ```{{ 'now'|date('F d, Y') }}``` to test it out.


## D8 @TODO

* Twig templates are stored as configuration, so you can edit them from the UI,
  and export them.
* Syntax highlighting for Twig in WYSIWYG editors using the Twig input filter.

## Future Development

I'm interested in extending this module to integrate Drupal and Twig in other
ways. Feel free to post feature request issues with your thoughts and/or code.
In particular, I'd like to add support for Tokens as Twig variables, and set up
the Twig sandbox to restrict privileges for users having access to Twig-enabled
input formats.
