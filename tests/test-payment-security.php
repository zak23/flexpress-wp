<?php
/**
 * Isolated payment regression suite. No WordPress DB, network, or real accounts.
 * Run: php tests/test-payment-security.php
 * FLEXPRESS_TEST_ROOT may point to a staged project root.
 */
ini_set('error_log', '/dev/null');
ini_set('display_errors', '1');
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');
define('DAY_IN_SECONDS', 86400);
define('HOUR_IN_SECONDS', 3600);
$root = getenv('FLEXPRESS_TEST_ROOT') ?: dirname(__DIR__);
$theme = $root . '/wp-content/themes/flexpress';
$meta = []; $calls = []; $logged_in = false; $current_id = 41; $checks = 0;
$posts = [101 => (object) ['ID' => 101, 'post_type' => 'episode', 'post_status' => 'publish', 'post_title' => 'Episode'], 102 => (object) ['ID' => 102, 'post_type' => 'extras', 'post_status' => 'publish', 'post_title' => 'Extras']];
$fields = [101 => ['access_type' => 'mixed', 'episode_price' => 25, 'member_discount' => 20], 102 => ['access_type' => 'mixed', 'extras_price' => 20, 'member_discount' => 25]];
class RedirectResponse extends Exception { public $url; public function __construct($url) {$this->url=$url;} }
class JsonResponse extends Exception { public $success; public $data; public $status; public function __construct($success,$data,$status) {$this->success=$success;$this->data=$data;$this->status=$status;} }
class FlexPress_Flowguard_API {
    public function __construct(...$args) {}
    public function cancel_subscription(...$args) {$GLOBALS['cancellations'][]=$args[0];return ['success'=>true];}
    public function start_subscription($data) {global $calls;$calls[]=$data;return ['success'=>true,'session_id'=>'session-'.count($calls)];}
    public function start_purchase($data) {global $calls;$calls[]=$data;return ['success'=>true,'session_id'=>'session-'.count($calls)];}
}
class PaymentTestDB {
    public $last_error=''; public $prefix='wp_'; public $rows=[]; public $insert_id=0; public $busy=false; public $fail_receipt=false; private $snapshot;
    public function prepare($sql,...$args) {return [$sql,$args];}
    public function get_row($query,$format) {
        [$sql,$args]=$query;
        foreach (array_reverse($this->rows) as $row) {
            if (isset($row['__table']) && !str_ends_with($row['__table'],'flexpress_flowguard_transactions')) continue;
            if (str_contains($sql,'WHERE transaction_id =') && ($row['transaction_id']??'')===$args[0]) return $row;
            if (str_contains($sql,'WHERE user_id =') && (int)($row['user_id']??0)===$args[0] && ($row['reference_id']??'')===$args[1]) return $row;
        }
        return null;
    }
    public function insert($table,$data,$format=[]) {
        if ($this->fail_receipt && str_ends_with($table,'flexpress_flowguard_webhooks')) return false;
        $data['__table']=$table;$this->rows[]=$data;$this->insert_id++;return 1;
    }
    public function get_var($query) {
        if (!is_array($query)) return null;
        [$sql,$args]=$query;
        if (str_contains($sql,'GET_LOCK(')) return $this->busy ? 0 : 1;
        if (str_contains($sql,'RELEASE_LOCK(')) return 1;
        if (str_contains($sql,'flexpress_flowguard_webhooks WHERE event_type')) {
            foreach($this->rows as $row) if (($row['event_type']??'')===$args[0] && ($row['transaction_id']??'')===$args[1] && ($row['user_id']??0)===$args[2] && ($row['processed']??0)===1) return 1;
        }
        return null;
    }
    public function query($sql) {
        global $meta;
        if($sql==='START TRANSACTION') $this->snapshot=[$this->rows,$meta];
        if($sql==='ROLLBACK' && $this->snapshot) {[$this->rows,$meta]=$this->snapshot;$this->snapshot=null;}
        if($sql==='COMMIT') $this->snapshot=null;
        return 1;
    }
}
$wpdb = new PaymentTestDB();
function add_action(...$a) {}
function nocache_headers() {}
function get_option($key,$default=[]) {if($key==='flexpress_discord_settings')return ['webhook_url'=>'https://example.invalid'];return $key==='flexpress_flowguard_settings' ? ['shop_id'=>'123','signature_key'=>'test-only-secret'] : $default;}
function get_userdata($id) {return $id>0 ? (object)['ID'=>$id,'user_email'=>'synthetic@example.invalid','display_name'=>'Synthetic'] : false;}
function wp_get_current_user() {return get_userdata(get_current_user_id());}
function get_current_user_id() {global $logged_in,$current_id;return $logged_in ? $current_id : 0;}
function is_user_logged_in() {global $logged_in;return $logged_in;}
function get_user_meta($id,$key,$single=true) {global $meta;return $meta[$id][$key]??'';}
function update_user_meta($id,$key,$value) {global $meta;$meta[$id][$key]=$value;return true;}
function delete_user_meta($id,$key) {global $meta;unset($meta[$id][$key]);}
function wp_set_auth_cookie(...$a) {throw new RuntimeException('Payment flow attempted to set an auth cookie');}
function wp_set_current_user(...$a) {throw new RuntimeException('Payment flow attempted to select a user');}
function wp_safe_redirect($url) {throw new RedirectResponse($url);}
function wp_redirect($url) {throw new RedirectResponse($url);}
function sanitize_text_field($v) {return trim($v);}
function wp_unslash($v) {return stripslashes($v);}
function absint($v) {return abs((int)$v);}
function home_url($v='') {return 'https://example.invalid'.$v;}
function add_query_arg($args,$value=null,$url=null) {
    if (!is_array($args)) {$args=[$args=>$value];} else {$url=$value;}
    return $url . ($args ? (str_contains($url,'?')?'&':'?').http_build_query($args) : '');
}
function get_post($id) {global $posts;return $posts[$id]??null;}
function get_field($key,$id) {global $fields;return $fields[$id][$key]??null;}
function get_the_ID() {return 101;}
function current_time($format) {return $format==='timestamp' ? time() : gmdate('Y-m-d H:i:s');}
function flexpress_user_is_founder($id) {return false;}
function flexpress_is_episode_released($id) {return !get_field('unreleased',$id);}
function flexpress_update_membership_status($id,$status) {update_user_meta($id,'membership_status',$status);}
function wp_cache_delete(...$a) {}
function flexpress_invalidate_user_cache(...$a) {}
function flexpress_flowguard_store_session($session) {global $sessions;$sessions[$session['session_id']]=$session;}
function flexpress_flowguard_get_session($id) {global $sessions;return $sessions[$id]??false;}
function flexpress_flowguard_get_session_by_reference($ref) {global $sessions;foreach($sessions??[] as $s){if($s['reference_id']===$ref)return $s;}return false;}
function flexpress_discord_notify_purchase_approved(...$a) {}
function flexpress_discord_notify_subscription_approved(...$a) {
    flexpress_flowguard_defer_effect('discord', ['embed'=>[], 'content'=>'synthetic', 'channel'=>'financial', 'suppress_notifications'=>false]);
}
class FlexPress_Discord_Notifications {
    public function send_notification(...$args) {$GLOBALS['deliveries'][]=$args;return empty($GLOBALS['delivery_fails']);}
}
function flexpress_discord_notify_subscription_extend(...$a) {}
function flexpress_discord_notify_subscription_cancel(...$a) {}
function flexpress_discord_notify_subscription_expiry(...$a) {}
function flexpress_discord_notify_subscription_rebill(...$a) {}
function flexpress_discord_notify_refund(...$a) {}
function get_users($args) {global $meta;return array_keys(array_filter($meta,fn($m)=>in_array($m['membership_status']??'', ['active','cancelled'],true)));}
function flexpress_add_email_to_blacklist(...$a) {}
function wp_json_encode($v,...$a) {return json_encode($v,...$a);}
function check_ajax_referer($action,$key) {if (($_POST[$key]??'')!=='valid-test-nonce') throw new JsonResponse(false,'nonce',403);}
function wp_send_json_error($data,$status=200) {throw new JsonResponse(false,$data,$status);}
function wp_send_json_success($data,$status=200) {throw new JsonResponse(true,$data,$status);}
function __($v,...$a) {return $v;}
function esc_html__($v,...$a) {return htmlspecialchars($v);}
function esc_html($v) {return htmlspecialchars((string)$v);}
function esc_attr($v) {return htmlspecialchars((string)$v);}
function esc_url($v) {return $v;}
function esc_js($v) {return addslashes((string)$v);}
function get_permalink($id) {return home_url('/content/'.$id);}
function get_header() {}
function get_footer() {}
function wp_next_scheduled(...$a) {return true;}
function wp_schedule_event(...$a) {}
function wp_schedule_single_event(...$a) {}
function flexpress_is_affiliate_system_enabled() {return false;}
function status_header($status) {global $response_status;$response_status=$status;}
function check($condition,$message) {global $checks;$checks++;if(!$condition)throw new RuntimeException($message);}
function load_project_function($path,$name) {
    $source=file_get_contents($path);
    if(!preg_match('/^function '.preg_quote($name,'/').'\([^\n]*\)\s*\{.*?^\}/ms',$source,$match)) throw new RuntimeException('Missing function '.$name);
    eval($match[0]);
}
require $theme.'/includes/flowguard-integration.php';
require $theme.'/includes/flowguard-webhook-handler.php';
require $theme.'/includes/subscription-expiry-checker.php';
foreach(['flexpress_handle_flowguard_payment_return','flexpress_handle_ppv_payment_return','flexpress_handle_ppv_webhook','flexpress_create_ppv_purchase','flexpress_create_flowguard_ppv_purchase_ajax','flexpress_check_episode_access','flexpress_check_extras_access','flexpress_get_membership_status'] as $fn) load_project_function($theme.'/functions.php',$fn);

