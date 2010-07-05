<?php
 
/**
* Copyright 2009 Kousuke Ebihara
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/
 
/**
* This class is for helping to use OAuth of OpenPNE
*
* @author Kousuke Ebihara <ebihara@tejimaya.com>
*/
class OpenPNEOAuth
{
  protected static $instance = null;
 
  protected
    $baseUrl = null,
    $consumer = null;
 
  protected function __construct($url, $key, $secret)
  {
    $this->consumer = new OAuthConsumer($key, $secret);
    $this->baseUrl = $url;
  }
 
  public static function getInstance($baseUrl = null, $key = null, $secret = null)
  {
    if (!self::$instance && func_num_args() != 3)
    {
      throw new LogicException('You must specify consumer key and consumer secret to get a consumer.');
    }
 
    if (func_num_args() == 3)
    {
      self::$instance = new OpenPNEOAuth($baseUrl, $key, $secret);
    }
 
    return self::$instance;
  }
 
  public function getRequestToken($callbackUrl)
  {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, null, 'POST', $this->getRequestTokenUrl(), array('oauth_callback' => $callbackUrl));
    $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, null);
 
    $res = OpenPNEOAuthUtil::doPost($request->get_normalized_http_url(), $request->to_postdata());
    $token = OpenPNEOAuthUtil::getTokenArrayByString($res);
 
    return $token;
  }
 
  public function getAccessToken($requestToken, $verifier)
  {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $requestToken, 'POST', $this->getAccessTokenUrl(), array('oauth_token' => $requestToken, 'oauth_verifier' => $verifier));
    $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, $requestToken);
 
    $res = OpenPNEOAuthUtil::doPost($request->get_normalized_http_url(), $request->to_postdata());
    $token = OpenPNEOAuthUtil::getTokenArrayByString($res);
 
    return $token;
  }
 
  protected function getRequestTokenUrl()
  {
    return $this->baseUrl.'oauth/request_token';
  }
 
  public function getAuthorizeUrl($token)
  {
    return $this->baseUrl.'oauth/authorize?oauth_token='.$token['oauth_token'];
  }
 
  protected function getAccessTokenUrl()
  {
    return $this->baseUrl.'oauth/access_token';
  }
 
  protected function getSignRequest($uri, $token, $method = 'POST')
  {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $token['oauth_token'], strtoupper($method), $uri, $token);
    $request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, new OAuthToken($token['oauth_token'], $token['oauth_token_secret']));
 
    return $request;
  }
 
  public function doOAuthPost($uri, $data, $token)
  {
    $request = $this->getSignRequest($uri, $token, 'POST');
 
    return OpenPNEOAuthUtil::doPost($request->get_normalized_http_url(), $data, array($request->to_header()));
  }
 
  public function doOAuthGet($uri, $token)
  {
    $request = $this->getSignRequest($uri, $token, 'GET');
 
    return OpenPNEOAuthUtil::doGet($uri, array($request->to_header()));
  }
 
  public function doOAuthPut($uri, $data, $token)
  {
    $request = $this->getSignRequest($uri, $token, 'PUT');
 
    return OpenPNEOAuthUtil::doPut($request->get_normalized_http_url(), $data, array($request->to_header()));
  }
 
  public function doOAuthDelete($uri, $token)
  {
    $request = $this->getSignRequest($uri, $token, 'DELETE');
 
    return OpenPNEOAuthUtil::doDelete($uri, array($request->to_header()));
  }
}
 
class OpenPNEOAuthUtil
{
  public static function getCommonCurlHandler($uri, $header)
  {
    $h = curl_init();
    curl_setopt($h, CURLOPT_URL, $uri);
    curl_setopt($h, CURLOPT_HTTPHEADER, $header);
    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
 
    return $h;
  }
 
  public static function doPost($uri, $data, $header = array())
  {
    $h = self::getCommonCurlHandler($uri, $header);
    curl_setopt($h, CURLOPT_POST, true);
    curl_setopt($h, CURLOPT_POSTFIELDS, $data);
    curl_setopt($h, CURLOPT_HTTPHEADER, array('Expect:'));  
    $result = curl_exec($h);
 
    curl_close($h);
 
    return $result;
  }
 
  public static function doGet($uri, $header = array())
  {
    $h = self::getCommonCurlHandler($uri, $header);
    $result = curl_exec($h);
 
    curl_close($h);
 
    return $result;
  }
 
  public static function doPut($uri, $data, $header = array())
  {
    $h = self::getCommonCurlHandler($uri, $header);
    curl_setopt($h, CURLOPT_PUT, true);
    curl_setopt($h, CURLOPT_PUTFIELDS, $data);
 
    $result = curl_exec($h);
 
    curl_close($h);
 
    return $result;
  }
 
  public static function doDelete($uri, $header = array())
  {
    $h = self::getCommonCurlHandler($uri, $header);
    curl_setopt($h, CURLOPT_CUSTOMREQUEST, 'DELETE');
    $result = curl_exec($h);
 
    curl_close($h);
 
    return $result;
  }
 
  public static function getTokenArrayByString($string)
  {
    $result = array();
    $params = explode('&', $string);
 
    foreach ($params as $param)
    {
      $pieces = explode('=', $param);
      $result[$pieces[0]] = $pieces[1];
    }
 
    return $result;
  }
}
