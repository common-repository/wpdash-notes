<?php

namespace WPF\WPDash_Notes;

class Plugin {

	use Singleton;

	protected function init() {
		add_action( 'init', array( __CLASS__, 'wpf_post_it_dashboard' ), 0 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'wpf_add_meta_boxes' ) );
		add_action( 'admin_print_scripts-post-new.php', array( __CLASS__, 'enqueue_select2_jquery' ), 11 );
		add_action( 'admin_print_scripts-post.php', array( __CLASS__, 'enqueue_select2_jquery' ), 11 );
		add_action( 'save_post', array( __CLASS__, 'wpf_save_post' ), 100 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'wpf_wp_dashboard_setup' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'wpf_edit_form_after_title' ) );
		add_action( 'wp_ajax_postitaddnewcomment', array( __CLASS__, 'wp_ajax_post_it_add_new_comment' ), 1 );
		add_action( 'wp_ajax_postitlistcomment', array( __CLASS__, 'wp_ajax_post_it_list_comment' ), 1 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_postit' ), 999 );
		add_action( 'wp_ajax_postit_add', array( __CLASS__, 'postit_add' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
		add_action( 'admin_footer', array( __CLASS__, 'admin_footer' ) );
		add_filter( 'plugin_action_links_' . WPDASH_NOTES_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	public static function admin_notices() {
		$screen = get_current_screen();
		if ( $screen && 'edit-wpf_post_it' === $screen->id ) {
			include( WPDASH_NOTES_DIR . '/blocks/pub_wpboutik.php' );
		}
	}

	public static function install() {
		$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		if ( 'fr_FR' === $locale ) {
			$args = array(
				'post_status'  => 'publish',
				'post_type'    => 'wpf_post_it',
				'post_title'   => 'Nouvelle note',
				'post_content' => 'Contenu de ma note à modifier'
			);
		} else {
			$args = array(
				'post_status'  => 'publish',
				'post_type'    => 'wpf_post_it',
				'post_title'   => __( 'New note', 'wpdash-notes' ),
				'post_content' => __( 'Content of my note to modify', 'wpdash-notes' )
			);
		}

		$post_id = wp_insert_post( $args );

		update_post_meta( $post_id, 'myuser', 'on' );
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public static function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="' . admin_url( 'edit.php?post_type=wpf_post_it' ) . '">' . __( 'Notes', 'wpdash-notes' ) . '</a>' );

		return $links;
	}

	public static function admin_footer() {
		global $current_user;
		$posts = get_posts( array( 'post_type' => 'wpf_post_it' ) );

		if ( ! empty( $posts ) && is_array( $posts ) ) :
			foreach ( $posts as $key => $post ) :
				$color           = get_post_meta( $post->ID, 'color', true );
				$color_text      = get_post_meta( $post->ID, 'color_text', true );
				$myuser          = get_post_meta( $post->ID, 'myuser', true );
				$all_users       = get_post_meta( $post->ID, 'all_users', true );
				$dsn_target      = get_post_meta( $post->ID, 'dsn_target', true );
				$dsn_target_user = get_post_meta( $post->ID, 'dsn_target_user', true );

				if ( ( is_array( $dsn_target ) && in_array( $current_user->roles[0], $dsn_target ) ) ||
				     ( is_array( $dsn_target_user ) && in_array( get_current_user_id(), $dsn_target_user ) ) ||
				     $all_users === 'on' || ( $myuser === 'on' && get_current_user_id() == $post->post_author ) || ( get_current_user_id() == $post->post_author ) ) :

					echo '<style>#wpf-post-it-' . $post->ID . ' { background-color: ' . $color . ';color: ' . $color_text . ' }</style>';
				endif;
			endforeach;
			echo '<style>[id^=wpf-post-it-] .postbox-header {border-bottom: 0;}[id^=wpf-post-it-] .inside {padding-bottom: 0 !important;}[id^=wpf-post-it-] ul { list-style-type: disc;padding-left:26px }</style>';
		endif;
	}

	public static function template_redirect() {
		if ( is_singular( 'wpf_post_it' ) ) {
			wp_redirect( admin_url(), 301 );
			exit;
		}
	}

	public static function postit_add() {
		check_ajax_referer( 'postit-ajax-nonce', 'nonce' );

		$args = array(
			'post_status' => 'publish',
			'post_type'   => 'wpf_post_it',
			'post_title'  => __( 'New note', 'wpdash-notes' ),
		);

		$post_id = wp_insert_post( $args );

		update_post_meta( $post_id, 'myuser', 'on' );

		$title_tag = version_compare( get_bloginfo( 'version' ), '4.4', '>=' ) ? 'h2' : 'h3';

		ob_start(); ?>

        <div id="wpf-post-it-<?php echo $post_id; ?>" class="postbox">
            <div class="postbox-header"><<?php echo $title_tag; ?> class="hndle
                ui-sortable-handle"><?php _e( 'New note', 'wpdash-notes' ); ?> <?php _e( '(Addressed to myself)', 'wpdash-notes' ); ?>
            </<?php echo $title_tag; ?>>
        </div>
        <div class="inside">

            <p><?php _e( 'Content of my note to modify', 'wpdash-notes' ); ?></p>

            <p class="community-events-footer" style="padding: 12px 0;">
				<?php
				printf(
					'<a href="%1$s" target="_blank" class="modify-post-it">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-edit"></span></a>',
					'/wp-admin/post.php?post=' . $post_id . '&action=edit',
					__( 'Edit' ),
					/* translators: Accessibility text. */
					__( '(opens in a new tab)' )
				);
				?>

                |

				<?php
				printf(
					'<a href="%1$s" target="_blank">%2$s <span aria-hidden="true" class="dashicons dashicons-no-alt"></span></a>',
					get_delete_post_link( $post_id ),
					__( 'Delete' )
				);
				?>
            </p>
        </div>
        </div>

		<?php
		$return['postit']  = ob_get_clean();
		$return['post_id'] = $post_id;

		echo json_encode( $return );

		die();
	}

	public static function add_postit( $wp_admin_bar ) {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();

		// Only show on dashboard
		if ( 'dashboard' !== $screen->id ) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'id'     => 'postit-add',
			'parent' => 'top-secondary',
			'title'  => '+ ' . __( 'Add note', 'wpdash-notes' ),
			'href'   => 'javascript:void(0);',
		) );
	}

	public static function wp_ajax_post_it_add_new_comment() {
		check_ajax_referer( 'postid_add_new_comment_' . sanitize_text_field( $_POST['postid'] ), 'noncepostit_' . sanitize_text_field( $_POST['postid'] ) );

		$referer = isset( $_POST['referer'] ) ? sanitize_text_field( $_POST['referer'] ) : '';
		if ( $referer !== '/wp-admin/' && $referer !== '/wp-admin/index.php' ) {
			return wp_send_json_error();
		}

		$text    = isset( $_POST['text'] ) ? wp_kses_post( $_POST['text'] ) : '';
		$postid  = isset( $_POST['postid'] ) ? sanitize_text_field( $_POST['postid'] ) : '';
		$user_id = get_current_user_id();

		$post = get_post( $postid );

		if ( 'open' !== $post->comment_status ) {
			return wp_send_json_error();
		}

		$current_user = wp_get_current_user();

		$data = array(
			'comment_post_ID'      => $postid,
			'comment_author'       => ( ! empty( $current_user->user_nicename ) ) ? $current_user->user_nicename : $current_user->user_login,
			'comment_author_email' => $current_user->user_email,
			'comment_content'      => $text,
			'user_id'              => $current_user->ID
		);
		wp_insert_comment( $data );

		wp_send_json_success( [ 'post_id' => $postid, 'nonce' => wp_create_nonce( 'nonce_list_comment' ) ] );
	}

	public static function wp_ajax_post_it_list_comment() {
		check_ajax_referer( 'nonce_list_comment' );

		$post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '';

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( 'wpf_post_it' === $post->post_type ) {
				$comments = get_comments(
					array(
						'post_id' => $post_id,
						'order'   => 'ASC'
					)
				);

				$html = '';

				if ( ! empty ( $comments ) ) {
					$texthtml = ( count( $comments ) > 1 ) ? __( 'Comments :', 'wpdash-notes' ) : __( 'Comment :', 'wpdash-notes' );
					$html     .= $texthtml . '<br>';
					foreach ( $comments as $comment ) {
						$html .= '<b>' . $comment->comment_author . '</b> : ' . $comment->comment_content . '<br>';
					}
				}

				wp_send_json_success( array( 'html' => $html, 'post_id' => $post_id ) );
			}
		}
	}

	public static function wpf_edit_form_after_title() {
		global $post, $wp_meta_boxes;
		if ( 'wpf_post_it' !== $post->post_type ) {
			return false;
		}
		do_meta_boxes( get_current_screen(), 'advanced', $post );
		unset( $wp_meta_boxes[ get_post_type( $post ) ]['advanced'] );
	}

	public static function wpf_wp_dashboard_setup() {
		global $current_user;
		$posts = get_posts( array( 'post_type' => 'wpf_post_it' ) );

		if ( ! empty( $posts ) && is_array( $posts ) ) :
			foreach ( $posts as $key => $post ) :
				$myuser          = get_post_meta( $post->ID, 'myuser', true );
				$all_users       = get_post_meta( $post->ID, 'all_users', true );
				$dsn_target      = get_post_meta( $post->ID, 'dsn_target', true );
				$dsn_target_user = get_post_meta( $post->ID, 'dsn_target_user', true );

				if ( ( is_array( $dsn_target ) && in_array( $current_user->roles[0], $dsn_target ) ) ||
				     ( is_array( $dsn_target_user ) && in_array( get_current_user_id(), $dsn_target_user ) ) ||
				     $all_users === 'on' || ( $myuser === 'on' && get_current_user_id() == $post->post_author ) || ( get_current_user_id() == $post->post_author ) ) :

					$title = $post->post_title;
					if ( get_current_user_id() == $post->post_author || current_user_can( 'manage_options' ) ) {
						if ( ! empty( $myuser ) ) {
							$title .= ' ' . __( '(Addressed to myself)', 'wpdash-notes' );
						} elseif ( $all_users === 'on' ) {
							$title .= ' ' . __( '(Addressed to all)', 'wpdash-notes' );
						} elseif ( ! empty( $dsn_target ) && is_array( $dsn_target ) ) {
							$title .= ' ' . '(' . __( 'Addressed to roles', 'wpdash-notes' ) . ' ' . implode( ', ', $dsn_target ) . ')';
						} elseif ( ! empty( $dsn_target_user ) && is_array( $dsn_target_user ) ) {
							$info = array();
							foreach ( $dsn_target_user as $user ) {
								$first_name = get_user_meta( $user, 'first_name', true );
								$last_name  = get_user_meta( $user, 'last_name', true );
								if ( ! empty( $last_name ) ) {
									$info[] = $first_name . ' ' . $last_name;
								} else {
									$info[] = $first_name;
								}
							}
							if ( count( $info ) > 1 ) {
								$title .= ' (' . __( 'Addressed to users', 'wpdash-notes' ) . ' ' . implode( ', ', $info ) . ')';
							} else {
								$title .= ' (' . __( 'Addressed to user', 'wpdash-notes' ) . ' ' . implode( ', ', $info ) . ')';
							}
						}
					}

					add_meta_box( 'wpf-post-it-' . $post->ID, $title, array(
						__CLASS__,
						'dashboard_sticky_notes_post_content'
					), 'dashboard', 'normal', 'high', $post );
				endif;
			endforeach;
		endif;
	}

	public static function dashboard_sticky_notes_post_content( $var, $args ) {
		$post_id = $args['args']->ID;

		echo do_shortcode( wpautop( $args['args']->post_content ) );

		if ( 'open' === $args['args']->comment_status ) {
			echo '<br><br>';
			echo '<div id="comments" style="clear: both;">';

			$comments = get_comments(
				array(
					'post_id' => $post_id,
					'order'   => 'ASC'
				)
			);

			if ( ! empty ( $comments ) ) {
				$texthtml = ( count( $comments ) > 1 ) ? __( 'Comments :', 'wpdash-notes' ) : __( 'Comment :', 'wpdash-notes' );
				echo $texthtml . '<br>';
				foreach ( $comments as $comment ) {
					echo '<b>' . $comment->comment_author . '</b> : ' . $comment->comment_content . '<br>';
				}
			}
			echo '</div>';

			echo '<p class="hide-if-no-js add-new-comment" data-postid="' . $post_id . '"><button type="button" class="button">' . __( 'Add Comment' ) . '</button></p>';
			echo '<form class="new-comment-form-' . $post_id . ' comment-form-postit" style="display: none" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" method="post">

                    ' . wp_nonce_field( 'postid_add_new_comment_' . $post_id, 'noncepostit_' . $post_id ) . '				
                    <textarea id="new-comment-text" class="regular-text" rows="5" cols="33" style="margin-bottom: 4px" name="new-comment"></textarea>
                    <input type="hidden" id="postid" name="postid" value="' . $post_id . '" />

					' . get_submit_button( __( 'Submit' ), 'secondary', 'new-comment-submit-' . $post_id, false ) . '

                    <button class="new-comment-cancel button-link" data-postid="' . $post_id . '" style="vertical-align: top;line-height: 2;height: 28px;text-decoration: underline;" type="button" aria-expanded="false">
						' . __( 'Cancel' ) . '
                    </button>

                    <span class="spinner"></span>
                    </form>';
		}

		if ( get_current_user_id() == $args['args']->post_author || current_user_can( 'manage_options' ) ) : ?>

            <p class="community-events-footer" style="padding: 12px 0;">
				<?php
				printf(
					'<a href="%1$s" target="_blank" class="modify-post-it">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-edit"></span></a>',
					'/wp-admin/post.php?post=' . $post_id . '&action=edit',
					__( 'Edit' ),
					/* translators: Accessibility text. */
					__( '(opens in a new tab)' )
				);
				?>

                |

				<?php
				printf(
					'<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-plus-alt2"></span></a>',
					'/wp-admin/post-new.php?post_type=wpf_post_it',
					__( 'Add' ),
					/* translators: Accessibility text. */
					__( '(opens in a new tab)' )
				);
				?>

                |

				<?php
				printf(
					'<a href="%1$s" target="_blank">%2$s <span aria-hidden="true" class="dashicons dashicons-no-alt"></span></a>',
					get_delete_post_link( $post_id ),
					__( 'Delete' )
				);
				?>
            </p>
		<?php else : ?>
            <p style="padding: 4px 0;"></p>
		<?php
		endif;

	}

	public static function enqueue_select2_jquery() {
		global $post_type;

		if ( 'wpf_post_it' != $post_type ) {
			return false;
		}

		wp_register_style( 'select2css', WPDASH_NOTES_URL . 'assets/css/select2.min.css', false, '1.0', 'all' );
		wp_register_script( 'select2', WPDASH_NOTES_URL . 'assets/js/select2.min.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'select2css' );
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	// Register Custom Post Type
	public static function wpf_post_it_dashboard() {

		$labels = array(
			'name'                  => _x( 'Notes', 'Post Type General Name', 'wpdash-notes' ),
			'singular_name'         => _x( 'Note', 'Post Type Singular Name', 'wpdash-notes' ),
			'menu_name'             => __( 'Notes', 'wpdash-notes' ),
			'name_admin_bar'        => __( 'Note', 'wpdash-notes' ),
			'archives'              => __( 'Note Archives', 'wpdash-notes' ),
			'attributes'            => __( 'Note Attributes', 'wpdash-notes' ),
			'parent_item_colon'     => __( 'Parent Note:', 'wpdash-notes' ),
			'all_items'             => __( 'All Notes', 'wpdash-notes' ),
			'add_new_item'          => __( 'Add New Note', 'wpdash-notes' ),
			'add_new'               => __( 'Add New', 'wpdash-notes' ),
			'new_item'              => __( 'New Note', 'wpdash-notes' ),
			'edit_item'             => __( 'Edit Note', 'wpdash-notes' ),
			'update_item'           => __( 'Update Note', 'wpdash-notes' ),
			'view_item'             => __( 'View Note', 'wpdash-notes' ),
			'view_items'            => __( 'View Note', 'wpdash-notes' ),
			'search_items'          => __( 'Search Note', 'wpdash-notes' ),
			'not_found'             => __( 'Not found', 'wpdash-notes' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wpdash-notes' ),
			'featured_image'        => __( 'Featured Image', 'wpdash-notes' ),
			'set_featured_image'    => __( 'Set featured image', 'wpdash-notes' ),
			'remove_featured_image' => __( 'Remove featured image', 'wpdash-notes' ),
			'use_featured_image'    => __( 'Use as featured image', 'wpdash-notes' ),
			'insert_into_item'      => __( 'Insert into Note', 'wpdash-notes' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Note', 'wpdash-notes' ),
			'items_list'            => __( 'Note list', 'wpdash-notes' ),
			'items_list_navigation' => __( 'Note list navigation', 'wpdash-notes' ),
			'filter_items_list'     => __( 'Filter Note list', 'wpdash-notes' ),
		);
		$args   = array(
			'label'               => __( 'Notes', 'wpdash-notes' ),
			'description'         => __( 'Post Type Description', 'wpdash-notes' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'author', 'comments' ),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-pressthis',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			//'show_in_rest'        => true,
		);
		register_post_type( 'wpf_post_it', $args );

	}

	public static function wpf_add_meta_boxes() {
		add_meta_box( 'wpfdiv', __( 'Note Options', 'wpdash-notes' ), array(
			__CLASS__,
			'wpf_add_meta_box'
		), 'wpf_post_it', 'advanced', 'high' );
	}

	public static function wpf_add_meta_box() {
		global $post;

		$color           = get_post_meta( $post->ID, 'color', true );
		$color_text      = get_post_meta( $post->ID, 'color_text', true );
		$myuser          = get_post_meta( $post->ID, 'myuser', true );
		$all_users       = get_post_meta( $post->ID, 'all_users', true );
		$dsn_target      = get_post_meta( $post->ID, 'dsn_target', true );
		$dsn_target_user = get_post_meta( $post->ID, 'dsn_target_user', true );
		$sendmail        = get_post_meta( $post->ID, 'sendmail', true );

		if ( empty( $dsn_target ) ) {
			$dsn_target = array();
		}
		if ( empty( $dsn_target_user ) ) {
			$dsn_target_user = array();
		} ?>
        <script>
            jQuery(document).ready(function ($) {
                $('.color_field').each(function () {
                    $(this).wpColorPicker();
                });
            });
        </script>
        <div style="float: left;margin-right: 20px">
            <div class="color-background">
                <p><?php esc_attr_e( 'Choose a color for your note.', 'wpdash-notes' ); ?></p>
                <input class="color_field" type="hidden" name="color" value="<?php esc_attr_e( $color ); ?>"/>
            </div>
        </div>
        <div style="float: left">
            <div class="color-text">
                <p><?php esc_attr_e( 'Choose a color for text your note.', 'wpdash-notes' ); ?></p>
                <input class="color_field" type="hidden" name="color_text" value="<?php esc_attr_e( $color_text ); ?>"/>
            </div>
        </div>
        <div style="clear:both"></div>
        <p>
            <span style="font-weight: bold"><?php _e( 'Show :', 'wpdash-notes' ); ?></span><br><br>
            <label for="myuser" class="selectit" style="margin-right: 10px">
                <input type="checkbox" name="myuser"
                       id="myuser" <?php echo ( $myuser === 'on' ) ? 'checked' : ''; ?> />
				<?php esc_attr_e( 'For just me', 'wpdash-notes' ); ?>
            </label>
            <label for="all_users" class="selectit" style="margin-right: 10px">
                <input type="checkbox" name="all_users"
                       id="all_users" <?php echo ( $all_users === 'on' ) ? 'checked' : ''; ?> />
				<?php esc_attr_e( 'For all users', 'wpdash-notes' ); ?>
            </label>
        </p>
        <p>
            <label for="dsn_target" style="margin-right: 10px"><?php _e( 'By role', 'wpdash-notes' ); ?>
                <select name="dsn_target[]" id="dsn_target" multiple="multiple">
					<?php
					$editable_roles = array_reverse( get_editable_roles() );

					foreach ( $editable_roles as $role => $details ) :
						$name = translate_user_role( $details['name'] );
						if ( in_array( $role, $dsn_target ) ) {
							echo '<option selected="selected" value="' . esc_attr( $role ) . '">' . $name . '</option>';
						} else {
							echo '<option value="' . esc_attr( $role ) . '">' . $name . '</option>';
						}
					endforeach;
					?>
                </select>
            </label>
			<?php if ( wp_is_mobile() ) : ?>
        </p><p>
			<?php endif; ?>
            <label for="dsn_target_user"><?php _e( 'By user', 'wpdash-notes' ); ?>
                <select name="dsn_target_user[]" id="dsn_target_user" multiple="multiple">
					<?php
					$editable_roles = array_reverse( get_editable_roles() );
					$users          = get_users( [
						'role__in' => array_keys( $editable_roles ),
						'exclude'  => array( get_current_user_id() ),
						'orderby'  => 'display_name',
						'order'    => 'ASC'
					] );

					foreach ( $users as $user ) :
						if ( in_array( $user->ID, $dsn_target_user ) ) {
							echo '<option selected="selected" value="' . $user->ID . '">' . $user->display_name . '</option>';
						} else {
							echo '<option value="' . $user->ID . '">' . $user->display_name . '</option>';
						}
					endforeach;
					?>
                </select>
            </label>
        </p>
        <p>
            <label for="sendmail" class="selectit">
                <input type="checkbox" name="sendmail"
                       id="sendmail" <?php echo ( $sendmail === 'on' ) ? 'checked' : ''; ?> />
				<?php esc_attr_e( 'Send alert by mail', 'wpdash-notes' ); ?>
            </label>
            <input type="hidden" name="login_url" value="<?php echo esc_url( wp_login_url() ); ?>">
        </p>
        <script type="text/javascript">
            // <![CDATA[
            jQuery(document).ready(function ($) {
                $('#dsn_target').select2();
                $('#dsn_target_user').select2();
            });
            //-->
        </script>
        <style>
            #wpfdiv .select2-selection__rendered li {
                margin-bottom: 0
            }

            #wpfdiv .select2-search__field {
                margin-top: 0;
            }
        </style>
		<?php

	}

	public static function wpf_save_post() {
		global $post, $post_id;

		if ( ! isset( $post->post_type ) || $post->post_type != 'wpf_post_it' ) {
			return;
		}

		$color           = isset( $_POST['color'] ) ? sanitize_text_field( $_POST['color'] ) : null;
		$color_text      = isset( $_POST['color_text'] ) ? sanitize_text_field( $_POST['color_text'] ) : null;
		$myuser          = isset( $_POST['myuser'] ) ? sanitize_text_field( $_POST['myuser'] ) : null;
		$all_users       = isset( $_POST['all_users'] ) ? sanitize_text_field( $_POST['all_users'] ) : null;
		$dsn_target      = isset( $_POST['dsn_target'] ) ? self::sanitize_dsn_target( $_POST['dsn_target'] ) : null;
		$dsn_target_user = isset( $_POST['dsn_target_user'] ) ? self::sanitize_dsn_target_user( $_POST['dsn_target_user'] ) : null;
		$sendmail        = isset( $_POST['sendmail'] ) ? sanitize_text_field( $_POST['sendmail'] ) : null;

		if ( ! empty( $color ) ) {
			update_post_meta( $post_id, 'color', $color );
		}

		if ( ! empty( $color_text ) ) {
			update_post_meta( $post_id, 'color_text', $color_text );
		}

		if ( ! empty( $myuser ) ) {
			update_post_meta( $post_id, 'myuser', $myuser );
		} else {
			update_post_meta( $post_id, 'myuser', '' );
		}

		if ( ! empty( $all_users ) ) {
			update_post_meta( $post_id, 'all_users', $all_users );
			if ( ! empty( $sendmail ) ) {
				$users = get_users();
				foreach ( $users as $user ) {
					$user            = get_userdata( $user );
					$to              = $user->user_email;
					$subject         = '[' . get_bloginfo( 'name' ) . '] - ' . __( 'A new note for you', 'wpdash-notes' );
					$subject         = apply_filters( 'wpf_post_it_dashboard_subject', $subject );
					$post_authorname = get_the_author_meta( 'display_name', $post->post_author );
					$body            = __( 'A new note which concerns you has been published by', 'wpdash-notes' ) . ' ' . $post_authorname . "\r\n";
					$body            .= '<a href="' . esc_url( $_POST['login_url'] ) . '">' . __( 'Log in to your WordPress Dashboard to read it.', 'wpdash-notes' ) . '</a>' . "\r\n";
					$body            .= 'WordPressement,' . "\r\n";
					$body            .= 'WPDash Notes';
					$body            = apply_filters( 'wpf_post_it_dashboard_body', $body );
					$headers         = array( 'Content-Type: text/html; charset=UTF-8' );

					wp_mail( $to, $subject, $body, $headers );
				}
			}
		} else {
			update_post_meta( $post_id, 'all_users', '' );
		}

		if ( ! empty( $dsn_target ) ) {
			update_post_meta( $post_id, 'dsn_target', $dsn_target );
			if ( ! empty( $sendmail ) ) {
				$users = get_users( [ 'role__in' => $dsn_target ] );
				foreach ( $users as $user ) {
					$user            = get_userdata( $user );
					$to              = $user->user_email;
					$subject         = '[' . get_bloginfo( 'name' ) . '] - ' . __( 'A new note for you', 'wpdash-notes' );
					$subject         = apply_filters( 'wpf_post_it_dashboard_subject', $subject );
					$post_authorname = get_the_author_meta( 'display_name', $post->post_author );
					$body            = __( 'A new note which concerns you has been published by', 'wpdash-notes' ) . ' ' . $post_authorname . "\r\n";
					$body            .= '<a href="' . esc_url( $_POST['login_url'] ) . '">' . __( 'Log in to your WordPress Dashboard to read it.', 'wpdash-notes' ) . '</a>' . "\r\n";
					$body            .= 'WordPressement,' . "\r\n";
					$body            .= 'WPDash Notes';
					$body            = apply_filters( 'wpf_post_it_dashboard_body', $body );
					$headers         = array( 'Content-Type: text/html; charset=UTF-8' );

					wp_mail( $to, $subject, $body, $headers );
				}
			}
		} else {
			update_post_meta( $post_id, 'dsn_target', '' );
		}

		if ( ! empty( $sendmail ) ) {
			update_post_meta( $post_id, 'sendmail', $sendmail );
		} else {
			update_post_meta( $post_id, 'sendmail', '' );
		}

		if ( ! empty( $dsn_target_user ) ) {
			update_post_meta( $post_id, 'dsn_target_user', $dsn_target_user );
			if ( ! empty( $sendmail ) ) {
				foreach ( $dsn_target_user as $user ) {
					$user = get_userdata( $user );

					$to              = $user->user_email;
					$subject         = '[' . get_bloginfo( 'name' ) . '] - ' . __( 'A new note for you', 'wpdash-notes' );
					$subject         = apply_filters( 'wpf_post_it_dashboard_subject', $subject );
					$post_authorname = get_the_author_meta( 'display_name', $post->post_author );
					$body            = __( 'A new note which concerns you has been published by', 'wpdash-notes' ) . ' ' . $post_authorname . "\r\n";
					$body            .= '<a href="' . esc_url( $_POST['login_url'] ) . '">' . __( 'Log in to your WordPress Dashboard to read it.', 'wpdash-notes' ) . '</a>' . "\r\n";
					$body            .= 'WordPressement,' . "\r\n";
					$body            .= 'WPDash Notes';
					$body            = apply_filters( 'wpf_post_it_dashboard_body', $body );
					$headers         = array( 'Content-Type: text/html; charset=UTF-8' );

					wp_mail( $to, $subject, $body, $headers );
				}
			}
		} else {
			update_post_meta( $post_id, 'dsn_target_user', '' );
		}
	}

	public static function sanitize_dsn_target( $vals ) {

		if ( ! is_array( $vals ) ) {
			return null;
		}

		$possible_values = array_keys( get_editable_roles() );

		foreach ( $vals as $val ) {
			if ( ! in_array( $val, $possible_values ) ) {
				return null;
			}
		}

		return $vals;
	}


	public static function sanitize_dsn_target_user( $vals ) {

		if ( ! is_array( $vals ) ) {
			return null;
		}

		$editable_roles = get_editable_roles();
		$users          = get_users( [
			'role__in' => array_keys( $editable_roles ),
			'orderby'  => 'ID',
			'order'    => 'ASC'
		] );

		$possible_values = array();

		foreach ( $users as $user ) {
			array_push( $possible_values, $user->ID );
		}

		foreach ( $vals as $val ) {
			if ( ! in_array( $val, $possible_values ) ) {
				return null;
			}
		}

		return $vals;
	}

}