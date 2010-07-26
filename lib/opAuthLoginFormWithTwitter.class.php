<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * Twitter Login form (submit button only.)
 *
 * @package    OpenPNE
 * @subpackage form
 * @author     Mamoru Tejima
 */
class opAuthLoginFormWithTwitter extends opAuthLoginForm
{
  public function configure()
  {

    parent::configure();
  }

  public function validate($validator, $values, $arguments = array())
  {
    return $result;
  }

  public function getRedirectHtml()
  {
    return $this->getValue('redirect_html');
  }

  public function getRedirectUrl()
  {
    return $this->getValue('redirect_url');
  }
}
