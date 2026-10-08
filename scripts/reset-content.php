<?php
/**
 * Permanently deletes ALL entries and assets (files included). For wiping the
 * demo content before a client's real content goes in — or for starting over.
 *
 *   ddev exec php scripts/reset-content.php --yes
 */

require dirname(__DIR__) . '/bootstrap.php';

/** @var craft\console\Application $app */
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use craft\elements\Asset;
use craft\elements\Entry;

if (!in_array('--yes', $argv, true)) {
    fwrite(STDERR, "This deletes every entry and asset. Re-run with --yes to confirm.\n");
    exit(1);
}

$elements = Craft::$app->getElements();

$entries = Entry::find()->status(null)->drafts(null)->revisions(null)->all();
foreach ($entries as $entry) {
    $elements->deleteElement($entry, true);
}
echo count($entries) . " entries deleted.\n";

$assets = Asset::find()->status(null)->all();
foreach ($assets as $asset) {
    $elements->deleteElement($asset, true);
}
echo count($assets) . " assets deleted.\n";

Craft::$app->getGc()->run(true);
echo "Done.\n";
