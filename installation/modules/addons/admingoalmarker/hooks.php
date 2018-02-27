<?php

/**
 * Include wbDatabase
 */
  if (defined('ROOTDIR'))
    require_once ROOTDIR . '/modules/addons/wbDatabase.php';

/**
 * Render Homepage Widget
 */
  add_hook('AdminHomepage', 1, function ( $vars )
  {

    /**
     * Required
     */
    if (!class_exists('wbDatabase'))
      return;

    /**
     * Permission
     * Need to block based on Module Permission
     */

    /**
     * Language
     */
    $language = isset($GLOBALS['admin']['language']) ? $GLOBALS['admin']['language'] : 'english';
    if (file_exists(__DIR__.'/lang/' . $language . '.php'))
      include __DIR__.'/lang/' . $language . '.php';
    else
      include __DIR__.'/lang/english.php';

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
    $annual_target  = (real)(isset($setting['annual_target']) ? $setting['annual_target'] : 100000);
    $invoice_filter = (array)(isset($setting['invoice_filter']) ? $setting['invoice_filter'] : array('Paid', 'Unpaid', 'Overdue', 'Collections'));

    /**
     * Pull Total
     */
    $db->runQuery("
      SELECT SUM(`total`)
      FROM `tblinvoices` AS `invoice`
      WHERE `status` IN ('". implode("','", $invoice_filter) ."')
        AND `date` >= '". date('Y') ."-01-01'
      ");
    $invoiceTotal = $db->getValue();

    /**
     * Pull Total Paid
     */
    $db->runQuery("
      SELECT SUM(`total`)
      FROM `tblinvoices` AS `invoice`
      WHERE `status` = 'Paid'
        AND `date` >= '". date('Y') ."-01-01'
        -- AND `datepaid` >= '". date('Y') ."-01-01'
      ");
    $invoiceTotalPaid = $db->getValue();

    $db->runQuery("
      SELECT SUM(`accounts`.`fees`)
      FROM `tblaccounts` AS `accounts`
      RIGHT JOIN `tblinvoices` AS `invoices` ON `invoices`.`id` = `accounts`.`invoiceid`
      WHERE `invoices`.`status` = 'Paid'
        AND `invoices`.`datepaid` >= '". date('Y') ."-01-01'
      ");
    $accountFeesTotal = $db->getValue();

    $db->runQuery("
      SELECT SUM(`accounts`.`amountin`)
      FROM `tblaccounts` AS `accounts`
      RIGHT JOIN `tblinvoices` AS `invoices` ON `invoices`.`id` = `accounts`.`invoiceid`
      WHERE `accounts`.`date` >= '". date('Y') ."-01-01'
      ");
    $transactionAmountTotal = $db->getValue();

    $current_year       = (int)date('Y');
    $current_month      = (int)date('m');
    $current_day        = (int)date('d');
    $current_daysinmo   = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $current_target     = (($current_month - 1) + ($current_day / $current_daysinmo)) * ($annual_target / 12);
    $current_position   = round(($invoiceTotal / $current_target) * 100);
    $current_status     = (abs($current_position) > 100 ? "ahead" : (abs($current_position) < 100 ? "behind" : "on"));

    ob_start();
    ?>
    <style>
      .addon-html-output-container {
        position:relative;
      }
      .admingoalmarker_report {
        position: absolute;
        top: -52px;
        right: 0;
        text-align: center;
      }
      .admingoalmarker_report .target_report {
        font-size: 1.2em;
        color: white;
        background: red;
        padding: 5px 10px;
        border-radius: 4px;
      }
      .admingoalmarker_report .target_report.on,
      .admingoalmarker_report .target_report.ahead {
        background: green;
      }
      .admingoalmarker_report .target_report > div {
        display: inline-block;
        margin-right: 10px;
        padding-right: 15px;
        border-right: 2px solid white;
      }
      .admingoalmarker_report .target_report > div:last-child {
        border-right: none;
      }
      .admingoalmarker_report .collection_report {
        display: inline-block;
        font-size: 1.0em;
        color: white;
        background: gray;
        padding: 5px 10px;
        margin: 0 6px;
        border-radius: 0 0 4px 4px;
      }
      .admingoalmarker_report .collection_report > div {
        display: inline-block;
        margin-right: 5px;
        padding-right: 10px;
        border-right: 2px solid white;
      }
      .admingoalmarker_report .collection_report > div:last-child {
        border-right: none;
      }
    </style>
    <div class="admingoalmarker_report">
      <div class="target_report <?= $current_status ?>">
        <div class="invoiced_total">
          <?= sprintf($_ADDONLANG['invoiced_total'], date('Y'), formatCurrency($invoiceTotal)) ?>
        </div>
        <div class="current_position">
          <?= sprintf($_ADDONLANG['current_position_' . $current_status], ($current_position > 100 ? $current_position - 100 : 100 - $current_position), formatCurrency($current_target)) ?>
        </div>
      </div>
      <div class="collection_report">
        <div class="total_unpaid">
          <?= sprintf($_ADDONLANG['total_unpaid'], formatCurrency($invoiceTotal - $invoiceTotalPaid)) ?>
        </div>
        <div class="total_collected">
          <?= sprintf($_ADDONLANG['total_collected'], formatCurrency($transactionAmountTotal)) ?>
        </div>
        <div class="total_fees">
          <?= sprintf($_ADDONLANG['total_fees'], formatCurrency($accountFeesTotal)) ?>
        </div>
        <div class="total_net">
          <?= sprintf($_ADDONLANG['total_net'], formatCurrency($transactionAmountTotal - $accountFeesTotal)) ?>
        </div>
      </div>
    </div>
    <?php

    return ob_get_clean();

  });

