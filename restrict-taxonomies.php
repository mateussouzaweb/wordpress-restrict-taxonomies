<?php
/*
Plugin Name: Restrict Taxonomies
Description: Restrict the taxonomies that users can view, add, and edit in the admin panel.
Author: Mateus Souza
Author URI: http://mateussouzaweb.com
Version: 1.0
*/

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$class = new Restrict_Taxonomies();

class Restrict_Taxonomies{

	private $list = array();

	/**
	 * CONSTRUCT
	 * return @void
	 */
	public function __construct(){

		if( is_admin() ){

		$post_type = ( isset( $_GET['post_type'] ) ) ? $_GET['post_type'] : false;

  			if( $post_type != false ){
				add_action( 'admin_init', array( &$this, 'posts' ) );
  			}

			add_action( 'admin_init', array( &$this, 'init' ) );
			add_action( 'admin_menu', array( &$this, 'add_admin_menu' ) );
			add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );

		}

		if( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ){
			add_action( 'xmlrpc_call', array( &$this, 'posts' ) );
		}

	}

	/**
	 * Register database options and set defaults, which are blank
	 * @return void
	 */
	public function init() {

		register_setting( 'restrict_taxonomies_options_group', 'restrict_taxonomies_options', array( &$this, 'options_sanitize' ) );
		register_setting( 'restrict_taxonomies_user_options_group', 'restrict_taxonomies_user_options', array( &$this, 'options_sanitize' ) );

		add_option( 'restrict_taxonomies_options' );
		add_option( 'restrict_taxonomies_user_options' );

		if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'restrict-taxonomies' ):

			if( !isset( $_REQUEST['action'] ) ){
				return;
			}

			if( 'reset' !== $_REQUEST['action'] ){
				return;
			}

			$nonce = $_REQUEST['_wpnonce'];

			if( !wp_verify_nonce( $nonce, 'reset-nonce' ) ){
				wp_die( __( 'Security check', 'restrict-taxonomies' ) );
			}

			update_option( 'restrict_taxonomies_options', array() );
			update_option( 'restrict_taxonomies_user_options', array() );

		endif;
	}

	/**
	 * Add admin options page
	 * @return void
	 */
	public function add_admin_menu() {
		add_users_page(
			__('Restrict Taxonomies', 'restrict-taxonomies'),
			__('Restrict Taxonomies', 'restrict-taxonomies'),
			'add_users',
			'restrict-taxonomies',
			array( &$this, 'admin_page' )
		);
	}

	/**
	 * Builds the options settings page
	 * @return void
	 */
	public function admin_page() {

		$options       = $this->populate_opts();
		$user_options  = $this->populate_user_opts();

		$tab = 'roles';
		if( isset( $_GET['type'] ) && $_GET['type'] == 'users' ){
			$tab = 'users';
		}

		$roles_tab = esc_url( admin_url( 'users.php?page=restrict-taxonomies' ) );
		$users_tab = add_query_arg( 'type', 'users', $roles_tab );
		?>

		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php _e('Restrict Taxonomies', 'restrict-taxonomies'); ?></h2>
            <h2 class="nav-tab-wrapper">
            	<a href="<?php echo $roles_tab; ?>" class="nav-tab <?php echo ( $tab == 'roles' ) ? 'nav-tab-active' : ''; ?>"><?php _e( 'Roles', 'restrict-taxonomies' ); ?></a>
                <a href="<?php echo $users_tab; ?>" class="nav-tab <?php echo ( $tab == 'users' ) ? 'nav-tab-active' : ''; ?>"><?php _e( 'Users', 'restrict-taxonomies' ); ?></a>
            </h2>

			<form method="post" action="options.php">
			<?php $boxes = new Restrict_Taxonomies_User_Role_Boxes();

                if( $tab == 'roles' ): ?>

                <fieldset style="margin-top: 20px">
                    <?php
                    settings_fields( 'restrict_taxonomies_options_group' );
                    $boxes->show(
                    	get_option( 'restrict_taxonomies_options' ),
                    	$options, 'restrict_taxonomies_options'
                    );
                    ?>
                </fieldset>

				<?php elseif ( $tab == 'users' ): ?>

                <fieldset style="margin-top: 20px">
                	<p>Selecting taxonomies for a user will <em>override</em> the taxonomies you have chosen for that user's role.</p>
            		<?php
					settings_fields( 'restrict_taxonomies_user_options_group' );
                    $boxes->show(
                    	get_option( 'restrict_taxonomies_user_options' ),
                    	$user_options,
                    	'restrict_taxonomies_user_options'
                    );
                    ?>
                </fieldset>

                <?php endif; ?>

				<script>
				jQuery(document).ready(function(){

					jQuery('.select-all').click(function(e){

						e.preventDefault();

						var items = jQuery( this ).parents('.inside').find('input[type="checkbox"]:visible');
						if( items.length === items.filter( ':checked' ).length ){
							items.removeAttr( 'checked' );
						} else{
							items.prop( 'checked', true );
						}

					});

					jQuery('.postbox .handlediv, .postbox .hndle').click(function(){

						var inside = jQuery(this).parents('.postbox').find('.inside');

						if( inside.is(':visible') ){
							inside.hide();
						}else{
							inside.show();
						}

					});

				});
				</script>
                <?php submit_button(); ?>
			</form>

            <h3><?php _e('Reset to Default Settings', 'restrict-taxonomies'); ?></h3>
			<p><?php _e('This option will reset all changes you have made to the default configuration.  <strong>You cannot undo this process</strong>.', 'restrict-taxonomies'); ?></p>

			<form method="post">
				<?php submit_button( __( 'Reset', 'restrict-taxonomies' ), 'secondary', 'reset' ); ?>
                <input type="hidden" name="action" value="reset" />
                <?php wp_nonce_field( 'reset-nonce' ); ?>
			</form>

		</div>
	<?php

	}

	/**
	 * Add Settings link to Plugins page
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function plugin_action_links( $links, $file ) {

		if( $file == plugin_basename(__FILE__) ){
			$links[] = '<a href="users.php?page=restrict-taxonomies">' . __( 'Settings', 'restrict-taxonomies' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Display admin notices
	 * @return void
	 */
	public function admin_notices(){

		if( isset( $_REQUEST['action'] ) ){

			switch( $_REQUEST['action'] ):
				case 'reset' :
					echo '<div id="message" class="updated"><p>' . __( 'Restrict taxonomies reset.' , 'restrict-taxonomies') . '</p></div>';
				break;
			endswitch;

		}elseif( isset($_REQUEST['settings-updated']) AND $_REQUEST['settings-updated'] ){
			echo '<div id="message" class="updated"><p>' . __( 'Restrict taxonomies saved.' , 'restrict-taxonomies') . '</p></div>';
		}

	}

	/**
	 * Get all taxonomies that will be used as options.
	 * @return array
	 */
	public function get_taxonomies(){

		$_taxonomies = array();
		$all = get_taxonomies();

		unset( $all['nav_menu'] );
		unset( $all['category'] );
		unset( $all['post_format'] );
		unset( $all['link_category'] );

		foreach ($all as $key => $value) {

			$taxonomies = get_terms($key, 'hide_empty=0');

			if( $taxonomies ){

				$_taxonomies[ $key ] = array();
				foreach( $taxonomies as $taxonomy ) {
					$_taxonomies[ $key ][] = array(
						'slug' => $taxonomy->slug,
					);
				}

			}

		}

		return $_taxonomies;
	}

	/**
	 * Set up the options array which will output all roles with taxonomies.
	 * @return array
	 */
	public function populate_opts(){

		$options = array();
		$roles 	= $this->get_roles();
		$taxonomies = $this->get_taxonomies();

		foreach ( $roles as $name => $id ) {
			$options[] = array(
				'name'      => $name,
				'id'        => "{$id}_taxonomies",
				'options'   => $taxonomies
			);
		}

		return $options;
	}

	/**
	 * Set up the user options array which will output all users with taxonomies.
	 * @return array
	 */
	public function populate_user_opts(){

		$user_options = array();
		$logins	= $this->get_logins();
		$taxonomies = $this->get_taxonomies();

		foreach ( $logins as $name => $id ) {
			$user_options[] = array(
				'name'     => $name,
				'id'       => "{$id}_user_taxonomies",
				'options'  => $taxonomies
			);
		}

		return $user_options;
	}

	/**
	 * Set up the roles array which uses similar code to wp_dropdown_roles().
	 * @return array
	 */
	public function get_roles(){

		$roles = array();
		$editable_roles = get_editable_roles();

		foreach ( $editable_roles as $role => $name ) {
			$roles[ $name['name'] ] = $role;
		}

		return $roles;
	}

	/**
	 * Set up the user logins array.information about the blog's users. WP 3.0
	 * @return array
	 */
	public function get_logins(){

		$users = array();

		if( function_exists( 'get_users' ) ){

			$blogusers = get_users();
			foreach ( $blogusers as $login ) {
				$users[ $login->user_login ] = $login->user_nicename;
			}

		} elseif ( function_exists( 'get_users_of_blog' ) ){

			$blogusers = get_users_of_blog();
			foreach ( $blogusers as $login ) {
				$users[ $login->user_login ] = $login->user_id;
			}

		}

		return $users;
	}

	/**
	 * Sanitize input
	 * @param array $input
	 * @return array
	 */
	public function options_sanitize( $input ){

		if( !isset( $_REQUEST['option_page'] ) ){
			return;
		}

		$options = ( 'restrict_taxonomies_options_group' == $_REQUEST['option_page'] ) ?
			get_option( 'restrict_taxonomies_options' ) :
			get_option( 'restrict_taxonomies_user_options'
		);

		if ( is_array( $input ) ) {
			foreach( $input as $k => $v ) {
				$options[ $k ] = $v;
			}
		}

		return $options;
	}

	/**
	 * Rewrites the query to only display the selected taxonomies from the settings page
	 * @return void
	 */
	public function posts() {

		global $wp_query, $current_user;

		$defaults = array( 'restrict_taxonomies_default' );
		$user = new WP_User( $current_user->ID );
		$user_cap = $user->roles;

		if( function_exists( 'get_users' ) ){
			$user_login = $user->user_nicename;

		} elseif ( function_exists( 'get_users_of_blog' ) ){
			$user_login = $user->ID;
		}

		$settings = get_option( 'restrict_taxonomies_options' );
		$settings_user = get_option( 'restrict_taxonomies_user_options' );

		if( is_array( $settings_user ) && !empty( $settings_user[ $user_login ] ) ){

			foreach( $settings_user[ $user_login ] as $taxonomy => $array ){

				$this->list[ $taxonomy ] = '';
				foreach ($array as $key => $value) {
					$term_id = get_term_by('slug', $value, $taxonomy)->term_id;
					if( $term_id ) $this->list[ $taxonomy ] .= $term_id . ',';
				}

			}

			$this->taxonomies_filters( $this->list );

		} else {

			foreach ( $user_cap as $cap ) {

				if( is_array( $settings ) && !empty( $settings[ $cap ] ) ){

					foreach( $settings[ $cap ] as $taxonomy => $array ){

						$this->list[ $taxonomy ] = '';
						foreach ($array as $key => $value) {
							$term_id = get_term_by('slug', $value, $taxonomy)->term_id;
							if( $term_id ) $this->list[ $taxonomy ] .= $term_id . ',';
						}

					}

				}

				$this->taxonomies_filters( $this->list );
			}

		}

	}

	/**
	 * Adds filters for taxonomy restriction
	 * @return void
	 */
	public function taxonomies_filters( $taxonomies ){
		global $pagenow;

		if( empty( $this->list ) ){
			return;
		}

		if ( $pagenow == 'edit.php' || ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ){
			add_filter( 'pre_get_posts', array( &$this, 'posts_query' ) );
		}

		$pages = array( 'edit.php', 'post-new.php', 'post.php', 'edit-tags.php' );

		if ( in_array( $pagenow, $pages ) || ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ){
			add_filter( 'list_terms_exclusions', array( &$this, 'exclusions' ) );
		}

	}

	/**
	 * Remove posts from edit.php with restricted taxonomies
	 * @param array $query
	 * @return array
	 */
	public function posts_query( $query ){

		if ( $this->list ) {

			$array = array();

			foreach (get_taxonomies() as $key => $value) {

				$taxonomy = $key;
				$list = $this->list[ $taxonomy ];
				$list = trim( $list, ',' );

				if( !$list ){
					continue;
				}

				$ids = explode(',', $list);
				$array[] = array(
					'taxonomy' => $key,
					'field' => 'id',
					'terms' => $ids
				);

			}

			if( $array ){
				$query->set( 'tax_query', $array );
			}

		}

		return $query;
	}

	/**
	 * Explicitly remove extra taxonomies from view that user can manage
	 * Will affect taxonomy management page, posts dropdown filter, and new/edit post taxonomy list
	 * @return string
	 */
	public function exclusions($excluded){

		$excluded = '';

		foreach (get_taxonomies() as $key => $value) {

			$taxonomy = $key;
			$list = $this->list[ $taxonomy ];
			$list = trim( $list, ',' );

			if( !$list ){
				continue;
			}

			$excluded .= " AND ( t.term_id IN ( $list ) OR tt.taxonomy NOT IN ( '". $taxonomy. "' ) )";
		}

		return $excluded;
	}

}

