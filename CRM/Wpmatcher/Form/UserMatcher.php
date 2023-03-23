<?php

use CRM_Wpmatcher_ExtensionUtil as E;

class CRM_Wpmatcher_Form_UserMatcher extends CRM_Core_Form {
  private $wpUsers;

  public function buildQuickForm() {
    $this->getWpUsers();
    $this->addWpUsersToForm();
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
      if (strpos($k, 'contact_') === 0) {
        // extract the id
        $wordpressId = substr($k, strlen('contact_'));
        $userMatcher[$wordpressId] = $civiContactId;
      }
    }

    return $userMatcher;
  }

  private function addWpUsersToForm() {
    $select2Properties = [
      'api' => [
        'params' => [
          'contact_type' => 'Individual',
        ]
      ],
    ];

    foreach ($this->wpUsers as $wpUser) {
      $fieldName = 'contact_' . $wpUser->id;
      $label = $wpUser->user_email . ' (' . $wpUser->user_nicename . ')';
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

    foreach ($this->wpUsers as $wpUser) {
      $contactId = $civiContact->getContactIdFromWpId($wpUser->id);
      if ($contactId) {
        $fieldName = 'contact_' . $wpUser->id;
        $defaults[$fieldName] = $contactId;
      }
    }

    $this->setDefaults($defaults);
  }

  private function getWpUsers() {
    $wpUser = new CRM_Wpmatcher_WpUser();
    $this->wpUsers = $wpUser->getAllUsers();
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
