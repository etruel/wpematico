<?php
// don't load directly 
if ( !defined('ABSPATH') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if (!class_exists('WPeMatico_XML_Importer')) :
class WPeMatico_XML_Importer {
	
	public static $xmlnodes = array();
	public static $xmlreturn = array();

    public static function hooks() {
        add_action('wp_ajax_wpematico_xml_check_data', array( __CLASS__, 'ajax_xml_check_data'));
        add_filter('Wpematico_process_fetching', array(__CLASS__, 'process_fetching'), 10, 1);
    }

    public static function process_fetching($campaign) {
        
        if (is_array($campaign) && $campaign['campaign_type'] == 'xml') {
            
            if ( ! class_exists('Blank_SimplePie')) {
                require_once(WPEMATICO_PLUGIN_DIR. 'app/lib/blank-simplepie.php');
            }
            
            $simplepie = new Blank_SimplePie( $campaign['campaign_xml_feed_url'], 'WPeMatico XML Campaign Type', 'WPeMatico XML Campaign Type');
            
            $data_xml = WPeMatico::get_file_from_url( $campaign['campaign_xml_feed_url'] );
            if ( ! empty( $data_xml ) ) {
                $xml = @simplexml_load_string( $data_xml );

                $campaign_xml_node  = $campaign['campaign_xml_node'];
                $xpath_title        = ( !empty( $campaign_xml_node['post_title'] ) ? $campaign_xml_node['post_title'] : '' );
                $xpath_content      = ( !empty( $campaign_xml_node['post_content'] ) ? $campaign_xml_node['post_content'] : '' );
                $xpath_permalink    = ( !empty( $campaign_xml_node['post_permalink'] ) ? $campaign_xml_node['post_permalink'] : '' );
                $xpath_date         = ( !empty( $campaign_xml_node['post_date'] ) ? $campaign_xml_node['post_date'] : '' );
                $xpath_author       = ( !empty( $campaign_xml_node['post_author'] ) ? $campaign_xml_node['post_author'] : '' );
                 


                if ( ! empty( $xpath_title ) ) {

                    $nodes_title        = $xml->xpath( $xpath_title );
                    $nodes_content      = ( ! empty( $xpath_content ) ? $xml->xpath( $xpath_content ) : array() );
                    $nodes_permalink    = ( ! empty( $xpath_permalink ) ? $xml->xpath( $xpath_permalink ) : array() );
                    $nodes_date         = ( ! empty( $xpath_date ) ? $xml->xpath( $xpath_date ) : array() );
                    $nodes_author       = ( ! empty( $xpath_author ) ? $xml->xpath( $xpath_author ) : array() );
                    
                    foreach ($nodes_title as $key_node_title => $node_title) {
                        

                        $new_title      = $node_title;
                        $new_content    = ( ! empty( $nodes_content[$key_node_title] ) ? $nodes_content[$key_node_title] : '' );
                        $new_permalink  = ( ! empty( $nodes_permalink[$key_node_title] ) ? $nodes_permalink[$key_node_title] : sanitize_title($new_title) );
                        $new_date       = ( ! empty( $nodes_date[$key_node_title] ) ? $nodes_date[$key_node_title] : '' );
                        $new_author     = ( ! empty( $nodes_author[$key_node_title] ) ? new Blank_SimplePie_Item_Author($nodes_author[$key_node_title])  : '' );
                            
                        $new_simplepie_item = new Blank_SimplePie_Item( $new_title, $new_content, $new_permalink, $new_date, $new_author );
                        $new_simplepie_item->set_feed($simplepie);
                        $simplepie->addItem( $new_simplepie_item );
                   
                    }
                    

                }


            }
                        
            return $simplepie;
        }
        return $campaign;
    }

	public static function metabox( $post ) {
		global $post, $campaign_data, $helptip;
		$campaign_xml_feed_url = $campaign_data['campaign_xml_feed_url'];
        $campaign_xml_node = $campaign_data['campaign_xml_node'];
        
		?>
		<label for="campaign_xml_feed_url"><?php _e('URL of XML', 'wpematico' ); ?></label>
        <input type="text" class="regular-text" id="campaign_xml_feed_url" value="<?php echo $campaign_xml_feed_url; ?>" name="campaign_xml_feed_url">
        <button class="button" type="button" id="xml-campaign-upload-xml-btn"><?php _e('Upload XML', 'wpematico' ); ?></button>
		<div class="xml-campaign-check-data-container">
			<br>
            <button class="button" type="button" id="xml-campaign-check-data-btn"><?php _e('Check data', 'wpematico' ); ?></button>
        </div>

        <div id="xml-campaign-input-nodes-container" <?php echo ( empty($campaign_xml_node) ? 'style="display:none;"' : ''); ?>>
            <?php if ( ! empty( $campaign_xml_node ) ) {
                self::get_xml_input_nodes($campaign_data);
            }
            ?>


        </div>   
        <?php
	}

    public static function ajax_xml_check_data() {
        $nonce = '';
        if (isset($_REQUEST['nonce'])) {
            $nonce = $_REQUEST['nonce'];
        }
        
        if (!wp_verify_nonce($nonce, 'wpematico-xml-check-data-nonce')) {
            wp_die('Security check'); 
        }

        $xml_url = ( !empty( $_REQUEST['xml_feed'] ) ? $_REQUEST['xml_feed'] : '' ); 

        if ( empty( $xml_url ) ) {
            wp_die('Error: Empty feed URL');
        }
        $campaign_data = array(
            'campaign_xml_feed_url' => $xml_url,
            'campaign_xml_node'     => array(),
        );
        self::get_xml_input_nodes( $campaign_data );
        die();
    }

    public static function get_xml_input_nodes($campaign_data) {
        $campaign_xml_feed_url = $campaign_data['campaign_xml_feed_url'];
        $campaign_xml_node = $campaign_data['campaign_xml_node'];
        $data_xml = WPeMatico::get_file_from_url( $campaign_xml_feed_url );
        if ( ! empty( $data_xml ) ) {
            $xml = @simplexml_load_string( $data_xml );
            self::recurse_xml($xml);
        }
        
        ?>
        <br>
        <table class="table_check_data_xml">
            <tr>
                <td><strong><?php _e('Properties', 'wpematico' ); ?></strong></td>
                <td><strong><?php _e('Elements of XML', 'wpematico' ); ?></strong></td>
                
            </tr>
            <tr>
                <td><?php _e('Post title', 'wpematico' ); ?></td>
                <td><?php self::get_select_node_html('post_title', ( !empty( $campaign_xml_node['post_title'] ) ? $campaign_xml_node['post_title'] : '' )  ); ?></td>
            
            </tr>
            <tr>
                <td><?php _e('Post content', 'wpematico' ); ?></td>
                <td><?php self::get_select_node_html('post_content', ( !empty( $campaign_xml_node['post_content'] ) ? $campaign_xml_node['post_content'] : '' )  ); ?></td>
               
            </tr>

            <tr>
                <td><?php _e('Post permalink', 'wpematico' ); ?></td>
                <td><?php self::get_select_node_html('post_permalink', ( !empty( $campaign_xml_node['post_permalink'] ) ? $campaign_xml_node['post_permalink'] : '' )  ); ?></td>
               
            </tr>

            <tr>
                <td><?php _e('Post date', 'wpematico' ); ?></td>
                <td><?php self::get_select_node_html('post_date', ( !empty( $campaign_xml_node['post_date'] ) ? $campaign_xml_node['post_date'] : '' )  ); ?></td>
               
            </tr>

            <tr>
                <td><?php _e('Post author', 'wpematico' ); ?></td>
                <td><?php self::get_select_node_html('post_author', ( !empty( $campaign_xml_node['post_author'] ) ? $campaign_xml_node['post_author'] : '' )  ); ?></td>
            
            </tr>

            
        </table>
        <?php
    }

    public static function get_select_node_html($input, $value) {
        ?>
        <select name="campaign_xml_node[<?php echo $input; ?>]" id="campaign_xml_node_<?php echo $input; ?>" class="">
            <option><?php _e('Select a XML node please.', 'wpematico' ); ?></option>
            <?php
            $first_node_select = "";
            foreach ( self::$xmlnodes as $nodekey => $nodecount ) : ?>
                <?php if ( $first_node_select == '' ) : $first_node_select = $nodecount['key']; endif; ?>
                <option value="<?php echo $nodecount['key']; ?>" <?php selected($nodecount['key'], $value, true); ?> >
                    <?php echo $nodecount['name'].' ('.$nodecount['count'].') '.$nodecount['key'].''; ?>
                </option>

            <?php   foreach ( self::$xmlnodes[$nodekey]['attributes'] as $atr_key => $attr ) : ?>
                        <option value="<?php echo $nodecount['key']. '/@'. $atr_key; ?>" <?php selected($nodecount['key'] . '/@'. $atr_key, $value, true); ?> >
                            <?php echo '- ' . $nodecount['name'].' ('.$nodecount['count'].') '.$nodecount['key']. '/@'. $atr_key; ?>
                        </option>
            <?php
                    endforeach;
            endforeach;
            ?>
        </select>
        <?php
    }

	private static function recurse_xml( $xml , $parent = "" ) {
        $child_count = 0;
        if ( ! empty($xml->children()) ) {

            foreach( $xml->children() as $key => $value ) :
                $child_count++;

                    $name = $value->getName();
                    $current_key = ( empty($parent) ?  (string)$key : $parent . "/" . (string)$key );
                    $count = ( isset( self::$xmlnodes[$current_key]['count'] )  ? self::$xmlnodes[$current_key]['count'] + 1  : 1); 
                    
                    self::$xmlnodes[$current_key] = array(
                        'count'         =>  $count,
                        'name'          =>  $name,
                        'attributes'    =>  $value->attributes(),
                        'key'           =>  $current_key,
                    );
                    
                
                // No childern, aka "leaf node".
                if( self::recurse_xml( $value , $current_key ) == 0 ) {
                    self::$xmlreturn[] = array(
                        'key'           =>  $parent . "/" . (string)$key,
                        'attributes'    =>  $value->attributes(),
                        'value'         =>  maybe_unserialize( htmlspecialchars( $value ) )
                    );
                }
            endforeach;

        }
        
       return $child_count;
    }


}
endif;
WPeMatico_XML_Importer::hooks();

?>