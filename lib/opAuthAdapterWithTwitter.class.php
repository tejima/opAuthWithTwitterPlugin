<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAuthAdapterGoogleApps will handle authentication for OpenPNE by GoogleApps's OpenID
 *
 * @package    OpenPNE
 * @subpackage user
 * @author     Mamoru Tejima <tejima@tejimaya.com>
 */
class opAuthAdapterWithTwitter extends opAuthAdapter
{
  protected
    $authModuleName = 'WithTwitter',
    $consumer = null,
    $response = null;

  public function configure()
  {
  }

  public function getConsumer()
  {
    if (!$this->consumer)
    {
      //$this->consumer = OpenPNEOAuth::getInstance('https://twitter.com/',$app['all']['twipne_config']['consumer_key'],$app['all']['twipne_config']['consumer_secret'])->consumer;
      
    }
    return $this->consumer;
  }

  public function getResponse()
  {
    if (!$this->response)
    {
      //todo complete がないので代わりのものを。よくわかっていない。
      $response = $this->getConsumer()->complete($this->getCurrentUrl());
      if ($response->status === Auth_OpenID_SUCCESS)
      {
        $this->response = $response;
        $sreg = new Auth_OpenID_SRegResponse();
        $obj = $sreg->fromSuccessResponse($response);
        $data = $obj->contents();
      }
    }

    return $this->response;
  }

  public function getAuthParameters()
  {
    $params = parent::getAuthParameters();
    $openid = null;

    if (sfContext::getInstance()->getRequest()->hasParameter('openid_mode'))
    {
      if ($this->getResponse())
      {
        $openid = $this->getResponse()->getDisplayIdentifier();
      }
    }

    $params['openid'] = $openid;

    return $params;
  }

  public function authenticate()
  {
    $result = parent::authenticate();
    //Twitterからのコールバック
    if(isset($_GET['oauth_token'])&&isset($_GET['oauth_verifier'])){
      error_log("authenticateDouble::"."\n",3,'/tmp/aaaaa');
      $instance =  OpenPNEOAuth::getInstance('http://twitter.com/',  Doctrine::getTable('SnsConfig')->get('awt_consumer'),  Doctrine::getTable('SnsConfig')->get('awt_secret'));
      $token = $instance->getAccessToken($_GET['oauth_token'], $_GET['oauth_verifier']);
      print_r($token);
      //phpinfo();    $member = $this->getUser()->getMember();
      error_log("p:". print_r($token,true)."\n",3,'/tmp/aaaaa');
      if($token['screen_name']){
        $line = Doctrine::getTable('MemberConfig')->findOneByNameAndValue("twitter_user_id",$token['user_id']);
        if($line){
          $result = $line->member_id;
        } else {
          $member = new Member();
          $member->setName("tmp");
          $member->setIsActive(true);
          $member->save();
          
          $member->setConfig('pc_address', $token['screen_name'] . "@twitter.com");
          $member->setConfig('twitter_user_id', $token['user_id']);
          $member->setConfig('twitter_oauth_token',$token['oauth_token']);
          $member->setConfig('twitter_screen_name', $token['screen_name']);
          $member->setConfig('twitter_oauth_token_secret',$token['oauth_token_secret']);
          $result = $member->getId();
        }
        return $result;
      }else{
        header("Location: http://yahoo.com");
        exit;
      }
    }

    //コールバックでは無く、最初にログインボタン押されたらこちら
    $client = OpenPNEOAuth::getInstance('http://twitter.com/',Doctrine::getTable('SnsConfig')->get('awt_consumer'), Doctrine::getTable('SnsConfig')->get('awt_secret'));
    $token = $client->getRequestToken( $this->getCurrentUrl());

    error_log("getCurrentUrl()".$this->getCurrentUrl()."\n",3,'/tmp/aaaaa');

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
  }

  public function getCurrentUrl()
  {
    return sfContext::getInstance()->getRequest()->getUri();
  }

  public function registerData($memberId, $form)
  {
    $member = Doctrine::getTable('Member')->find($memberId);
    if (!$member)
    {
      return false;
    }

    $member->setIsActive(true);
    return $member->save();
  }

  public function isRegisterBegin($member_id = null)
  {
    opActivateBehavior::disable();
    $member = Doctrine::getTable('Member')->find((int)$member_id);
    opActivateBehavior::enable();

    if (!$member || $member->getIsActive())
    {
      return false;
    }

    return true;
  }

  public function isRegisterFinish($member_id = null)
  {
    return false;
  }

  protected function appendMemberInformationFromProvider($member)
  {
    $ax = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($this->getResponse());
    if ($ax)
    {
      $axExchange = new opOpenIDProfileExchange('ax', $member);
      $axExchange->setData($ax->data);
      error_log(print_r($ax->data,true), 3, "/tmp/php/log.txt"); 
    }else{
      error_log("no ax.\n", 3, "/tmp/php/log.txt"); 
    }

    $sreg = Auth_OpenID_SRegResponse::fromSuccessResponse($this->getResponse());
    if ($sreg)
    {
      $sregExchange = new opOpenIDProfileExchange('sreg', $member);
      $sregExchange->setData($sreg->contents());
    }

    return $member;
  }
}
