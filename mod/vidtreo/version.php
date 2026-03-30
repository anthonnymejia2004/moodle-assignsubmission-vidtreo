<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026032900;
$plugin->requires  = 2024042200; // Moodle 4.4+
$plugin->component = 'mod_vidtreo';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
$plugin->dependencies = ['local_vidtreo' => 2026032900];