function flexpress_get_flowguard_shop_id_for_plan($plan) {return '123';}
function flexpress_convert_currency_symbol_to_code($currency) {return 'USD';}
function flexpress_get_pricing_plan($id) {return ['plan_type'=>'recurring','price'=>29.95,'currency'=>'USD','duration'=>30,'duration_unit'=>'days'];}
foreach(['flexpress_create_flowguard_subscription_data','flexpress_create_flowguard_purchase_data'] as $fn) {
    load_project_function($theme.'/includes/pricing-helpers.php',$fn);
    $data=$fn('test-plan',flexpress_get_pricing_plan('test-plan'),41)['data'];
    parse_str(parse_url($data['successUrl'],PHP_URL_QUERY),$query);
    check(($query['ref']??'')===$data['referenceId'],'Provider redirect lost receipt reference: '.$fn);
}
flexpress_flowguard_create_subscription(41,'test-plan');
$data=end($calls);parse_str(parse_url($data['successUrl'],PHP_URL_QUERY),$query);
check(($query['ref']??'')===$data['referenceId'],'Direct subscription redirect lost receipt reference');
$calls=[];$sessions=[];$meta=[];

load_project_function($theme.'/includes/pricing-helpers.php','flexpress_create_flowguard_payment_data');
$plan=flexpress_get_pricing_plan('test-plan');$plan['plan_type']='one_time';
$data=flexpress_create_flowguard_payment_data('test-plan',$plan,41)['data'];
check($data['subscriptionType']==='one-time' && $data['period']==='P30D','Fixed-duration membership lost subscription semantics');
$plan['plan_type']='lifetime';
check(!flexpress_create_flowguard_payment_data('test-plan',$plan,41)['success'],'Unsupported lifetime plan must fail before payment');
$meta=[];

