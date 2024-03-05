<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RTP_RateThisPost {
	protected static $instance = null;

	private function __construct() {
		// Activation and deactivation hooks
		register_activation_hook( RTP_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( RTP_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Init the plugin
		$this->init();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function init() {
		add_action( 'wp_ajax_rtp_vote', array( $this, 'process_vote' ) );
		add_action( 'wp_ajax_nopriv_rtp_vote', array( $this, 'process_vote' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_filter( 'the_content', array( $this, 'display_voting_buttons' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function activate() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;

		$table_name = $wpdb->prefix . 'rtp_votes';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id mediumint(9) NOT NULL,
			user_ip varchar(100) NOT NULL,
			vote tinyint(1) NOT NULL,
			time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	public function deactivate() {
		// Plugin deactivation logic
	}

	public function enqueue_scripts() {
	    wp_enqueue_script( 'rtp-vote-script', plugins_url( '/assets/js/vote-script.js', RTP_PLUGIN_FILE ), array( 'jquery' ), null, true );
	    wp_localize_script( 'rtp-vote-script', 'rtp_ajax_obj', array( 
	        'ajax_url' => admin_url( 'admin-ajax.php' ),
	        'nonce'    => wp_create_nonce( 'rtp_vote_nonce' ),
	    ) );
	    wp_enqueue_style( 'vote-style', plugins_url( '/assets/css/vote-style.css', RTP_PLUGIN_FILE ) );
	}

	public function process_vote() {
		global $wpdb;
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

		// Check nonce for security
	    if (!check_ajax_referer( 'rtp_vote_nonce_' . $post_id, 'security', false )) {
	    	wp_send_json_error('Nonce verification failed.');
	    	return;
	    }
	    
		$vote = isset( $_POST['vote'] ) && in_array( $_POST['vote'], array( 'Yes', 'No' ) ) ? $_POST['vote'] : '';
		$user_ip = $this->get_user_ip();
		$table_name = $wpdb->prefix . 'rtp_votes';

		// Check if the user has already voted
		$has_voted = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND user_ip = %s",
			$post_id, $user_ip
		) );

		if ( ! $post_id || ! $vote ) {
			wp_send_json_error( 'Invalid request' );
		}

		if ( $has_voted ) {
			wp_send_json_error( 'Already voted' );
		}

		// Insert new vote
		$result = $wpdb->insert(
			$table_name,
			array(
				'post_id' => $post_id,
				'user_ip' => $user_ip,
				'vote'    => $vote === 'Yes' ? 1 : 0,
				'time'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%s' )
		);

		if ( $result ) {
			// Calculate and return new vote counts
			$total_votes = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE post_id = $post_id" );
			$yes_votes = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE post_id = $post_id AND vote = 1" );

			wp_send_json_success( array(
				'total_votes' => $total_votes,
				'yes_votes'   => $yes_votes,
				'no_votes'    => $total_votes - $yes_votes,
			) );
		} else {
			wp_send_json_error( 'Could not insert vote' );
		}
	}

	public function add_meta_boxes() {
		add_meta_box( 'rtp_vote_results', __( 'Vote Results', 'vote-this-post' ), array( $this, 'vote_results_meta_box' ), 'post', 'side' );
	}

	public function vote_results_meta_box( $post ) {
	    global $wpdb;
	    $table_name = $wpdb->prefix . 'rtp_votes';
	    $post_id = $post->ID;

	    // Fetch the vote counts from the database
	    $yes_votes = $wpdb->get_var($wpdb->prepare(
	        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND vote = 1", 
	        $post_id
	    ));
	    $no_votes = $wpdb->get_var($wpdb->prepare(
	        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND vote = 0", 
	        $post_id
	    ));
	    $total_votes = $yes_votes + $no_votes;

	    // Calculate percentages
	    $yes_percentage = $total_votes > 0 ? round(($yes_votes / $total_votes) * 100, 2) : 0;
	    $no_percentage = $total_votes > 0 ? round(($no_votes / $total_votes) * 100, 2) : 0;

	    // Display markup with results
	    echo '<div style="padding: 20px;">';
	    echo '<h4>Voting Results</h4>';
	    if ($total_votes > 0) {
	        echo "<p>Yes: $yes_votes ($yes_percentage%)</p>";
	        echo "<p>No: $no_votes ($no_percentage%)</p>";
	        echo "<p>Total Votes: $total_votes</p>";
	    } else {
	        echo "<p>No votes have been cast yet.</p>";
	    }
	    echo '</div>';
	}

	public function display_voting_buttons( $content ) {
	    if ( is_single() && in_the_loop() && is_main_query() ) {
	        global $post;
	        global $wpdb;
	        $user_ip = $this->get_user_ip();
	        $table_name = $wpdb->prefix . 'rtp_votes';
	        $post_id = get_the_ID();

	        // Fetch the user's vote for this post, if it exists.
	        $user_vote = $wpdb->get_row( $wpdb->prepare(
	            "SELECT vote FROM $table_name WHERE post_id = %d AND user_ip = %s",
	            $post_id, $user_ip
	        ) );
	        $total_votes = $wpdb->get_var( $wpdb->prepare(
	        	"SELECT COUNT(*) FROM $table_name WHERE post_id = %d", 
	        	$post_id));
	        $yes_votes = $wpdb->get_var( $wpdb->prepare(
	        	"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND vote = 1", 
	        	$post_id));
	        $no_votes = $total_votes - $yes_votes;

	        // Calculate percentages
	        $yes_percentage = $total_votes > 0 ? round(($yes_votes / $total_votes) * 100) : 0;
        	$no_percentage = $no_percentage = 100 - $yes_percentage;
	        // Create a nonce
        	$vote_nonce = wp_create_nonce('rtp_vote_nonce_' . $post_id);
        	// Vote checks
	        $has_voted = !is_null($user_vote);
	        $vote_type = $has_voted ? ($user_vote->vote ? 'Yes' : 'No') : '';

	        $vote_buttons = array(
	        	array(
	        		'label' => 'Yes',
	        		'class' => 'rtp-vote-btn-yes',
	        		'votes_poercentage' => $yes_percentage,
	        		'icon' 	=> '<svg xmlns="http://www.w3.org/2000/svg" fill="#999999" width="25px" height="25px" viewBox="0 0 256 256" id="Flat"><path d="M128,24A104,104,0,1,0,232,128,104.12041,104.12041,0,0,0,128,24Zm36,72a12,12,0,1,1-12,12A12.0006,12.0006,0,0,1,164,96ZM92,96a12,12,0,1,1-12,12A12.0006,12.0006,0,0,1,92,96Zm84.50488,60.00293a56.01609,56.01609,0,0,1-97.00976.00049,8.00016,8.00016,0,1,1,13.85058-8.01074,40.01628,40.01628,0,0,0,69.30957-.00049,7.99974,7.99974,0,1,1,13.84961,8.01074Z"/></svg>',
	        	),
	        	array(
	        		'label' => 'No',
	        		'class' => 'rtp-vote-btn-no',
	        		'votes_poercentage' => $no_percentage,
	        		'icon'	=> '<svg xmlns="http://www.w3.org/2000/svg" fill="#999999" width="25px" height="25px" viewBox="0 0 256 256" id="Flat"><path d="M128,24A104,104,0,1,0,232,128,104.12041,104.12041,0,0,0,128,24ZM92,96a12,12,0,1,1-12,12A12.0006,12.0006,0,0,1,92,96Zm76,72H88a8,8,0,0,1,0-16h80a8,8,0,0,1,0,16Zm-4-48a12,12,0,1,1,12-12A12.0006,12.0006,0,0,1,164,120Z"/></svg>',
	        	)
	        );

	        // Markup for voting buttons
	        $voting_html = '<div id="rtp-vote-buttons" data-has-voted="'.$has_voted.'">';

	        if ($has_voted)
	        	$voting_html .= '<p class="rtp-title">Thank you for your feedback.</p>';
	        else
	        	$voting_html .= '<p class="rtp-title">Was this article helpful?</p>';

            $voting_html .= '<div class="rtp-btn-wrap">';

            foreach ($vote_buttons as $btn) {

            	// Add active class
            	if ($vote_type == $btn['label'])
            		$active_class = "rtp-active";
            	else
            		$active_class = '';

            	// Add the percentage if voted
            	if ($has_voted)
            		$btn['label'] = $btn['votes_poercentage'] . '%';

            	$voting_html .= '<button class="rtp-vote-btn '.$btn['class'].' '.$active_class.'" data-vote="'.$btn['label'].'" data-post-id="'.$post_id.'" data-nonce="'.$vote_nonce.'">'.$btn['icon'].' '.$btn['label'].'</button>';
            }
            $voting_html .= '</div>';
	        $voting_html .= '</div>';

	        $content .= $voting_html;
	    }

	    return $content;
	}

	private function get_user_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // Trim for safety measures
                    $ip = trim($ip);
                    // Validate IP
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return '0.0.0.0';
    }

}
