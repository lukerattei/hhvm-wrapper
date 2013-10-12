<?php
/**
 * hhvm-wrapper
 *
 * Copyright (c) 2012-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   hhvm-wrapper
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2012-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 2.0.0
 */

namespace SebastianBergmann\HHVM\CLI;

use SebastianBergmann\FinderFacade\FinderFacade;
use Symfony\Component\Console\Command\Command as AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SebastianBergmann\HHVM\Analyzer;
use SebastianBergmann\HHVM\Result;
use SebastianBergmann\HHVM\Ruleset;
use SebastianBergmann\HHVM\Log\Checkstyle;
use SebastianBergmann\HHVM\Log\Text;

/**
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link      http://github.com/sebastianbergmann/hhvm-wrapper/tree
 * @since     Class available since Release 2.0.0
 */
class AnalyzeCommand extends AbstractCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('analyze')
             ->setDefinition(
                 array(
                   new InputArgument(
                     'values',
                     InputArgument::IS_ARRAY
                   )
                 )
               )
             ->addOption(
                 'names',
                 NULL,
                 InputOption::VALUE_REQUIRED,
                 'A comma-separated list of file names to check',
                 array('*.php')
               )
             ->addOption(
                 'names-exclude',
                 NULL,
                 InputOption::VALUE_REQUIRED,
                 'A comma-separated list of file names to exclude',
                array()
               )
             ->addOption(
                 'exclude',
                 NULL,
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'Exclude a directory from code analysis'
               )
             ->addOption(
                 'ruleset',
                 NULL,
                 InputOption::VALUE_REQUIRED,
                 'Read list of rules to apply from XML file'
               )
             ->addOption(
                 'checkstyle',
                 NULL,
                 InputOption::VALUE_REQUIRED,
                 'Write report in Checkstyle XML format to file'
               );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|integer null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new FinderFacade(
            $input->getArgument('values'),
            $input->getOption('exclude'),
            $this->handleCSVOption($input, 'names'),
            $this->handleCSVOption($input, 'names-exclude')
        );

        $files = $finder->findFiles();

        if (empty($files)) {
            $output->writeln('No files found to scan');
            exit(1);
        }

        $checkstyle  = $input->getOption('checkstyle');
        $quiet       = $output->getVerbosity() == OutputInterface::VERBOSITY_QUIET;
        $rulesetFile = $input->getOption('ruleset');

        if (!$rulesetFile) {
            $rulesetFile = $this->getDefaultRulesetFile();
        }

        try {
            $ruleset = new Ruleset($rulesetFile);
            $rules   = $ruleset->getRules();
        }

        catch (\Exception $e) {
            $output->writeln('Could not read ruleset');
            exit(1);
        }

        $output->writeln('Using ruleset ' . $rulesetFile . "\n");

        $analyzer = new Analyzer;
        $result   = new Result;
        $result->setRules($rules);

        try {
            $analyzer->run($files, $result);
        }

        catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());
            exit(1);
        }

        if (!$quiet) {
            $report = new Text;
            $report->generate($result, 'php://stdout');
        }

        $numFilesWithViolations = 0;
        $numViolations          = 0;

        foreach ($result->getViolations() as $lines) {
            $numFilesWithViolations++;

            foreach ($lines as $violations) {
                $numViolations += count($violations);
            }
        }

        $output->writeln(
            sprintf(
                "%sFound %d violation%s in %d file%s (out of %d total file%s).",
                !$quiet && $numViolations > 0 ? "\n" : '',
                $numViolations,
                $numViolations != 1 ? 's' : '',
                $numFilesWithViolations,
                $numFilesWithViolations != 1 ? 's' : '',
                count($files),
                count($files) != 1 ? 's' : ''
            )
        );

        if ($checkstyle) {
            $report = new Checkstyle;
            $report->generate($result, $checkstyle);
        }

        if ($numViolations > 0) {
            exit(1);
        }
    }

    private function getDefaultRulesetFile()
    {
        if (defined('__HHVM_PHAR__')) {
            return 'phar://' . basename(__HHVM_PHAR__) . '/ruleset.xml';
        }

        else if (strpos('@data_dir@', '@data_dir') === 0) {
            return realpath(
              dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'ruleset.xml'
            );
        }

        return realpath(
          '@data_dir@' . DIRECTORY_SEPARATOR . 'hhvm-wrapper' . DIRECTORY_SEPARATOR . 'ruleset.xml'
        );
    }

    /**
     * @param  Symfony\Component\Console\Input\InputOption $input
     * @param  string                                      $option
     * @return array
     */
    private function handleCSVOption(InputInterface $input, $option)
    {
        $result = $input->getOption($option);

        if (!is_array($result)) {
            $result = explode(',', $result);
            array_map('trim', $result);
        }

        return $result;
    }
}