function signed_postback($payload,$alg='HS256',$key='test-only-secret') {
    $b64=function($s){return rtrim(strtr(base64_encode($s),'+/','-_'),'=');};
    $body=$b64(json_encode(['alg'=>$alg])).'.'.$b64(json_encode($payload));
    return $body.'.'.$b64(hash_hmac('sha256',$body,$key,true));
}
$payload=['shopId'=>123,'postbackType'=>'approved','orderType'=>'purchase','referenceId'=>'ppv_ex102_uid41_affnone_promonone_srcnone_ts12345678','transactionId'=>'tx-extras','saleId'=>'sale-extras','priceAmount'=>'20.00','priceCurrency'=>'USD'];
$settings=get_option('flexpress_flowguard_settings');
check(flexpress_flowguard_verify_postback(signed_postback($payload),$settings)===$payload,'Valid signed postback rejected');
foreach(['', 'unsigned', 'a.b.c', signed_postback($payload).'x', signed_postback($payload,'none'), signed_postback($payload,'HS256','wrong-key'), signed_postback(array_merge($payload,['shopId'=>456])), signed_postback(array_merge($payload,['shopId'=>[]]))] as $bad) check(flexpress_flowguard_verify_postback($bad,$settings)===false,'Invalid postback accepted');
check(flexpress_flowguard_verify_postback(signed_postback($payload),[])===false,'Missing merchant configuration accepted');

