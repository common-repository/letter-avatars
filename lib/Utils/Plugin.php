<?php

/** This file intentionally left without namespace */

use SGI\LtrAv\Core\Bootstrap;

/**
 * Function which retrieves main plugin instance
 * 
 * @return SGI\LtrAv\Core\Bootstrap Main plugin class
 * @since 3.5
 */
function LtrAv()
{

    return Bootstrap::getInstance();

}