class Restrict_Taxonomies_User_Role_Boxes {

	/**
	 * Retrieve the taxonomy list options
	 * @param string $taxonomy
	 * @param string $options_name
	 * @param string $options_name_item
	 * @param array $selected
	 * @return string
	 */
	public function list_taxonomy_options($taxonomy, $options_name, $options_name_item, $selected = array()){

		$output = '';
		$args = array(
			'parent' => 0,
			'hide_empty' => FALSE,
			'hierarchical' => FALSE
		);

		$terms = get_terms($taxonomy, $args);

		foreach($terms as $term){

			$output .= sprintf(
			'<li><label class="selectit"><input value="%2$s" type="checkbox" name="%3$s[%4$s][%5$s][]" %6$s /> %7$s</label>',
				$term->term_id,
				$term->slug,
				$options_name,
				$options_name_item,
				$taxonomy,
				checked( in_array( $term->slug, $selected ), true, false ),
				esc_html( apply_filters( 'the_category', $term->name ) )
			);

			$child = get_terms($taxonomy, array(
				'hierarchical' => FALSE,
				'hide_empty' => TRUE,
				'parent' => $term->term_id,
				'child_of' => $term->term_id
			));

			if( $child ){

				$output .= '<ul style="margin-left: 20px; margin-top: 5px" class="children">';

				foreach ($child as $item) {

					$output .= sprintf(
					'<li><label class="selectit"><input value="%2$s" type="checkbox" name="%3$s[%4$s][%5$s][]" %6$s /> %7$s</label>',
						$item->term_id,
						$item->slug,
						$options_name,
						$options_name_item,
						$taxonomy,
						checked( in_array( $item->slug, $selected ), true, false ),
						esc_html( apply_filters( 'the_category', $item->name ) )
					);

				}

				$output .= '</ul>';

			}

			$output .= '</li>';

		}

		return $output;
	}

