<?php

if ( !defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class Uji_Interst_Functions {

    /**
     * Check if trigger it
     * @since  1.0
     */
    protected function is_interads( $id, $ajax = null ) {
        $args = array(
            'post_type' => 'interads',
            'post_status' => 'publish',
            'order' => 'DESC',
            'orderby' => 'date',
            'posts_per_page' => 1
        );

        $queryin = new WP_Query( $args );
        $cicle = true;


        while ( $queryin->have_posts() && $cicle ):
            $queryin->the_post();
            $valid = true;

            //Selected
            $is_as_html = get_post_meta( get_the_ID(), 'include_html', true );
            $is_as_url = get_post_meta( get_the_ID(), 'include_url', true );
            $is_as_post = get_post_meta( get_the_ID(), 'add_posts', true );
            if ( $valid && empty( $is_as_html ) && empty( $is_as_url ) && empty( $is_as_post ) ) {
                $valid = false;
            }
          
            //Home Page
            $where = get_post_meta( get_the_ID(), 'where_show', true );

            if ( $valid && $where == 'show_home' ) {
                if ( !is_front_page() && !isset($ajax['is_home']) ) {
                    $valid = false;
                }elseif ( isset($ajax['is_home']) && !$ajax['is_home'] ) {
                    $valid = false;
                }
            }
  
            //CUSTOM PAGE			
            if ( $valid && $where == 'show_cust' && !is_home() && !is_front_page() ) {
        
                $ads_posts = get_post_meta( get_the_ID(), 'ads_posts', true );
                if ( !empty( $ads_posts ) ) {
                    $ids = explode( ",", str_replace(' ', '', $ads_posts) );
                    if ( !in_array( $id, $ids ) || isset($ajax['is_home']) ) {
                        $valid = false;
                    }
                }
            }
        
            //CUSTOM PAGE NOT HOME
            if ( $valid && $where == 'show_cust' && ( is_home() || is_front_page() ) ) {
                $valid = false;
            }
          
            //END RETURN
            if ( $valid ) {
                $cicle = false;
                return get_the_ID();
            }

        endwhile;
        wp_reset_postdata();
    }

    /**
     * Add impression
     * @since  1.0
     */
    protected function impression( $id ) {
        $num = get_post_meta( $id, 'ads_impressions', true );
        $num = (!empty( $num )) ? (int) $num + 1 : 1;
        update_post_meta( $id, 'ads_impressions', $num );
    }

    /**
     * Get Option
     * @since  1.0
     */
    protected function int_option( $name, $default = NULL ) {
        $val = get_option( $this->token );

        if ( !empty( $val[$name] ) )
            return $val[$name];
        elseif ( $default && !empty( $val[$name] ) )
            return $default;
        else
            return '';
    }

    /**
     * Is Cache Plugin
     * @since  1.0
     */
    public function is_cached() {
        $is = $this->int_option( 'cache_in', 'no' );
        $chached = ($is == 'yes') ? true : false;
        return $chached;
    }

    /**
     * Ad content with Cache Plugin
     * @since  1.0
     */
    public function inter_ajax_ads() {
        $id = $_POST['id_post'];
        $ajax = ( isset($_POST['is_front']) && $_POST['is_front'] == 1 ) ? array( 'is_home' => true ) : NULL;

        $ad_id = $this->is_interads( $id, $ajax );
        $mess = $this->inter_ads( $id, $ajax );
        
        
        if ( !empty( $mess ) && $ad_id ) {
            if( !$this->is_cached() ) $this->impression( $ad_id );
            echo $mess;
        } else if ( empty( $mess ) || !$ad_id ) {
            echo 'none_interads';
        }

        die();
    }

    /**
     * Add impression +
     * @since  1.0
     */
    public function inter_ajax_impress() {
        $id = $_POST['id_ad'];
        $this->impression( $id , true);
        die();
    }
    
    /**
     * Get Ad Contents
     * @since  1.0
     */
    protected function get_interad( $id, $return = 'content' ) {

        switch ( $return ) {
            case 'title':
                $show_it = get_post_meta( $id, 'show_title', true );
                if ( $show_it == 'yes' ) {
                    $get_ad = get_post( $id );
                    return $get_ad->post_title;
                }
                break;
            case 'close':
                $close = get_post_meta( $id, 'add_close', true );
                return ($close == "yes") ? true : false;
                break;
            case 'timer':
                $timer = get_post_meta( $id, 'show_count', true );
                return ($timer == "yes") ? true : false;
                break;
            case 'wait':
                //$wtimer  = get_post_meta( $id, 'show_count', true );
                $swait = get_post_meta( $id, 'on_wait_time', true );
                return ( $swait == 'yes' ) ? true : false;
                break;
            default:
                return $this->get_content( $id );
        }
    }

    /**
     * Get Ad Contents
     * @since  1.0
     */
    private function get_content( $id ) {

        $cnt_html = get_post_meta( $id, 'include_html', true );
        $cnt_url = get_post_meta( $id, 'include_url', true );
        $cnt_post = get_post_meta( $id, 'add_posts', true );
        $types = array( 'include_html' );

      
            //is HTML
            if ( $cnt_html ) {
                $get_ad = get_post( $id );
                return do_shortcode( $get_ad->post_excerpt );
            }
            //is HTML
            if ( $cnt_url ) {
                $find_url = false;
                $url_ad = '';
                for ( $x = 1; $x <= 5; $x++ ) {
                    $url = get_post_meta( $id, 'ads_link' . $x, true );
                    if ( !empty( $url ) && $find_url == false ) {
                        $url_ad = $url;
                        $find_url = true;
                    }
                }
                if ( !empty( $url_ad ) ) {
                    $content = '<iframe src="' . $url_ad . '" height="100%" frameborder="0" scrolling="no" id="interads_frame"></iframe> ';
                    return $content;
                } else {
                    return _( 'None URL Ad found', 'ujinter' );
                }
            }
            //include POST
            if ( $cnt_post ) {
                $post_ids = explode( ",", $cnt_post );
                $post_id = trim( $post_ids[0] );
                $page = get_post( $post_id );
                $content = '<h2>' . $page->post_title . '</h2>';
                $content .= '<p>' . do_shortcode( $page->post_content ) . '</p>';
                return $content;
            }
        
    }

}

// End Class
?>
