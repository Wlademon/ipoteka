<?php


namespace App\Drivers;


use App\Drivers\DriverResults\CalculatedInterface;
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
    protected $accessToken;
    protected $ClientID = "Wpsa0QvBoyjwUMQYJ6707A..";
    protected $ClientSecret = "waSVo19oyiyd78T-QCMxIw..";

    public function __construct(Repository $repository, string $prefix = ''){
         //Инициализация $clientID, $ClientSecret
    }

    public function getToken(){
        $json = Http::asForm()
            ->withHeaders([
                'Authorization'=>'Basic '.base64_encode($this->ClientID.':'.$this->ClientSecret)
                          ])
            ->post('https://represtapi.absolutins.ru/ords/rest/oauth/token',[
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


    public function calculate(array $data): CalculatedInterface
    {
        $accessToken = $this->getToken();
        if (!empty($data['objects']['life'])){
            dd('life');
        }
        if (!empty($data['objects']['property'])){
            dd('property');
        }
        // TODO: Implement calculate() method.
    }

    /**
     * @inheritDoc
     */
    public function getPayLink(Contracts $contract, PayLinks $payLinks): PayLinkInterface
    {
        // TODO: Implement getPayLink() method.
    }

    /**
     * @inheritDoc
     */
    public function createPolicy(Contracts $contract, array $data): CreatedPolicyInterface
    {
        // TODO: Implement createPolicy() method.
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
