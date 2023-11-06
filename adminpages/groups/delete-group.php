<?php

// Delete a group.
$group_id = (int) $_REQUEST['group_id'];
pmpro_delete_level_group( $group_id );