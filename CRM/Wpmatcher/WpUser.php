<?php

class CRM_Wpmatcher_WpUser {
  public function getAllInternUsers() {
    $this->switchDbToWordpress();

    $sql = "select id, user_login, user_nicename, user_email from picum_users where user_email like 'intern_@picum.org'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $allUsers = $dao->fetchAll();

    $this->switchDbToCiviCRM();

    return $allUsers;
  }

  private function switchDbToWordpress() {
    $config = CRM_Core_Config::singleton();
    $ufDSN = CRM_Utils_SQL::autoSwitchDSN($config->userFrameworkDSN);
  }

  private function switchDbToCiviCRM() {
    $config = CRM_Core_Config::singleton();
    $ufDSN = CRM_Utils_SQL::autoSwitchDSN($config->dsn);
  }

}