<?php

namespace Tests\Unit;

use Tests\TestCase;

class ApiTest extends TestCase
{
    public function testPostPolicyCalculateSuccess()
    {
        echo PHP_EOL . '====== Start testPostPolicyCalculateSuccess() =======' . PHP_EOL;

        $data = '{
          "activeFrom": "2020-09-15",
          "activeTo": "2020-10-14",
          "programCode": "ABSOLUT_LIFE_001_01",
          "insuredSum": 100000,
          "objects": [
            {
              "birthDate": "01-01-1980"
            }
          ],
          "sportIds": [
            "1"
          ]
        }';
        $response = $this->post('/v1/policies/calculate', json_decode($data, true));
        $content = json_decode($response->content(), true);
        print_r($content);

        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['premium', 'duration', 'insuredSum', 'programId']]);
        return $content['data']['premium'];
    }


    /**
     * @depends testPostPolicyCalculateSuccess
     * @param float $premium
     * @return mixed
     */
    public function testPostPolicyCreateSuccess(float $premium)
    {
        echo PHP_EOL . '====== Start testPostPolicyCreateSuccess() =======' . PHP_EOL;

        $data = '{
          "ownerId": 1,
          "options": {
            "trafficSource": [
              "test"
            ]
          },
          "activeFrom": "2020-09-15",
          "activeTo": "2020-10-14",
          "signedAt": "2020-09-04",
          "insuredSum": 100000,
          "programCode": "ABSOLUT_LIFE_001_01",
          "objects": [
            {
              "lastName": "Иванов",
              "firstName": "Иван",
              "middleName": "Иванович",
              "birthDate": "01-01-1980",
              "gender": 0
            }
          ],
          "subject": {
            "lastName": "Иванов",
            "firstName": "Иван",
            "middleName": "Иванович",
            "birthDate": "01-01-1980",
            "gender": 0,
            "phone": "+7434234-22-34",
            "email": "example@mail.com",
            "docSeries": "2112",
            "docNumber": "543954",
            "docIssueDate": "01-01-2000",
            "docIssuePlace": "Москва",
            "state": "Московская область",
            "city": "Москва",
            "street": "Ленина",
            "house": "1",
            "block": "корп 2",
            "apartment": "12",
            "kladr": "5002700000000"
          },
          "sportIds": [
            "1"
          ]
        }';
        $response = $this->post('/v1/policies', json_decode($data, true));
        $content = json_decode($response->content(), true);
        print_r($content);

        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['contractId', 'policyNumber', 'premiumSum']]);
        $this->assertEquals($content['data']['premiumSum'], $premium);
        return $content['data']['contractId'];
    }

    /**
     * @depends testPostPolicyCreateSuccess
     * @param $contractId
     * @return mixed
     */
    public function testGetPolicySuccess($contractId)
    {
        echo PHP_EOL . '====== Start testGetPolicySuccess() =======' . PHP_EOL;
        echo 'ContractId: ' . $contractId . PHP_EOL;

        $response = $this->get('/v1/policies/' . $contractId);
        $content = json_decode($response->content(), true);
        print_r($content);

        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['contractId', 'premium']]);
        return $content['data'];
    }

    public function testGetPolicyError()
    {
        echo PHP_EOL . '====== Start testGetPolicyError() =======' . PHP_EOL;
        $contractId = 0;
        echo 'ContractID: ' . $contractId . PHP_EOL;
        echo 'Not Found contract'. PHP_EOL;

        $response = $this->get('/v1/policy/' . $contractId);
        print_r(json_decode($response->content()));
        $response
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    /**
     * @depends testPostPolicyCreateSuccess
     * @param $contractId
     * @return mixed
     */
    public function testGetPolicyPayLinkSuccess($contractId)
    {
        echo PHP_EOL . '====== Start testGetPolicyPayLinkSuccess() =======' . PHP_EOL;
        echo 'ContractID: ' . $contractId . PHP_EOL;

        $response = $this->json('GET', '/v1/policies/' . $contractId . '/payLink', [
            'successUrl' => '/ns/pay/success',
            'failUrl' => '/ns/pay/fail'
        ]);
        $content = json_decode($response->content(), true);
        print_r($content);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['url', 'orderId']]);

        return $content['data']['orderId'];
    }

    public function testGetPolicyPayLinkError()
    {
        echo PHP_EOL . '====== Start testGetPolicyPayLinkError() =======' . PHP_EOL;

        $contractId = 0;
        echo 'ContractId: ' . $contractId . PHP_EOL;
        $response = $this->get('/v1/policies/' . $contractId . '/payLink', [
            'successUrl' => '/ns/pay/success',
            'failUrl' => '/ns/pay/fail'
        ]);
        print_r(json_decode($response->content()));
        $response
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    /**
     * @depends testGetPolicyPayLinkSuccess
     * @param $orderId
     */
    public function testPostPolicyAcceptError($orderId)
    {
        echo PHP_EOL . '====== Start testPostPolicyAcceptError() =======' . PHP_EOL;
        echo 'orderId: ' . $orderId . PHP_EOL;

        $response = $this->post('/v1/policies/' . $orderId . '/accept');
        print_r(json_decode($response->content()));
        $response
            ->assertStatus(500)
            ->assertJson(['success' => false]);
    }

    /**
     * @depends testPostPolicyCreateSuccess
     * @param $contractId
     */
    public function testGetPolicyStatusSuccess($contractId)
    {
        echo PHP_EOL . '====== Start testGetPolicyStatusSuccess() =======' . PHP_EOL;
        echo 'ContractID: ' . $contractId . PHP_EOL;
        $response = $this->get('/v1/policies/' . $contractId . '/status');
        $content = json_decode($response->content(), true);
        print_r($content);

        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['status']]);
        $this->assertEquals($content['data']['status'], 'Draft');
    }

    public function testGetPolicyStatusError()
    {
        echo PHP_EOL . '====== Start testGetPolicyStatusError() =======' . PHP_EOL;

        $contractId = 0;
        echo 'ContractId: ' . $contractId . PHP_EOL;
        $response = $this->get('/v1/policies/' . $contractId . '/status');
        print_r(json_decode($response->content()));
        $response
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
