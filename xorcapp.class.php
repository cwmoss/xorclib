<?php

/**
 * XorcApp - Basisklasse für MVC Anwendungen
 *
 * konfiguration etc.
 *
 * @author Robert Wagner
 * @version $Id$
 * @copyright 20sec.net, 28 January, 2006
 * @package xorc
 **/

require_once(__DIR__ . "/mvc/xorc_request.class.php");
require_once(__DIR__ . "/mvc/xorc_response.class.php");
require_once(__DIR__ . "/mvc/xorc_router.class.php");
require_once(__DIR__ . "/mvc/xorc_env.class.php");
require_once(__DIR__ . "/mvc/xorc_controller.class.php");
require_once(__DIR__ . "/mvc/xorc_view.class.php");
require_once(__DIR__ . "/mvc/helper.php");
// require_once("mvc/error.php");
require_once(__DIR__ . "/mvc/xorc_exception.class.php");
require_once(__DIR__ . "/xorc.class.php");
if (!defined('XORCAPP_NODISPATCH')) define('XORCAPP_NODISPATCH', false);
# print memory_get_usage()."\n";
if (!defined('XORCAPP_CLASSIC')) define('XORCAPP_CLASSIC', true);

if (XORCAPP_CLASSIC) require_once(__DIR__ . "/app_classic.php");
