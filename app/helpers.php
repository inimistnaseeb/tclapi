<?php
define('NO_DUE_DATE', '2050-01-01');
define('STATUS_COMPLETE', 3);
define('STATUS_NOT_STARTED', 1);
define('STATUS_INPROCESS', 2);
define('STATUS_OVERDUE', 4);
define('STATUS_INTIAL', 1);
define('STATUS_ACTIVE', 1);
define('STATUS_PENDING', 2);
define('STATUS_INACTIVE', 3);
define('RISK_LOW', 1);
define('RISK_MODERATE', 2);
define('RISK_HIGH', 3);
define('ADMIN_EMAIL', 'swilliams@thecontrolist.com');
define('ADMIN_TITLE', 'SAAS Admin');
define('DEFAULT_FROM', ADMIN_EMAIL);
//define('USERFILES', WWW_ROOT . 'files');
//define('SCODATASPATH', WWW_ROOT . DS . 'sco' . DS . 'data');
//define('CONTROLFILES', WWW_ROOT . 'files');
//define('__SYSTEM_SITE_NAME', 'TheControlist: A Williams/Jackson Project');
define('MAX_REGULAR_USERS', 5); //max. no of users can be added as defined.
define('MAX_GUEST_USERS', 10);
define('SUPER_ADMIN_USERNAME', 'Master');
define('SUPER_ADMIN_PASSWORD', 'master#1357');

//User Roles Constant
define('ADMIN', 1);
define('USER', 2);
define('SUPER_ADMIN', 5);
define('GUEST_STUDENT', 6);
define('SUBCONTRACTOR', 7);


//Email for Tasks/Controls Comments or Complete
define('NO_EMAIL', 0);
define('EMAIL_CREATOR_ONLY', 1);
define('EMAIL_EVERYONE', 2);
define('EMAIL_ASSIGNEE', 3);
define('SITE_ROOT', dirname(__FILE__));

if (!function_exists('pr')) {
    function pr($d, $bt = null)
    {
        $appPath = SITE_ROOT;
        $bt = $bt ?: debug_backtrace();
        $caller = array_shift($bt);
        $relativePath = str_replace($appPath, '', $caller['file']);
        $file_line = $relativePath . "(line " . $caller['line'] . ")\n";
        print($file_line . "\n");
        print("<pre>");
        print_r($d);
        print("</pre>");
        print("\n");
    }
}
