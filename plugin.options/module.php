<?php
class WPW_PluginOptions{
    public $options_id = "";
    public $page_name = "";
    public $options_list = [];

    function __construct($options_id, $page_name){
        $this->options_id = $options_id;
        $this->page_name = $page_name;

        add_action( 'admin_menu', array( $this, 'my_admin_menu'));
        add_action( 'admin_post_nopriv_save_'.$this->options_id, array( &$this, 'save_form' ));
        add_action( 'admin_post_save_'.$this->options_id, array( &$this, 'save_form' ));        

        add_action('admin_enqueue_scripts', array( &$this, 'wp_scripts'));
    }

    function wp_scripts(){
        wp_enqueue_style( "wpw-options", plugin_dir_url( __FILE__ ) . '/options.css', array(), rand().rand() );
        wp_enqueue_script( 'vue', plugin_dir_url( __FILE__ ) . '/vue.global.js', rand().rand(), true );
        wp_enqueue_script( 'sortable', plugin_dir_url( __FILE__ ) . '/sortable.min.js', rand().rand(), true );
        wp_enqueue_script( 'vue-sortable', plugin_dir_url( __FILE__ ) . '/vue.sortable.min.js', rand().rand(), true );
        wp_enqueue_script( 'wpw-options', plugin_dir_url( __FILE__ ) . '/options.js', array("jquery"), rand().rand(), true );
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
            if(isset($option["key"]) && $option["key"] == $option_key){
                $found_option = $option;
            }            
            
            if(isset($option["key"]) && $option["key"] == $this->options_id."_".$option_key){
                $found_option = $option;
            }
        }

        if(!$found_option){
            return $default;
        }

        if(!isset($found_option["default"])){
            $found_option["default"] = $default;
        }

        $option_value = get_option($found_option["key"], $found_option["default"]);
        $option_value = trim(stripslashes($option_value));
        
        if(strpos($option_value, "[") === 0 || strpos($option_value, "{") == 0){
            $json_result = json_decode($option_value);

            if (json_last_error() === JSON_ERROR_NONE) {
                $option_value = $json_result;
            }
        }

        return $option_value;
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
            <label><?php echo $option["label"]; ?></label>
            <input type="text" v-model='element.<?php echo $option["key"]; ?>' name="<?php echo $option["key"]; ?>" value="<?php echo $option["value"]; ?>" />
        <?php
    }
    
    function generate_input_select($option){
        ?>
            <label><?php echo $option["label"]; ?></label>
            <select v-model='element.<?php echo $option["key"]; ?>' name="<?php echo $option["key"]; ?>" data-default="<?php echo $option["value"]; ?>">
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
        <?php
    }
    
    function generate_textarea($option){
        ?>
            <label><?php echo $option["label"]; ?></label>
            <br>
            <textarea v-model='element.<?php echo $option["key"]; ?>' name="<?php echo $option["key"]; ?>"><?php echo $option["value"]; ?></textarea>
            <br>
            <small><?php echo $option["description"]; ?></small>
        <?php
    }      

    function generate_repeater($option){
        ?>
            <div class="repeater-module">
                <label><?php echo $option["label"]; ?></label>
                <small><?php echo $option["description"]; ?></small>

                <?php
                    $item_props = [];
                    if(isset($option["props"]) && is_array($option["props"])){
                        foreach ($option["props"] as $key => $prop) {
                            $item_props[] = $prop["key"];
                        }
                    }
                ?>

                <div class="wpw-repeater-panel">
                    <button 
                    data-props="<?php echo implode(",", $item_props); ?>" 
                    @click.prevent="module.createItem('<?php echo implode(",", $item_props); ?>')"><?php echo $option["label_btn_add"]; ?></button>
                </div>

                <textarea style="display:none;" name="<?php echo $option["key"]; ?>" ref="source_data"><?php echo stripslashes($option["value"]); ?></textarea>
                <div class="wpw-repeater-items">

                    <draggable
                    v-model="module.items" 
                    item-key="uid"
                    handle="label"
                    >
                        <template #item="{element}">
                            <div class="wpw-repeater-item">
                                <div class="item-panel">
                                    <span @click.prevent="module.move(element, -1)"> &#9650; </span>
                                    <span @click.prevent="module.move(element, 1)"> &#9660; </span>

                                    <span @click.prevent="module.deleteItem(element)">delete</span>
                                </div>
                                <?php 
                                if(isset($option["props"]) && is_array($option["props"])){
                                    foreach ($option["props"] as $key => $prop) {
                                        $this->generate_input($prop);
                                    }
                                }
                                ?>
                            </div>
                        </template> 
                    </draggable>
                </div>
            </div>
        <?php
    }  

    function input_wrap($option){
        if(isset($option["key"])){
            $option["value"] = get_option($option["key"], $option["default"]);
        }  

        $defaults = [
            "class" => ""
        ];

        $option = array_merge($defaults, $option);

        if(!isset($option["class"])){
            $option["class"] = "";
        }
        ?>
            <div class="options site-general-options <?php echo $option["class"]; ?>" data-input="<?php echo $option["key"]; ?>" data-type="<?php echo $option["type"]; ?>">
                <?php 
                    if($option["type"] == "input"){
                        $this->generate_input_text($option);
                    }
                
                    if($option["type"] == "select"){
                        $this->generate_input_select($option);
                    }    
            
                    if($option["type"] == "textarea"){
                        $this->generate_textarea($option);
                    }
            
                    if($option["type"] == "repeater"){
                        $this->generate_repeater($option);
                    }
                ?>
            </div>
        <?php
    }

    function generate_input($option){
        $inputs = ["textarea", "input", "select", "repeater"];

        if(in_array($option["type"], $inputs)){
            $this->input_wrap($option);
        }

        if($option["type"] == "html"){
            $this->generate_html($option);
        }

    }  
}

