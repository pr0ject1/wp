<?php

//=============================================================================================
// FUNCTION -> ADD SCRIPT FOR NO-JS JS CLASS
//=============================================================================================

function mytheme_html_js_class () {
    echo '<script>document.documentElement.className = document.documentElement.className.replace("no-js","js");</script>'. "\n";
}
add_action( 'wp_head', 'mytheme_html_js_class', 1 );
//=============================================================================================
//Add no-js class to theme html tag (it will be replaced with js if javascript  working) <html class="no-js"></html>
//CSS to fix theme when js is not active no-js .myclass { cssfix }


//=============================================================================================
// FUNCTION -> ADD OPEN GRAPH META TAGS
//=============================================================================================

function meta_og() {
	global $post;
	if ( is_single() ) {
		if( has_post_thumbnail( $post->ID ) ) {
			$img_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'thumbnail' );
		} 
		$excerpt = strip_tags($post->post_content);
		$excerpt_more = '';
		if ( strlen($excerpt ) > 155) {
			$excerpt = substr($excerpt,0,155);
			$excerpt_more = ' ...';
		}
		$excerpt = str_replace( '"', '', $excerpt );
		$excerpt = str_replace( "'", '', $excerpt );
		$excerptwords = preg_split( '/[\n\r\t ]+/', $excerpt, -1, PREG_SPLIT_NO_EMPTY );
		array_pop( $excerptwords );
		$excerpt = implode( ' ', $excerptwords ) . $excerpt_more;
		?>
<meta name="author" content="Your Name">
<meta name="description" content="<?php echo $excerpt; ?>">
<meta property="og:title" content="<?php echo the_title(); ?>">
<meta property="og:description" content="<?php echo $excerpt; ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="<?php echo the_permalink(); ?>">
<meta property="og:site_name" content="Your Site Name">
<meta property="og:image" content="<?php echo $img_src[0]; ?>">
<?php
	} else {
			return;
	}
}
add_action('wp_head', 'meta_og', 5);


//=============================================================================================
// CLASS -> MINIFY HTML OUTPUT
//=============================================================================================

class WP_HTML_Compression {
    protected $compress_css = true;
    protected $compress_js = true;
    protected $info_comment = true;
    protected $remove_comments = true;
 
    protected $html;
    public function __construct($html) {
      if (!empty($html)) {
		    $this->parseHTML($html);
	    }
    }
    public function __toString() {
	    return $this->html;
    }
    protected function minifyHTML($html) {
	    $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
	    preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
	    $overriding = false;
	    $raw_tag = false;
	    $html = '';
	    foreach ($matches as $token) {
		    $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
		    $content = $token[0];
		    if (is_null($tag)) {
			    if ( !empty($token['script']) ) {
				    $strip = $this->compress_js;
			    }
			    else if ( !empty($token['style']) ) {
				    $strip = $this->compress_css;
			    }
			    else if ($content == '<!--wp-html-compression no compression-->') {
				    $overriding = !$overriding;
				    continue;
			    }
			    else if ($this->remove_comments) {
				    if (!$overriding && $raw_tag != 'textarea') {
					    $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
				    }
			    }
		    }
		    else {
			    if ($tag == 'pre' || $tag == 'textarea') {
				    $raw_tag = $tag;
			    }
			    else if ($tag == '/pre' || $tag == '/textarea') {
				    $raw_tag = false;
			    }
			    else {
				    if ($raw_tag || $overriding) {
					    $strip = false;
				    }
				    else {
					    $strip = true;
					    $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);
					    $content = str_replace(' />', '/>', $content);
				    }
			    }
		    }
		    if ($strip) {
			    $content = $this->removeWhiteSpace($content);
		    }
		    $html .= $content;
	    }
	    return $html;
    }
    public function parseHTML($html) {
	    $this->html = $this->minifyHTML($html);
    }
    protected function removeWhiteSpace($str) {
	    $str = str_replace("\t", ' ', $str);
	    $str = str_replace("\n",  '', $str);
	    $str = str_replace("\r",  '', $str);
	    while (stristr($str, '  ')) {
		    $str = str_replace('  ', ' ', $str);
	    }
	    return $str;
    }
}
function wp_html_compression_finish($html) {
    return new WP_HTML_Compression($html);
}
function wp_html_compression_start() {
    ob_start('wp_html_compression_finish');
}
add_action('get_header', 'wp_html_compression_start');










//=============================================================================================
// CLASS -> ADD META BOX TO POST PAGE OR CUSTOM POST (EXTENDS CUSTOM FIELDS)
//=============================================================================================

/**
 * Register a meta box using a class.
 */
class WPDocs_Custom_Meta_Box {
 
