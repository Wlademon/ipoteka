<?php


namespace App\Drivers;


use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLink;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Drivers\Traits\DriverTrait;
use App\Models\Contract;
use App\Services\PayService\PayLinks;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\PaymentService;
use PHPUnit\Framework\MockObject\RuntimeException;
use Psy\Util\Str;

class AbsoluteDriver implements DriverInterface, LocalPaymentDriverInterface
{

    use DriverTrait;

    /**
     * @inheritDoc
     */
    protected $baseUrl = 'https://represtapi.absolutins.ru/ords/rest';
    protected $accessToken;
    protected $ClientID = 'Wpsa0QvBoyjwUMQYJ6707A..';
    protected $ClientSecret = 'waSVo19oyiyd78T-QCMxIw..';
    protected $payService;
    protected $pdfpath = 'ab/pdf/';


    public function __construct(Repository $repository, string $prefix = ''){
        $this->payService = new PaymentService($repository->get($prefix . 'pay_host'));
        $this->accessToken = $this->getToken();
    }

    public function getToken(){
        $json = Http::asForm()
            ->withHeaders([
                'Authorization'=>'Basic '.base64_encode($this->ClientID.':'.$this->ClientSecret)
                          ])
            ->post($this->baseUrl.'/oauth/token',[
            'grant_type'=>'client_credentials'
        ])
        ->json();
        if ($json){
            $v=Validator::make($json,[
              'access_token'=>'required',
              'token_type'=>'required',
              'expires_in'=>'required',
            ]);

            if ($v->validated()){
                return $json['access_token'];
            }
        }
    }

    public function post($data,$path,$validateFields){
        try {
            $json = $this->request($data)
                ->post($this->baseUrl . $path);
            if ($json->getStatusCode() === 200) {
                $v = Validator::make($json->json(),$validateFields);
                if ($v->validated()){
                    return $json;
                } else {
                    throw new RuntimeException("Validate error: from {$path}");
                }
            } else {
                throw new RuntimeException("Status code: {$json->getStatusCode()} from {$path}");
            }
        } catch (\Exception $e){
            throw new RuntimeException("Put request exception from {$path}");
        }
    }

    public function put($path,$validateFields,$data = null){
        try {
           // $json = $this->request($data)->put($this->baseUrl . $path);

            $json = Http::withHeaders(['Authorization'=>'bearer '.$this->accessToken])->put($this->baseUrl . $path);

            if ($json->getStatusCode() === 200) {

                $v = Validator::make($json->json(),$validateFields);
                if ($v->validated()){

                    return $json;
                } else {
                    throw new RuntimeException("Validate error: from {$path}");
                }
            } else {
                throw new RuntimeException("Status code: {$json->getStatusCode()} from {$path}");
            }
        } catch (\Exception $e){
            throw new RuntimeException("Put request exception from {$path}");
        }
    }

    public function get($path,$validateFields){
        try {
            $json = $this->request()->get($this->baseUrl.$path);
            if ($json->getStatusCode() === 200){
                $v=Validator::make($json->json(),$validateFields);
                if ($v->validated()){
                    return $json;
                } else {
                    throw new RuntimeException("Validate error: from {$path}");
                }
            } else {
                throw new RuntimeException("Status code: {$json->getStatusCode()} from {$path}");
            }
        } catch (\Exception $e){
            throw new RuntimeException("Get request exception from {$path}");
        }
    }

    private function request($data=null){
        return Http::withBody(json_encode($data),'application/json')
            ->withHeaders(['Authorization'=>'bearer '.$this->accessToken]);

    }


