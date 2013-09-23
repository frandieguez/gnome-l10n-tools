gnome-l10n-tools
================

Tools to help translators of GNOME Project

What is this!
-------------

For now this is  just an experiment that eventually will get a full set of tools
to help GNOME translators and commiters to make their work easier.

I have plans to create some commands like:

-   Fetch real-time stats from Damned Lies
-   Use real-time stats to fetch uncompleted translations and raise an editor to complete them
-   Allow to upload completed translations by handling conflicts and rebases.

Installation
------------

After clone the repository you must install all the dependencies with composer.
Take a look at the next link to get more information about this:

http://getcomposer.org/doc/01-basic-usage.md

    composer.phar install
 
After that you have to copy the config.yaml.sample to config.yaml and change your desired values inside it

    # Which release set do you want to translate
    release_set: gnome-3-10
    .
    # Which language do you want to work with
    language: gl # Galician
    .
    # GNOME Git repository settings
    repository:
        username: your_gnome_username


Usage
-----

You can get all the available commands by checking the build in help

    ./console --help
    ./console command:name --help

Available commands
------------------

### workflow:full

This is the main command, and the one that uses the rest as parts of its workflow.

workflow:full checks the GNOME Damned Lies web service to
fetch real-time translation status for a given release set and language.

If there are new untranslated strings it shows the list of modules
and allows the user to select one of them to translate.
When the user selects one and pofile editor will raise to complete
those strings.
After that it will ask the user to accept changes, it will commit
them to the local repository and finally it will push them to the
GNOME Git repository.

All the workflow will start at the beggining until all the modules
are completed.

### module:translate

module:translate translates a module given the module and branch name.

Fetches the latest changes in module repository, updates transaltions against
current code and opens the pofile editor to complete untranslated strings.

### module:download

module:download clones the GNOME repository
for a given module.

Executes a git clone against the module repository in the
GNOME servers and initializes the available submodules in
the cloned repository.

If the repository is already cloned it gets the latest changes
from the external repository.

### module:commit

module:commit commits available changes to the local repository.

Before committing changes it shows the available changes
and asks the user to accept them.

### module:push

module:push commits available changes to
the local repository.

Before pushing changes to the external repository, module:push
fetches all the changes from there, in order to avoid push errors.

### module:review

module:review checks all the translations for
a given module and language.

This command uses posieve under the hood and all the rules available
for the selected language inside it.

[WARNING] Check if your language has posieve rules for your language.

### module:review:spell

module:review:spell performs an spelling review  in
all the translations for a given module and language.

This command uses posieve under the hood and your available hunspell
dictionary.

[WARNING] Check if your language has available hunspell rules
for your language.

### module:reset

The module:reset discards all the changes in the repository files for a given module.


And... what else?
-----------------
If you find a bug or want to suggest a new command, please let us know in [a ticket](http://github.com/frandieguez/gnome-l10n-tools/issues).
