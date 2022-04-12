<?php

use CRM_Wpmatcher_ExtensionUtil as E;

class CRM_Wpmatcher_Form_UserMatcher extends CRM_Core_Form {
  private $wpInternUsers;

  public function buildQuickForm() {
    $this->getWpInternUsers();
    $this->addWpInternsToForm();
    $this->fillMatchingContactIds();
    $this->addButtonsToForm();

    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function validate() {
    $userMatcher = $this->getUserMatcherSubmission();

    $civiContact = new CRM_Wpmatcher_CiviContact();
    $errors = $civiContact->validateNewMatch($userMatcher);

    if (count($errors) > 0) {
      CRM_Core_Session::setStatus(implode('<br>', $errors), 'Error', 'error');
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  public function postProcess() {
    $userMatcher = $this->getUserMatcherSubmission();
    $civiContact = new CRM_Wpmatcher_CiviContact();
    $civiContact->updateUserMatches($userMatcher);

    CRM_Core_Session::setStatus('All users are processed. Make sure the names of the Wordpress users correspond selected contact.', 'Done!', 'success');
    parent::postProcess();
  }

  private function getUserMatcherSubmission() {
    $userMatcher = [];

    $values = $this->exportValues();

    foreach ($values as $k => $civiContactId) {
      if (strpos($k, 'intern_') === 0) {
        // extract the id
        $wordpressId = substr($k, strlen('intern_'));
        $userMatcher[$wordpressId] = $civiContactId;
      }
    }

    return $userMatcher;
  }

  private function addWpInternsToForm() {
    $select2Properties = [
      'api' => [
        'params' => [
          'contact_type' => 'Individual',
        ]
      ],
    ];

    foreach ($this->wpInternUsers as $wpInternUser) {
      $fieldName = 'intern_' . $wpInternUser->id;
      $label = $wpInternUser->user_email . ' (' . $wpInternUser->user_nicename . ')';
      $this->addEntityRef($fieldName, $label , $select2Properties);
    }
  }

  private function addButtonsToForm() {
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  private function fillMatchingContactIds() {
    $defaults = [];
    $civiContact = new CRM_Wpmatcher_CiviContact();

    foreach ($this->wpInternUsers as $wpInternUser) {
      $contactId = $civiContact->getContactIdFromWpId($wpInternUser->id);
      if ($contactId) {
        $fieldName = 'intern_' . $wpInternUser->id;
        $defaults[$fieldName] = $contactId;
      }
    }

    $this->setDefaults($defaults);
  }

  private function getWpInternUsers() {
    $wpUser = new CRM_Wpmatcher_WpUser();
    $this->wpInternUsers = $wpUser->getAllInternUsers();
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
