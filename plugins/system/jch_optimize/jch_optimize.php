<?php

/**
 * JCH Optimize - Joomla! plugin to aggregate and minify external resources for
 *   optmized downloads
 * @author Samuel Marshall <sdmarshall73@gmail.com>
 * @copyright Copyright (c) 2010 Samuel Marshall
 * @license GNU/GPLv3, See LICENSE file
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 *
 * This plugin, inspired by CssJsCompress <http://www.joomlatags.org>, was
 * created in March 2010 and includes other copyrighted works. See individual
 * files for details.
 */
/**
 * Modified for Joomla 1.6 by Branislav Maksin - www.maksin.ms
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
require_once ( dirname(__FILE__) . DS . 'cache' . DS . 'CSS.php' );
require_once ( dirname(__FILE__) . DS . 'cache' . DS . 'HTML.php' );
require_once ( dirname(__FILE__) . DS . 'cache' . DS . 'jsmin.php' );

class plgSystemJCH_Optimize extends JPlugin {

    /** @var object   Holds reference to JCACHE object */
    protected $oCache = '';
    /** @var string   Head section of html */
    protected $sHead = '';
    /** @var string   Html of full page */
    protected $sBody = '';
    /** @var array    Array of css or js urls taken from head */
    protected $aLinks = array();
    /** @var array    Array of css media types */
    protected $aMedia = array();
    /** @var array    Array of ordered js files */
    protected $aOrder = array();
    /** @var integer  First index for $alinks */
    protected $iCnt = 2000;
    /** @var array    Array of arguments to be used in callback functions */
    protected $aCallbackArgs = array();
    /** @var object   Holds reference to JURI object */
    protected $oUri = '';

    /**
     * Triggered by onAfterRender event; will remove all urls of css and js
     * files in head tags (except those excluded) and aggregate in single css or
     * js file
     *
     */
    public function onAfterRender() {
        $iCss = $this->params->get('css', 1);
        $iJavaScript = $this->params->get('javascript', 1);
        $iHtmlMin = $this->params->get('html_minify', 0);
        $sExComp = $this->params->get('excludeComponents', '');
        $iExExtensions = $this->params->get('excludeAllExtensions', '1');

        if (!$iCss && !$iJavaScript && !$iHtmlMin) {
            return true;
        }

        $oApplication = JFactory::getApplication();
        if ($oApplication->getName() != 'site') {
            return true;
        }

        $oDocument = JFactory::getDocument();
        $sDocType = $oDocument->getType();
        $sLnEnd = $oDocument->_getLineEnd();
        $sTab = $oDocument->_getTab();

        if ($sDocType != 'html') {
            return;
        }
        $sExCompRegex = '';
        $sExExtensionsRegex = '';

        if ($iExExtensions) {
            $sExExtensionsRegex = '|(?:/components/)|(?:/modules/)|(?:/plugins/)';
        }
        if (isset($sExComp) && $sExComp) {
            $aComponents = $this->getArray($sExComp);
            foreach ($aComponents as $sComponent) {
                $sExCompRegex .= '|(?:/' . $sComponent . '/)';
            }
        }

        $this->sBody = JResponse::getBody();
        $sHeadRegex = '~<head[^>]*>.*?</head>~msi';
        preg_match($sHeadRegex, $this->sBody, $aHeadMatches);
        $this->sHead = $aHeadMatches[0];

        $this->oUri = clone JURI::getInstance();

        $sCacheGroup = 'plg_jch_optimize';
        $this->oCache = JFactory::getCache($sCacheGroup, 'callback', 'file');
        $this->oCache->setCaching(1);
        $this->oCache->setLifetime((int) $this->params->get('lifetime', '30') * 24 * 60 * 60);

        if ($iJavaScript) {
            $this->aOrder = $this->getArray($this->params->get('customOrder', ''));
            $this->aCallbackArgs['excludes'] = $this->getArray($this->params->get('excludeJs', ''));
            unset($this->aLinks);
            $sType = 'js';

            $this->excludeIf($sType);

            $iDefer = $this->params->get('defer_js', 0);
            $iJsPosition = $this->params->get('bottom_js', 1);

            $this->aCallbackArgs['type'] = $sType;
            $this->aCallbackArgs['counter'] = 0;
            $sJsRegexStart = '~<script
                               (?=[^>]+?src\s?=\s?["\']([^"\']+?/([^/]+\.js)(?:\?[^"\']*?)?)["\'])
                               (?=[^>]+?type\s?=\s?["\']text/javascript["\'])
                               (?:(?!(?:\.php)|(?:/editors/)';
            $sJsRegexEnd = ')[^>])+>(?:(?:[^<]*?)</script>)?~ix';
            $sJsRegex = $sJsRegexStart . $sExExtensionsRegex . $sExCompRegex . $sJsRegexEnd;
            $this->sHead = preg_replace_callback($sJsRegex, array($this, 'replaceScripts'), $this->sHead);

            $sLink = '<script type="text/javascript" src="URL"';
            $sLink .= $iDefer ? ' defer="defer"' : '';
            $sLink .= '></script>';
            $sNewJsLink = $iJsPosition == 1 ? $sTab . $sLink . $sLnEnd . '</body>' : $sLink;
            $iCnt = $this->aCallbackArgs['counter'];
            if (!empty($this->aLinks)) {
                $iJsId = $this->processLink($sType, $sCacheGroup, $sNewJsLink, $sLnEnd, $iCnt);
            }
        }

        if ($iCss || $this->params->get('csg_enable', 0)) {
            $this->aCallbackArgs['excludes'] = $this->getArray($this->params->get('excludeCss', ''));
            unset($this->aLinks);
            $sType = 'css';
            $this->excludeIf($sType);
            $this->aCallbackArgs['type'] = $sType;
            $this->aCallbackArgs['counter'] = 0;
            $sCssRegexStart = '~<link
                                    (?=[^>]+?href\s?=\s?["\']([^"\']+?/([^/]+\.css)(?:\?[^"\']*?)?)["\'])
                                    (?=[^>]+?type\s?=\s?["\']text/css["\'])
                                    (?:(?!(?:\.php)|(?:title\s?=\s?["\'])';
            $sCssRegexEnd = ')[^>])+>~ix';
            $sCssRegex = $sCssRegexStart . $sExExtensionsRegex . $sExCompRegex . $sCssRegexEnd;
            $this->sHead = preg_replace_callback($sCssRegex, array($this, 'replaceScripts'), $this->sHead);
            //print_r($this->aLinks);
            $sNewCssLink = '</title>' . $sLnEnd . $sTab . '<link rel="stylesheet" type="text/css" ';
            $sNewCssLink .= 'href="URL"/>';

            if (!empty($this->aLinks)) {
                $iCssId = $this->processLink('css', $sCacheGroup, $sNewCssLink, $sLnEnd);
            }
        }

        $sBody = preg_replace($sHeadRegex, $this->sHead, $this->sBody);
        $aOptions = array();
        if ($this->params->get('css_minify', 0)) {
            $aOptions['cssMinifier'] = array('Minify_CSS', 'process');
        }
        if ($this->params->get('js_minify', 0)) {
            $aOptions['jsMinifier'] = array('JSMin', 'minify');
        }
        if ($iHtmlMin) {
            $sBody = Minify_HTML::minify($sBody, $aOptions);
        }
        JResponse::setBody($sBody);
    }

    /**
     * Add js and css urls in conditional tags to excludes list
     *
     * @param string $sType   css or js
     */
    protected function excludeIf($sType) {
        if (preg_match_all('~<\!--.*?-->~is', $this->sHead, $aMatches)) {
            foreach ($aMatches[0] as $sMatch) {
                preg_match_all('~.*?/([^/]+\.' . $sType . ').*?~', $sMatch, $aExcludesMatches);
                $this->aCallbackArgs['excludes'][] = @$aExcludesMatches[1][0];
            }
        }
    }

    /**
     * Use generated id to cache aggregated file
     *
     * @param string $sType           css or js
     * @param string $sCacheGroup    Name of cache group
     * @param string $sLink           Url for aggregated file
     * @param string $sLnEnd         Line end
     */
    protected function processLink($sType, $sCacheGroup, $sLink, $sLnEnd, $iCnt='') {
        if ($sType == 'js') {
            $iMinify = $this->params->get('js_minify', 0);
        } elseif ($sType == 'css') {
            $iMinify = $this->params->get('css_minify', 0);
        }
        $iImport = $this->params->get('import', 0);
        $iSprite = $this->params->get('csg_enable', 0);

        $sId = md5(serialize(implode('', $this->aLinks) . $this->params));
        $aArgs = array($this->aLinks, $sType, $sLnEnd, $iMinify, $iImport, $iSprite, $sId);
        $aFunction = array($this, 'getContents');

        $bCached = $this->loadCache($aFunction, $aArgs, $sId);
        $sFileName = $this->getFilename($sId, $sCacheGroup);

        $iTime = (int) $this->params->get('lifetime', '30');

        if ($bCached) {
            $sUrl = $this->buildUrl($sFileName, $sType, $iTime, $this->isGZ());
            $sNewLink = str_replace('URL', $sUrl, $sLink);
            $this->replaceLink($sNewLink, $sType, $iCnt);
        }
        //print_r($sFile);
        return $sId;
    }

    /**
     * Returns url of aggregated file
     *
     * @param string $sFile		Aggregated file name
     * @param string $sType		css or js
     * @param mixed $bGz		True (or 1) if gzip set and enabled
     * @param number $sTime		Expire header time
     * @return string			Url of aggregated file
     */
    protected function buildUrl($sFile, $sType, $iTime, $bGz=false) {
        $sPath = JURI::base(true) . '/plugins/system/jch_optimize/cache/';
        if ($this->params->get('htaccess', 0)) {
            $sUrl = $sPath . ($bGz ? 'gz/' : 'nz/') . $iTime . '/' . $sFile . '.' . $sType;
        } else {
            $oUri = $this->oUri;
            $oUri->setPath($sPath . 'jscss.php');

            $aVar = array();
            $aVar['f'] = $sFile;
            $aVar['type'] = $sType;
            if ($bGz) {
                $aVar['gz'] = 'gz';
            }
            $aVar['d'] = $iTime;
            $oUri->setQuery($aVar);

            $sUrl = htmlentities($oUri->toString(array('path', 'query')));
        }
        return ($sUrl);
    }

    /**
     * Insert url of aggregated file in html
     *
     * @param string $sNewLink   Url of aggregated file
     */
    protected function replaceLink($sNewLink, $sType, $iCnt='') {
        if ($sType == 'css') {
            $this->sHead = str_replace('</title>', $sNewLink, $this->sHead);
        }
        if ($sType == 'js') {
            switch ($this->params->get('bottom_js', 1)) {
                case 0: //First found javascript tag
                    $this->sHead = preg_replace('~<JCH_SCRIPT>~', $sNewLink, $this->sHead, 1);
                    $this->sHead = str_replace('<JCH_SCRIPT>', '', $this->sHead);
                    break;
                case 2: //Last found javascript tag
                    $iCnt--;
                    $this->sHead = preg_replace('~<JCH_SCRIPT>~', '', $this->sHead, $iCnt);
                    $this->sHead = str_replace('<JCH_SCRIPT>', $sNewLink, $this->sHead);
                    break;
                case 1: //Bottom of page
                    $this->sHead = str_replace('<JCH_SCRIPT>', '', $this->sHead);
                    $this->sBody = str_replace('</body>', $sNewLink, $this->sBody);
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Create and cache aggregated file if it doesn't exists, file will have
     * lifetime set in global configurations.
     *
     * @param array $aFunction    Name of function used to aggregate files
     * @param array $aArgs        Arguments used by function above
     * @param string $sId         Generated id to identify cached file
     * @return boolean           True on success
     */
    protected function loadCache($aFunction, $aArgs, $sId) {
        unset($bCached);
        $bCached = $this->oCache->get($aFunction, $aArgs, $sId);
        if (isset($bCached)) {
            return true;
        }
        return false;
    }

    /**
     * Gets name of aggregated file
     *
     * @param string $sId			Id of cached file
     * @param string $sCacheGroup	Name of cache group
     * @return string				Cache file name
     */
    protected function getFilename($sId, $sCacheGroup) {
        $oStorage = $this->oCache->_getStorage();
        $sName1 = md5($oStorage->_application . '-' . $sId . '-' . $oStorage->_language);
        $sName = $oStorage->_hash . '-cache-' . $sCacheGroup . '-' . $sName1;
        return $sName;
    }

    /**
     * Check if gzip is set or enabled
     *
     * @return boolean   True if gzip parameter set and server is enabled
     */
    public function isGZ() {
        $iIsGz = $this->params->get('gzip', 0);
        if ($iIsGz) {

            if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                return false;
            } elseif (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
                return false;
            } elseif (false !== strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Callback function used to remove urls of css and js files in head tags
     *
     * @param array $aMatches Array of all matches
     * @return string        Returns the url if excluded, empty string otherwise
     */
    protected function replaceScripts($aMatches) {
        $sUrl = $aMatches[1];

        if ($this->oUri->isInternal($sUrl)) {
            $sFile = $aMatches[2];

            $aExcludes = array();
            if ($this->aCallbackArgs) {
                $aExcludes = $this->aCallbackArgs['excludes'];
            }
            if (in_array($sFile, $aExcludes)) {
                return $aMatches[0];
            } else {
                if (in_array($sFile, $this->aOrder)) {

                    $sKey = array_search($sFile, $this->aOrder);
                    $this->aLinks[$sKey] = $sUrl;
                } else {

                    $this->aLinks[$this->iCnt++] = $sUrl;
                }
                $iResult = preg_match('~media\s?=\s?["\']([^"\']+?)["\']~i', $aMatches[0], $aMediaTypes);
                if ($iResult > 0) {
                    $this->aMedia[$sUrl] = $aMediaTypes[1];
                }
                if ($this->aCallbackArgs['type'] == 'js') {
                    $this->aCallbackArgs['counter']++;
                    return '<JCH_SCRIPT>';
                } else {
                    return '';
                }
            }
        }
        return $aMatches[0];
    }

    /**
     * Prepare css or js files for aggregation and minification
     *
     * @param array $aUrlArray   Array of urls of css or js files for aggregation
     * @param string $sType       css or js
     * @param string $sLnEnd     line end
     * @return string         Aggregated (and possibly minified) contents of files
     */
    public function getContents($aUrlArray, $sType, $sLnEnd, $iMinify, $iImport, $iSprite, $sId) {
        ksort($aUrlArray);

        $sContents = $this->combineFiles($aUrlArray, $sLnEnd, $sType);

        if ($sType == 'css') {
            if ($iImport) {
                $sContents = $this->replaceImports($sContents, $sLnEnd);
            }
            $sContents = trim($this->sortImports($sContents, $sLnEnd));

            if ($iSprite) {
                $sContents = $this->generateSprite($sContents, $sLnEnd);
            }

            if ($iMinify) {
                $sContents = Minify_CSS::process($sContents);
            }
        }
        if ($iMinify && $sType == 'js') {
            try{
                $sContents = JSMin::minify($sContents);
            }catch (JSMinException $e){
                //Need to test how this handles
                JError::raiseWarning(101, $e->getMessage());
		//return false;
            }    
        }
        $sContents = str_replace('LINE_END', $sLnEnd, $sContents);
        return $sContents;
    }

    /**
     * Aggregate contents of CSS and JS files
     *
     * @param array $aUrlArray      Array of links of files
     * @param string $sType          CSS or js
     * @return string               Aggregarted contents
     */
    protected function combineFiles($aUrlArray, $sLnEnd, $sType='') {
        $iJQueryNoConflict = $this->params->get('jqueryNoConflict', '');
        $sJQueryFile = $this->params->get('jquery', '');
        $sContents = '';
        foreach ($aUrlArray as $sUrl) {
            $sPath = $this->getFilepath($sUrl);
            $aPath = explode(DS, $sPath);
            $sFile = end($aPath);

            preg_match('~.*\.[A-Za-z]{2,3}~', $sPath, $aMatches);
            if (file_exists($aMatches[0])) {
                if (preg_match('~.*?\.php~', $sPath)) {
                    $sContent = $this->evalCss($sPath, $sUrl);
                } else {
                    $sContent = file_get_contents($sPath);
                }
                if ($iJQueryNoConflict && $sJQueryFile == $sFile && $sType == 'js') {
                    $sContent.="\n jQuery.noConflict();\n";
                }
                if ($sType == 'css') {
                    unset($this->aCallbackArgs['css_url']);
                    $this->aCallbackArgs['css_url'] = $sUrl;
                    $sContent = preg_replace('~@import\s?[\'"]([^\'"]+?)[\'"];~', '@import url("$1");', $sContent);
                    $sContent = preg_replace_callback('~url\s?\([\'"]?(?![a-z]+:|/+)([^\'")]+)[\'"]?\)~i', array($this, 'correctUrl'), $sContent);
                    if (@$this->aMedia[$sUrl]) {
                        $sContent = '@media ' . $this->aMedia[$sUrl] . ' {' . $sLnEnd . $sContent . $sLnEnd . ' }';
                    }
                }
                $sContents .= $sContent . 'LINE_END';
            }
        }
        return $sContents;
    }

    /**
     * Get local path of file from the url
     *
     * @param string  $sUrl  Url of file
     * @return string       File path
     */
    static public function getFilepath($sUrl) {
        $sUriBase = str_replace('~', '\~', JURI::base());
        $sUriPath = str_replace('~', '\~', JURI::base(true));
        $sUrl = preg_replace(array('~^' . $sUriBase . '~', '~^' . $sUriPath . '/~', '~\?.*?$~'), '', $sUrl);
        $sUrl = str_replace('/', DS, $sUrl);
        $sFilePath = JPATH_ROOT . DS . $sUrl;

        return $sFilePath;
    }

    /**
     * Splits a string into an array using any regular delimiter or whitespace
     *
     * @param string  $sString   Delimited string of components
     * @return array            An array of the components
     */
    static public function getArray($sString) {
        return $aArray = preg_split('~[\s,;:]+~', $sString);
    }

    /**
     * Returns url of current host
     *
     * @return string    Url of current host
     */
    public function getHost() {
        $sWww = $this->oUri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
        return $sWww;
    }

    /**
     * Callback function to correct urls in aggregated css files
     *
     * @param array $aMatches Array of all matches
     * @return string         Correct url of images from aggregated css file
     */
    protected function correctUrl($aMatches) {

        if (!preg_match('~^(/|http)~', $aMatches[1])) {
            $sCssRootPath = preg_replace('~/[^/]+\.css~', '/', $this->aCallbackArgs['css_url']);
            $sImagePath = $sCssRootPath . $aMatches[1];
            $oUri = clone JURI::getInstance($sImagePath);
            $oUri->setPath($oUri->getPath());
            $sCleanPath = $oUri->getPath();
            $sCleanPath = preg_replace('~^(?!/)~', '/', $sCleanPath);
            return 'url(' . $sCleanPath . ')';
        } else {
            return $aMatches[0];
        }
    }

    /**
     * Replace all @import with internal urls with the file contents
     *
     * @param string $sCss   Combined css file
     * @return string       CSS file with contents from import prepended.
     */
    protected function replaceImports($sCss, $sLnEnd) {
        unset($this->aLinks);
        $this->aCallbackArgs = array();
        // print_r($sCss);
        $sImportCss = '';
        $sCss = preg_replace_callback('~@import.*?url\([\'"]?(.*?/([^/]+\.css))[\'"]?\);~i', array($this, 'replaceScripts'), $sCss);
        if (!empty($this->aLinks)) {
            $sImportCss = $this->combineFiles($this->aLinks, $sLnEnd, 'css');
        }
        return $sImportCss . $sCss;
    }

    /**
     * Sorts @import and @charset as according to w3C <http://www.w3.org/TR/CSS2/cascade.html> Section 6.3
     *
     * @param string $sCss       Combined css
     * @param string $sLnEnd     Line ending
     * @return string           CSS with @import and @charset properly sorted
     */
    protected function sortImports($sCss, $sLnEnd) {
        $sImportRegex = '~@import[^;]+?;~i';
        $sCharsetRegex = '~@charset[^;]+?;~i';

        $n = preg_match_all($sImportRegex, $sCss, $aImportMatches);
        $aExcludesMatches = preg_match($sCharsetRegex, $sCss, $charset);

        $sImports = '';
        foreach ($aImportMatches[0] as $sImport) {
            $sImports .= $sImport . $sLnEnd;
        }
        preg_replace(array($sImportRegex, $sCharsetRegex), '', $sCss);
        $charset = $aExcludesMatches <= 0 ? '' : $charset[0];
        return $sCss = trim($charset . $sLnEnd . $sImports . $sCss);
    }

    /**
     * //Not implemented yet
     * @param <type> $sPath
     * @param <type> $sUrl
     */
    protected function evalCss($sPath, $sUrl) {
        $oUri = JURI::getInstance($sUrl);
        $sQuery = $oUri->getQuery(true);
        eval('?>' . $sContent = file_get_contents($sPath) . '<?');
    }

    /**
     * Grabs background images with no-repeat attribute from css and merge them in one file called a sprite.
     * Css is updated with sprite url and correct background positions for affected images.
     * Sprite saved in {Joomla! base}/images/jch-optimize/
     *
     * @param string $sCss       Aggregated css file before sprite generation
     * @param string $sLnEnd     Document line end
     * @return string           Css updated with sprite information on success. Original css on failure
     */
    protected function generateSprite($sCss, $sLnEnd) {
        if (extension_loaded('imagick') && extension_loaded('exif')) {
            $sImageLibrary = 'imagick';
        } else {
            if (!extension_loaded('gd') || !extension_loaded('exif')) {
                return $sCss;
            }
            $sImageLibrary = 'gd';
        }
        $iMinMaxImages = $this->params->get('csg_min_max_images', 0);
        $sDelStart = '~';
        $sRegexStart = '(?:(?<=^|})
                            (?=([^{]+?)({[^}]+?(url\(([^}]+?\.(?:png|gif|jpe?g))[^}]*?\))[^}]+?}))
                            (?:(?!(?:\s(?<!no-)repeat(?:-(?:x|y))?)|(?:background[^;]+?(?:\s(?:left|right|center|top|bottom)';
        $sRegexMin = '|(?:\s0)|(?:\s\d{1,5}(?:%|in|(?:c|m)m|e(?:m|x)|p(?:t|c|x)))){1,2}[^;]*?;)';
        $sRegexMax = '|(?:\s[1-9]\d{0,4}(?:%|in|(?:c|m)m|e(?:m|x)|p(?:t|c|x)))){1,2}[^;]*?;)';
        $sRegexEnd = ')[^}])*?})';
        $sDelEnd = '~sx';

        $aIncludeImages = $this->getArray($this->params->get('csg_include_images'));
        $aExcludeImages = $this->getArray($this->params->get('csg_exclude_images'));
        $sIncImagesRegex = '';
        if (!empty($aIncludeImages[0]) && !$iMinMaxImages) {
            foreach ($aIncludeImages as $sIncImage) {
                $sIncImage = str_replace('.', '\.', $sIncImage);
                $sIncImagesRegex .= '|(?:(?<=^|})([^{]+?){[^}]+?(url\(([^}]+?' . $sIncImage . ')[^}]*?\))[^}]*?})';
            }
        }
        $sExImagesRegex = '';
        if (!empty($aExcludeImages[0]) && $iMinMaxImages) {
            foreach ($aExcludeImages as $sExImage) {
                $sExImage = str_replace('.', '\.', $sExImage);
                $sExImagesRegex .= '|(?:\b' . $sExImage . ')';
            }
        }


        $sMinMaxRegex = $iMinMaxImages ? $sRegexMax : $sRegexMin;
        $sRegex = $sDelStart . $sRegexStart . $sMinMaxRegex . $sExImagesRegex . $sRegexEnd . $sIncImagesRegex . $sDelEnd;

        $iResult = preg_match_all($sRegex, $sCss, $aMatches);
        //print_r($aMatches);
        if ($iResult <= 0) {
            return $sCss;
        }

        require_once ( dirname(__FILE__) . DS . 'cache' . DS . 'css-sprite-gen.inc.php' );

        $aDeclaration = $aMatches[2];
        $aImages = $aMatches[4];
        //print_r($aDeclaration);
        //print_r($sImages);
        $aFormValues = array();
        $aFormValues['wrap-columns'] = $this->params->get('csg_wrap_images', 'off');
        $aFormValues['build-direction'] = $this->params->get('csg_direction', 'vertical');
        $aFormValues['image-output'] = $this->params->get('csg_file_output', 'PNG');
        $oSpriteGen = new CssSpriteGen($sImageLibrary, $aFormValues);
        $aImageTypes = $oSpriteGen->GetImageTypes();

        $oSpriteGen->CreateSprite($aImages);
        $aSpriteCss = $oSpriteGen->GetCssBackground();
        //print_r($aSpriteCss);
        $aNeedles = array();
        $aReplacements = array();
        $sImageSelector = '';

        $sBaseUrl = JURI::base(true);
        $sBaseUrl = $sBaseUrl == '/' ? $sBaseUrl : $sBaseUrl . '/';
        for ($i = 0; $i < count($aSpriteCss); $i++) {
            if (@$aSpriteCss[$i]) {
                $aNeedles[] = $aDeclaration[$i];
                preg_match('~(?<=background)(?:[^;]+?)(
                            (?<!/)\b(?:aqua|black|blue|fuchsia|gray|green|lime|maroon|navy|olive|purple|red|silver|teal|white|yellow)\b(?!/)
                            |\#[a-fA-F\d]{3,6}
                            |rgb\([^\)]+?\))
                            (?:[^;]*?);~ix', $aDeclaration[$i], $aBgColor);
                preg_match('~(?<=background)(?:[^;]+?)\s(
                                scroll|fixed
                                )\s(?:(?:[^;]+?)?;)~ix', $aDeclaration[$i], $aBgAttach);
                $sBgImage = 'url(' . $sBaseUrl . 'images/jch-optimize/' . $oSpriteGen->GetSpriteFilename() . ')';
                $sBackground = 'background: ' . @$aBgColor[1] . ' ' . $sBgImage . ' ' . @$aBgAttach[1] . ' ' . $aSpriteCss[$i] . ' no-repeat; ';
                $sDecUnique = preg_replace('~background[^;]+?;~sx', '', $aDeclaration[$i]);
                $aReplacements[] = str_replace('{', '{' . $sLnEnd . $sBackground, $sDecUnique);
            }
        }
        $sCss = str_replace($aNeedles, $aReplacements, $sCss);
        //$sCss    =   $sImageSelector.'{background-image:'.$sBgImage.');}'.$sCss;
        return $sCss;
    }

}
