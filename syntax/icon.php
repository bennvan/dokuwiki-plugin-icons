<?php
/**
 * Plugin Icons for DokuWiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2015-2018, Giuseppe Di Terlizzi
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class syntax_plugin_icons_icon extends DokuWiki_Syntax_Plugin
{

    const IS_ICON      = null;
    const IS_FONT_ICON = null;

    protected $pattern     = '{{icon>.+?}}';
    protected $linkPattern = '\[\[[^\]\r\n]*\|%s\]\]';

    protected $flags   = array();
    protected $classes = array();
    protected $styles  = array();

    /**
     * Syntax Type
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     *
     * @return string
     */
    public function getType()
    {return 'substition';}

    /**
     * Sort for applying this mode
     *
     * @return int
     */
    public function getSort()
    {return 299;}

    /**
     * @param  string  $mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern($this->pattern, $mode, 'plugin_icons_' . $this->getPluginComponent());
        $this->Lexer->addSpecialPattern(sprintf($this->linkPattern, $this->pattern), $mode, 'plugin_icons_' . $this->getPluginComponent());
    }

    public function resolveLinkUrl($link, Doku_Renderer $renderer)
    {
        global $ID;
        global $conf;
        global $INFO;

        $type = $link['type'];
        $id = $link['src'];

        if ($type == 'externallink') {
            // Just return the original
            return array($id, false);
        }

        if ($type == 'interwikilink') {
            //get interwiki URL
            $exists = null;
            $url    = $renderer->_resolveInterWiki($link['wikiname'], $link['wikiuri'], $exists);
            return array($url, $exists);
        }

        if ($type == 'emaillink') {
            // escape characters and return mailto
            $address = $renderer->_xmlEntities($id);
            $address = obfuscate($address);
            if($conf['mailguard'] == 'visible') $address = rawurlencode($address);

            return array('mailto:'.$address, true);
        }

        if ($type == 'locallink') {
            // just return the hash as is
            return array($id, true);
        }

        // Render an internallink

        $params = '';
        $parts  = explode('?', $id, 2);
        if(count($parts) === 2) {
            $id     = $parts[0];
            $params = $parts[1];
        }

        // For empty $id we need to know the current $ID
        // We need this check because _simpleTitle needs
        // correct $id and resolve_pageid() use cleanID($id)
        // (some things could be lost)
        if($id === '') {
            $id = $ID;
        }

        // now first resolve and clean up the $id
        $exists = null;
        resolve_pageid(getNS($ID), $id, $exists, $renderer->date_at, true);

        //keep hash anchor
        @list($id, $hash) = explode('#', $id, 2);
        if(!empty($hash)) $hash = $renderer->_headerToLink($hash);

        if($renderer->date_at) {
            $params = $params.'&at='.rawurlencode($renderer->date_at);
        }

        // Build url
        $url = wl($id, $params);
        //keep hash
        if($hash) $url .= '#'.$hash;
        return array($url, $exists);
    }

    /**
     * Handler to prepare matched data for the rendering process
     *
     * @param   string        $match    The text matched by the patterns
     * @param   int           $state    The lexer state for the match
     * @param   int           $pos      The character position of the matched text
     * @param   Doku_Handler  $handler  The Doku_Handler object
     * @return  bool|array              Return an array with all data you want to use in render, false don't add an instruction
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {

        $url   = null;
        $flags = array();
        $title = null;
        $pack  = null;
        $icon  = null;

        $match = substr($match, 2, -2); // strip markup

        @list($match, $title, $title2) = explode('|', $match);

        if (isset($title2)) {
            $title .= '}}';
        }

        if (isset($title) && preg_match('/' . $this->pattern . '/', $title)) {

            $url   = $match;
            $match = $title;

            $match               = substr($match, 2, -2); // strip markup
            list($match, $title) = explode('|', $match);

            if (isset($title2)) {
                $title = rtrim($title2, '}');
            }
            $link_params = Icons_Handler_Parse_Link($url, $handler);
        }

        $align_left   = false;
        $align_right  = false;
        $align_center = false;
        $align_flag   = '';

        if (substr($match, 0, 1) == ' ') {
            $align_right = true;
            $align_flag  = "align=right";
        }

        if (substr($match, -1, 1) == ' ') {
            $align_left = true;
            $align_flag = "align=left";
        }

        if ($align_left && $align_right) {
            $align_center = true;
            $align_flag   = "align=center";
        }

        list($match, $flags) = explode('?', trim($match), 2);
        list($pack, $icon)   = explode('>', trim($match), 2);

        $flags .= "&$align_flag";

        return array($pack, $icon, explode('&', rtrim($flags, '&')), $title, $link_params, $match, $state, $pos);
    }

    /**
     * Handles the actual output creation.
     *
     * @param   string         $mode      output format being rendered
     * @param   Doku_Renderer  $renderer  the current renderer object
     * @param   array          $data      data created by handler()
     * @return  boolean                   rendered correctly? (however, returned value is not used at the moment)
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {

        if ($mode !== 'xhtml') {
            return false;
        }

        /** @var Doku_Renderer_xhtml $renderer */

        list($pack, $icon, $flags, $title, $link_params) = $data;
        $this->parseFlags($pack, $icon, $flags);

        if ($this->isIcon()) {

            $icon_size       = $this->getFlag('size');
            $icon_pack       = $this->getFlag('pack');
            $icon_base_url   = rtrim($this->getConf(sprintf('%sURL', $icon_pack)), '/');
            $icon_url        = $this->makePath($icon, $icon_size, $icon_base_url);
            $cached_icon_url = ml($icon_url, array('cache' => 'recache', 'w' => $icon_size, 'h' => $icon_size));
            $icon_markup     = sprintf('<img src="%s" title="%s" class="%s" style="%s" />',
                $cached_icon_url, $title,
                $this->toClassString($this->getClasses()),
                $this->toInlineStyle($this->getStyles()));

        } else {

            if (! $icon == strstr($icon, ':')) {
                $icon = "$pack:$icon";
            }

            $icon_markup = sprintf('<span class="iconify dw-icons %s" style="%s" title="%s" data-icon="%s" %s></span>',
                $this->toClassString($this->getClasses()), $this->toInlineStyle($this->getStyles()), $title, $icon, $this->toAttributes($this->attrs)
            );

        }

        if (isset($link_params)) {

            global $conf;

            list($url, $exists) = $this->resolveLinkUrl($link_params, $renderer);
            $is_external = ($link_params['type'] == 'externallink');

            $link        = array();
            $link['target'] = ($is_external) ? $conf['target']['extern'] : $conf['target']['wiki'];
            $link['style']  = '';
            $link['pre']    = '';
            $link['suf']    = '';
            $link['more']   = '';
            $link['class']  = '';
            $link['url']    = $url;
            $link['name']   = $icon_markup;

            if ($exists) {
                $link['class'] = 'wikilink1';
            } else {

                $link['rel'] = 'nofollow';

                if (!$is_external) {
                    $link['class'] = 'wikilink2';
                }

            }

            $renderer->doc .= $renderer->_formatLink($link);
            return true;

        }

        $renderer->doc .= $icon_markup;
        return true;

    }

    protected function isIcon()
    {
        $class_icon = sprintf('syntax_plugin_icons_%s', $this->getFlag('pack'));
        return constant("$class_icon::IS_ICON");
    }

    protected function isFontIcon()
    {
        $class_icon = sprintf('syntax_plugin_icons_%s', $this->getFlag('pack'));
        return constant("$class_icon::IS_FONT_ICON");
    }

    protected function toClassString($things)
    {
        return trim(implode(' ', $things), ' ');
    }

    private static function toAttributes($attrs = array())
    {
        return implode(' ', array_map(
            function ($val, $key) {
                return $key . '="' . htmlspecialchars($val) . '"';
            },
            $attrs,
            array_keys($attrs)
        ));
    }

    protected function toInlineStyle($things)
    {

        $result = '';

        foreach ($things as $property => $value) {
            $result .= "$property:$value;";
        }

        $result = trim($result, ';');

        return $result;

    }

    protected function getFlag($name)
    {
        return (isset($this->flags[$name]) ? $this->flags[$name] : null);
    }

    protected function getFlags()
    {
        return $this->flags;
    }

    protected function parseFlags($pack, $icon, $flags)
    {

        $this->flags   = array();
        $this->classes = array();
        $this->styles  = array();

        $this->flags['pack'] = $pack;
        $this->flags['icon'] = $icon;

        if ((int) $flags[0] > 0 && !in_array($flags[0], array('2x', '3x', '4x', '5x'))) {
            $flags[] = "size=" . $flags[0];
            unset($flags[0]);
        }

        if ($left = array_search('left', $flags)) {
            $flags[] = 'align=left';
            unset($flags[$left]);
        }

        if ($right = array_search('right', $flags)) {
            $flags[] = 'align=right';
            unset($flags[$right]);
        }

        if ($center = array_search('center', $flags)) {
            $flags[] = 'align=center';
            unset($flags[$center]);
        }

        foreach ($flags as $flag) {

            @list($flag, $value) = explode('=', $flag);

            if (!$flag) {
                continue;
            }

            $this->flags[$flag] = $value;

            switch ($flag) {

                case 'size':

                    $this->flags[$flag] = (int) $value;

                    //if ($this->isFontIcon()) {
                        $this->attrs['data-height'] = $value;
                        $this->attrs['data-width']  = $value;
                    //}

                    break;

                case 'circle':
                    $this->flags[$flag] = true;
                    $this->classes[]    = 'circle';
                    break;

                case 'border':

                    $this->flags[$flag] = true;
                    $this->classes[] = 'border';
                    break;

                case 'borderColor':
                    $this->styles['border-color'] = $value;
                    break;

                case 'padding':
                    $this->styles['padding'] = $value;
                    break;

                case 'background':
                    $this->styles['background-color'] = $value;
                    break;

                case 'color':
                    $this->styles['color'] = $value;
                    break;

                case 'class':
                    $this->classes[] = $value;
                    break;

                case 'align':

                    if ($this->isIcon()) {
                        $this->classes[] = "media$value";
                    } else {

                        if ($value == 'center') {
                            $this->styles['text-align'] = 'center';
                            $this->styles['margin'] = '0.2em auto';
                            $this->styles['display'] = 'block'; 
                            $this->styles['clear'] = 'both';
                        } else {
                            $this->styles['padding-' . (($value == 'left') ? 'right' : 'left')] = '.2em';
                            $this->styles['float']                                              = $value;
                        }

                    }

                    break;

                case 'rotate':

                    if (in_array($value, array(90, 180, 270))) {
                        $this->classes[] = "fa-rotate-$value";
                    }

                    break;

                case 'flip':

                    if (in_array($value, array('horizontal', 'vertical'))) {
                        $this->classes[] = "fa-flip-$value";
                    }

                    break;

                case 'pull-left':
                case 'pull-right':
                case 'spin':
                case 'pulse':
                    $this->classes[] = "$flag";
                    break;

                case 'fw':
                case 'lg':
                case '2x':
                case '3x':
                case '4x':
                case '5x':

                    $this->classes[]     = "size-$flag";
                    $this->flags['size'] = true;

                    unset($this->styles['font-size']);
                    break;

                default:
                    $this->classes[] = $flag;

            }

        }

        if (!isset($this->flags['size'])) {

            $this->flags['size'] = (int) $this->getConf('defaultSize');

            if ($this->isFontIcon()) {
                $this->attrs['data-height'] = $this->getConf('defaultSize');
                $this->attrs['data-width']  = $this->getConf('defaultSize');
            }

        }

        if ($this->flags['pack'] == 'icon') {
            $this->flags['pack'] = $this->getConf('defaultPack');
        }

    }

    protected function getStyles()
    {
        return $this->styles;
    }

    protected function getClasses()
    {
        return $this->classes;
    }

    public static function makePath($icon, $size, $base_url)
    {
        return true;
    }

}