	/**
	 * Show the box options
	 * @param array $settings
	 * @param array $options
	 * @param string $options_name
	 * @return void
	 */
	public function show($settings, $options, $options_name){

		foreach( $options as $_key => $_value ): ?>

			<h3 style="width: 100%; display: block; clear: both; padding-left: 10px; margin-bottom: 5px">
			<?php
				$user = get_user_by('login', $_value['name']);
				echo ( $user ) ? $user->display_name : $_value['name'];
			?>
			</h3>

			<?php
			foreach( $_value['options'] as $key => $value ):

				$id = $_value['name'];

				if( isset( $settings[ $id ][ $key ] ) && is_array( $settings[ $id ][ $key ] ) ){
					$selected = $settings[ $id ][ $key ];
				} else{
					$selected = array();
				}

				?>
				<div id="side-sortables" class="metabox-holder" style="float:left; padding:5px;">
					<div class="postbox">
						<div class="handlediv" title="Clique para expandir ou recolher."><br></div>
						<h3 class="hndle">
							<?php
							$taxonomy = get_taxonomy($key);
							$post_type = $taxonomy->object_type['0'];
							$post_type = get_post_type_object($post_type);
							echo $post_type->label. ' - '. $taxonomy->label ?>
						</h3>

		                <div class="inside" style="display:none; padding:0 10px;">
							<ul class="taxonomychecklist form-no-clear">
							<?php echo $this->list_taxonomy_options($key, $options_name, $_value['name'], $selected); ?>
                            </ul>
                            <input style="display:none;" type="checkbox" value="restrict_taxonomies_default" checked="checked" name="<?php echo $options_name; ?>[<?php echo $_value['name']; ?>][<?php echo $key; ?>][]">
                            <p><a href="#" class="select-all" title="Select all">Select all</a></p>
						</div>

					</div>
				</div>
			<?php endforeach; ?>
		<?php endforeach;
	}

}