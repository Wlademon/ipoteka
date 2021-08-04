<?php


namespace App\Drivers;


use App\Drivers\DriverResults\Calculated;
use App\Drivers\DriverResults\CalculatedInterface;
use App\Drivers\DriverResults\CreatedPolicy;
use App\Drivers\DriverResults\CreatedPolicyInterface;
use App\Drivers\DriverResults\PayLinkInterface;
use App\Models\Contracts;
use App\Services\PayService\PayLinks;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AbsoluteDriver implements DriverInterface
{

    /**
     * @inheritDoc
     */
    protected $baseUrl = 'https://represtapi.absolutins.ru/ords/rest';
    protected $accessToken;
    protected $ClientID = 'Wpsa0QvBoyjwUMQYJ6707A..';
    protected $ClientSecret = 'waSVo19oyiyd78T-QCMxIw..';

    public function __construct(Repository $repository, string $prefix = ''){
         //Инициализация $clientID, $ClientSecret
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

    public function request($body,$path,$validatefields){
        $json = Http::withBody(json_encode($body),'application/json')
            ->withHeaders(['Authorization'=>'bearer '.$this->accessToken])
            ->post($this->baseUrl.$path)
        ->json();
        if ($json){
            $v=Validator::make($json,$validatefields);

            if ($v->validated()){
               return $json;
            }
        }
    }


    public function calculate(array $data): CalculatedInterface
    {
        $life = 0;
        $property = 0;
        $validatefields = [
            'result'=>'required',
            'result.*.data'=>'required',
            'result.*.data.isn'=>'required',
            'result.*.data.premium_sum'=>'required',
        ];
       // if (!empty($data['objects']['life'])){
        if (($data['programCode']=='ABSOLUT_MORTGAGE_003_01')||($data['programCode']=='ABSOLUT_MORTGAGE_002_01')){
            $body = [
                'limit_sum'=>$data['remainingDebt'],
                'sex'=>$data['objects']['life']['gender']==0 ? 'М':'Ж',
                'birthday'=>$data['objects']['life']['birthDate'],
            ];
            $path = '/api/mortgage/sber/life/calculation/create';
            $life =  $this->request($body,$path,$validatefields)['result']['data']['premium_sum'];
        }
      //  if (!empty($data['objects']['property'])){
        if (($data['programCode']=='ABSOLUT_MORTGAGE_001_01')||($data['programCode']=='ABSOLUT_MORTGAGE_002_01')){
            $body = [
                'limit_sum'=>$data['remainingDebt'],
            ];
            $path = '/api/mortgage/sber/property/calculation/create';
            $property = $this->request($body,$path,$validatefields)['result']['data']['premium_sum'];
        }
        $result = [
            'life'=>$life,
            'property'=>$property,
        ];
        return new Calculated($data['isn'] ?? null, $result['life'] ?? null, $result['property'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {

        $validatefields = [
            'result'=>'required',
            'result.*.data'=>'required',
            'result.*.data.payment_link'=>'required',
        ];

         dd($contract->options);
        $body = [
            'isn'=>$contract->options['isn'],
            'sms'=>'false',
            'email'=>'false',
            'webhook'=>$payLinks->getSuccessUrl(),
        ];

        dd($body);
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {

        $validatefields = [
            'result'=>'required',
            'result.*.data'=>'required',
            'result.*.data.isn'=>'required',
            'result.*.data.premium_sum'=>'required',
            'result.*.data.policy_no'=>'required',
        ];

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
            $response = $this->request($body,$path,$validatefields);
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
            $response = $this->request($body,$path,$validatefields);
            $property = $response['result']['data']['premium_sum'];
            $policyIdProperty = $response['result']['data']['isn'];
            $policyNumberProperty = $response['result']['data']['policy_no'];

        }

        $options = $contract->options ?? [];
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
    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null)
    {

        // TODO: Implement printPolicy() method.
    }

    /**
     * @inheritDoc
     */
    public function payAccept(Contracts $contract): void
    {
        // TODO: Implement payAccept() method.
    }

    /**
     * @inheritDoc
     */
    public function sendPolice(Contracts $contract): string
    {
        // TODO: Implement sendPolice() method.
    }

    /**
     * @inheritDoc
     */
    public function getStatus(Contracts $contract): array
    {
        // TODO: Implement getStatus() method.
    }
}
