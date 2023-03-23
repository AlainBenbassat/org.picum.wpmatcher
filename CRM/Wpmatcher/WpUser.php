<?php

class CRM_Wpmatcher_WpUser {
  public function getAllUsers() {
    global $wpdb;
    $sql = "select id, user_login, user_nicename, user_email from wp_users order by id";
    $allUsers = $wpdb->get_results($sql);
    return $allUsers;
  }
}