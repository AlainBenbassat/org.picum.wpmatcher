<?php

class CRM_Wpmatcher_WpUser {
  public function getAllInternUsers() {
    global $wpdb;
    $sql = "select id, user_login, user_nicename, user_email from picum_users where user_email like 'intern_@picum.org'";
    $allUsers = $wpdb->get_results($sql);
    return $allUsers;
  }
}