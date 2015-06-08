<?php

if ( ! class_exists('WordUp_Blogroll_Widget') ) : 

class WordUp_Blogroll_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'widget_wordup_blogroll',
            __( 'WordUp Blogroll', WordUp_Blogroll::TEXTDOMAIN ),
            array(
                'classname' => 'widget_wordup_blogroll widget_links',
                'description' => __( 'WordUp Blogroll', WordUp_Blogroll::TEXTDOMAIN )
            )
        );
    }


    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }

        $blogs = apply_filters('wordup_blogroll_get_blogs_info', array());
        if ( ! empty($blogs) ) {
            ?>
            <ul>
                <?php foreach ( (array)$blogs as $blog ) : ?>
                <li><a href="<?php echo esc_attr($blog->url); ?>"><?php echo esc_html($blog->title); ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php
        }

        echo $args['after_widget'];
    }


    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'WordUp Blogroll', WordUp_Blogroll::TEXTDOMAIN );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', WordUp_Blogroll::TEXTDOMAIN ); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php 
    }


    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }

}

endif;