    public function calculate(array $data): CalculatedInterface
    {
        $life = 0;
        $property = 0;
        $validateFileds = [
            'result' => 'required',
            'result.*.data' => 'required',
            'result.*.data.*.premium_sum' => 'required',
        ];

            // Возможны три варианта страхования ABSOLUT_MORTGAGE_003_01 (Жизнь); ABSOLUT_MORTGAGE_001_01 (Имущество); ABSOLUT_MORTGAGE_002_01 (Жизнь + имущество)
            if (($data['programCode'] == 'ABSOLUT_MORTGAGE_003_01') || ($data['programCode'] == 'ABSOLUT_MORTGAGE_002_01')) {
                $body = [
                    'limit_sum' => $data['remainingDebt'],
                    'sex' => $data['objects']['life']['gender'] == 0 ? 'М' : 'Ж',
                    'birthday' => $data['objects']['life']['birthDate'],
                ];
                $path = '/api/mortgage/sber/life/calculation/create';
                $life = $this->post($body, $path, $validateFileds)['result']['data']['premium_sum'];
            }
            if (($data['programCode'] == 'ABSOLUT_MORTGAGE_001_01') || ($data['programCode'] == 'ABSOLUT_MORTGAGE_002_01')) {
                $body = [
                    'limit_sum' => $data['remainingDebt'],
                ];
                $path = '/api/mortgage/sber/property/calculation/create';
                $property = $this->post($body, $path, $validateFileds)['result']['data']['premium_sum'];
            }
            $result = [
                'life' => $life,
                'property' => $property,
            ];
            return new Calculated($data['isn'] ?? null, $result['life'] ?? null, $result['property'] ?? null);
        }