// Full handler transport tests run in child PHP processes; all effects remain in memory.
if (($argv[1]??'')==='transport') {
    $mode=$argv[2]; $response_status=200;
    $_SERVER['REQUEST_METHOD']=$mode==='get'?'GET':'POST';
    $transport_payload=$mode==='subscription' ? array_merge($payload,['orderType'=>'subscription','subscriptionType'=>'recurring','subscriptionPhase'=>'normal','referenceId'=>'user_41_plan_monthly','nextChargeOn'=>'2026-12-07']) : $payload;
    $test_input=in_array($mode,['valid','subscription'],true) ? signed_postback($transport_payload) : 'custom1=102&custom2=41&custom3=forged';
    class PaymentInputStream {
        public $context; private $position=0;
        public function stream_open($path,$mode,$options,&$opened_path){return true;}
        public function stream_read($count){global $test_input;$out=substr($test_input,$this->position,$count);$this->position+=strlen($out);return $out;}
        public function stream_eof(){global $test_input;return $this->position>=strlen($test_input);}
        public function stream_stat(){return [];}
    }
    stream_wrapper_unregister('php');stream_wrapper_register('php',PaymentInputStream::class);
    register_shutdown_function(function()use($mode){global $meta,$response_status;$granted=in_array(102,$meta[41]['ppv_purchases']??[],true);if(($mode==='valid' && !$granted)||(!in_array($mode,['valid','subscription'],true) && ($granted || $meta))||($mode==='subscription' && ($meta[41]['membership_status']??'')!=='active')||($mode==='invalid' && $response_status!==403)||($mode==='get' && $response_status!==405)){fwrite(STDERR,"Transport assertion failed\n");exit(1);}});
    if (($argv[3]??'')==='canonical') flexpress_flowguard_webhook_handler();
    flexpress_handle_ppv_webhook();exit;
}

// Return routes cannot change membership, purchases, or authentication.
foreach([false,true] as $login){
    $logged_in=$login;
    foreach(['flexpress_handle_flowguard_payment_return','flexpress_handle_ppv_payment_return'] as $fn){
        $_GET=['user_id'=>1,'episode_id'=>101,'ref'=>'invented','plan'=>'premium'];$before=$meta;
        try{$fn();throw new RuntimeException('Return failed to redirect');}catch(RedirectResponse $r){check(str_contains($r->url,$login?'/payment-success/':'/login/'),'Wrong return destination');check(!str_contains($r->url,'user_id'),'Untrusted user ID forwarded');}
        check($meta===$before,'Browser return changed account metadata');
    }
}
$logged_in=false;$_GET=['user_id'=>1];
try{include $theme.'/page-templates/payment-success.php';throw new RuntimeException('Success page accepted unauthenticated user');}catch(RedirectResponse $r){check(str_contains($r->url,'/login/'),'Success page did not require login');}

