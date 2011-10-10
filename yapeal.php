#!/usr/bin/php -Cq
<?php
/**
 * Used to get information from Eve-online API and store in database.
 *
 * This script expects to be ran from a command line or from a crontab job. The
 *  script can optionally be pass a config file name with -c option.
 *
 * PHP version 5
 *
 * LICENSE: This file is part of Yet Another Php Eve Api library also know
 * as Yapeal.
 *
 *  Yapeal is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Yapeal is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with Yapeal. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Michael Cummings <mgcummings@yahoo.com>
 * @copyright  Copyright (c) 2008-2011, Michael Cummings
 * @license    http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @package    Yapeal
 * @link       http://code.google.com/p/yapeal/
 * @link       http://www.eveonline.com/
 * @since      revision 561
 */
/**
 * @internal Allow viewing of the source code in web browser.
 */
if (isset($_REQUEST['viewSource'])) {
  highlight_file(__FILE__);
  exit();
};
/**
 * @internal Only let this code be ran in CLI.
 */
if (PHP_SAPI != 'cli') {
  header('HTTP/1.0 403 Forbidden', TRUE, 403);
  $mess = basename(__FILE__) . ' only works with CLI version of PHP but tried';
  $mess = ' to run it using ' . PHP_SAPI . ' instead';
  die($mess);
};
/**
 * @internal Only let this code be ran directly.
 */
$included = get_included_files();
if (count($included) > 1 || $included[0] != __FILE__) {
  $mess = basename(__FILE__) . ' must be called directly and can not be included';
  fwrite(STDERR, $mess . PHP_EOL);
  fwrite(STDOUT, 'error' . PHP_EOL);
  exit(1);
};
/**
 * Define short name for directory separator which always uses unix '/'.
 */
define('DS', '/');
/**
 * We know we are in the 'base' directory might as well set constant.
 */
// Used to overcome path issues caused by how script is ran on server.
$dir = str_replace('\\', DS, dirname(__FILE__));
define('YAPEAL_BASE', $dir . DS);
// Pull in Yapeal revision constants.
require_once YAPEAL_BASE . 'revision.php';
/**
 * Since we know that we are at 'base' directory we know where 'inc' should be
 * as well.
 */
define('YAPEAL_INC', YAPEAL_BASE . DS . 'inc' . DS);
// Pull in path constants.
require_once YAPEAL_INC . 'common_paths.php';
// If function getopts available get any command line parameters.
if (function_exists('getopt')) {
  $iniFile = parseCommandLineOptions($argv);
} else {
  /**
   * @var mixed Holds path and name of ini configuration file when set.
   */
  $iniFile = NULL;
};// if function_exists getopt ...
require_once YAPEAL_INC . DS . 'common_backend.php';
try {
  /**
   * Give ourself a 'soft' limit of 10 minutes to finish.
   */
  define('YAPEAL_MAX_EXECUTE', strtotime('10 minutes'));
  /**
   * This is used to have the same time on all APIs that error out and need to
   * be tried again.
   */
  define('YAPEAL_START_TIME', gmdate('Y-m-d H:i:s', YAPEAL_MAX_EXECUTE));
  /* ************************************************************************
   * Generate section list
   * ************************************************************************/
  $sectionList = FilterFileFinder::getStrippedFiles(YAPEAL_CLASS, 'Section');
  if (count($sectionList) == 0) {
    $mess = 'No section classes were found check path setting';
    trigger_error($mess, E_USER_ERROR);
  };
  //$sectionList = array_map('strtolower', $sectionList);
  // Randomize order in which API sections are tried if there is a list.
  if (count($sectionList) > 1) {
    shuffle($sectionList);
  };
  $sql = 'select `section`';
  $sql .= ' from `' . YAPEAL_TABLE_PREFIX . 'utilSections`';
  try {
    $con = YapealDBConnection::connect(YAPEAL_DSN);
    $result = $con->GetCol($sql);
  }
  catch(ADODB_Exception $e) {
    // Nothing to do here was already report to logs.
  }
  $result = array_map('ucfirst', $result);
  if (count($result) == 0) {
    $mess = 'No sections were found in utilSections check database';
    trigger_error($mess, E_USER_ERROR);
  };
  $sectionList = array_intersect($sectionList, $result);
  // Now take the list of sections and call each in turn.
  foreach ($sectionList as $sec) {
    $class = 'Section' . $sec;
    try {
      $instance = new $class();
      $instance->pullXML();
    }
    catch (ADODB_Exception $e) {
      // Do nothing use observers to log info
    }
    // Going to sleep for a second to let DB time to flush etc between sections.
    sleep(1);
  };// foreach $section ...
  /* ************************************************************************
   * Final admin stuff
   * ************************************************************************/
  // Release all the ADOdb connections.
  YapealDBConnection::releaseAll();
  // Reset cache intervals
  CachedInterval::resetAll();
}
catch (Exception $e) {
  require_once YAPEAL_CLASS . 'YapealErrorHandler.php';
  $mess = 'Uncaught exception in ' . basename(__FILE__);
  YapealErrorHandler::print_on_command($mess);
  YapealErrorHandler::elog($mess, YAPEAL_ERROR_LOG);
  $mess =  'EXCEPTION: ' . $e->getMessage() . PHP_EOL;
  if ($e->getCode()) {
    $mess .= '     Code: ' . $e->getCode() . PHP_EOL;
  };
  $mess .= '     File: ' . $e->getFile() . '(' . $e->getLine() . ')' . PHP_EOL;
  $mess .= '    Trace:' . PHP_EOL;
  $mess .= $e->getTraceAsString() . PHP_EOL;
  $mess .= str_pad(' END TRACE ', 30, '-', STR_PAD_BOTH);
  YapealErrorHandler::print_on_command($mess);
  YapealErrorHandler::elog($mess, YAPEAL_ERROR_LOG);
}
exit;
/**
 * Function used to parser command line options.
 *
 * @param array $argv Array of arguments passed to script.
 *
 * @return mixed Returns path and name of configuration file if set.
 */