    /**
     * @inheritDoc
     */
    public function getPayLink(Contract $contract, PayLinks $payLinks): PayLinkInterface
    {

        $urls = [
            'success'=>$payLinks->getSuccessUrl(),
            'fail'=>$payLinks->getFailUrl(),
        ];

        switch ($contract->options['programCode']){
            case 'ABSOLUT_MORTGAGE_002_01':
                $array = [
                    [
                        'price'=>$contract->options['price']['priceLife'],
                        'isn'=>$contract->options['isn']['isnLife'],
                        'description' => 'Жизнь',
                    ],
                    [
                        'price'=>$contract->options['price']['priceProperty'],
                        'isn'=>$contract->options['isn']['isnProperty'],
                        'description' => 'Имущество',
                    ],
                ];
                break;
            case 'ABSOLUT_MORTGAGE_001_01':
                $array = [
                    [
                        'price'=>$contract->options['price']['priceProperty'],
                        'isn'=>$contract->options['isn']['isnProperty'],
                        'description' => 'Имущество',
                    ],
                ];
                break;
            case 'ABSOLUT_MORTGAGE_003_01':
                $array = [
                    [
                        'price'=>$contract->options['price']['priceLife'],
                        'isn'=>$contract->options['isn']['isnLife'],
                        'description' => 'Жизнь',
                    ],
                ];
                break;
        }

        $result = $this->payService->payLink($contract,$urls,$array);

            return new PayLink(
                $result['orderId'], $result['url'], $contract->remainingDebt
            );
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contract $contract, array $data): CreatedPolicyInterface
    {

        if (($data['programCode']=='ABSOLUT_MORTGAGE_003_01')||($data['programCode']=='ABSOLUT_MORTGAGE_002_01')) {
            $body = [
                'date_begin' => $data['activeFrom'],
                'agr_credit_number'=>$data['mortgageAgreementNumber'],
                'agr_credit_date_conc'=>$data['activeTo'],
                'limit_sum'=>$data['remainingDebt'],
                'policy_holder'=>[
                    'lastname'=>$data['objects']['life']['lastName'],
                    'firstname'=>$data['objects']['life']['firstName'],
                    'parentname'=>$data['objects']['life']['middleName'],
                    'sex'=>$data['objects']['life']['gender']==0 ? 'М':'Ж',
                    'birthday'=>$data['objects']['life']['birthDate'],
                    'address'=>[
                        [
                            'code'=>2247,
                            'code_desc'=>'',
                            'text'=>$data['subject']['state'].', '.$data['subject']['city'].', '.$data['subject']['street'].
                            ','.$data['subject']['house'].', '.$data['subject']['block'].', '.$data['subject']['apartment'],
                            'fias_id'=>'',
                        ],
                        [
                            'code'=>2246,
                            'code_desc'=>'',
                            'text'=>$data['subject']['state'].', '.$data['subject']['city'].', '.$data['subject']['street'].
                            ', '.$data['subject']['house'].', '.$data['subject']['block'].', '.$data['subject']['apartment'],
                        ],
                    ],
                    'contact'=>[
                        [
                            'code'=>2243,
                            'code_desc'=>'E-mail',
                            'text'=>$data['subject']['email'],
                        ],
                        [
                            'code'=>2240,
                            'text'=>$data['subject']['phone'],
                        ],
                    ],
                    'document'=>[
                        'code'=>1165,
                        'series'=>$data['subject']['docSeries'],
                        'number'=>$data['subject']['docNumber'],
                        'issue_date'=>$data['subject']['docIssueDate'],
                        'issue_by'=>$data['subject']['docIssuePlace'],
                    ],
                ],
            ];

            $path = '/api/mortgage/sber/life/agreement/create';

            $validateFields = [
                'result'=>'required',
                'result.*.data'=>'required',
                'result.*.data.*.premium_sum' => 'required',
                'result.*.data.*.isn' => 'required',
                'result.*.data.*.policy_no' => 'required',
            ];

            $response = $this->post($body,$path,$validateFields);

            $life =  $response['result']['data']['premium_sum'];

            $policyIdLife = $response['result']['data']['isn'];
            $policyNumberLife = $response['result']['data']['policy_no'];
        }

        if (($data['programCode']=='ABSOLUT_MORTGAGE_001_01')||($data['programCode']=='ABSOLUT_MORTGAGE_002_01')){
            $body = [
                'date_begin' => $data['activeFrom'],
                'agr_credit_number'=>$data['mortgageAgreementNumber'],
                'agr_credit_date_conc'=>$data['activeTo'],
                'limit_sum'=>$data['remainingDebt'],
                'ins_object'=>[
                    'address'=>[
                        'code'=>2247,
                        'code_desc'=>'',
                        'text'=>$data['objects']['property']['state'].', '.$data['objects']['property']['city'].', '.$data['objects']['property']['street'].
                            ', '.$data['objects']['property']['house'].', '.$data['objects']['property']['block'].', '.$data['objects']['property']['apartment'],
                        'fias_id'=>'',
                    ],
                ],
                'policy_holder'=>[
                    'lastname'=>$data['objects']['life']['lastName'],
                    'firstname'=>$data['objects']['life']['firstName'],
                    'parentname'=>$data['objects']['life']['middleName'],
                    'sex'=>$data['objects']['life']['gender']==0 ? 'М':'Ж',
                    'birthday'=>$data['objects']['life']['birthDate'],
                    'address'=>[
                        [
                            'code'=>2247,
                            'code_desc'=>'',
                            'text'=>$data['subject']['state'].', '.$data['subject']['city'].', '.$data['subject']['street'].
                                ','.$data['subject']['house'].', '.$data['subject']['block'].', '.$data['subject']['apartment'],
                            'fias_id'=>'',
                        ],
                        [
                            'code'=>2246,
                            'code_desc'=>'',
                            'text'=>$data['subject']['state'].', '.$data['subject']['city'].', '.$data['subject']['street'].
                                ', '.$data['subject']['house'].', '.$data['subject']['block'].', '.$data['subject']['apartment'],
                        ],
                    ],
                    'contact'=>[
                        [
                            'code'=>2243,
                            'code_desc'=>'E-mail',
                            'text'=>$data['subject']['email'],
                        ],
                        [
                            'code'=>2240,
                            'text'=>$data['subject']['phone'],
                        ],
                    ],
                    'document'=>[
                        'code'=>1165,
                        'series'=>$data['subject']['docSeries'],
                        'number'=>$data['subject']['docNumber'],
                        'issue_date'=>$data['subject']['docIssueDate'],
                        'issue_by'=>$data['subject']['docIssuePlace'],
                    ],
                ],
            ];

            $path = '/api/mortgage/sber/property/agreement/create';
            $validateFields = [
                'result'=>'required',
                'result.*.data'=>'required',
                'result.*.data.*.premium_sum' => 'required',
                'result.*.data.*.isn' => 'required',
                'result.*.data.*.policy_no' => 'required',
            ];
            $response = $this->post($body,$path,$validateFields);
            $property = $response['result']['data']['premium_sum'];
            $policyIdProperty = $response['result']['data']['isn'];
            $policyNumberProperty = $response['result']['data']['policy_no'];

        }
        $options = $contract->options ?? [];
        $options['price'] = [
          'priceLife'=>$life,
          'priceProperty'=>$property,
        ];
        $options['isn'] = [
            'isnLife'=> isset($policyIdLife) ? $policyIdLife : null,
            'isnProperty'=> isset($policyIdProperty) ? $policyIdProperty : null,
        ];
        $contract->options = $options;
        return new CreatedPolicy(
            null,
            isset($policyIdLife) ? $policyIdLife : null,
            isset($policyIdProperty) ? $policyIdProperty : null,
            $life ?? null,
            $property ?? null,
            $policyNumberLife ?? null,
            $policyNumberProperty ?? null,
        );
    }

