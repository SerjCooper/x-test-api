<?php
/**
 * Created by PhpStorm.
 * User: Serj
 * Date: 21.05.2017
 * Time: 21:54
 */

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

require ("../configs/vendor/autoload.php");

class PositiveApiTest extends TestCase
{
  protected $client;
  protected $params = [];

  protected $merchant_id;
  protected $project_id;
  protected $api_key;
  protected $payment_systems_id;
  protected $packages_api;
  protected $base_uri;
  

  protected function setUp()
  {
    $this->params = require '../configs/params.php';

    $this->merchant_id = $this->params['merchant_id'];
    $this->project_id = $this->params['project_id'];
    $this->api_key = $this->params['api_key'];
    $this->payment_systems_id = $this->params['payment_systems_id'];
    $this->packages_api = $this->params['packages_api'];
    $this->base_uri = $this->params['base_uri'];

    $auth_uri = 'https://' . $this->merchant_id . ":" . $this->api_key . "@" . $this->base_uri;
    $this->client = new GuzzleHttp\Client(['base_uri' => $auth_uri]);
    $this->client->get('', [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->merchant_id . ":" . $this->api_key),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
      ]
    ]);
  }

  /*
   * Проверка вывода информации об акциях
   */
  public function testGetPromos()
  {
    $response = $this->client->get("");
    $this->assertEquals(200, $response->getStatusCode());
    $data = json_decode($response->getBody(), true);
    var_dump($data);
  }

  public function testPostCreatePromo()
  {
    $response = $this->client->post("promotions", [
      'json' => [
        'technical_name' => 'Bond\'s promotion',
        'label' => ['en' => '13% SAVE'],
        'name' => ['en' => '13% Webmoney Discount'],
        'description' => ['en' => '13% Webmoney Discount test'],
        'project_id' => $this->project_id
      ]
    ]);
    $this->assertEquals(201, $response->getStatusCode());
    $data = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('id', $data);
    var_dump($data);
    return $data;
  }
