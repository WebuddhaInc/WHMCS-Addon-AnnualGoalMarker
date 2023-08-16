<?php

/*************************** Valid Request */
  defined("WHMCS") or die("wbTeamPro Error: Invalid Access");

/*************************** WHMCS Hook */
  add_hook("AdminHomeWidgets", 1, "widget_admingoalmarker_goalmarker");

/*************************** Widget Function */
  function widget_admingoalmarker_goalmarker($params) {

    /**
     * Required
     */
    if (!class_exists('wbDatabase'))
      return;

    /**
     * Path
     */
    $basePath = dirname(__DIR__);

    /**
     * Language
     */
    $language = isset($GLOBALS['admin']['language']) ? $GLOBALS['admin']['language'] : 'english';
    if (file_exists($basePath.'/lang/' . $language . '.php'))
      include $basePath.'/lang/' . $language . '.php';
    else
      include $basePath.'/lang/english.php';

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
    $annual_target  = (float)(isset($setting['annual_target']) ? $setting['annual_target'] : 100000);
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

    $instance_id = 'admingoalmarker-' . substr(md5(time().rand(1000,9999)), 0, 8);

    ob_start();
    if (empty($params['customClass'])) {
      ?>
      <style>
        #<?php echo $instance_id ?> {
          padding: 10px 12px;
        }
        #<?php echo $instance_id ?> .target_report {
          font-size: 1.2em;
          color: white;
          background: red;
          padding: 5px 10px;
          border-radius: 4px;
          margin-bottom: 10px;
        }
        #<?php echo $instance_id ?> .target_report.on,
        #<?php echo $instance_id ?> .target_report.ahead {
          background: green;
        }
        #<?php echo $instance_id ?> .target_report > div {
          margin-right: 10px;
          padding-right: 15px;
        }
        #<?php echo $instance_id ?> .target_report > div:last-child {
          font-size: .8em;
          font-style: italic;
        }
        #<?php echo $instance_id ?> .collection_report {
          font-size: 1.0em;
          padding: 5px 10px;
        }
        #<?php echo $instance_id ?> .collection_report > div {
          margin: 0 0 5px 0;
          font-weight: bold;
        }
        #<?php echo $instance_id ?> .collection_report > div > span {
          font-weight: normal;
        }
        #<?php echo $instance_id ?> .collection_report > div:last-child {
          border-right: none;
        }
      </style>
      <?php
    }
    ?>
    <div class="admingoalmarker_report <?php echo @$params['customClass'] ?>" id="<?php echo $instance_id ?>">
      <div class="target_report <?= $current_status ?>">
        <div class="invoiced_total">
          <?= sprintf($_ADDONLANG['invoiced_total'], date('Y'), '<span>'.formatCurrency($invoiceTotal).'</span>') ?>
        </div>
        <div class="current_position">
          <?= sprintf($_ADDONLANG['current_position_' . $current_status], '<span>'.($current_position > 100 ? $current_position - 100 : 100 - $current_position), formatCurrency($current_target).'</span>') ?>
        </div>
      </div>
      <div class="collection_report">
        <div class="total_unpaid">
          <?= sprintf($_ADDONLANG['total_unpaid'], '<span>'.formatCurrency($invoiceTotal - $invoiceTotalPaid).'</span>') ?>
        </div>
        <div class="total_collected">
          <?= sprintf($_ADDONLANG['total_collected'], '<span>'.formatCurrency($transactionAmountTotal).'</span>') ?>
        </div>
        <div class="total_fees">
          <?= sprintf($_ADDONLANG['total_fees'], '<span>'.formatCurrency($accountFeesTotal).'</span>') ?>
        </div>
        <div class="total_net">
          <?= sprintf($_ADDONLANG['total_net'], '<span>'.formatCurrency($transactionAmountTotal - $accountFeesTotal).'</span>') ?>
        </div>
      </div>
    </div>
    <?php
    $html = ob_get_clean();

    // Return
      return array(
        'title'       => 'Goal Marker',
        'content'     => $html,
        'jquerycode'  => ''
        );

  }
