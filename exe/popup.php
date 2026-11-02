<?php
/**
 * Plugin Icons: Popup helper
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2015-2019, Giuseppe Di Terlizzi
 */

# NOTE Some Linux distributon change the location of DokuWiki core libraries (DOKU_INC)
#
#      Bitnami (Docker)         /opt/bitnami/dokuwiki
#      LinuxServer.io (Docker)  /app/dokuwiki
#      Arch Linux               /usr/share/webapps/dokuwiki
#      Debian/Ubuntu            /usr/share/dokuwiki
#
# NOTE If DokuWiki core libraries (DOKU_INC) is in another location you can
#      create a PHP file in bootstrap3 root directory called "doku_inc.php" with
#      this content:
#
#           <?php define('DOKU_INC', '/path/dokuwiki/');
#
#      (!) This file will be deleted on every upgrade of template

$doku_inc_dirs = array(
  '/opt/bitnami/dokuwiki',                       # Bitnami (Docker)
  '/usr/share/webapps/dokuwiki',                 # Arch Linux
  '/usr/share/dokuwiki',                         # Debian/Ubuntu
  '/app/dokuwiki',                               # LinuxServer.io (Docker),
  realpath(dirname(__FILE__) . '/../../../../'), # Default DokuWiki path
);

# Load doku_inc.php file
#
if (file_exists(dirname(__FILE__) . '/../doku_inc.php')) {
    require_once dirname(__FILE__) . '/../doku_inc.php';
}

if (!defined('DOKU_INC')) {
    foreach ($doku_inc_dirs as $dir) {
        if (!defined('DOKU_INC') && @file_exists("$dir/inc/init.php")) {
            define('DOKU_INC', "$dir/");
        }
    }
}

define('DOKU_MEDIAMANAGER', 1); // needed to get proper CSS/JS

global $lang;
global $conf;
global $JSINFO;
global $INPUT;

require_once DOKU_INC . 'inc/init.php';
require_once DOKU_INC . 'inc/template.php';
require_once DOKU_INC . 'inc/lang/en/lang.php';
require_once DOKU_INC . 'inc/lang/' . $conf['lang'] . '/lang.php';

$JSINFO['id']        = '';
$JSINFO['namespace'] = '';

$tmp = array();
\dokuwiki\Extension\Event::createAndTrigger('MEDIAMANAGER_STARTED', $tmp);
session_write_close(); //close session

$icons_plugin = plugin_load('action', 'icons');

$popup_url = DOKU_BASE . 'lib/plugins/icons/exe/popup.php';
$pack      = $INPUT->str('pack', $icons_plugin->getConf('defaultPack'));

$collections_dir    = dirname(__FILE__) . '/../assets/iconify/json';
$collections        = json_decode(file_get_contents(dirname(__FILE__) . '/../assets/iconify/collections.json'), true);
$custom_collections = json_decode(file_get_contents(dirname(__FILE__) . '/../assets/iconify/custom-collections.json'), true);

$collections = array_merge($collections, $custom_collections);
if (!isset($collections[$pack])) $pack = 'fa';
$categories  = array();

ksort($collections);

foreach ($collections as $collection => $data) {
    $categories[$data['category']][] = $collection;
}

ksort($categories);

