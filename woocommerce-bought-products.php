<?php
/**
 * Plugin Name: Woocommerce Bought Products 
 * Description: You get a complete list of the products purchased by a current user . You may use [boughtproduct] as a shortcode in your page.
 * Author: MohammedYasar Khalifa
 * Author URI: https://myasark.wordpress.com/
 * Text Domain: woo-bought-products
 * Domain Path: /languages
 * Version: 1.2
 * License: GPL2 or later
 */
defined('ABSPATH') || exit;
class Wooboughtp {
	public function __construct() {
		add_action('admin_menu', array($this, 'woobp_plugin_menu'));
		add_action('admin_init', array($this, 'woobp_register_settings'));
		add_shortcode('boughtproduct', array($this, 'woobp_boughtproduct_list'));
        $yboprolopt = get_option('yboprol_options_name');
        if (isset($yboprolopt['owncss']) != "1") {
        add_action( 'wp_enqueue_scripts', array($this,'woobp_register_plugin_styles' ));
    }
	}
    function woobp_register_plugin_styles() {
        wp_register_style('woobp_css', plugins_url('/assets/css/rbrurls.css', __FILE__));
        wp_enqueue_style('woobp_css');
    }
	public function woobp_register_settings() {
		add_option('yboprol_options_name');
		register_setting('yboprol_options_group', 'yboprol_options_name');
	}
	public function woobp_plugin_menu() {
		add_menu_page('Bought Product', 'WC Bought Product', 'manage_options', 'woobp-setting', array($this, 'woobp_settings'), 'dashicons-list-view', 70);
	}
	public function woobp_settings() {?>
		<div class="container">
		    <form method="post" action="options.php">
		        <?php settings_fields('yboprol_options_group');?>
		        <?php $yboprolopt = get_option('yboprol_options_name');?>
		        <input type="checkbox" name="yboprol_options_name[pagination]" id="pagination" value="1"<?php checked(isset($yboprolopt['pagination']), '1');?> /><label><?php _e( 'Show Pagination', 'woo-bought-products' ); ?></label>
                 <input type="checkbox" name="yboprol_options_name[owncss]" id="owncss" value="1"<?php checked(isset($yboprolopt['owncss']), '1');?> /><label><?php _e( 'Use Own Css', 'woo-bought-products' ); ?></label>
		        <input type="checkbox" name="yboprol_options_name[image]" id="image" value="1"<?php checked(isset($yboprolopt['image']), '1');?> /><label><?php _e( 'Show Image', 'woo-bought-products' ); ?></label>
		        <input type="text" name="yboprol_options_name[productlist]" id="productlist" value="<?php if (isset($yboprolopt['productlist'])) {echo $yboprolopt['productlist'];}?>"/><label><?php _e( 'How Many Product Show on Page.', 'woo-bought-products' ); ?></label><p> <?php _e( '-1 For all products', 'woo-bought-products' ); ?></p>
		        <?php submit_button();?>
		    </form>
		</div>
<?php
}
	public function woobp_boughtproduct_list() {
		?>
 	<?php
       $posts_per_page =-1;		
		$paged = (get_query_var('page')) ? get_query_var('page') : 1;
		$customer_orders = get_posts(array(
			'meta_key' => '_customer_user',
			'meta_value' => get_current_user_id(),
			'post_type' => wc_get_order_types('view-orders'),
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
			'post_status' => array_keys(wc_get_order_statuses()),
		));
		$total_records = count($customer_orders);
		$total_pages = ceil($total_records / $posts_per_page);
		$recently = array();
		foreach ($customer_orders as $customer_order):
			$order = wc_get_order($customer_order);
			$item_count = $order->get_item_count();
			foreach ($order->get_items() as $item_key => $item_values):
				$item_data = $item_values->get_data();
				$product_id = $item_data['product_id'];
				$variation_id = $item_data['variation_id'];            
				if ($variation_id != "0") {
                    $recently[] = $variation_id;} 
                    else { $recently[] = $product_id;}
			endforeach;
		endforeach;	
 $outputs = array_unique($recently);
$yboprolopt = get_option('yboprol_options_name');
$productlist= $yboprolopt['productlist'];
if ($productlist != "") {
    $nb_elem_per_page =$productlist;
}
else {
    $nb_elem_per_page =-1;
}
$page = isset($_GET['brpl'])?intval($_GET['brpl']-1):0;
$number_of_pages = intval(count($outputs)/$nb_elem_per_page)+1;
 $outputs= array_filter($outputs);
foreach (array_slice($outputs, $page*$nb_elem_per_page, $nb_elem_per_page) as  $recently) :
$product = wc_get_product($recently);
$parents = $product->parent_id; ?>
<div class="brpl" > <?php
  $ststus=get_post_status($recently);
  if($ststus == "publish"){
    if ($yboprolopt['image'] == "1") { ?><?php echo $product->get_image(); ?><?php } ?>
      <a href="<?php  if ($parents != "") {echo  get_permalink($parents);} else {echo get_permalink($recently);}?>" ><?php echo $product->get_name(); ?></a>
 <?php } else { ?>
      <?php if ($yboprolopt['image'] == "1") { ?><?php echo $product->get_image(); ?><?php } ?>
<?php echo $product->get_name(); ?>
<?php } ?>
</div> 
<?php endforeach; ?>
<?php if ($yboprolopt['pagination'] == "1") { ?>
<ul class="brpl_pagination" >
    <li><a href="./?brpl=1"><<</span></a></li>
    <?php
    $current_page = isset($_GET['brpl'])?intval($_GET['brpl']-1):0;
   echo '<li class="active"><a href="./?brpl='.$current_page.'">Previous</a></li>';
    if ($current_page <4){
        if ($number_of_pages<9)
        {
            for($i = 1; $i <=  $number_of_pages; $i++)
            {
                if ($i == $current_page)
                {
                    echo '<li class="active"><a href="#">'; echo $i.'</a></li>'."\n";
                } else {
                    echo ' <li><a href="./?brpl='.$i.'">'; echo $i.'</a></li>'."\n";
                }
            }
        }
        else
        {
            for($i = 1; $i <=  9; $i++)
            {
                if ($i == $current_page)
                {
                    echo '<li class="active"><a href="#">'; echo $i.'</a></li>'."\n";
                } else {
                    echo ' <li><a href="./?brpl='.$i.'">'; echo $i.'</a></li>'."\n";
                }
            }
        }
    }
    elseif ($current_page >$number_of_pages-4)
    {
        if ($number_of_pages<9)
        {
            for($i = 1; $i <=  $number_of_pages; $i++){
                if ($i == $current_page){
                    echo '<li class="active"><a href="#">'; echo $i.'</a></li>'."\n";
                } else {
                    echo ' <li><a href="./?brpl='.$i.'">'; echo $i.'</a></li>'."\n";
                }
            }
        }else{
            for($i = $number_of_pages-8; $i <=  $number_of_pages; $i++){
                if ($i == $current_page){
                    echo '<li class="active"><a href="#">'; echo $i.'</a></li>'."\n";
                } else {
                    echo ' <li><a href="./?brpl='.$i.'">'; echo $i.'</a></li>'."\n";
                }
            }
        }
    }else{
        for($i = max(1, $current_page - 4); $i <= min($current_page + 4, $number_of_pages); $i++){
            if ($i == $current_page){
                echo '<li class="active"><a href="#">'; echo $i.'</a></li>'."\n";
            } else {
                echo ' <li><a href="./?brpl='.$i.'">'; echo $i.'</a></li>'."\n";
            }
        }
    }
    ?>
    <li>
        <a href="./?brpl=<?php echo $current_page ?>"><?php _e( 'Next', 'woo-bought-products' ); ?></a>
    </li>
    <li>
        <a href="./?brpl=<?php echo $number_of_pages ?>">>></a>
    </li>
</ul>
<?php } ?>
	<?php return;
	}
}
$Wooboughtp = new Wooboughtp();
function woobp_remove() {
	delete_option('yboprol_options_name');
	unregister_setting('yboprol_options_group', 'yboprol_options_name');
}
register_deactivation_hook(__FILE__, 'woobp_remove');