// Real access helpers, shared checkout helper and fake provider exercise actual prices.
$logged_in=true;$meta=[];
$result=flexpress_flowguard_create_ppv_purchase(41,101,0.01);
check($result['success'] && end($calls)['priceAmount']==='25.00','Client price affected the charge');
$meta[41]['membership_status']='active';
check(flexpress_flowguard_create_ppv_purchase(41,101,0.01)['final_price']===20.0,'Stored episode member discount not applied');
check(flexpress_flowguard_create_ppv_purchase(41,102,0.01,'extras')['final_price']===15.0,'Extras price or discount incorrect');
$extra_order=end($calls);check(str_starts_with($extra_order['referenceId'],'ppv_ex102_uid41_'),'Extras type lost in reference');
check(flexpress_flowguard_parse_enhanced_reference($extra_order['referenceId'])['content_type']==='extras','Extras reference parser failed');
check(flexpress_flowguard_parse_enhanced_reference('ppv_101_41_1234567890')['episode_id']===101,'Legacy references broken');
$meta[41]['membership_status']='none';
$_POST=['nonce'=>'valid-test-nonce','episode_id'=>101,'base_price'=>25,'final_price'=>2.95,'member_discount'=>0];
try{flexpress_create_ppv_purchase();}catch(JsonResponse $r){check($r->success && $r->data['final_price']===25.0,'AJAX trusted submitted price');}
$_POST=['nonce'=>'valid-test-nonce','extras_id'=>102,'final_price'=>0.01];
try{flexpress_create_ppv_purchase();}catch(JsonResponse $r){check($r->success && $r->data['final_price']===20.0,'Extras AJAX failed');}
$_POST=['nonce'=>'valid-test-nonce','episode_id'=>101];
try{flexpress_create_flowguard_ppv_purchase_ajax();}catch(JsonResponse $r){check($r->success && $r->data['final_price']===25.0,'Alternate PPV AJAX failed');}
$logged_in=false;
try{flexpress_create_ppv_purchase();}catch(JsonResponse $r){check(!$r->success && $r->status===401,'Anonymous checkout accepted');}
$logged_in=true;$_POST['nonce']='invalid';
try{flexpress_create_ppv_purchase();}catch(JsonResponse $r){check(!$r->success && $r->status===403,'Invalid nonce accepted');}
$posts[101]->post_status='draft';check(!flexpress_flowguard_create_ppv_purchase(41,101)['success'],'Draft sold');$posts[101]->post_status='publish';
$fields[101]['unreleased']=true;check(!flexpress_flowguard_create_ppv_purchase(41,101)['success'],'Unreleased episode sold');unset($fields[101]['unreleased']);
$fields[101]['access_type']='membership_only';check(!flexpress_flowguard_create_ppv_purchase(41,101)['success'],'Membership-only content sold');$fields[101]['access_type']='mixed';
$meta[41]['membership_status']='banned';check(!flexpress_flowguard_create_ppv_purchase(41,101)['success'],'Banned account purchased');
$meta[41]['membership_status']='active';$meta[41]['trial_expires_at']=gmdate('Y-m-d H:i:s',time()-3*DAY_IN_SECONDS);
check(flexpress_flowguard_create_ppv_purchase(41,101)['final_price']===25.0,'Expired trial got member discount');unset($meta[41]['trial_expires_at']);
$fields[101]['episode_price']=1;check(flexpress_flowguard_create_ppv_purchase(41,101)['final_price']===2.95,'Provider minimum lost');$fields[101]['episode_price']=25;
check(!flexpress_flowguard_create_ppv_purchase(41,102,null,'episode')['success'],'Content type mismatch accepted');

// Signed payment fulfillment unlocks Extras; duplicate delivery does not duplicate access.
$meta=[];$wpdb->rows=[];flexpress_flowguard_handle_purchase_approved($payload);
check(flexpress_check_extras_access(102,41)['has_access'],'Verified Extras purchase not accessible');
flexpress_flowguard_handle_purchase_approved($payload);
check(count($meta[41]['ppv_purchases'])===1,'Duplicate purchase access entry');
check(!flexpress_flowguard_create_ppv_purchase(41,102)['success'],'Owned content sold twice');
$ep_payload=array_merge($payload,['referenceId'=>'ppv_ep101_uid41_affnone_promonone_srcnone_ts12345678','transactionId'=>'tx-episode']);
flexpress_flowguard_handle_purchase_approved($ep_payload);check(flexpress_check_episode_access(101,41)['has_access'],'Episode fulfillment broken');
$bad_type=array_merge($payload,['referenceId'=>'ppv_ep102_uid41_affnone_promonone_srcnone_ts12345678','transactionId'=>'tx-mismatch']);$before=$meta;
flexpress_flowguard_handle_purchase_approved($bad_type);check($meta===$before,'Mismatched content reference changed account');
// Refund cleanup must remove the matching Extras entitlement and shared purchase list.
$meta[41]['purchased_extras_102']='legacy';flexpress_handle_refund_access_revocation(41,array_merge($payload,['postbackType'=>'credit']));
check(!in_array(102,$meta[41]['ppv_purchases'],true) && !isset($meta[41]['purchased_extras_102']),'Extras refund did not revoke access');

