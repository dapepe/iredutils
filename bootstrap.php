<?php

/* Initialize includes
--------------------------------------------------- */
define('BASE_DIR'    , dirname(__FILE__).DIRECTORY_SEPARATOR);
define('DOCS_DIR'    , BASE_DIR.'docs'.DIRECTORY_SEPARATOR);
define('VENDOR_DIR'  , BASE_DIR.'vendor'.DIRECTORY_SEPARATOR);
define('CMD_DIR'     , BASE_DIR.'commands'.DIRECTORY_SEPARATOR);
define('BIN_DIR'     , BASE_DIR.'bin'.DIRECTORY_SEPARATOR);
define('TEMPLATE_DIR', BASE_DIR.'template'.DIRECTORY_SEPARATOR);
define('TEMP_DIR'    , BASE_DIR.'temp'.DIRECTORY_SEPARATOR);
define('DIST_DIR'    , BASE_DIR.'dist'.DIRECTORY_SEPARATOR);
define('LIB_DIR'     , BASE_DIR.'lib'.DIRECTORY_SEPARATOR);
define('RES_DIR'     , BASE_DIR.'res'.DIRECTORY_SEPARATOR);

set_include_path(
	LIB_DIR.PATH_SEPARATOR.
	VENDOR_DIR.PATH_SEPARATOR.
	BASE_DIR.PATH_SEPARATOR.
	CMD_DIR.PATH_SEPARATOR
);

/* Include libraries and config
--------------------------------------------------- */
include 'autoload.php';
include 'config.php';
include 'helper.php';
include 'command.php';

/* Initialize settings
--------------------------------------------------- */
if (!defined('VMAIL_USER'))
	define('VMAIL_USER', 'vmail');
if (!defined('DB_VMAIL_HOST'))
	define('DB_VMAIL_HOST', '127.0.0.1');
if (!defined('DB_VMAIL_NAME'))
	define('DB_VMAIL_NAME', 'vmail');
if (!defined('DB_VMAIL_USER'))
	define('DB_VMAIL_USER', 'root');
if (!defined('DB_VMAIL_PASSWORD'))
	define('DB_VMAIL_PASSWORD', '');
if (!defined('DB_CLUEBRINGER_HOST'))
	define('DB_CLUEBRINGER_HOST', '127.0.0.1');
if (!defined('DB_CLUEBRINGER_NAME'))
	define('DB_CLUEBRINGER_NAME', 'cluebringer');
if (!defined('DB_CLUEBRINGER_USER'))
	define('DB_CLUEBRINGER_USER', 'cluebringer');
if (!defined('DB_CLUEBRINGER_PASSWORD'))
	define('DB_CLUEBRINGER_PASSWORD', '');
if (!defined('CMD_PYTHON'))
	define('CMD_PYTHON', 'python');
if (!defined('CMD_SA_LEARN'))
	define('CMD_SA_LEARN', 'sa-learn');
if (!defined('STORAGE_DIR'))
	define('STORAGE_DIR', '/var/vmail');
if (!defined('STORAGE_NODE'))
	define('STORAGE_NODE', 'vmail1');
if (!defined('STORAGE_USER'))
	define('STORAGE_USER', 'vmail');
if (!defined('STORAGE_GROUP'))
	define('STORAGE_GROUP', 'vmail');
