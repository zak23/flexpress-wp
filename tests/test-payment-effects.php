<?php
/** Actual notification adapters, with fake transports. Never sends HTTP/email. */
define('ABSPATH', __DIR__ . '/');
$root = getenv('FLEXPRESS_TEST_ROOT') ?: dirname(__DIR__);
$theme = $root . '/wp-content/themes/flexpress';
$calls = []; $checks = 0;
function add_action(...$args) {}
function get_option($key, $default=[]) { return $key==='flexpress_discord_settings' ? ['webhook_url'=>'https://example.invalid/discord'] : $default; }
function get_bloginfo($key) { return 'Synthetic'; }
function get_userdata($id) { return (object) ['ID'=>$id,'user_email'=>'synthetic@example.invalid','display_name'=>'Synthetic']; }
function get_user_meta(...$args) { return ''; }
function wp_remote_post(...$args) { $GLOBALS['calls'][]='discord'; return ['response'=>['code'=>200],'body'=>'']; }
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function is_wp_error($value) { return false; }
class FlexPress_Plunk_API {
    public function is_configured() { return true; }
    public function get_contact_by_email($email) { $GLOBALS['calls'][]='lookup'; return ['id'=>'synthetic']; }
    public function update_contact(...$args) { $GLOBALS['calls'][]='identify'; return []; }
    public function track_event(...$args) { $GLOBALS['calls'][]='track'; return []; }
}
function flexpress_plunk_service() { return new FlexPress_Plunk_Service(); }
function check($condition,$message) { $GLOBALS['checks']++;if(!$condition)throw new RuntimeException($message); }
require $theme.'/includes/flowguard-effects.php';
require $theme.'/includes/discord-notifications.php';
require $theme.'/includes/integrations/class-flexpress-plunk-service.php';
$GLOBALS['flexpress_flowguard_effect_buffer']=[];
$plunk=flexpress_plunk_service();
$plunk->identify_user(1,['status'=>'Current']);
$plunk->track_user_event(1,'membership_started');
$plunk->update_membership_traits(1,'Current');
(new FlexPress_Discord_Notifications())->send_notification(['title'=>'Synthetic'], 'Synthetic', 'financial');
$jobs=$GLOBALS['flexpress_flowguard_effect_buffer'];
check(count($jobs)===4,'Notification adapter did not defer all operations');
check(!$calls,'Adapter sent a request inside the transaction');
check($jobs[2]['args']['method']==='update_membership_traits','Membership traits captured before commit instead of after');
unset($GLOBALS['flexpress_flowguard_effect_buffer']);
foreach($jobs as $job)check(flexpress_flowguard_deliver_effect($job),'Deferred adapter delivery failed');
check(count(array_filter($calls,fn($call)=>$call==='discord'))===1,'Discord delivery count wrong');
check(count(array_filter($calls,fn($call)=>$call==='track'))===1,'Plunk tracking count wrong');
echo "Payment notification adapter tests passed ($checks assertions).\n";
