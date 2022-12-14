<?php 
/**
 * @version		1.0.0
 * @package		CGChangeLog content plugin
 * @author		ConseilGouz
 * @copyright	Copyright (C) 2022 ConseilGouz. All rights reserved.
 * @license		GNU/GPL v2; see LICENSE.php
 **/
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Changelog\Changelog;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

class plgContentCGChangelog extends CMSPlugin
{	
    public $myname='CGChangelog';
    private $xmlParser;
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }
    
	public function onContentPrepare($context, &$article, &$params, $page = 0) {
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}
		// check chglog tags
		$shortcode = $this->params->get('shortcode','chglog'); 
		if (strpos($article->text, '{'.$shortcode.'') === false ) {
			return true;
		}
		$regex_all		= '/{'.$shortcode.'\s*.*?}/si';
		if (preg_match_all($regex_all,$article->text,$matches)) {
			$uri = Uri::getInstance();
		    $regex = '/(?:<(div|p)[^>]*>)?{'.$shortcode.'(?:=(.+))?}/i';
		    foreach($matches[0] as $key=>$ashort) {
		        if (preg_match_all($regex, $ashort, $chglogs, PREG_SET_ORDER)) { // ensure the more specific regex matches
		            foreach ($chglogs as $chglog) {
		                $infos = explode('|',$chglog[2]);
						$db = Factory::getDbo();
						$query = $db->getQuery(true);
						$query->select($db->quoteName('changelogurl'));
						$query->from($db->quoteName('#__extensions'));
						$query->where($db->quoteName('element').' like '.$db->quote($infos[0]));
						$db->setQuery($query);
						$extension = $db->loadObject();
						if (!$extension->changelogurl) { // changelog not found : empty shortcode and exit
							$article->text = str_replace($chglog[0], '', $article->text);
							continue;
						}
						$changelog = $this->loadFromXml($extension->changelogurl);
						$limit = 0;
						$version = "minor";
						foreach ($infos as $one) {
						    if (strpos($one,'limit') !== false) {
						        $limit = (int)str_replace("limit=", '', $one);
						    }
						    if (strpos($one,'version') !== false) {
						        $version = str_replace("version=", '', $one);
						    }
						}
						$fullbutton = "";
						$fullmodal = "";
						if ($limit) {// add full changelog display button
							$fullbutton = '<button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#fullModal">';
							$fullbutton .= Text::_('PLG_CONTENT_CGCHANGELOG_FULLBUTTON');
							$fullbutton .= '</button>';
							$fullmodal = '<div class="modal fade" id="fullModal" tabindex="-1" aria-labelledby="fullModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">';
							$fullmodal .= '<div class="modal-dialog modal-dialog-scrollable">';
							$fullmodal .= '<div class="modal-content"><div class="modal-header">';
							$fullmodal .= '<h5 class="modal-title" id="fullModalLabel">'.Text::sprintf("PLG_CONTENT_CGCHANGELOG_FULLTITLE",$infos[0]).'</h5>';
							$fullmodal .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.Text::_("JCLOSE").'"></button></div>';
							$fullmodal .= '<div class="modal-body">';
						}
						$str = $fullbutton;
						$last_version = "";
						$major = "";
						$minor = "";
						$count = 0;
						foreach($changelog as $one ) {
						   $tmp = explode('.',$one->version);
						   if (($tmp[1] == 0) && ($tmp[2] == 0)) {
						        
						   } 
						   if ($limit > 0 && $count <= $limit) {
						      $str .= "Version ".$one->version;
						   }
						   if (!$last_version) {
								$last_version = $one->version;
								$tmp = explode('.',$one->version);
								$major = $tmp[0];
								$minor = $tmp[1];
						   }
						   $note = "";
						   foreach ($one->note as $element) {
						       $note .= ' ('.$element->item.')';
						   }
						   $fix = "";
						   foreach ($one->fix as $element) {
						       $fix .= '<li><span title="Fix"># '.$element->item.'</span></li>'; 
						   }
						   $addition = "";
						   foreach ($one->addition as $element) {
						       $addition .= '<li><span title="Add">+ '.$element->item.'</span></li>';
						   }
						   $remove = "";
						   foreach ($one->remove as $element) {
						       $remove .= '<li><span title="Remove">- '.$element->item.'</span></li>';
						   }
						   $change = "";
						   foreach ($one->change as $element) {
						       $change .= '<li><span title="Change">^ '.$element->item.'</span></li>';
						   }
						   $security = "";
						   foreach ($one->security as $element) {
						       $security .= '<li><span title="Security">S '.$element->item.'</span></li>';
						   }
						   if ($limit > 0 && $count <= $limit) { // display it
						       $str .= $note.'<ul>'.$fix.$addition.$remove.$change.$security.'</ul>';
						   }
						   if ($fullmodal) {
							   $fullmodal .=  "<p class='mb-0'>Version ".$one->version.$note.'<ul>'.$fix.$addition.$remove.$change.$security."</ul></p>";
							   
						   }
						   $count++;
						}
						if ($fullmodal) { // close full modal
							$fullmodal .= '</div>';
							$fullmodal .= '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_("JCLOSE").'</button>';
							$fullmodal .= '</div></div></div></div>';
						}
						$article->text = str_replace($chglog[0], $str.$fullmodal, $article->text);
						
		            }
		        }
		    }
		}
		return true;
	}
	public function loadFromXml($url)
	{
	    $response = simplexml_load_file($url);
	    if ($response === null) {
	        
	        return false;
	    }
	    
	    return $response;
	}
}
?>