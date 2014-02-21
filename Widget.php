<?php

/*
Plugin Name: WordPress MemoryCoin Widget
Description: Add the MemoryCoin price and a donation address to your WordPress blog.
Author: s4l1h
Version: 1.0
Revision Date: 21.02.2014
Requires at least: WP 3.2, PHP 5.3
Tested up to: WP 3.5.1, PHP 5.4
*/

define('mmc_widget_dir', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('mmc_btc', mmc_widget_dir . 'mmc_btc.json');
define('btc_usd', mmc_widget_dir . 'btc_usd.json');
define('mmc_widget_cache_time', 60*60); // 1 hours = 360 sec

function getData($url, $ref = null, $gzip = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Ubuntu; X11; Linux x86_64; rv:8.0) Gecko/20100101 Firefox/8.0");
    if ($ref != null) {
        curl_setopt($curl, CURLOPT_REFERER, $ref);
    } else {
        curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com/');
    }
    if ($gzip != null) {
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language: en-us,en;q=0.5",
                "Accept-Encoding: gzip, deflate",
                "Connection: keep-alive"
            )
        );
    }

    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    $source = curl_exec($curl);

    if (substr($source, 0, 2) == "\x1f\x8b") {
        $source = gzinflate(substr($source, 10, -8));
    }
    if ($source === false) {
        return false;
    }

    return $source;
}


function getMemoryCoinPrice()
{
    $data = getData('http://data.bter.com/api/1/tickers');

    if ($data === false) {
        return false;
    }
    $data=json_decode($data,true);
    file_put_contents(mmc_btc, json_encode($data['mmc_btc']));
}

function getBitcoinPrice()
{
    $data = getData('https://www.bitstamp.net/api/ticker/');

    if ($data === false) {
        return false;
    }
    $data=json_decode($data,true);
    file_put_contents(btc_usd, json_encode($data));
}
function numYap($n){
    return number_format($n, 2, '.', ',');
}
function toCal($a, $b)
{
    return numYap((double)$a * (double)$b);
}

function getMmcWidget()
{
    if (!is_writable(mmc_widget_dir)) {

        echo mmc_widget_dir . " is not writable";
        exit();
    }


    if (file_exists(mmc_btc)) {
        if ((time() - mmc_widget_cache_time) > filemtime(mmc_btc)) {
            getMemoryCoinPrice();
        }
    }else{
        getMemoryCoinPrice();
    }
    if (file_exists(btc_usd)) {
        if ((time() - mmc_widget_cache_time) > filemtime(btc_usd)) {
            getBitcoinPrice();
        }
    }else{
        getBitcoinPrice();

    }
    $mmc_btc = json_decode(file_get_contents(mmc_btc), true);

    $btc_usd = json_decode(file_get_contents(btc_usd), true);

    ?>

    <ul>
        <li><strong>Last:</strong>&nbsp;&nbsp;$<?php echo toCal($mmc_btc['last'], $btc_usd['last']); ?></li>
        <li><strong>High:</strong>&nbsp;$<?php echo toCal($mmc_btc['high'], $btc_usd['last']); ?></li>
        <li><strong>Low:</strong>&nbsp;&nbsp;$<?php echo toCal($mmc_btc['low'], $btc_usd['last']); ?></li>
        <li><strong>Avg:</strong>&nbsp;&nbsp;&nbsp;$<?php echo toCal($mmc_btc['avg'], $btc_usd['last']); ?></li>
        <!--
        <li><strong>Sell:</strong>&nbsp;&nbsp;&nbsp;$<?php echo toCal($mmc_btc['sell'], $btc_usd['last']); ?></li>
        <li><strong>Buy:</strong>&nbsp;&nbsp;&nbsp;$<?php echo toCal($mmc_btc['buy'], $btc_usd['last']); ?></li>
        -->
        <li><strong>Vol MMC:</strong>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo numYap($mmc_btc['vol_mmc']); ?> MMC</li>
        <li><strong>Vol BTC:</strong>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo numYap($mmc_btc['vol_btc']); ?> BTC</li>
        <li><strong>BTC:</strong>&nbsp;&nbsp;&nbsp;&nbsp;$<?php echo numYap($btc_usd['last']); ?></li>
    </ul>
<?php
}

class Memorycoin_Widget extends WP_Widget
{


    /**
     * Register widget with WordPress.
     */
    function __construct()
    {
        parent::__construct(
            'memorycoin_widget', // Base ID
            __('MemoryCoins', 'text_domain'), // Name
            array('description' => __('Show some memorycoin stuff', 'text_domain'),) // Args
        );
    }


    // Extract Args //

    function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']); // The widget title
        $show_price = isset($instance['show_price']) ? $instance['show_price'] : false; // Show the MemoryCoins price
        $donate = isset($instance['donate_memorycoins']) ? $instance['donate_memorycoins'] : false; // Get some MemoryCoins for your blog
        $donation_address = isset($instance['donation_address']) ? $instance['donation_address'] : false; // Donation address

        // Before widget //

        echo $args['before_widget'];

        // Title of widget //

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        // Widget output //
        ?>

        <?php

        if ($show_price) {
            getMmcWidget();
        }
        if ($donate) {
            ?>
            <p style="font-size:10px;">
                Send me some MemoryCoins! <?php echo $donation_address; ?>
            </p>
        <?php
        }

        // After widget //

        echo $args['after_widget'];
    }

    // Update Settings //

    function update($new_instance, $old_instance)
    {

        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['show_price'] = $new_instance['show_price'];
        $instance['donate_memorycoins'] = $new_instance['donate_memorycoins'];
        $instance['donation_address'] = $new_instance['donation_address'];

        return $instance;
    }

    // Widget Control Panel //

    function form($instance)
    {

        $defaults = array(
            'title' => 'MemoryCoins!',
            'show_price' => 'on',
            'donate_memorycoins' => 'on',
            'donation_address' => 'MQoJDguWCMAug2J2rRUEsZPC9UjdSCppkS'
        );
        $instance = wp_parse_args((array)$instance, $defaults); ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>'" type="text"
                   value="<?php echo $instance['title']; ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('show_price'); ?>"><?php _e(
                    'Show the MemoryCoin price?'
                ); ?></label>
            <input type="checkbox" class="checkbox" <?php checked($instance['show_price'], 'on'); ?>
                   id="<?php echo $this->get_field_id('show_price'); ?>"
                   name="<?php echo $this->get_field_name('show_price'); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('donate_memorycoins'); ?>"><?php _e(
                    'Add a memorycoin donation address?'
                ); ?></label>
            <input type="checkbox" class="checkbox" <?php checked($instance['donate_memorycoins'], 'on'); ?>
                   id="<?php echo $this->get_field_id('donate_memorycoins'); ?>"
                   name="<?php echo $this->get_field_name('donate_memorycoins'); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('donation_address'); ?>"><?php _e(
                    'Donation Address:'
                ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('donation_address'); ?>"
                   name="<?php echo $this->get_field_name('donation_address'); ?>" type="text"
                   value="<?php echo $instance['donation_address']; ?>"/>
        </p>
    <?php
    }

} // End class memorycoin_widget


// Register and load the widget
function memorycoin_load_widget()
{
    register_widget('memorycoin_widget');
}

add_action('widgets_init', 'memorycoin_load_widget');