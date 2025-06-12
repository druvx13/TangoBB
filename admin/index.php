<?php

define('BASEPATH', 'Staff');
require_once('../applications/wrapper.php');

if (!$TANGO->perm->check('access_administration')) {
    redirect(SITE_URL);
}//Checks if user has permission to create a thread.
//require_once('template/top.php');
echo $ADMIN->template('top');

echo '<div class="col-md-12">
        <div class="page-header">
          <h1>Administration Panel</h1>
        </div>
      </div>';

// Attempt to fetch version list
$versions_url = 'http://api.codetana.com/iko/version_list.php';
$versions_content = file_get_contents($versions_url);

if ($versions_content === false) {
    // Failed to fetch version list. Log this or handle as appropriate.
    // error_log("Admin Dashboard: Failed to fetch version list from " . $versions_url);
    $versions = ''; // Mimic behavior of @ suppressing error leading to empty $versions
} else {
    $versions = $versions_content;
}

if ($versions != '') {
    $versionList = explode("|", $versions);
    foreach ($versionList as $version) {
        if (version_compare(TANGOBB_VERSION, $version, '<')) {
            $alert = $ADMIN->alert('<p>New version found: ' . $version . '<br /><a href="' . SITE_URL . '/admin/update.php?doUpdate=true&step=1">&raquo; Download Now?</a></p>', 'warning');
        }
    }
}
echo $ADMIN->box(
    'Dashboard',
    'This forum is powered by TangoBB <strong>' . TANGOBB_VERSION . '</strong>.' . (isset($alert) ? $alert : ''),
    '<table class="table">
         <thead>
           <tr>
             <th>Forum Statistic</th>
              <th>Value</th>
            </tr>
         </thead>
         <tbody>
           <tr>
             <td>Threads</td>
             <td>' . stat_threads() . '</td>
           </tr>
          <tr>
             <td>Posts</td>
             <td>' . stat_posts() . '</td>
           </tr>
           <tr>
             <td>Users</td>
             <td>' . stat_users() . '</td>
           </tr>
        </tbody>
       </table>'
);

echo $ADMIN->box(
    'Github and Updates',
    'Fork TangoBB on Github <a href="https://github.com/Codetana/TangoBB">here</a>.<br />
       To keep up with the updates on TangoBB, you can fork/watch the TangoBB Github repository or visit our website at <a href="http://tangobb.com">TangoBB.Com</a> regularly!'
);

//require_once('template/bot.php');
echo $ADMIN->template('bot');

?>