/**
    * @param string $match matched syntax
    * @param Duku handler
    * @return bool mode handled?
    */
function Icons_Handler_Parse_Link($match, $handler=null) {

    $link = $match;
    // Split title from URL
    $link = explode('|',$link,2);
    if ( !isset($link[1]) ) {
        $link[1] = null;
    } 

    $link[0] = trim($link[0]);

    //decide which kind of link it is
    if ( link_isinterwiki($link[0]) ) {
        // Interwiki
        $type = 'interwikilink';
        $interwiki = explode('>',$link[0],2);
        $wikiname = strtolower($interwiki[0]);
        $wikiuri = $interwiki[1];
        if ($handler) $handler->addCall($type,array($link[0],$link[1],$wikiname,$wikiuri,true),null);

    }elseif ( preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u',$link[0]) ) {
        // Windows Share
        $type = 'windowssharelink';  
    }elseif ( preg_match('#^([a-z0-9\-\.+]+?)://#i',$link[0]) ) {
        // external link (accepts all protocols)
        $type = 'externallink';    
    }elseif ( preg_match('<'.PREG_PATTERN_VALID_EMAIL.'>',$link[0]) ) {
        // E-Mail (pattern above is defined in inc/mail.php)
        $type = 'emaillink';
        if ($handler) $handler->addCall($type,array($link[0],$link[1], true),null);
    }elseif ( preg_match('!^#.+!',$link[0]) ){
        // local link
        $type = 'locallink';
        if ($handler) $handler->addCall($type,array(substr($link[0],1),$link[1],true),null);
    }else{
        // internal link
        $type = 'internallink';
        if ($handler) $handler->addCall($type,array($link[0],$link[1], null, true),null);      
    }

    $params = array(
        'type'=>$type,
        'src'=>$link[0],
        'title'=>$link[1],
        'wikiname'=>(isset($wikiname) ? $wikiname : null),
        'wikiuri'=>(isset($wikiuri) ? $wikiuri : null)
    );
    return $params;
}