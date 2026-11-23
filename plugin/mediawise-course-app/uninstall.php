<?php
/**
 * Deleting the plugin leaves uploaded courses in place (wp-content/uploads/mediawise-courses/),
 * so a reinstall picks them up again. Delete that folder by hand to remove them.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
