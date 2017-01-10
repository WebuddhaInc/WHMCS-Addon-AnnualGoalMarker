<?php

/**
 *
 */
  add_hook('AdminHomepage', 1, function ( $vars )
  {

    /**
     * Required
     */
    if (!class_exists('wbDatabase'))
      return;

    /**
     * Database Handler
     */
    $db = wbDatabase::getInstance();

    /**
     * Pull Module Configuration
     */
    $db->runQuery("
      SELECT `value`
      FROM `tbladdonmodules`
      WHERE `module` = 'admingoalmarker'
        AND `setting` = 'annual_target'
      ");
    $annual_target = (real)$db->getValue();

    /**
     * Pull Total
     */
    $db->runQuery("
      SELECT SUM(`total`)
      FROM `tblinvoices` AS `invoice`
      WHERE `status` = 'Paid'
        AND `datepaid` >= '". date('Y') ."-01-01'
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

    $current_year     = (int)date('Y');
    $current_month    = (int)date('m');
    $current_day      = (int)date('d');
    $current_daysinmo = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $current_target   = (($current_month - 1) + ($current_day / $current_daysinmo)) * ($annual_target / 12);
    $current_position = round(($invoiceTotalPaid / $current_target) * 100, 2);
    $current_status   = (abs($current_position) > 100 ? "ahead" : (abs($current_position) < 100 ? "behind" : "on"));

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
      .admingoalmarker_report .target_report b {
        margin-right: 10px;
        padding-right: 15px;
        border-right: 2px solid white;
      }
      .admingoalmarker_report .collection_report {
        display: inline-block;
        font-size: 1.0em;
        color: white;
        background: gray;
        padding: 5px 10px;
        border-radius: 0 0 4px 4px;
      }
    </style>
    <div class="admingoalmarker_report">
      <div class="target_report <?= $current_status ?>">
        <b><?= abs(100 - $current_position) ?>% <?= ucfirst($current_status) ?> of schedule</b>
        <?= $current_position ?>% of today's target position of <?= formatCurrency($current_target) ?>
      </div>
      <div class="collection_report">
        <?= date('Y') ?> Invoiced <?= formatCurrency($invoiceTotalPaid) ?>, Collected <?= formatCurrency($transactionAmountTotal) ?> - Fees <?= formatCurrency($accountFeesTotal) ?> = <?= formatCurrency($transactionAmountTotal - $accountFeesTotal) ?>
      </div>
    </div>
    <?php

    return ob_get_clean();

  });

