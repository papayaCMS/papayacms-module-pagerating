<?php
/**
* Page ranking administration
*
* @copyright 2002-2007 by papaya Software GmbH - All rights reserved.
* @link http://www.papaya-cms.com/
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
*
* You can redistribute and/or modify this script under the terms of the GNU General Public
* License (GPL) version 2, provided that the copyright and license notes, including these
* lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
*
* @package Papaya-Modules
* @subpackage Free-PageRating
* @version $Id: admin_pagerating.php 39818 2014-05-13 13:15:13Z weinert $
*/

/**
* Page ranking administration
*
* @package Papaya-Modules
* @subpackage Free-PageRating
*/
class admin_pagerating extends base_pagerating {

  /**
  * topic parameter name
  * @var string $topicParamName
  */
  var $topicParamName = 'rate';

  /**
  * vote sort
  * @var array $voteSort
  */
  var $voteSort = NULL;

  /**
  * This array is used to store icons, that are used within this module only.
  * @var array
  */
  var $localImages = array();

  /**
   * @var base_module
   */
  public $module = NULL;

  /**
   * @var PapayaTemplate
   */
  public $layout = NULL;

  /**
   * @var array
   */
  private $sessionPageParams = array();

  /**
  * Initial function for module
  *
  * @access public
  */
  function initialize() {
    $this->initializeParams();
    $this->sessionPageParams =
      $this->getSessionValue('PAPAYA_SESS_'.$this->topicParamName);
    $this->sessionParams = $this->getSessionValue($this->sessionParamName);
    $this->initializeSessionParam('topic_id');
    $imagePath = 'module:'.$this->module->guid;
    $this->localImages = array(
      'star' => "$imagePath/star.png"
    );
  }

