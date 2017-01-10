<?php

/**
 * (c)2015 Webuddha.com, The Holodyn Corporation - All Rights Reserved
 **/

defined("WHMCS") or die("This file cannot be accessed directly");

function admingoalmarker_config() {

  // Initialize
    if (!class_exists('wbDatabase'))
      return;

  // Configuration
    $configarray = array(
      "name"            => "Admin Goal Marker",
      "description"     => "Places a goal marker on the top/right of the admin dashboard",
      "version"         => "1.0.0.0",
      "release"         => "stable",
      "release_filter"  => "stable,rc",
      "author"          => "Holodyn, Inc.",
      "language"        => "english",
      "licensekey"      => null,
      "fields"          => array(

        "annual_target " => array (
          "FriendlyName" => 'Annual Target',
          "Type"         => "text",
          "Description"  => 'Dollar value to use when comparing current annual income',
          "Default"      => "100000",
          "Size"         => "64"
          )

        )
      );

  // Complete
    return $configarray;

}
