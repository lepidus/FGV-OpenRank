<?php

// Both versions declare the same global classes, so loading this one while the
// previous plugin is still installed is a fatal error on every page.
if (is_dir(dirname(__DIR__) . '/rankingPlugin')) {
    require_once('FgvOpenRankPendingRemovalPlugin.inc.php');
    return new FgvOpenRankPendingRemovalPlugin();
}

require_once('FgvOpenRankPlugin.inc.php');
return new FgvOpenRankPlugin();
