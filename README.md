# hphpa

**hphpa** is a convenience wrapper for [HipHop](http://github.com/facebook/hiphop-php/)'s static analyzer.

## Installation

There are two supported ways of installing HPHPA.

You can use the [PEAR Installer](http://pear.php.net/manual/en/guide.users.commandline.cli.php) to download and install HPHPA as well as its dependencies. You can also download a [PHP Archive (PHAR)](http://php.net/phar) of HPHPA that has all required dependencies of HPHPA bundled in a single file.

### PEAR Installer

The following two commands (which you may have to run as `root`) are all that is required to install HPHPA using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/hphpa

### PHP Archive (PHAR)

    wget http://pear.phpunit.de/get/hphpa.phar
    chmod +x hphpa.phar

## Usage Example

    ➜  ~  hphpa --checkstyle hphpa.xml /usr/local/src/code-coverage/PHP
    hphpa 1.3.0 by Sebastian Bergmann.

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