    /**
     * Constructor.
     */
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
            add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
        }
 
    }
 
    /**
     * Meta box initialization.
     */
    public function init_metabox() {
        add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );
        add_action( 'save_post',      array( $this, 'save_metabox' ), 10, 2 );
    }
 
    /**
     * Adds the meta box.
     */
    public function add_metabox() {
        add_meta_box(
            'my-meta-box',
            __( 'Meta Box', 'textdomain' ),
            array( $this, 'render_metabox' ),
			
			// where to show, use array to show on diferent post types or pages and posts
			'post',
			 //array( 'post_type_slider', 'post', 'page'),
            'advanced',
            'default'
        );
 
    }
 
    /**
     * Renders the meta box.
     */
    public function render_metabox( $post ) {
		
		
		$meta = get_post_meta( $post->ID );
		$mytheme_input_field = ( isset( $meta['mytheme_input_field'][0] ) && '' !== $meta['mytheme_input_field'][0] ) ? $meta['mytheme_input_field'][0] : '';
		$mytheme_radio_value = ( isset( $meta['mytheme_radio_value'][0] ) && '' !== $meta['mytheme_radio_value'][0] ) ? $meta['mytheme_radio_value'][0] : '';
		$mytheme_is_featured_checkbox_value = ( isset( $meta['mytheme_is_featured_checkbox_value'][0] ) &&  '1' === $meta['mytheme_is_featured_checkbox_value'][0] ) ? 1 : 0;
		$mytheme_limit_featured_excerpt = ( isset( $meta['mytheme_limit_featured_excerpt'][0] ) && '' !== $meta['mytheme_limit_featured_excerpt'][0] ) ? $meta['mytheme_limit_featured_excerpt'][0] : '';

		wp_nonce_field( 'custom_nonce_action', 'custom_nonce' ); // Always add nonce to your meta boxes!
		
		
        // Add nonce for security and authentication. 
       // wp_nonce_field( 'custom_nonce_action', 'custom_nonce' );
		
		?>
        
        		<style type="text/css">
			.post_meta_extras p{margin: 20px;}
			.post_meta_extras label{display:block; margin-bottom: 10px;}
		</style>
		<div class="post_meta_extras">
			<p>
				<label><?php esc_attr_e( 'Input text', 'textdomain' ); ?></label>
				<input type="text" name="mytheme_input_field" value="<?php echo esc_attr( $mytheme_input_field ); ?>">
			</p>
			<p>
				<label>
					<input type="radio" name="mytheme_radio_value" value="value_1" <?php checked( $mytheme_radio_value, 'value_1' ); ?>>
					<?php esc_attr_e( 'Radio value 1', 'textdomain' ); ?>
				</label>
				<label>
					<input type="radio" name="mytheme_radio_value" value="value_2" <?php checked( $mytheme_radio_value, 'value_2' ); ?>>
					<?php esc_attr_e( 'Radio value 2', 'textdomain' ); ?>
				</label>
				<label>
					<input type="radio" name="mytheme_radio_value" value="value_3" <?php checked( $mytheme_radio_value, 'value_3' ); ?>>
					<?php esc_attr_e( 'Radio value 3', 'textdomain' ); ?>
				</label>
			</p>
			<p>
				<label><input type="checkbox" name="mytheme_is_featured_checkbox_value" value="1" <?php checked( $mytheme_is_featured_checkbox_value, 1 ); ?> /><?php esc_attr_e( 'Featured Post (Check to show on homepage)', 'textdomain' ); ?></label>
			</p>
            
            <p>
				<label><?php esc_attr_e( 'Limit Featured Post Excerpt (Showed on homepage)', 'textdomain' ); ?></label>
				<input type="text" name="mytheme_limit_featured_excerpt" value="<?php echo esc_attr( $mytheme_limit_featured_excerpt ); ?>">
			</p>
        
        <?php
		
    }
 
    /**
     * Handles saving the meta box.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @return null
     */
    public function save_metabox( $post_id, $post ) {
        // Add nonce for security and authentication.
        $nonce_name   = isset( $_POST['custom_nonce'] ) ? $_POST['custom_nonce'] : '';
        $nonce_action = 'custom_nonce_action';
 
        // Check if nonce is set.
        if ( ! isset( $nonce_name ) ) {
            return;
        }
 
        // Check if nonce is valid.
        if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
            return;
        }
 
        // Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
 
        // Check if not an autosave.
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }
 
        // Check if not a revision.
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
		
		/* Ok to save */
 
		if ( isset( $_POST['mytheme_input_field'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'mytheme_input_field', sanitize_text_field( wp_unslash( $_POST['mytheme_input_field'] ) ) ); // Input var okay.
		}
 
		if ( isset( $_POST['mytheme_radio_value'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'mytheme_radio_value', sanitize_text_field( wp_unslash( $_POST['mytheme_radio_value'] ) ) ); // Input var okay.
		}
 
		$mytheme_is_featured_checkbox_value = ( isset( $_POST['mytheme_is_featured_checkbox_value'] ) && '1' === $_POST['mytheme_is_featured_checkbox_value'] ) ? 1 : 0; // Input var okay.
		update_post_meta( $post_id, 'mytheme_is_featured_checkbox_value', esc_attr( $mytheme_is_featured_checkbox_value ) );
		
		if ( isset( $_POST['mytheme_limit_featured_excerpt'] ) ) { // Input var okay.
			update_post_meta( $post_id, 'mytheme_limit_featured_excerpt', sanitize_text_field( wp_unslash( $_POST['mytheme_limit_featured_excerpt'] ) ) ); // Input var okay.
		}
		
    }
}
 
new WPDocs_Custom_Meta_Box();
// to show outside of loop
// get_post_meta( int $post_id, string $key = '', bool $single = false )

// inside of the loop
// $input_text = get_post_meta( get_the_ID(), 'mytheme_input_field', true );
// if ( isset( $input_text ) && '' !== $input_text ) {
//	echo esc_attr( $input_text );
// }

// or for checkbox
// $featured_checked = get_post_meta( get_the_ID(), 'mytheme_is_featured_checkbox_value', true );
// if ( isset( $featured_checked ) && '0' !== $input_text ) {
//	 do your code if featured
// }

// to filter all posts which are checked and show only one (last published)
// $the_query = new WP_Query( array( 'post_type'  => 'post', 'meta_key' => 'mytheme_is_featured_checkbox_value', 'meta_value' => '1', 'posts_per_page' => '1'  ) ); 
// while ($the_query -> have_posts()) : $the_query -> the_post();
//  do your stuff
// endwhile; wp_reset_postdata();    

?>
