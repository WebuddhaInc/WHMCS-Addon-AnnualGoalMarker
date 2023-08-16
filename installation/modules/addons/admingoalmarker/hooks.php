<?php

/**
 * Include wbDatabase
 */
  if (defined('ROOTDIR'))
    require_once ROOTDIR . '/modules/addons/wbDatabase.php';

/**
 * Include Widgets
 */
  require_once('widgets/goalmarker.php');

/**
 * Render Homepage Widget
 */
  add_hook('AdminHomepage', 1, function ( $vars )
  {

    /**
     * Database Handler
     */
    $db = wbDatabase::getInstance();

    /**
     * Pull Module Configuration
     */
    $db->runQuery("
      SELECT `setting`, `value`
      FROM `tbladdonmodules`
      WHERE `module` = 'admingoalmarker'
      ");
    $settingRows = $db->getRows();
    foreach ($settingRows AS $settingRow) {
      $setting[ $settingRow['setting'] ] = $settingRow['value'];
    }
    $float_display = (isset($setting['float_display']) ? $setting['float_display'] == 'Yes' : true);

    /**
     * Float Display
     */
    if (!$float_display) return;

    /**
     * Pull Report from Widget
     */
    $report = widget_admingoalmarker_goalmarker(array('customClass' => 'headFloat'));

    ob_start();
    ?>
    <style>
      .addon-html-output-container {
        position: relative;
      }
      .admingoalmarker_report.headFloat {
        position: absolute;
        top: -52px;
        right: 0;
        text-align: center;
      }
      .admingoalmarker_report.headFloat .target_report {
        font-size: 1.2em;
        color: white;
        background: red;
        padding: 5px 10px;
        border-radius: 4px;
      }
      .admingoalmarker_report.headFloat .target_report.on,
      .admingoalmarker_report.headFloat .target_report.ahead {
        background: green;
      }
      .admingoalmarker_report.headFloat .target_report > div {
        display: inline-block;
        margin-right: 10px;
        padding-right: 15px;
        border-right: 2px solid white;
      }
      .admingoalmarker_report.headFloat .target_report > div:last-child {
        border-right: none;
      }
      .admingoalmarker_report.headFloat .collection_report {
        display: inline-block;
        font-size: 1.0em;
        color: white;
        background: gray;
        padding: 5px 10px;
        margin: 0 6px;
        border-radius: 0 0 4px 4px;
      }
      .admingoalmarker_report.headFloat .collection_report > div {
        display: inline-block;
        margin-right: 5px;
        padding-right: 10px;
        border-right: 2px solid white;
      }
      .admingoalmarker_report.headFloat .collection_report > div:last-child {
        border-right: none;
      }
    </style>
    <?php
    echo $report['content'];

    return ob_get_clean();

  });

