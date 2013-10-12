# hphpa

**hphpa** is a convenience wrapper for [HHVM](http://github.com/facebook/hiphop-php/)'s static analyzer.

## Installation

### PHP Archive (PHAR)

The easiest way to obtain HPHPA is to download a [PHP Archive (PHAR)](http://php.net/phar) that has all required dependencies of HPHPA bundled in a single file:

    wget https://phar.phpunit.de/hphpa.phar
    chmod +x hphpa.phar
    mv hphpa.phar /usr/local/bin/hphpa

You can also immediately use the PHAR after you have downloaded it, of course:

    wget https://phar.phpunit.de/hphpa.phar
    php hphpa.phar

### Composer

Simply add a dependency on `sebastian/hphpa` to your project's `composer.json` file if you use [Composer](http://getcomposer.org/) to manage the dependencies of your project. Here is a minimal example of a `composer.json` file that just defines a development-time dependency on HPHPA:

    {
        "require-dev": {
            "sebastian/hphpa": "*"
        }
    }

For a system-wide installation via Composer, you can run:

    composer global require 'sebastian/hphpa=2.0'

Make sure you have `~/.composer/vendor/bin/` in your path.

### PEAR Installer

The following two commands (which you may have to run as `root`) are all that is required to install HPHPA using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/hphpa

## Usage Example

    ➜  ~  hphpa --checkstyle hphpa.xml /usr/local/src/code-coverage/PHP
    hphpa 2.0.0 by Sebastian Bergmann.

    Using ruleset /usr/share/pear/data/hphpa/ruleset.xml

    /usr/local/src/code-coverage/PHP/CodeCoverage/Filter.php
      206   Too many arguments in function or method call:
            $this->addFileToWhitelist($file, FALSE)

    Found 1 violation in 1 file (out of 21 total files).

    ➜  ~  cat hphpa.xml
    <?xml version="1.0" encoding="UTF-8"?>
    <checkstyle>
     <file name="/usr/local/src/code-coverage/PHP/CodeCoverage/Filter.php">
      <error line="206"
             message="Too many arguments in function or method call:
                      $this-&gt;addFileToWhitelist($file, FALSE)"
             source="HipHop.PHP.Analysis.TooManyArgument"
             severity="error"/>
     </file>
    </checkstyle>