/*
 * Проверка активации акции при её создании
 */
  public function testPostCreatePromoValidEnabled()
  {
    $response = $this->client->post("promotions", [
      'json' => [
        'technical_name' => 'Bond\'s promotion',
        'label' => ['en' => '13% SAVE'],
        'name' => ['en' => '13% Webmoney Discount'],
        'description' => ['en' => '13% Webmoney Discount test'],
        'project_id' => $this->project_id,
        'enabled' => true
      ]
    ]);
    $data = json_decode($response->getBody(), true);
    $response = $this->client->get('promotions/' . $data['id']);
    $data = json_decode($response->getBody(), true);
    $this->assertEquals(false, $data['enabled']);
    var_dump($data);
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testPutUpdatePromo(array $data)
  {
    $response = $this->client->put("promotions/" . $data['id'], [
      'json' => [
        'technical_name' => 'Bond\'s UPDATE promotion',
        'label' => ['en' => '31% SAVE'],
        'name' => ['en' => '31% Webmoney Discount'],
        'description' => ['en' => '31% Webmoney Discount test'],
        'project_id' => $this->project_id
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $data = json_decode($response->getBody(), true);
    var_dump($data);
  }
/*
 * Тест на возможность изменения project_id при ReadOnly = true
 */
  public function testPutUpdatePromoAttrProjectIdReadOnly()
  {
    $data['id'] = 10531;
    try {
      $response = $this->client->put("promotions/" . $data['id'], [
        'json' => [
          'technical_name' => 'Bond\'s UPDATE promotion',
          'label' => ['en' => '31% SAVE'],
          'name' => ['en' => '31% Webmoney Discount'],
          'description' => ['en' => '31% Webmoney Discount test'],
          'project_id' => 1
        ]
      ]);
    } catch (\GuzzleHttp\Exception\RequestException $e)
    {
      $code_status = $e->getCode();
      $this->assertEquals(403, $code_status);
      var_dump($e->getMessage());
    }
    //$this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testPutUpdateAttrReadOnlyPromo(array $data)
  {
    $response = $this->client->put("promotions/" . $data['id'], [
      'json' => [
        'technical_name' => 'Bond\'s UPDATE promotion',
        'label' => ['en' => '31% SAVE'],
        'name' => ['en' => '31% Webmoney Discount'],
        'description' => ['en' => '31% Webmoney Discount test'],
        'project_id' => $this->project_id,
        'read_only' => true
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    var_dump(json_decode($response->getBody(), true));
  }

  /**
   * @depends testPostCreatePromo
   **/
  public function testPutPromoPaySys(array $data)
  {
    $response = $this->client->put('promotions/' . $data['id'] . '/payment_systems', [
      'json' => [
        'payment_systems' => [
          [
            'id' => 24 //PayPal
          ]
        ]
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    var_dump(json_decode($response->getBody(), true));
  }

  public function testGetPromoSubject()
  {
    $promo_id = 8103;
    $response = $this->client->get('promotions/' . $promo_id . '/subject');
    $this->assertEquals(200, $response->getStatusCode());
    var_dump(json_decode($response->getBody(), true));
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testPutPromoSubject(array $data)
  {
    $promo_id = $data['id'];
    $response = $this->client->put('promotions/' . $promo_id . '/subject', [
      'json' => [
        'purchase' => true,
        'items' => null,
        'packages' => $this->packages_api,
        'subscriptions' => null,
        'digital_contents' => null
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    var_dump(json_decode($response->getBody(), true));
  }

  public function testGetPromoPaySys()
  {
    $promo_id = 10479;
    $response = $this->client->get('promotions/' . $promo_id . '/payment_systems');
    $data = json_decode($response->getBody(), true);
    $this->assertEquals(200, $response->getStatusCode());
    var_dump($data);
  }

  public function testGetHasKeyPromoPaySys()
  {
    $promo_id = 10479;
    $response = $this->client->get('promotions/' . $promo_id . '/payment_systems');
    $data = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('payment_systems', $data);
    var_dump($data);
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testPutPromoPeriods(array $data)
  {
    $dateTime = new DateTime("2023-05-22 22:35:00");
    $response = $this->client->put('promotions/' . $data['id'] . '/periods', [
      'json' => [
        'periods' => [
          [
            'from' => $dateTime->format(\DateTime::ISO8601),
            'to' => $dateTime->modify('+ 1 month')->format(\DateTime::ISO8601)
          ]
        ]
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $response = $this->client->get('promotions/' . $data['id'] . '/periods');
    var_dump(json_decode($response->getBody(), true));
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testGetPromoPeriods(array $data)
  {
    $promo_id = $data['id'];
    $response = $this->client->get('promotions/' . $promo_id . '/periods');
    $data = json_decode($response->getBody(), true);
    $this->assertEquals(200, $response->getStatusCode());
    var_dump($data);
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testPutPromoRewards(array $data)
  {
    $promo_id = $data['id'];
    $response = $this->client->put('promotions/' . $promo_id . '/rewards', [
      'json' => [
        'purchase' => [
          'discount_percent' => 10
        ],
        'package' => [
          'bonus_percent' => 5,
          'bonus_amount' => 5
        ],
        'item' => [
          'discount' => [
            [
              'sku' => 't-43-3-unique-id',
              'name' => 'T-34-3',
              'max_amount' => 10,
              'discount_percent' => 5
            ]
          ],
          'bonus' => [
            [
              'sku' => 't-43-3-unique-id',
              'name' => 'T-34-3',
              'amount' => 2
            ]
          ]
        ],
        'subscription' =>
          [
            'trial_days' => 30
          ]
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $response = $this->client->get('promotions/' . $promo_id . '/rewards');
    var_dump(json_decode($response->getBody(), true));
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testGetPromoRewards(array $data)
  {
    $promo_id = $data['id'];
    $response = $this->client->get('promotions/' . $promo_id . '/rewards');
    $data = json_decode($response->getBody(), true);
    $this->assertEquals(200, $response->getStatusCode());
    var_dump($data);
  }
  /**
   * @depends testPostCreatePromo
   */
  public function testGetReviewPromo(array $data)
  {
    $response = $this->client->get("promotions/" . $data['id'] . "/review");
    $data = json_decode($response->getBody(), true);
    var_dump($data);
    $this->assertNotEmpty($data);
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testPutTogglePromo(array $data)
  {
    $promo_id = $data['id'];
    $response = $this->client->put("promotions/" . $promo_id . "/toggle", [
      'json' => [
        'promotion_id' => $promo_id
      ]
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    var_dump(json_decode($response->getBody(), true));
  }
  /**
   * @depends testPostCreatePromo
   */
  public function testIsTooglePromo(array $data)
  {
    $promo_id = $data['id'];
    $response = $this->client->get("promotions/" . $promo_id);
    $data = json_decode($response->getBody(), true);
    $isToogleBefore = $data['enabled'];
    printf("promo_enabled_before: " . $isToogleBefore);
    $response = $this->client->put("promotions/" . $promo_id . "/toggle", [
      'json' => [
        'promotion_id' => $promo_id
      ]
    ]);
    $response = $this->client->get("promotions/" . $promo_id);
    $data = json_decode($response->getBody(), true);
    $isToogleAfter = $data['enabled'];
    printf("promo_enabled_After: " . $isToogleAfter);
    $this->assertNotEquals($isToogleBefore, $isToogleAfter);
    var_dump(json_decode($response->getBody(), true));
  }

  /**
   * @depends testPostCreatePromo
   */
  public function testDeletePromo(array $data)
  {
    $response = $this->client->delete('promotions/' . $data['id']);
    $this->assertEquals(204, $response->getStatusCode());
    var_dump(json_decode($response->getBody(), true));
  }

}
