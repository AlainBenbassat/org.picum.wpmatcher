<?php

use CRM_Wpmatcher_ExtensionUtil as E;

class CRM_Wpmatcher_Form_UserMatcher extends CRM_Core_Form {
  public function buildQuickForm() {
    $defaults = [];

    // get the email addresses of the interns
    $sql = "select * from civicrm_uf_match where uf_name like 'intern_@picum.org' order by uf_name";
    $dao = CRM_Core_DAO::executeQuery($sql);

    // create a select2 for each one
    while ($dao->fetch()) {
      $props = [
        'api' => [
          'params' => [
            'contact_type' => 'Individual',
          ]
        ],
      ];
      $fieldName = 'intern_' . $dao->id;
      $this->addEntityRef($fieldName, $dao->uf_name, $props);

      // set the default value
      $defaults[$fieldName] = $dao->contact_id;
    }

    // set defaults
    $this->setDefaults($defaults);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function validate() {
    $errors = [];

    $userMatcher = $this->getUserMatcherArray();
    foreach ($userMatcher as $id => $contact_id) {
      if ($contact_id) {
        // make sure the (new) contact id is not assigned to an existing user
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
            ufm.id <> $id 
          and 
            ufm.contact_id = $contact_id
        ";
        $dao = CRM_Core_DAO::executeQuery($sql);
        if ($dao->fetch()) {
          $errors[] = 'The contact ' . $dao->display_name . ' is already linked to Wordpress user: ' . $dao->uf_name . ' (id = ' . $dao->uf_id . ')';
        }

        // make sure the id's are unique in the array
        foreach ($userMatcher as $k => $v) {
          if ($k <> $id) {
            if ($v == $contact_id) {
              $errors[] = 'You cannot assign a contact to multiple Wordpress users';
            }
          }
        }
      }
    }

    if (count($errors) > 0) {
      CRM_Core_Session::setStatus(implode('<br>', $errors), 'Error', 'error');
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  public function postProcess() {
    $userMatcher = $this->getUserMatcherArray();
    foreach ($userMatcher as $id => $contact_id) {
      if ($contact_id) {
        // update
        $sql = "update civicrm_uf_match set contact_id = $contact_id where id = $id";
        CRM_Core_DAO::executeQuery($sql);
      }
      else {
        // contact_id is empty, no nothing (we can't remove the entry)
      }
    }

    CRM_Core_Session::setStatus('All users are processed. Make sure the names of the Wordpress users correspond selected contact.', 'Done!', 'success');
    parent::postProcess();
  }

  private function getUserMatcherArray() {
    $userMatcher = [];

    $values = $this->exportValues();

    foreach ($values as $k => $v) {
      if (strpos($k, 'intern_') === 0) {
        // extract the id
        $id = substr($k, strlen('intern_'));

        // get the contact id
        $contact_id = $v;

        $userMatcher[$id] = $v;
      }
    }

    return $userMatcher;
  }

  public function getRenderableElementNames() {
    $elementNames = [];
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