  /**
  * Execute - basic function for handling parameters
  *
  * @access public
  */
  function execute() {
    $languageId = $this->papaya()->administrationLanguage->id;
    switch (@$this->params['cmd']) {
    case 'load_statistic' :
      $this->loadStatisticData($languageId);
      break;
    }
    //ablegen der neuen session daten
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);
    if ($this->loadPageList($languageId)) {
      $this->loadTopicTitle($languageId);
      $this->calculateRating();
    } else {
      $this->addMsg(MSG_WARNING, $this->_gt('Please refresh statistical data.'));
      $this->params['cmd'] = '';
    }
  }


  /**
  * Get xml for page output
  *
  * @access public
  */
  function getXML() {
    if (is_object($this->layout)) {
      $this->getXMLButtons();
      switch (@$this->params['cmd']) {
      default:
        $this->getPageInfo();
        $this->getXMLResultTree();
      }
    }
  }

  /**
  * Get List with topics
  *
  * @access public
  */
  function getPageInfo() {
    if (isset($this->params['topic_id']) && isset($this->pageList) &&
        is_array($this->pageList) &&
        isset($this->pageList[$this->params['topic_id']])) {
      $result = sprintf(
        '<listview title="%s">'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Pages info'))
      );
      $result .= '<cols>'.LF;
      $result .= sprintf(
        '<col>%s</col>'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Id'))
      );
      $result .= sprintf(
        '<col>%s</col>'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Value'))
      );
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;
      $result .= sprintf(
        '<listitem title="%s">'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Page title'))
      );
      $result .= sprintf(
        '<subitem>%s</subitem>'.LF,
        papaya_strings::escapeHTMLChars(
          $this->pageList[$this->params['topic_id']]['detail']['topic_title']
        )
      );
      $result .= '</listitem>';
      $result .= sprintf(
        '<listitem title="%s">'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Page Id'))
      );
      $result .= sprintf(
        '<subitem>%s</subitem>'.LF,
        papaya_strings::escapeHTMLChars(
          $this->pageList[$this->params['topic_id']]['detail']['topic_id']
        )
      );
      $result .= '</listitem>';
      $result .= sprintf(
        '<listitem title="%s"><subitem>%s</subitem></listitem>'.LF,
        papaya_strings::escapeHTMLChars($this->_gt('Total votes')),
        papaya_strings::escapeHTMLChars(
          $this->pageList[$this->params['topic_id']]['votes']
        )
      );

      foreach ($this->pageList[$this->params['topic_id']]['answer'] as $id => $answer) {
        $title = ($id < 0) ? $this->_gt('Negative') : $this->_gt('Positive');
        $result .= sprintf(
          '<listitem indent="1" title="%s (%d)"><subitem>%s</subitem></listitem>'.LF,
          papaya_strings::escapeHTMLChars($title),
          (int)$id,
          papaya_strings::escapeHTMLChars($answer)
        );
        if ($id == -1) {
          $result .= sprintf(
            '<listitem indent="1" title="%s (%d)"><subitem>%s</subitem></listitem>'.LF,
            papaya_strings::escapeHTMLChars($this->_gt('Neutral')),
            0,
            papaya_strings::escapeHTMLChars(
              $this->pageList[$this->params['topic_id']]['votes'] -
              array_sum($this->pageList[$this->params['topic_id']]['answer'])
            )
          );
        }
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
      $this->layout->add($result);
    }
  }

  /**
  * Page xml list
  *
  * @access public
  */
  function getXMLResultTree() {
    $result = sprintf('<listview title="%s" width="650">'.LF, $this->_gt('Pages'));
    if (isset($this->pageList) &&
        is_array($this->pageList) &&
        count($this->pageList) > 0) {
      $result .= '<cols>'.LF;
      $result .= sprintf(
        '<col href="%s">%s</col>'.LF,
        papaya_strings::escapeHTMLChars(
          $this->getLink(array('sort' => 'topic_title'))
        ),
        papaya_strings::escapeHTMLChars($this->_gt('Page'))
      );
      $result .= sprintf(
        '<col href="%s">%s</col>'.LF,
        papaya_strings::escapeHTMLChars($this->getLink(array('sort' => 'id'))),
        papaya_strings::escapeHTMLChars($this->_gt('Page id'))
      );
      $result .= sprintf(
        '<col href="%s">%s</col>'.LF,
        papaya_strings::escapeHTMLChars($this->getLink(array('sort' => 'votes'))),
        papaya_strings::escapeHTMLChars($this->_gt('Votes'))
      );
      $result .= sprintf(
        '<col href="%s">%s</col>'.LF,
        papaya_strings::escapeHTMLChars($this->getLink(array('sort' => 'rating'))),
        papaya_strings::escapeHTMLChars($this->_gt('Rating'))
      );
      $result .= sprintf('<col></col>'.LF);
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;

      foreach ($this->pageList as $id => $dummy) {
        if (isset($this->params) && isset($this->params['topic_id'])) {
          $selected =
            ($this->params['topic_id'] == $this->pageList[$id]['detail']['topic_id'])
              ? ' selected="selected"' : '';
        } else {
          $selected = '';
        }
        if (isset($this->pageList[$id]) && isset($this->pageList[$id]['detail'])) {
          $result .= sprintf(
            '<listitem href="%s" title="%s" %s>'.LF,
            papaya_strings::escapeHTMLChars(
              $this->getLink(
                array('topic_id' => $this->pageList[$id]['detail']['topic_id'])
              )
            ),
            papaya_strings::escapeHTMLChars(
              empty($this->pageList[$id]['detail']['topic_title'])
                ? '' : $this->pageList[$id]['detail']['topic_title']
            ),
            $selected
          );

        } else {
          $result .= sprintf(
            '<listitem title="%s">'.LF,
            papaya_strings::escapeHTMLChars($this->_gt('Unknown'))
          );
        }
        $result .= sprintf(
          '<subitem>%d</subitem>'.LF,
          (int)$id
        );
        $result .= sprintf(
          '<subitem>%s</subitem>'.LF,
          papaya_strings::escapeHTMLChars(
            empty($this->pageList[$id]['votes']) ? 0 : (int)$this->pageList[$id]['votes']
          )
        );
        $result .= sprintf(
          '<subitem>%s %%</subitem>'.LF,
          papaya_strings::escapeHTMLChars(
            empty($this->pageList[$id]['rating']) ? 0 : (int)$this->pageList[$id]['rating']
          )
        );

        $images = '';
        if ($this->pageList[$id]['rating'] > 0) {
          $images .= sprintf(
            '<glyph src="%s"/>',
            papaya_strings::escapeHTMLChars($this->localImages['star'])
          );
        }
        if ($this->pageList[$id]['rating'] > 20) {
          $images .= sprintf(
            '<glyph src="%s"/>',
            papaya_strings::escapeHTMLChars($this->localImages['star'])
          );
        }
        if ($this->pageList[$id]['rating'] > 40) {
          $images .= sprintf(
            '<glyph src="%s"/>',
            papaya_strings::escapeHTMLChars($this->localImages['star'])
          );
        }
        if ($this->pageList[$id]['rating'] > 60) {
          $images .= sprintf(
            '<glyph src="%s"/>',
            papaya_strings::escapeHTMLChars($this->localImages['star'])
          );
        }
        if ($this->pageList[$id]['rating'] > 80) {
          $images .= sprintf(
            '<glyph src="%s"/>',
            papaya_strings::escapeHTMLChars($this->localImages['star'])
          );
        }
        $result .= sprintf('<subitem>%s</subitem>', $images);

        $result .= '</listitem>';
      }
      $result .= '</items>'.LF;
    }
    $result .= '</listview>'.LF;
    $this->layout->addLeft($result);
  }

  /**
  * Get XML buttons
  *
  * @access public
  */
  function getXMLButtons() {
    $toolbar = new base_btnbuilder;
    $toolbar->images = $this->papaya()->images;
    $toolbar->addSeperator();
    $toolbar->addButton(
      'Refresh Statistic Data',
      $this->getLink(array('cmd' => 'load_statistic')),
      'actions-refresh'
    );
    if ($str = $toolbar->getXML()) {
      $this->layout->addMenu(sprintf('<menu ident="edit">%s</menu>'.LF, $str));
    }
  }
}

