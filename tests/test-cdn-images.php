<?php
/** Synthetic CDN/entitlement fixtures; no provider calls or live secrets. */
define('ABSPATH', __DIR__ . '/');
$theme = dirname(__DIR__) . '/wp-content/themes/flexpress';
require $theme . '/includes/media-cdn.php';
require $theme . '/includes/media-authorization.php';
require $theme . '/includes/bunnycdn.php';
$checks = 0;
function check($condition, $message) { ++$GLOBALS['checks']; if (!$condition) { throw new RuntimeException($message); } }
$fixture = json_decode(file_get_contents(__DIR__ . '/bunny-signing-vectors.json'), true);
foreach ($fixture['cases'] as $case) {
    $parts = parse_url($case['input']);
    parse_str($parts['query'] ?? '', $params);
    check(FlexPress_Cdn_Signer::sign($parts['path'], $parts['host'], $fixture['key'], $fixture['expires'], $params) === $case['expected'], 'Official Bunny reference vector mismatch');
}
foreach (['//evil.example/x.jpg', 'https://media.example.com.evil.example/x.jpg', 'http://media.example.com/x.jpg',
          'https://user@media.example.com/x.jpg', 'https://media.example.com:443/x.jpg', '/x.jpg?token=x', '/x.jpg#fragment',
          '/a/../x.jpg', '/a/%2E%2E/x.jpg', '/a%2Fx.jpg', '/a%252Fx.jpg', '/a\\x.jpg', '/a//x.jpg', '/x.mp4', '/x.svg', '/x.php', '/x.jpg/'] as $url) {
    try { FlexPress_Cdn_Signer::sign($url, 'media.example.com', 'key', 1893456000); $denied = false; }
    catch (InvalidArgumentException $error) { $denied = true; }
    check($denied, 'Unsafe path or origin accepted');
}
foreach ([['token_path'=>'/'], ['token'=>'forged'], ['expires'=>9999999999], ['token_ignore_params'=>'true'], ['width'=>[]], ['width'=>'1&token_path=/']] as $params) {
    try { FlexPress_Cdn_Signer::sign('/x.jpg', 'media.example.com', 'key', 1893456000, $params); $denied = false; }
    catch (InvalidArgumentException $error) { $denied = true; }
    check($denied, 'CDN parameter widened authorization');
}
$settings = ['bunnycdn_url'=>'media.example.com', 'bunnycdn_cdn_token_key'=>'synthetic-cdn-key',
    'bunnycdn_serve_url'=>'gallery.example.com', 'bunnycdn_storage_token_key'=>'synthetic-gallery-key',
    'bunnycdn_static_host'=>'static.example.com','bunnycdn_static_token_key'=>'synthetic-static-key'];
