<?php
/*
    Plugin Name: Starter Plugin
    Description: 
    Version: 0.1-26ian2023
    Author: wickedpixel
*/
include("plugin.options/module.php");

class WPW_StarterPlugin{
    public $key = "wickedpixel";

    //form parse
    public $email_input = false;

    function __construct() {
        add_action('init', array( &$this, 'init'));
        //add_shortcode( "the_shortcoooode",  array( &$this, "shortcode") );
    }

    function config(){
        global $wickedpixel_plugin_options;

        if($wickedpixel_plugin_options->get_value("api_mode", "sandbox") == "sandbox"){
            return [
                "api_url" => $wickedpixel_plugin_options->get_value("sandbox_api_url", ""), 
            ];
        } else {
            return [
                "api_url" => $wickedpixel_plugin_options->get_value("live_api_url", ""), 
            ];
        }
    }

    function init(){

    }
}

$starter_plugin = new WPW_StarterPlugin();
$starter_options = [
    [
        "key"=> "api_mode",
        "type" => "select",
        "label" => "API Mode",
        "options" => [
            "sandbox"=>"Sandbox",
            "live"=>"Live"
        ],
        "default" => "sandbox"
    ],

    //sandbox
    [
        "type" => "html",
        "value" => "<h3>SANDBOX</h3>",
    ],       
    
    [
        "key"=> "sandbox_api_url",
        "label" => "SANDBOX URL",
        "type" => "input",
        "default" => "",
    ],   
];


$wickedpixel_plugin_options = new WPW_PluginOptions($starter_plugin->key, "Wickedpixel Options");
$wickedpixel_plugin_options->register_options($starter_options);