// Confirmation can read only this customer's stored transaction; missing/other receipts stay pending.
check(flexpress_flowguard_get_return_transaction(42,'tx-episode')===null,'Other customer receipt leaked');
check(flexpress_flowguard_get_return_transaction(41,'invented')===null,'Unknown receipt accepted');
check(flexpress_flowguard_get_return_transaction(41)===null,'Unrelated latest payment used as confirmation');
check(flexpress_flowguard_get_return_transaction(41,'tx-episode')['transaction_id']==='tx-episode','Own receipt unavailable');
check(flexpress_flowguard_get_return_transaction(41,'',$ep_payload['referenceId'])['transaction_id']==='tx-episode','Reference confirmation failed');
function render_payment_success($params) {global $theme;$_GET=$params;ob_start();include $theme.'/page-templates/payment-success.php';return ob_get_clean();}
$logged_in=true;
$html=render_payment_success(['transaction_id'=>'invented','user_id'=>1]);check(str_contains($html,'Payment confirmation pending')&&!str_contains($html,'Transaction Details'),'Pending payment displayed success details');
$html=render_payment_success(['transaction_id'=>'tx-episode']);check(str_contains($html,'Payment confirmed')&&str_contains($html,'Transaction Details'),'Verified payment did not render receipt');
$current_id=42;$html=render_payment_success(['transaction_id'=>'tx-episode']);check(str_contains($html,'Payment confirmation pending')&&!str_contains($html,'tx-episode'),'Other customer transaction rendered');$current_id=41;

// Membership deadlines, purchase isolation, and recurring retry notices.
$meta=[41=>['membership_status'=>'cancelled','membership_expires'=>'2020-01-01']];
$fields[101]['access_type']='membership_only';
check(!flexpress_check_episode_access(101,41)['has_access'], 'Expired cancelled membership retained access');
$meta=[41=>['membership_status'=>'cancelled','membership_expires'=>gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS)]];
check(flexpress_check_episode_access(101,41)['has_access'], 'Paid-through cancelled member lost access');
$meta=[41=>['membership_status'=>'active','flowguard_sale_id'=>'subscription','next_rebill_date'=>gmdate('Y-m-d H:i:s',time()-3600)]];
check(flexpress_get_membership_status(41)==='active','Rebill grace period lost');
$meta[41]['next_rebill_date']='2020-01-01';
check(flexpress_get_membership_status(41)==='expired','Overdue subscription retained access');
$meta=[41=>['membership_status'=>'banned','trial_expires_at'=>'2020-01-01']];
check(flexpress_get_membership_status(41)==='banned','Expired trial removed account ban');
$meta=[41=>['membership_status'=>'cancelled','membership_expires'=>'2020-01-01'],42=>['membership_status'=>'active','membership_expires'=>'2020-01-01']];
check(flexpress_check_subscription_expiry()===2,'Scheduled expiry skipped cancelled/one-time memberships');
$meta=[41=>['membership_status'=>'active','flowguard_sale_id'=>'subscription','flowguard_subscriber_id'=>'subscription','subscription_amount'=>'40.00','next_rebill_date'=>'2099-01-01']];
flexpress_flowguard_handle_purchase_approved($payload);
check($meta[41]['flowguard_sale_id']==='subscription' && $meta[41]['flowguard_subscriber_id']==='subscription','PPV overwrote subscription ID');
check($meta[41]['subscription_amount']==='40.00','PPV overwrote membership price');
check($meta[41]['flowguard_ppv_sale_id']==='sale-extras','PPV sale audit missing');
$extend=['referenceId'=>'user_41_plan_monthly','subscriptionType'=>'recurring','orderType'=>'subscription','postbackType'=>'extend','nextChargeOn'=>'2099-02-01','expiresOn'=>'2099-02-01'];
flexpress_flowguard_handle_subscription_extend($extend);
check($meta[41]['next_rebill_date']==='2099-01-01' && !isset($meta[41]['membership_expires']),'Recurring retry changed paid access deadline');
$extend['subscriptionType']='one-time';
flexpress_flowguard_handle_subscription_extend($extend);
check($meta[41]['membership_expires']==='2099-02-01','One-time extension broken');
$meta=[41=>['membership_status'=>'expired','trial_expires_at'=>'2020-01-01','membership_expires'=>'2020-01-01']];
$subscription=array_merge($payload,['orderType'=>'subscription','subscriptionType'=>'recurring','referenceId'=>'user_41_plan_monthly','nextChargeOn'=>'2099-01-01']);
flexpress_flowguard_handle_subscription_approved($subscription);
check(flexpress_get_membership_status(41)==='active' && !isset($meta[41]['trial_expires_at']),'Paid subscription retained expired trial deadline');

