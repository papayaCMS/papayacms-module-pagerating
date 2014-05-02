<?php
/**
* Admin page page ranking
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
* @version $Id: edmodule_pagerating.php 39520 2014-03-05 16:43:31Z weinert $
*/

/**
* Admin page page ranking
*
* @package Papaya-Modules
* @subpackage Free-PageRating
*/
class edmodule_pagerating extends base_module {

  /**
  * permissions
  * @var array $permissions
  */
  var $permissions = array(
    1 => 'Manage',
  );


  /**
  * Function for execute module
  *
  * @access public
  */
  function execModule() {
    if ($this->hasPerm(1, TRUE)) {
      $pageRating = new admin_pagerating;
      $pageRating->module = $this;
      $pageRating->layout = $this->layout;

      $pageRating->initialize();
      $pageRating->execute();
      $pageRating->getXML();
    }
  }
}