header('Content-Type: text/html; charset=utf-8');
header('X-UA-Compatible: IE=edge,chrome=1');

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $conf['lang'] ?>" lang="<?php echo $conf['lang'] ?>" dir="<?php echo $lang['direction'] ?>" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Icons Plugin</title>
  <script>(function(H){H.className=H.className.replace(/\bno-js\b/,'js')})(document.documentElement)</script>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <?php echo tpl_favicon(array('favicon', 'mobile')) ?>
  <?php tpl_metaheaders()?>
  <style type="text/css">
    body { height: 100vh; margin: 0; padding: 0 !important; display: flex; flex-direction: column; overflow: hidden; }
    .popup-content { display: flex; flex: 1; min-height: 0; padding-right: 20px; }
    main { flex: 1; min-width: 0; display: flex; flex-direction: column; overflow-y: auto; }
    aside { flex: 0 0 20%; min-width: 0; overflow: auto; overflow-wrap: anywhere; }
    .btn-icon { margin: 4px; padding: 4px; }
    .collections { padding: 5px; }
    .collection-samples { margin-bottom: 10px; }
    .collection-category { margin-top: 10px; }
    .collection-name { margin-left: 10px; }
    .collection-box, .preview-box { padding: 0 0 0 10px; }
    .collection-box { display: flex; flex-direction: column; flex: 1 0 300px; min-height: 300px; }
    .collection-info, .collection-search { flex-shrink: 0; }
    .collection-icons { flex: 1; min-height: 80px; overflow-y: auto; padding: 10px 0; }
    .icon-entry { overflow-wrap: anywhere; }
    .preview-box { flex-shrink: 0; padding-bottom: 12px; }
    .preview-box > .row { margin-left: 0; margin-right: 0; }
    .preview-box .col-sm-6 { padding-left: 0; padding-right: 8px; }
    #preview { box-sizing: border-box; height: calc(1.5em + 20px); line-height: 1.5; padding: 9px; white-space: pre-wrap; overflow-wrap: anywhere; overflow: auto; }
    .collection-search { margin: 12px 0; }
    .collection-search label { display: block; }
    .collection-search input { box-sizing: border-box; width: 100%; max-width: 360px; }
    .collection-search small { display: block; margin-top: 4px; }
    .icon-entry[hidden], .collection-empty[hidden] { display: none !important; }
    .collection-info ul { margin: 0; padding: 0; }
    .collection-info ul li { display: inline-block; list-style-type: none; padding-right: 5px; }
    footer { flex-shrink: 0; }
    footer .navbar { margin-bottom: 0; }
    .col-sm-6 { width:50%; float: left; }
    .col-sm-4 { width:33.3%; float: left; }
    /**.tab-pane, .hide { display: none; }**/
    button.active { border-style: inset; }
    .box-size .btn { color: #000; }
  </style>
  <script type="text/javascript" src="popup.js?v=<?php echo filemtime(__DIR__ . '/popup.js'); ?>" defer="defer"></script>
</head>
<body class="container-fluid dokuwiki">

  <div class="popup-content">

    <aside>
      <div class="collections">
        <?php foreach ($categories as $category => $collects): ?>
        <h4 class="collection-category"><?php echo $category; ?></h4>
        <?php foreach ($collects as $collection): $data = $collections[$collection];?>
        <div class="collection-name">
          <a href="<?php echo $popup_url; ?>?pack=<?php echo $collection; ?>" data-pack="<?php echo $collection; ?>"><?php echo $data['name']; ?></a>
          <div class="collection-samples">
            <?php foreach ($data['samples'] as $sample) {echo "&nbsp;<span class='iconify' data-icon='$collection:$sample' data-height='16'></span>&nbsp;";}?>
          </div>
        </div>
        <?php
          endforeach;
          endforeach;
        ?>
      </div>
    </aside>

    <main>

      <?php
        if ($pack):
          $collection_name = $pack;
          $collection_data = json_decode(io_readFile("$collections_dir/$collection_name.json.gz"), true);
      ?>

      <div class="collection-box">

        <div class="collection-info">
          <h3>
            <?php echo $collection_data['info']['name']; ?> <?php echo (isset($collection_data['info']['version']) ? '<small>v' . $collection_data['info']['version'] . '</small>' : ''); ?>
          </h3>
          <ul>
            <li>
              <strong>Icon prefix</strong> <code><?php echo $collection_data['prefix']; ?></code>
            </li>
            <li>
              <strong>License</strong> <a href="<?php echo $collection_data['info']['license']['url']; ?>" target="_blank"><?php echo $collection_data['info']['license']['title']; ?></a>
            </li>
            <li>
              <strong>Author</strong> <a href="<?php echo $collection_data['info']['author']['url']; ?>" target="_blank"><?php echo $collection_data['info']['author']['name']; ?></a>
            </li>
            <li>
              <strong>Total icons</strong> <?php echo $collection_data['info']['total']; ?>
            </li>
          </ul>
        </div>
        <div class="collection-search">
          <label for="icon-search">Search this collection</label>
          <input type="search" id="icon-search" class="form-control" placeholder="Search icon names…" autocomplete="off" aria-controls="collection-icons" aria-describedby="icon-search-count" />
          <small id="icon-search-count" role="status" aria-live="polite"></small>
        </div>
        <div class="collection-icons" id="collection-icons">
          <p class="collection-empty" id="icon-search-empty" hidden>No icons found. Try another name.</p>
          <?php foreach (array_keys($collection_data['icons']) as $icon): ?>
            <div class="col-sm-4 col-md-3 icon-entry">
              <button class="btn btn-default btn-xs btn-icon" title="<?php echo $icon ?>" data-icon-name="<?php echo $icon ?>">
                <span class="iconify" data-icon="<?php echo $collection_name; ?>:<?php echo $icon ?>" data-height="32"></span>
              </button>
              <small><?php echo $icon ?></small>
            </div>
          <?php endforeach?>
        </div>
      </div>
      <?php endif;?>

      <div class="preview-box">

        <hr/>

        <div class="row">

          <div class="box-alignment col-sm-6">
            <label>Alignment</label>
            <div class="btn-group btn-group-xs">
              <button class="button btn btn-default active" data-icon-align="" title="Use no align">
                <img src="../../../images/media_align_noalign.png" />
              </button>
              <button class="button btn btn-default" data-icon-align="left" title="Align the icon on the left">
                <img src="../../../images/media_align_left.png" />
              </button>
              <button class="button btn btn-default" data-icon-align="center" title="Align the icon in the center">
                <img src="../../../images/media_align_center.png" />
              </button>
              <button class="button btn btn-default" data-icon-align="right" title="Align the icon on the right">
                <img src="../../../images/media_align_right.png" />
              </button>
            </div>
          </div>

          <div class="box-size col-sm-6">
            <label>Image size</label>
            <div class="btn-group btn-group-xs">
              <button type="button" class="button btn btn-default" data-icon-size="small" title="Small size">
                S
              </button><button type="button" class="button btn btn-default" data-icon-size="medium" title="Medium size">
                M
              </button><button type="button" class="button btn btn-default" data-icon-size="large" title="Large size">
                L
              </button><button type="button" class="button btn btn-default active" data-icon-size="original" title="Original text size">
                O
              </button><button type="button" class="button btn btn-default" data-icon-size="fw" title="Fixed width (for navs)">
                FW
              </button>
            </div>
          </div>

        </div>

        <label>Preview</label>
        <pre id="preview"></pre>

      </div>

    </main>
  </div>

  <footer>
    <nav class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-text">
          <button type="button" id="btn-preview" class="hidden btn btn-default">Preview code</button>
          <button type="button" id="btn-insert" class="btn btn-success">Insert</button>
          <button type="button" id="btn-reset" class="btn btn-default">Reset</button>
        </div>
      </div>
    </nav>
  </footer>

  <input type="hidden" id="output" />
  <input type="hidden" id="icon_pack" value="<?php echo hsc($pack); ?>" />
  <input type="hidden" id="icon_name" />
  <input type="hidden" id="icon_size" value="original" />
  <input type="hidden" id="icon_align" />

</body>
</html>