$fixed=array_merge($subscription,['subscriptionType'=>'one-time','expiresOn'=>'2099-02-01']);
unset($fixed['nextChargeOn']);
flexpress_flowguard_handle_subscription_approved($fixed);
check(!get_user_meta(41,'next_rebill_date',true) && !get_user_meta(41,'flowguard_next_charge_on',true),'Fixed-duration purchase retained previous recurring schedule');
check(get_user_meta(41,'membership_expires',true)==='2099-02-01','Fixed-duration approval lost expiry');

// Durable event receipts, state guards, failure rollback, and legitimate transitions.
$meta=[];$wpdb->rows=[];
$sub=array_merge($payload,['orderType'=>'subscription','subscriptionType'=>'recurring','referenceId'=>'user_41_plan_monthly','transactionId'=>'sub-approval','saleId'=>'sub-sale','nextChargeOn'=>'2099-01-01']);
check(flexpress_flowguard_process_postback($sub)['result']==='processed','New subscription not processed');
$before=$meta;$count=count($wpdb->rows);
check(flexpress_flowguard_process_postback($sub)['result']==='duplicate','Duplicate approval not detected');
check($meta===$before && count($wpdb->rows)===$count,'Duplicate changed data or wrote another receipt');
$meta[41]['membership_status']='banned';
$new_tx=array_merge($sub,['transactionId'=>'unseen-approval']);
check(flexpress_flowguard_process_postback($new_tx)['result']==='ignored' && $meta[41]['membership_status']==='banned','Callback lifted a ban');
$meta[41]['membership_status']='active';
$cancel=array_merge($sub,['postbackType'=>'cancel','expiresOn'=>'2099-01-01']);unset($cancel['transactionId']);
check(flexpress_flowguard_process_postback($cancel)['result']==='processed' && $meta[41]['membership_status']==='cancelled','Cancellation failed');
check(flexpress_flowguard_process_postback($cancel)['result']==='duplicate','Repeated cancellation not skipped');
$delayed=array_merge($sub,['transactionId'=>'late-approval']);
check(flexpress_flowguard_process_postback($delayed)['result']==='ignored' && $meta[41]['membership_status']==='cancelled','Delayed approval undid cancellation');
$uncancel=array_merge($cancel,['postbackType'=>'uncancel']);
check(flexpress_flowguard_process_postback($uncancel)['result']==='processed' && $meta[41]['membership_status']==='active','Legitimate uncancel blocked');
check(flexpress_flowguard_process_postback($cancel)['result']==='processed' && $meta[41]['membership_status']==='cancelled','Second genuine cancellation blocked');
$rebill=array_merge($sub,['postbackType'=>'rebill','transactionId'=>'new-rebill','nextChargeOn'=>'2099-02-01']);
check(flexpress_flowguard_process_postback($rebill)['result']==='processed' && $meta[41]['membership_status']==='cancelled','Rebill reversed cancellation');
$old_rebill=array_merge($rebill,['transactionId'=>'late-rebill','nextChargeOn'=>'2099-01-01']);
check(flexpress_flowguard_process_postback($old_rebill)['result']==='ignored' && $meta[41]['next_rebill_date']==='2099-02-01','Old rebill moved access deadline backwards');
$new_sale=array_merge($sub,['transactionId'=>'new-sale-approval','saleId'=>'new-sub-sale','nextChargeOn'=>'2099-03-01']);
check(flexpress_flowguard_process_postback($new_sale)['result']==='processed' && $meta[41]['flowguard_sale_id']==='new-sub-sale','New paid subscription blocked');
$old_expiry=array_merge($cancel,['postbackType'=>'expiry']);unset($old_expiry['nextChargeOn']);
check(flexpress_flowguard_process_postback($old_expiry)['result']==='ignored' && $meta[41]['membership_status']==='active','Old sale expired current membership');
$old_approval=array_merge($sub,['transactionId'=>'old-sale-replayed']);
check(flexpress_flowguard_process_postback($old_approval)['result']==='ignored' && $meta[41]['flowguard_sale_id']==='new-sub-sale','Superseded sale replaced current subscription');
$wpdb->busy=true;
check(flexpress_flowguard_process_postback($sub)['status']===503,'Lock contention acknowledged instead of retried');$wpdb->busy=false;
$wpdb->fail_receipt=true;$before=$meta;$count=count($wpdb->rows);
$failed=array_merge($payload,['transactionId'=>'retryable-purchase']);
check(flexpress_flowguard_process_postback($failed)['status']===503,'Receipt write failure acknowledged');
check($meta===$before && count($wpdb->rows)===$count,'Failed receipt did not roll back account changes');
$wpdb->fail_receipt=false;
check(flexpress_flowguard_process_postback($failed)['result']==='processed','Provider retry could not recover after rollback');
check(flexpress_flowguard_process_postback(array_merge($sub,['postbackType'=>'unknown']))['status']===422,'Unknown event accepted');

