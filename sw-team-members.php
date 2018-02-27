<?php
/*
Plugin Name: SW Team Members
Plugin URI: https://www.sw-developpement.com/
Description: Simple team members plugin - Set on your website the names, socials and pictures of your team
Author: Camille JULES GASTON
Author URI: https://www.julesgaston.fr/
Text Domain: sw-team-members
Version: 1.0
Domain Path: /languages/
License: GPL
*/
function swtm_load_plugin_textdomain() {
    load_plugin_textdomain( 'sw-team-members', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'swtm_load_plugin_textdomain' );

class swtm{
  public function __construct($mode = 'install'){
	  if($mode == "install"){
  		add_action('admin_menu',array(&$this,'admin_menu'));
		  add_action('admin_action_save_member',array(&$this,'save_member'));
	  }
  }

  function admin_menu() {
   add_menu_page("Team members","Team members",'administrator',"sw-team-members",array(&$this,'list_members'),plugins_url( '', __FILE__ ).'/icon.png');
  }

  function list_members(){
    if($_GET['action'] == 'delete_member') $this->delete_member();
    elseif($_GET['action'] == 'publish_member') $this->publish_member();
    require_once ('members.php');
  }


  function listMembers($limit = '', $status = '', $search = ''){
    global $wpdb;
    $res = $wpdb->get_results(
            "SELECT * FROM wp_posts
            WHERE post_type = 'sw-team-member'
            ".(!empty($status) ? "AND post_status = '".$status."'" : '')."
            ".(!empty($search) ? "AND post_title LIKE \"%".$search."%\"" : '')."
            AND post_status != 'trash'
            ORDER BY post_name
            ".$limit);
    return $res;
  }

  function howManyMembers($status = '', $search = ''){
    global $wpdb;
    $res = $wpdb->get_results("SELECT COUNT(ID) AS v FROM wp_posts
                              WHERE post_type = 'sw-team-member'
                              ".(!empty($status) ? "AND post_status = '".$status."'" : '')."
                              ".(!empty($search) ? "AND post_title LIKE \"%".$search."%\"" : '')."
                              AND post_status != 'trash'
                              LIMIT 1");
    return $res[0]->v;
  }

  function isValidMember($s){
    global $wpdb;
    if(!empty($s)){
      $res = $wpdb->get_results("SELECT COUNT(ID) AS v FROM wp_posts WHERE ID = '".$s."' AND post_type = 'sw-team-member' AND post_status IN ('draft','publish') LIMIT 1");
      if($res[0]->v == 1)
        return true;
      else return false;
    }else return false;
  }

  function getAMember($s){
    global $wpdb;
    if($this->isValidMember($s)){
      $res = $wpdb->get_results("SELECT * FROM wp_posts WHERE ID = '".$s."' AND post_type = 'sw-team-member' AND post_status IN ('draft','publish') LIMIT 1");
      return $res[0];
    }
  }

  function save_member(){
    global $wpdb;
    $post = $_POST['post'];
    $member_name = $_POST['member_name'];
    $member_bio = $_POST['member_bio'];
    $member_status = $_POST['member_status'];

    $datas = array(
      'ID' => $post,
      'post_title' => $member_name,
      'post_content' => $member_bio,
      'post_type' => 'sw-team-member',
      'comment_status' => 'closed',
      'ping_status' => 'closed',
      'post_status' => $member_status
    );
    $ID = wp_insert_post($datas);

    $member_twitter = $_POST['member_twitter'];
    $member_linkedin = $_POST['member_linkedin'];
    $member_viadeo = $_POST['member_viadeo'];

    update_post_meta($ID,'member_twitter',$member_twitter);
    update_post_meta($ID,'member_linkedin',$member_linkedin);
    update_post_meta($ID,'member_viadeo',$member_viadeo);

    $file = $_FILES['picture'];
    if(!empty($file['name'])){
      $arr_file_type = wp_check_filetype(basename($file['name']));
      $uploaded_file_type = $arr_file_type['type'];
      $allowed_file_types = array('image/jpg','image/jpeg','image/gif','image/png');
      if(in_array($uploaded_file_type, $allowed_file_types)) {
        $logoRet = wp_handle_upload($file, array('test_form' => FALSE));
        if(isset($logoRet['file'])){
           $file_name_and_location = $logoRet['file'];
           $file_title_for_media_library = $structure_title;
           $attachment = array(
                'post_mime_type' => $uploaded_file_type,
                'post_title' => addslashes($file_title_for_media_library),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment( $attachment, $file_name_and_location );
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );
            wp_update_attachment_metadata($attach_id,  $attach_data);

            $existing_uploaded_image = (int) get_post_meta($ID,'attached_picture_member', true);
            if(is_numeric($existing_uploaded_image)) {
                wp_delete_attachment($existing_uploaded_image);
            }

            update_post_meta($ID,'attached_picture_member',$attach_id);
        }
      }
    }

  	wp_redirect( $_SERVER['HTTP_REFERER'] );
  	exit();
  }

  function delete_member(){
    global $wpdb;
    $s = $_GET['post'];
    if(!empty($s)){
      wp_trash_post($s);
    }
  }

  function publish_member(){
    $s = $_GET['post'];
    if(!empty($s)){
		$datas = array(
		  'ID' => $s,
		  'post_status' => 'publish'
		);
		wp_update_post($datas);
    }
  }

}
if (class_exists('swtm')) {
   $swtm = new swtm();
}

function int_types_action($val){ return (int)$val; }

function display_sw_team_members(){
  $swtm = new swtm();
  if($swtm->howManyMembers('publish') > 0){
    $members = $swtm->listMembers('','publish');
    echo '<ul class="stm-list">';
    foreach($members as $m){
      $thumbnail = get_post_meta($m->ID,'attached_picture_member');
      $member_twitter = get_post_meta($m->ID,'member_twitter', true);
      $member_linkedin = get_post_meta($m->ID,'member_linkedin', true);
      $member_viadeo = get_post_meta($m->ID,'member_viadeo', true);
    	echo '<li class="stm-item">
              <h3 class="stm-title">'.$m->post_title.'</h3>
              <p class="stm-bio">'.$m->post_content.'</p>
              '.(!empty($thumbnail[0]) ? '<div class="stm-picture"><img src="'.wp_get_attachment_thumb_url( $thumbnail[0]).'" /></div>' : '').'
              '.(!empty($member_twitter) || !empty($member_twitter) || !empty($member_twitter) ? '
                <div class="stm-social">
                  '.(!empty($member_twitter) ? '<a href="'.$member_twitter.'"><i class="stm-twitter"></i></a>' : '').'
                  '.(!empty($member_linkedin) ? '<a href="'.$member_linkedin.'"><i class="stm-linkedin"></i></a>' : '').'
                  '.(!empty($member_viadeo) ? '<a href="'.$member_viadeo.'"><i class="stm-viadeo"></i></a>' : '').'
                </div>
              ' : '').'
    		    </li>';
    }
    echo '</ul>';
  }
}
add_shortcode('display_sw_team_members', 'display_sw_team_members');
?>