function parseCommandLineOptions($argv) {
  $shortOpts = 'c:hV';
  if (version_compare(PHP_VERSION, '5.3.0', '>=')
    || strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
    $longOpts = array('config:', 'help', 'version');
    $options = getopt($shortOpts, $longOpts);
  } else {
    $options = getopt($shortOpts);
  };
  if (empty($options)) {
    return NULL;
  };
  $file = NULL;
  $exit = FALSE;
  foreach ($options as $opt => $value) {
    switch ($opt) {
      case 'c':
      case 'config':
        $file = $value;
        break;
      case 'h':
      case 'help':
        usage($argv);
        // Fall through is intentional.
      case 'V':
      case 'version':
        $mess = basename($argv[0]);
        if (YAPEAL_VERSION != 'svnversion') {
          $mess .= ' ' . YAPEAL_VERSION . ' (' . YAPEAL_STABILITY . ') ';
          $mess .= YAPEAL_DATE . PHP_EOL . PHP_EOL;
        } else {
          $rev = str_replace(array('$', 'Rev:'), '', '$Rev$');
          $date = str_replace(array('$', 'Date:'), '', '$Date$');
          $mess .= $rev . '(export)' . $date . PHP_EOL . PHP_EOL;
        };
        $mess .= 'Copyright (c) 2008-2011, Michael Cummings.' . PHP_EOL;
        $mess .= 'License LGPLv3+: GNU LGPL version 3 or later';
        $mess .= ' <http://www.gnu.org/copyleft/lesser.html>.' . PHP_EOL;
        $mess .= 'See COPYING and COPYING-LESSER for more details.' . PHP_EOL;
        $mess .= 'This program comes with ABSOLUTELY NO WARRANTY.' . PHP_EOL . PHP_EOL;
        fwrite(STDOUT, $mess);
        $exit = TRUE;
        break;
    };// switch $opt
  };// foreach $options...
  if ($exit == TRUE) {
    exit;
  };
  return $file;
};// function parseCommandLineOptions
/**
 * Function use to show the usage message on command line.
 *
 * @param array $argv Array of arguments passed to script.
 */
function usage($argv) {
  $mess = PHP_EOL . 'Usage: ' . basename($argv[0]);
  $mess .= ' [OPTION]...' . PHP_EOL . PHP_EOL;
  $mess .= 'OPTIONs:' . PHP_EOL;
  $mess .= str_pad('  -c, --config=FILE', 25);
  $mess .= 'Read custom configuration from FILE.';
  $mess .= " File must be in 'ini' format." . PHP_EOL;
  $mess .= str_pad('  -h, --help', 25);
  $mess .= 'Show this help.' . PHP_EOL;
  $mess .= str_pad('  -V, --version', 25);
  $mess .= 'Show version and licensing information.' . PHP_EOL . PHP_EOL;
  fwrite(STDOUT, $mess);
};// function usage
?>