$age = true; $deadline = time()+35; $denied = false; $admin = false; $ajax = false; $editor = false; $user = 0; $banned = false; $access = false;
$post = (object)['ID'=>101,'post_type'=>'episode','post_status'=>'publish','post_password'=>''];
$fields=[];
function get_option($name,$default=[]) { return $GLOBALS['settings']; }
function flexpress_exclusv_age_enabled() { return $GLOBALS['age']; }
function flexpress_exclusv_age_media_deadline() { if ($GLOBALS['denied']) { throw new RuntimeException('Unavailable'); } return $GLOBALS['deadline']; }
function is_admin() { return $GLOBALS['admin']; }
function wp_doing_ajax() { return $GLOBALS['ajax']; }
function current_user_can($cap,$id) { return $GLOBALS['editor'] && $id === 101; }
function get_the_ID() { return 101; }
function get_post($id) { return $id === 101 ? $GLOBALS['post'] : null; }
function get_field($name,$id) { return $GLOBALS['fields'][$name] ?? ''; }
function get_current_user_id() { return $GLOBALS['user']; }
function wp_cache_delete(...$args) {}
function flexpress_user_is_founder($id) { return false; }
function flexpress_get_membership_status($id) { return $GLOBALS['banned'] ? 'banned' : 'expired'; }
function flexpress_check_episode_access(...$args) { return ['has_access'=>$GLOBALS['access']]; }
function flexpress_check_extras_access(...$args) { return ['has_access'=>$GLOBALS['access']]; }
function flexpress_get_episode_gallery($id) { return $GLOBALS['gallery']; }
function flexpress_get_extras_gallery($id) { return $GLOBALS['gallery']; }
function wp_get_upload_dir() { return ['baseurl'=>'https://site.example.com/wp-content/uploads']; }
// Load the production gallery sorter without instantiating the WordPress admin class.
$source=file_get_contents($theme.'/includes/gallery-system.php');
preg_match('/^function flexpress_sort_gallery_images_by_filename\([^\n]*\)\s*\{.*?^\}/ms',$source,$match);
eval($match[0]);
$url=flexpress_sign_cdn_image('/x.jpg','stream'); parse_str(parse_url($url,PHP_URL_QUERY),$query);
check((int)$query['expires']===$deadline && str_starts_with($query['token'],'HS256-'),'Image outlived age or used embed signature');
$denied=true;
check(flexpress_sign_cdn_image('/x.jpg','gallery')==='','Issuer outage issued image');
$denied=false;$deadline=time()-1;
check(flexpress_sign_cdn_image('/x.jpg','stream')==='','Expired age issued image');
$deadline=time()+35;
$original=$settings;unset($settings['bunnycdn_cdn_token_key']);$settings['bunnycdn_token_key']='iframe-only-key';
check(flexpress_stream_image_url('11111111-1111-1111-1111-111111111111','thumbnail.jpg')==='','Iframe key substituted for CDN key');
$settings=$original;
check(flexpress_stream_image_url('11111111-1111-1111-1111-111111111111','../video.mp4')==='','Stream filename escaped image scope');
check(flexpress_get_bunnycdn_optimized_image_url('https://site.example.com/wp-content/uploads/image.jpg',['width'=>400])!=='','Own upload not signed');
foreach (['https://other.example.com/wp-content/uploads/x.jpg', 'https://site.example.com/wp-content/plugins/private.jpg', 'https://site.example.com/wp-content/uploads/../private.jpg', 'https://site.example.com/wp-content/uploads/image.jpg?token_path=/'] as $url) {
    check(flexpress_get_bunnycdn_optimized_image_url($url)==='','Optimizer signed unapproved source/path');
}
$gallery=[];
for($i=0;$i<7;$i++) { $gallery[]=['filename'=>'image'.$i.'.jpg','bunnycdn_url'=>'https://gallery.example.com/full/image'.$i.'.jpg','bunnycdn_thumbnail_url'=>'https://gallery.example.com/thumbs/image'.$i.'.jpg']; }
foreach (['episode','extras'] as $type) {
    $post->post_type=$type;
    check(flexpress_get_authorized_gallery_image_url(101,0,'large')!=='','First preview denied');
    check(flexpress_get_authorized_gallery_image_url(101,4,'thumbnail')!=='','Fifth thumbnail denied');
    check(flexpress_get_authorized_gallery_image_url(101,4,'large')==='','Fifth full image leaked');
    check(flexpress_get_authorized_gallery_image_url(101,5,'thumbnail')==='','Locked gallery image leaked');
    check(flexpress_get_bound_gallery_url($gallery[6]['bunnycdn_url'],101)==='','Compatibility signer bypassed entitlement');
    $access=true;
    check(flexpress_get_authorized_gallery_image_url(101,6,'large')!=='','Purchased gallery denied');
    $user=41;$banned=true;
    check(flexpress_get_authorized_gallery_image_url(101,6,'large')==='','Banned purchaser obtained gallery URL');
    $user=0;$banned=false;$access=false;
}
$thumb=$gallery[4]['bunnycdn_thumbnail_url'];$gallery[4]['bunnycdn_thumbnail_url']=$gallery[4]['bunnycdn_url'];
check(flexpress_get_authorized_gallery_image_url(101,4,'thumbnail')==='','Aliased fifth thumbnail exposed full file');
$gallery[4]['bunnycdn_thumbnail_url']=$thumb;
$admin=true;$editor=true;$denied=true;
check(flexpress_get_authorized_gallery_image_url(101,6,'large',true)!=='','Authorized native editor blocked by visitor age');
$ajax=true;
check(flexpress_get_authorized_gallery_image_url(101,6,'large',true)==='','Admin AJAX context bypassed age');
$ajax=false;$editor=false;
check(flexpress_get_authorized_gallery_image_url(101,6,'large',true)==='','Non-editor used admin signing branch');
$admin=false;$denied=false;$post->post_type='episode';$fields['hidden_from_public']=true;
check(flexpress_get_authorized_gallery_image_url(101,0,'thumbnail')==='','Anonymous hidden-gallery signing');
$fields=[];$post->post_password='private';
check(flexpress_get_authorized_gallery_image_url(101,0,'thumbnail')==='','Password-protected gallery signing');
$post->post_password='';$post->post_status='draft';
check(flexpress_get_authorized_gallery_image_url(101,0,'thumbnail')==='','Unpublished gallery signing');
echo "CDN image authorization passed ($checks assertions), including official reference vectors.\n";
