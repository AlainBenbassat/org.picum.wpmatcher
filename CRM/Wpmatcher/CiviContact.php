<?php

class CRM_Wpmatcher_CiviContact {
  public function getContactIdFromWpId($wpId) {
    $sql = "select contact_id from civicrm_uf_match where uf_id = $wpId";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  public function validateNewMatch($userMatcher) {
    $errors = [];

    foreach ($userMatcher as $wpId => $civiContactId) {
      if ($civiContactId) {
        $this->validateIsNotDuplicateMatch($wpId, $civiContactId, $errors);
        $this->validateIsNotExactOneContactId($userMatcher, $wpId, $civiContactId, $errors);
      }
    }

    return $errors;
  }

  public function updateUserMatches($userMatcher) {
    foreach ($userMatcher as $wpId => $civiContactId) {
      if ($civiContactId) {
        $this->createOrUpdateUfMatch($wpId, $civiContactId);
      }
      else {
        $this->removeUfMatch($wpId);
      }
    }
  }

  private function createOrUpdateUfMatch($wpId, $civiContactId) {
    if ($this->existsUfId($wpId)) {
      $this->updateUfMatch($wpId, $civiContactId);
    }
    else {
      $this->createUfMatch($wpId, $civiContactId);
    }

  }

  private function existsUfId($wpId) {
    $sql = "select id from civicrm_uf_match where uf_id = $wpId";
    $id = CRM_Core_DAO::singleValueQuery($sql);
    if ($id) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function updateUfMatch($wpId, $civiContactId) {
    $sql = "update civicrm_uf_match set contact_id = $civiContactId where uf_id = $wpId";
    CRM_Core_DAO::executeQuery($sql);
  }

  private function createUfMatch($wpId, $civiContactId) {
    $sql = "insert into civicrm_uf_match (domain_id, contact_id, uf_id) values (1, $civiContactId, $wpId)";
    CRM_Core_DAO::executeQuery($sql);
  }

  private function removeUfMatch($wpId) {
    $sql = "delete from civicrm_uf_match where uf_id = $wpId";
    CRM_Core_DAO::executeQuery($sql);
  }

  private function validateIsNotDuplicateMatch($wpId, $civiContactId, &$errors) {
    $sql = "
      select 
        c.display_name
        , ufm.uf_name 
        , ufm.uf_id
      from 
        civicrm_uf_match ufm
      inner join
        civicrm_contact c on c.id = ufm.contact_id
      where 
        ufm.uf_id <> $wpId 
      and 
        ufm.contact_id = $civiContactId
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      $errors[] = 'The contact ' . $dao->display_name . ' is already linked to Wordpress user: ' . $dao->uf_name . ' (id = ' . $dao->uf_id . ')';
    }
  }

  private function validateIsNotExactOneContactId($userMatcher, $wpId, $civiContactId, &$errors) {
    foreach ($userMatcher as $k => $v) {
      if ($k != $wpId) {
        if ($v == $civiContactId) {
          $errors[] = 'You cannot assign a contact to multiple Wordpress users';
        }
      }
    }
  }
}
