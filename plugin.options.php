<?php
class SDW_PluginOptions{
    public $options_id = "";
    public $page_name = "";
    public $options_list = [];

    function __construct($options_id, $page_name){
        $this->options_id = $options_id;
        $this->page_name = $page_name;

        add_action( 'admin_menu', array( $this, 'my_admin_menu'));
        add_action( 'admin_post_nopriv_save_'.$this->options_id, array( &$this, 'save_form' ));
        add_action( 'admin_post_save_'.$this->options_id, array( &$this, 'save_form' ));        
    }

    function save_form() {
        if ( 
            ! isset( $_POST[$this->options_id.'-form-field'] ) 
            || ! wp_verify_nonce( $_POST[$this->options_id.'-form-field'], $this->options_id.'-save-options' ) 
        ) {

            wp_redirect(esc_url(site_url( '/wp-admin/admin.php?page='.$this->options_id.'_options_page&error=1' )));
        }

        foreach ($this->options_list as $option) {
            if(!isset($option["key"])) continue;
            $param_val = $this->get_post_data($option["key"], "");
            update_option($option["key"], $param_val);
        }

        wp_redirect(esc_url(site_url( '/wp-admin/admin.php?page='.$this->options_id.'_options_page' )));
    }


    /**
     * @param  [key, type, label, default, html]
     */
    function register_option($option){
        if(isset($option["key"])){
            $option["key"] = $this->options_id."_".$option["key"];
        }
        $this->options_list[] = $option;
    }   

    /**
     * Registers an array of options.
     * @param  [key, type, label, default, html]
     */
    function register_options($options){
        foreach ($options as $option) {
            if(isset($option["key"])){
                $option["key"] = $this->options_id."_".$option["key"];
            }
            
            $this->options_list[] = $option;
        }
    }    
    /**
     *  Gets the value of an option searching for option key or the option key with options id as prefix
     */
    function get_value($option_key, $default = false){
        $found_option = false;
        foreach ($this->options_list as $option) {
            if($option["key"] == $option_key){
                $found_option = $option;
            }            
            
            if($this->options_id."_".$option["key"] == $option_key){
                $found_option = $option;
            }
        }

        if(!$found_option){
            return $default;
        }

        return get_option($found_option["key"], $found_option["default"]);
    }

    function render_inputs(){
        foreach ($this->options_list as $option) {
            $this->generate_input($option);
        }
    }

    function my_admin_menu() {
        add_menu_page( $this->page_name, $this->page_name, 'edit_posts', $this->options_id.'_options_page', array( &$this, "options_page") );
    }

    function options_page(){
        wp_enqueue_media();

        ?>
        <div class="wrap">
    
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
            <form method="post" class='<?php echo $this->options_id; ?>-admin-settings wpw-admin-settings' action="admin-post.php">
                <input name='action' type="hidden" value='save_<?php echo $this->options_id; ?>'>
                <?php  $this->render_inputs(); ?>
                <hr>        
                <?php
                    wp_nonce_field( $this->options_id.'-save-options', $this->options_id.'-form-field' );
                    submit_button();
                ?>
            </form>
        
        </div><!-- .wrap -->
        <style type="text/css">
            .wpw-admin-settings .site-general-options label{
                min-width: 150px;
                display:block;
            }

            .wpw-admin-settings .site-general-options{
                margin-bottom:10px;
                max-width: 600px;
            }

            .wpw-admin-settings .site-general-options input, .wpw-admin-settings .site-general-options select 
            {
                width:400px;
                max-width: 100%;
            }

            .wpw-admin-settings .site-general-options textarea 
            {
                width:100%;
                max-width: 100%;
            }
        </style>
        <?php
    }

    ///////////////////

    function get_post_data($param, $default=""){
        $result = $default;
        if(isset($_REQUEST[$param])){
            $result = $_REQUEST[$param];
        }
        return $result;
    }

    function generate_html($option){
        ?>
            <div class="options site-general-options">
                <?php echo $option["value"]; ?>
            </div>
        <?php
    }

    function generate_input_text($option){
        ?>
            <div class="options site-general-options" data-input="<?php echo $option["key"]; ?>">
                <label><?php echo $option["label"]; ?></label>
                <input type="text" name="<?php echo $option["key"]; ?>" value="<?php echo $option["value"]; ?>" />
            </div>
        <?php
    }
    
    function generate_input_select($option){
        ?>
            <div class="options site-general-options" data-input="<?php echo $option["key"]; ?>">
                <label><?php echo $option["label"]; ?></label>
                <select name="<?php echo $option["key"]; ?>" data-default="<?php echo $option["value"]; ?>">
                    <?php
                        foreach($option["options"] as $key => $label){
                            $selected = "";
                            if($option["value"] == $key){
                                $selected = "selected='selected'";
                            }
                            ?>
                                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
        <?php
    }
    
    function generate_textarea($option){
        ?>
            <div class="options site-general-options" data-input="<?php echo $option["key"]; ?>">
                <label><?php echo $option["label"]; ?></label>
                <br>
                <textarea name="<?php echo $option["key"]; ?>"><?php echo $option["value"]; ?></textarea>
                <br>
                <small><?php echo $option["description"]; ?></small>
            </div>
        <?php
    }  

    function generate_input($option){
        if(isset($option["key"])){
            $option["value"] = get_option($option["key"], $option["default"]);
        }
        
        if($option["type"] == "input"){
            $this->generate_input_text($option);
        }
    
        if($option["type"] == "select"){
            $this->generate_input_select($option);
        }    

        if($option["type"] == "textarea"){
            $this->generate_textarea($option);
        }

        if($option["type"] == "html"){
            $this->generate_html($option);
        }
    }  
}

