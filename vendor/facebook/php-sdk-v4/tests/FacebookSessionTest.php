<?php

use Facebook\FacebookSession;
use Facebook\GraphSessionInfo;

class FacebookSessionTest extends PHPUnit_Framework_TestCase
{

  public static function setUpBeforeClass()
  {
    FacebookTestHelper::setUpBeforeClass();
  }

  public function testSessionToken()
  {
    $session = new FacebookSession(FacebookTestHelper::getAppToken());
    $this->assertEquals(
      FacebookTestHelper::getAppToken(), $session->getToken()
    );
  }

  public function testGetSessionInfo()
  {
    $response = FacebookTestHelper::$testSession->getSessionInfo();
    $this->assertTrue($response instanceof GraphSessionInfo);
    $this->assertNotNull($response->getAppId());
    $this->assertTrue($response->isValid());
    $scopes = $response->getPropertyAsArray('scopes');
    $this->assertTrue(is_array($scopes));
    $this->assertEquals(5, count($scopes));
  }

  public function testExtendAccessToken()
  {
    $response = FacebookTestHelper::$testSession->getLongLivedSession();
    $this->assertTrue($response instanceof FacebookSession);
    $info = $response->getSessionInfo();
    $nextWeek = time() + (60 * 60 * 24 * 7);
    $this->assertTrue(
      $info->getProperty('expires_at') > $nextWeek
    );
  }

  public function testSessionFromSignedRequest()
  {
    $data = array(
      'user_id' => 4,
      'oauth_token' => 'fjm',
      'state' => 'wow'
    );
    $signedRequest = self::makeSignedRequest($data);

    $session = FacebookSession::newSessionFromSignedRequest(
      $signedRequest, 'wow'
    );
    $this->assertTrue($session instanceof FacebookSession);
    $this->assertEquals('fjm', $session->getToken());
    $this->assertEquals(4, $session->getUserId());
  }

  public function testAppSessionValidates()
  {
    $session = FacebookSession::newAppSession();
    try {
      $session->validate();
    } catch (\Facebook\FacebookSDKException $ex) {
      $this->fail('Exception thrown validating app session.');
    }
  }
  
  public static function makeSignedRequest($data)
  {
    if (!is_array($data)) {
      throw new Exception('Invalid data.');
    }
    $data['algorithm'] = 'HMAC-SHA256';
    $data['issued_at'] = time();
    $base64data = base64_encode(json_encode($data));
    $rawSig = hash_hmac('sha256', $base64data,
      FacebookTestCredentials::$appSecret, true);
    $sig = base64_encode($rawSig);
    return $sig.'.'.$base64data;
  }

}
