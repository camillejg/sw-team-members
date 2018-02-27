<?php
$mId = $_GET['post'];
if($this->isValidMember($mId)) $member = $this->getAMember($mId);

$search = $_POST['s'];
$paged = $page = $_GET['paged'];
$maxPerPage = 25;
if(empty($page)) $paged = $page = 1;
$page--;
$limit = "LIMIT ".($page * $maxPerPage).",".$maxPerPage;

$nb = $this->howManyMembers(($_GET['publish'] == 'draft' ? 'draft' : ($_GET['publish'] == 'publish' ? 'publish' : '')),$search);
if($nb > 0) $nbPages = ($nb / $maxPerPage);
else $nbPages = 1;

$members = $this->listMembers($limit,($_GET['publish'] == 'draft' ? 'draft' : ($_GET['publish'] == 'publish' ? 'publish' : '')),$search);
$listMembers = '';

foreach($members as $m)
	$listMembers .= '
		<tr>
			<td>
        '.stripslashes($m->post_title).'
        <div class="row-actions">
          <span class="edit"><a href="'.admin_url('admin.php?page=sw-team-members&post='.$m->ID).'" aria-label="'._('Edit').' '.$m->post_title.' &raquo;">'._('Edit').'</a> | </span>
          <span class="trash"><a href="'.admin_url('admin.php?page=sw-team-members&action=delete_member&post='.$m->ID).'" class="submitdelete" aria-label="'._('Delete').'">'._('Delete').'</a> | </span>
          '.($m->post_status != 'publish' ? '<span class="publish"> | <a href="'.admin_url('admin.php?page=sw-team-members&action=publish_member&post='.$m->ID).'" aria-label="'._('Publish').'">'._('Publish').'</a></span>' : '').'
        </div>
      </td>
			<td>'.($m->post_status == 'publish' ? _('Published') : _('Draft')).'</td>
			<td>&nbsp;</td>
		</tr>';

$nbPublished = $this->howManyMembers('publish');
$nbDrafted = $this->howManyMembers('draft');
$nbTotal = $this->howManyMembers();

echo '<div class="wrap">
        <h1 class="wp-heading-inline">'._('Team members').'</h1>
        <hr class="wp-header-end">

        <div id="poststuff">
          <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content" style="position: relative;">

              <ul class="subsubsub">
              	<li class="all"><a href="'.admin_url('admin.php?page=sw-team-members').'" class="current" aria-current="page">'._('All').' <span class="count">('.$nbTotal.')</span></a> |</li>
              	<li class="publish"><a href="'.admin_url('admin.php?page=sw-team-members&publish=publish').'">'._('Published').' <span class="count">('.$nbPublished.')</span></a> |</li>
              	<li class="trash"><a href="'.admin_url('admin.php?page=sw-team-members&publish=draft').'">'._('Draft').' <span class="count">('.$nbDrafted.')</span></a></li>
              </ul>
              <form id="posts-filter" method="post" action="">

                <p class="search-box">
                	<label class="screen-reader-text" for="post-search-input">'._('Search for a member').'</label>
                	<input type="search" id="post-search-input" name="s" value="'.$search.'" />
                	<input type="submit" id="search-submit" class="button" value="'._('Looking for a member').'"  />
                </p>
              </form>

      				<table class="wp-list-table widefat fixed striped pages">
      				  <thead>
      					<tr>
      					  <th>'._('Member').'</th>
      					  <th>'._('Status').'</th>
      					</tr>
      				  </thead>
      				  <tbody>
      					'.$listMembers.'
      				  </tbody>
      				</table>
              <div class="tablenav bottom">
                <div class="tablenav-pages">
                  <span class="displaying-num">'.$nb.' element'.($nb > 1 ? 's' : '').'</span>
                  '.($paged > 1 ? '<a class="next-page" href="'.admin_url('admin.php?page=sw-team-members&paged=1').'">' : '').'
                  <span'.($paged <= 1 ? ' class="tablenav-pages-navspan"' : '').' aria-hidden="true">&laquo;</span>'.($paged > 1 ? '</a>' : '').'
                  '.($paged > 1 ? '<a class="next-page" href="'.admin_url('admin.php?page=sw-team-members&paged='.($paged - 1)).'">' : '').'
                  <span'.($paged <= 1 ? ' class="tablenav-pages-navspan"' : '').' aria-hidden="true">&lsaquo;</span>'.($paged > 1 ? '</a>' : '').'

                  <span id="table-paging" class="paging-input"><span class="tablenav-paging-text">'.$paged.' on <span class="total-pages">'.ceil($nbPages).'</span></span></span>

                  '.($paged < $nbPages ? '<a class="next-page" href="'.admin_url('admin.php?page=sw-team-members&paged='.($paged + 1)).'">' : '').'
                  <span'.($paged >= $nbPages ? ' class="tablenav-pages-navspan"' : '').' aria-hidden="true">&rsaquo;</span>'.($paged < $nbPages ? '</a>' : '').'
                  '.($paged < $nbPages ? '<a class="last-page" href="'.admin_url('admin.php?page=sw-team-members&paged='.$nbPages).'">' : '').'
                  <span'.($paged >= $nbPages ? ' class="tablenav-pages-navspan"' : '').' aria-hidden="true">&raquo;</span></a>'.($paged < $nbPages ? '</a>' : '').'
                  		<br class="clear">
                </div>
              </div>
            </div>

            <div id="postbox-container-1" class="postbox-container">
              <div class="postbox">
                <h2 class="hndle"><span>'._('Add a member').'</span></h2>

                <div class="inside">
                  <form method="post" action="'.admin_url( 'admin.php' ).'" enctype="multipart/form-data">
      		          <input type="hidden" name="action" value="save_member" />
                    '.(!empty($mId) ? '<input type="hidden" name="post" value="'.$mId.'" />' : '').'
                    <input name="member_name" value="'.$member->post_title.'" id="member_name" spellcheck="true" autocomplete="off" type="text" placeholder="'._('Member name').'" /><br>
                    <textarea name="member_bio" id="member_bio" spellcheck="true" autocomplete="off" placeholder="'._('Bio').'">'.$member->post_content.'</textarea><br>
                    <input name="member_twitter" value="'.(!empty($mId) ? get_post_meta($mId,'member_twitter', true) : '').'" id="member_twitter" spellcheck="true" autocomplete="off" type="text" placeholder="'._('Twitter URL').'" /><br>
                    <input name="member_linkedin" value="'.(!empty($mId) ? get_post_meta($mId,'member_linkedin', true) : '').'" id="member_linkedin" spellcheck="true" autocomplete="off" type="text" placeholder="'._('LinkedIn URL').'" /><br>
                    <input name="member_viadeo" value="'.(!empty($mId) ? get_post_meta($mId,'member_viadeo', true) : '').'" id="member_viadeo" spellcheck="true" autocomplete="off" type="text" placeholder="'._('Viadeo URL').'" /><br>
                    <label>Member picture :</label>
                    <input type="file" name="picture" /><br>
                    <select name="member_status" style="width:80px;">
                      <option value="draft">'._('Draft').'</option>
                      <option value="publish"'.($member->post_status == 'publish' ? ' selected' : '').'>'._('Published').'</option>
                    </select><br /><br />
                    <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="Enregistrer"  /><br>
                    '.(!empty($mId) ? '<a href="'.admin_url('admin.php?page=sw-team-members').'" class="red-link">&laquo; Retour &agrave; l&rsquo;ajout</a>' : '').'
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>';
?>