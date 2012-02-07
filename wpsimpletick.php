<?php
/**
 * @package WP_SIMPLETICK
 * @version 1.0.2
 */
/*
Plugin Name: SimpleTix eTicket Widget
Plugin URI: http://www.SimpleTix.com/wordpress.aspx
Description: Display events and sell tickets directly from your blog.
Author: Aron Kansal
Version: 1.0.2
Author URI: http://www.SimpleTix.com/
*/


$dirName = dirname(__FILE__);
$baseName = basename(realpath($dirName));

/* *********************************************************************************************************************************** */

add_action('init', 'wp_simpletick_my_init');
add_action('admin_menu', 'wp_simpletick_plugin_menu');
add_action('admin_menu', 'wp_simpletick_add_box');
add_filter('the_content', 'wp_simpletick_thecontent'); 
add_filter('the_content', 'wp_simpletick_thecontent'); 
add_action('wp_head', 'wp_simpletick_header');
add_action('wp_ajax_simpletick_ajax', 'wp_simpletick_ajax');
add_action('save_post', 'wp_simpletick_save');


function wp_simpletick_save($postID)
{
	if($parent_id = wp_is_post_revision($postID)){$postID = $parent_id;}

	if (isset($_REQUEST['simpletick_count'])) 
	{
		add_post_meta($postID, 'simpletick_count', $_REQUEST['simpletick_count'], true) or update_post_meta ($postID, 'simpletick_count', $_REQUEST['simpletick_count']);
	}

	if ($_REQUEST['simpletick_chk']=='1')
	{
		add_post_meta($postID, '_simpletick_show', '1', true) or update_post_meta ($postID, '_simpletick_show', '1');
	}
	else
	{
		add_post_meta($postID, '_simpletick_show', '0', true) or update_post_meta ($postID, '_simpletick_show', '0');
	}
	

	if ($_REQUEST['simpletick_chk_pos']=='1')
	{
		add_post_meta($postID, '_simpletick_show_pos', '1', true) or update_post_meta ($postID, '_simpletick_show_pos', '1');
	}
	else
	{
		add_post_meta($postID, '_simpletick_show_pos', '0', true) or update_post_meta ($postID, '_simpletick_show_pos', '0');
	}
}

function wp_simpletick_my_init() 
{
	if (!is_admin()) 
	{
		wp_deregister_script('jquery');
		wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"), false, '1.6.1');
		wp_enqueue_script('jquery');
	}
}

function wp_simpletick_plugin_menu() 
{
	add_options_page('SimpleTix Widget Settings', 'SimpleTix Widget', 'manage_options', 'm-simpletick-widget', 'wp_simpletick_add_options');
}

function wp_simpletick_add_options() 
{
	if (isset ($_REQUEST['wp_simpletick_code']))
	{
		update_option ('simpletick_code', $_REQUEST['wp_simpletick_code']);	
		$str_msg='Widget settings were saved';
	}
	echo '<form method=post><div id="wpbody-content" style="width:620px;">';
	echo '<div class="wrap"><h2>SimpleTix Widget Settings</h2></div>';
	echo '<div class="wrap"><p>You must have a SimpleTix powered site to use this plug-in. <a href="http://www.SimpleTix.com/" target="_blank">Click here</a> to create a new SimpleTix site now.</p></div>';
	echo '<div class="wrap" style="margin-top:30px;"><h3>Copy and Paste Your Widget Code Below</h3></div>';
	echo '<div class="wrap"><textarea style="width:600px; height:100px;" name="wp_simpletick_code">'.stripslashes(get_option('simpletick_code', '')).'</textarea></div>';
	echo '<div class="wrap" style="text-align:right; margin-top:10px;"><span style="float:left;">'.$str_msg.'</span><input type=submit value="Save"></div>';
	echo '</div></form>';
}

function wp_simpletick_add_box() 
{
	global $meta_box;
	add_meta_box('wp_simpletick_metabox', 'SimpleTix Widget', 'wp_simpletick_show_box', 'post', 'normal', 'high');
}

function wp_simpletick_thecontent($content) 
{
	global $post;
	$c='';
	if (is_single())
	{
		if (get_post_meta ($post->ID, '_simpletick_show', true)==1)
		{
			$c='<div id="simpletick-widget"></div>';
		}
	}
	
	if (get_post_meta ($post->ID, '_simpletick_show_pos', true)==1)
	{
		return $c.$content;
	}
	else
	{
		return $content.$c;	
	}
    
}

function wp_simpletick_header()
{	
	if (is_single())
	{
		global $post;
		$code=stripslashes(get_option('simpletick_code'));
		if (strlen($code)>0)
		{
			if (get_post_meta ($post->ID, '_simpletick_show', true))
			{
				echo str_replace ('MAXEVENTS', get_post_meta ($post->ID, 'simpletick_count', true), $code);
			}
		}	
	}
}

function wp_simpletick_show_box()
{
	global $post;
	echo "<script>";
	echo 'function select_wp_simpletick(obj)
		{
			var obj=document.getElementById("wp_simpletick_chk");
			var data = {action: "simpletick_ajax", post_id:"'.$post->ID.'", status: obj.checked?1:0, count: document.getElementById(\'wp_simpletick_count\').value};
			jQuery.post(ajaxurl, data, function(response) 
			{
				;
			});
		}';
	echo "</script>";
	echo "<div style='padding:10px'>";
	
	$checked_state=get_post_meta ($post->ID, '_simpletick_show', true); 
	if ($checked_state==1){$checked_state='checked';}else{$checked_state='';}

	echo "<p><input type=checkbox $checked_state onchange='select_wp_simpletick()' name='simpletick_chk' id='wp_simpletick_chk' value='1'> Display the SimpleTix Ticketing Widget on this Page</p>";


	$checked_state=get_post_meta ($post->ID, '_simpletick_show_pos', true); 
	if ($checked_state==1){$checked_state='checked';}else{$checked_state='';}

	echo "<p><input type=checkbox $checked_state name='simpletick_chk_pos' id='wp_simpletick_chk_pos' value='1'> Display widget before the content</p>";	


	$selected_id=get_post_meta ($post->ID, 'simpletick_count', true); 
	if ($selected_id==0){$selected_id=1;}

	echo "<p>How many events would you like to display: <select id='wp_simpletick_count' name='simpletick_count' onchange='select_wp_simpletick()'>";
	$arr_options=array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '15', '20');
	foreach ($arr_options as $cur)
	{
		if ($cur==$selected_id)
		{
			echo "<option selected value={$cur}>{$cur}</option>";		
		}
		else
		{
			echo "<option value={$cur}>{$cur}</option>";
		}
	}
	echo "</select></p>";



	echo "</div>";
}



function wp_simpletick_ajax() 
{
	add_post_meta($_REQUEST['post_id'], 'simpletick_count', $_REQUEST['count'], true) or update_post_meta ($_REQUEST['post_id'], 'simpletick_count', $_REQUEST['count']);

	if ($_REQUEST['status']=='1')
	{
		add_post_meta($_REQUEST['post_id'], '_simpletick_show', '1', true) or update_post_meta ($_REQUEST['post_id'], '_simpletick_show', '1');
	}
	else
	{
		add_post_meta($_REQUEST['post_id'], '_simpletick_show', '0', true) or update_post_meta ($_REQUEST['post_id'], '_simpletick_show', '0');
	}

	exit();
}

?>