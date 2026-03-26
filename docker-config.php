<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = getenv('MOODLE_DB_TYPE') ?: 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST') ?: 'db';
$CFG->dbname    = getenv('MOODLE_DB_NAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER') ?: 'moodle';
$CFG->dbpass    = getenv('MOODLE_DB_PASS') ?: 'moodle';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://localhost:8080';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

// PHPUnit test environment.
$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/var/www/moodledata/phpu_moodledata';

// Debug activado para desarrollo del plugin
$CFG->debug = E_ALL | E_STRICT;
$CFG->debugdisplay = 1;
$CFG->debugstringids = 1;
$CFG->perfdebug = 15;
$CFG->debugpageinfo = 1;

// Configuración de MailHog para desarrollo
$CFG->smtphosts = 'mailhog:1025';
$CFG->smtpsecure = '';
$CFG->smtpauthtype = 'PLAIN';
$CFG->smtpmaxbulk = 1;
$CFG->noreplyaddress = 'noreply@moodle.local';
$CFG->emailonlyfromnoreplyaddress = false;

require_once(__DIR__ . '/lib/setup.php');
