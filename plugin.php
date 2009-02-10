<?php

add_plugin_hook('install', 'csvimport_install');
function csvimport_install()
{
  // Set version number, create database tables, other installation needs.
  define('CSVIMPORT_PLUGIN_VERSION', '1.0.0');
}