// External work is durable, executes after commit, and preserves bans on cancellation.
$meta=[];$wpdb->rows=[];$deliveries=[];$cancellations=[];
$wpdb->fail_receipt=true;
check(flexpress_flowguard_process_postback($sub)['status']===503,'Failed notification-bearing payment acknowledged');
check(empty($meta[41]['flowguard_pending_effects']) && !$deliveries,'Rolled-back payment queued or sent a notification');
$wpdb->fail_receipt=false;
check(flexpress_flowguard_process_postback($sub)['result']==='processed','Notification-bearing payment failed');
check(count($meta[41]['flowguard_pending_effects'])===1 && !$deliveries,'Notification sent before post-commit worker');
check(flexpress_flowguard_process_postback($sub)['result']==='duplicate' && count($meta[41]['flowguard_pending_effects'])===1,'Replay queued another notification');
$delivery_fails=true;flexpress_flowguard_deliver_effects(41);
check(count($meta[41]['flowguard_pending_effects'])===1,'Failed notification was discarded');
$delivery_fails=false;foreach($meta[41]['flowguard_pending_effects'] as &$job)$job['next_attempt']=0;unset($job);
flexpress_flowguard_deliver_effects(41);
check(empty($meta[41]['flowguard_pending_effects']) && count($deliveries)===2,'Notification retry failed');
flexpress_flowguard_deliver_effects(41);
check(count($deliveries)===2,'Completed notification sent again');
$refund=array_merge($sub,['postbackType'=>'credit','transactionId'=>'refund-old-sale','saleId'=>'older-sale']);
check(flexpress_flowguard_process_postback($refund)['result']==='processed','Refund failed');
check($meta[41]['membership_status']==='banned' && !$cancellations,'Refund cancelled remotely before committing');
check($meta[41]['flowguard_subscriber_id']==='sub-sale','Old refund replaced current billing subscription');
flexpress_flowguard_deliver_effects(41);
check($cancellations===['sub-sale'] && $meta[41]['membership_status']==='banned','Deferred cancellation missed current sale or lifted ban');
$meta[41]['flowguard_pending_effects']=['old'=>['kind'=>'cancel_subscription','args'=>['user_id'=>41,'sale_id'=>'sub-sale'],'attempts'=>0,'next_attempt'=>0]];
$meta[41]['membership_status']='active';$meta[41]['flowguard_subscriber_id']='replacement-sale';
flexpress_flowguard_deliver_effects(41);
check(count($cancellations)===1,'Old cancellation job cancelled a replacement subscription');

foreach(['canonical','legacy'] as $endpoint) foreach(['invalid','get','valid','subscription'] as $mode){
    $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' transport '.escapeshellarg($mode).' '.escapeshellarg($endpoint);
    exec($cmd,$out,$status);check($status===0,'Webhook transport failed: '.$endpoint.' '.$mode);$out=[];
}
echo "Payment security tests passed ({$checks} assertions).\n";