    /**
     * @inheritDoc
     */

    public function generatePDF($bytes,$filename): string {
        $filepath = Storage::path($this->pdfpath);
        if (!Storage::exists($this->pdfpath)) {
            if (!mkdir($filepath, 0777, true) && !is_dir($filepath)) {
                throw new RuntimeException('Directory "%s" was not created :' . $filepath);
            }
        }
        $pdf = base64_decode($bytes);
        Storage::put($this->pdfpath.$filename.'.pdf',$pdf);
        $base64 = $this->generateBase64($filepath.$filename.'.pdf');
        return $base64;
    }

    public function policyExist($isn): bool{

        $result =  Storage::exists($this->pdfpath.$isn.'.pdf')?true:false;
      return $result;
    }

    public function getPolicyIsn($contract): array{
        switch ($contract->getOptionsAttribute()['programCode']){
            case 'ABSOLUT_MORTGAGE_002_01':
                $response = [
                    $contract->getOptionsAttribute()['isn']['isnLife'],
                    $contract->getOptionsAttribute()['isn']['isnProperty'],
                ];
                break;
            case 'ABSOLUT_MORTGAGE_001_01':
                $response = [
                    $contract->getOptionsAttribute()['isn']['isnProperty'],
                ];

                break;
            case 'ABSOLUT_MORTGAGE_003_01':
                $response = [
                    $contract->getOptionsAttribute()['isn']['isnLife'],
                ];
                break;
        }
        return $response;
    }

    public static function generateBase64($path): string
    {
        return base64_encode(file_get_contents($path));
    }

    public function printPolicy(Contract $contract, bool $sample, bool $reset, ?string $filePath = null)
    {
        $isnArray = $this->getPolicyIsn($contract);
        if(count($isnArray)){
            foreach ($isnArray as $isn) {
                if ($this->policyExist($isn)){
                    $result[]=[
                        $this->generateBase64($this->pdfpath.$isn.'.pdf'),
                    ];
                }
                else {
                    $validateFields = [
                        'result'=>'required',
                        'results.*.data'=>'required',
                        'results.*.data.*.document'=>'required',
                        'results.*.data.*.document.*.bytes'=>'required',
                    ];
                    $bytes = $this->get("/api/print/agreement/{$isn}",$validateFields)['result']['data']['document']['bytes'];
                    $result[] = [
                        $this->generatePDF($bytes,$isn),
                    ];
                }
            }
            return $result;
        } else{
            throw new RuntimeException('ISN not found for this contract');
        }

    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contract $contract): void
    {
        $validateFields = [
            'status'=>'required',
            'results'=>'required',
            'results.*.code'=>'required',
        ];
        $isnArray = $this->getPolicyIsn($contract);
        foreach ($isnArray as $isn) {
            $this->put("/api/agreement/set/released/{$isn}",$validateFields);
        }
//        switch ($contract->getOptionsAttribute()['programCode']){
//            case 'ABSOLUT_MORTGAGE_002_01':
//                $response[] = [
//                    'life' => $this->put("/api/agreement/set/released/{$contract->getOptionsAttribute()
//                    ['isn']['isnLife']}",$validateFields),
//                    'property'=>$this->put("/api/agreement/set/released/{$contract->getOptionsAttribute()
//                    ['isn']['isnProperty']}",$validateFields),
//                ];
//                break;
//            case 'ABSOLUT_MORTGAGE_001_01':
//                $response = [
//                    'property'=>$this->put("/api/agreement/set/released/{$contract->getOptionsAttribute()
//                    ['isn']['isnProperty']}",$validateFields),
//                ];
//                break;
//            case 'ABSOLUT_MORTGAGE_003_01':
//                $response = [
//                    'life' => $this->put("/api/agreement/set/released/{$contract->getOptionsAttribute()
//                    ['isn']['isnLife']}",$validateFields),
//                ];
//                break;
//        }
    }

    /**
     * @inheritDoc
     */
    public function sendPolice(Contract $contract): string
    {
        // TODO: Implement sendPolice() method.
    }
}
