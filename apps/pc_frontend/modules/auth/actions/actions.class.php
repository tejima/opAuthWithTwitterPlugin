<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * auth actions.
 *
 * @package    OpenPNE
 * @subpackage auth
 * @author     Mamoru Tejima
 * @version    SVN: $Id: actions.class.php 9301 2008-05-27 01:08:46Z dwhittle $
 */
class authActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfWebRequest $request A request object
  *
  */
 
  public function executeIndex(sfWebRequest $request)
  {
    $client = OpenPNEOAuth::getInstance('http://twitter.com/',Doctrine::getTable('SnsConfig')->get('awt_consumer'), Doctrine::getTable('SnsConfig')->get('awt_secret'));
    $token = $client->getRequestToken( Doctrine::getTable('SnsConfig')->get('awt_host').'auth/settoken/');
    //awt_host は Twitter認証ごもどってきてもらいたいURL。
    //$token = $client->getRequestToken("http://www.tejimaya.com");
    error_log("p:". print_r($token,true)."\n",3,'/tmp/aaaaa');
    error_log("getAuthorizeUrl():".OpenPNEOAuth::getInstance()->getAuthorizeUrl($token)."\n",3,'/tmp/aaaaa');
    error_log("awt_consumer:".Doctrine::getTable('SnsConfig')->get('awt_consumer')."\n",3,'/tmp/aaaaa');
    error_log("awt_secret:".Doctrine::getTable('SnsConfig')->get('awt_secret')."\n",3,'/tmp/aaaaa');
    error_log("awt_host:".Doctrine::getTable('SnsConfig')->get('awt_host')."\n",3,'/tmp/aaaaa');
    //header('Location: '.OpenPNEOAuth::getInstance()->getAuthorizeUrl($token)); // 認可用 URL を取得し、リダイレクト
    header('Location: ' . 'http://twitter.com/oauth/authenticate?oauth_token='.$token['oauth_token']);

    exit;
    //return sfView::SUCCESS;
  }
  public function executeSettoken(sfWebRequest $request)
  {
    error_log("executeSettoken:"."\n",3,'/tmp/aaaaa');
    $token = OpenPNEOAuth::getInstance('http://twitter.com/',  Doctrine::getTable('SnsConfig')->get('awt_consumer'),  Doctrine::getTable('SnsConfig')->get('awt_secret'))->getAccessToken($_GET['oauth_token'], $_GET['oauth_verifier']);
    print_r($token);
    //phpinfo();    $member = $this->getUser()->getMember();
    error_log("p:". print_r($token,true)."\n",3,'/tmp/aaaaa');
    $member = $this->getUser()->getMember();
    $member->setConfig('oauth_token',$token['oauth_token']);
    $member->setConfig('oauth_token_secret',$token['oauth_token_secret']);
    $member->save();
    exit;
  }
}
