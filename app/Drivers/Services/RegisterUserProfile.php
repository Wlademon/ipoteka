<?php


namespace App\Drivers\Services;


use App\Exceptions\Drivers\AlphaException;
use Illuminate\Support\Arr;

class RegisterUserProfile
{
    const REGISTER_PROFILE_URL = '/api/userPartner/';

    protected array $data = [];

    public function registerProfile($client, $host): string
    {
        $result = $client->post(
            $host . self::REGISTER_PROFILE_URL, [
                'json' => $this->data
            ]
        );
        if ($result->getStatusCode() !== 200) {
            throw new AlphaException('Error create user profile');
        }
        $decodeResult = json_decode($result->getBody()->getContents(), true);

        return Arr::get($decodeResult, 'user_id', 0);
    }

    public function setEmail(string $email)
    {
        $this->data['email'] = $email;
    }

    public function setFullName(string $name, string $lastName, ?string $middleName)
    {
        $this->data['name'] = $name;
        $this->data['last_name'] = $lastName;
        $this->data['second_name'] = $middleName ?? '';
    }

    public function setPassword(?string $password)
    {
        $this->data['password'] = $password;
    }

    public function setPhone(string $phone)
    {
        $this->data['phone'] = $phone;
    }

    public function setBirthday(string $birthday)
    {
        $this->data['birthday'] = $birthday;
    }

    public function setContractNumber(?string $contractNumber)
    {
        $this->data['contract_number'] = $contractNumber;
    }

    public function setOfferAccepted(?string $offerAccepted)
    {
        $this->data['offer_accepted'] = $offerAccepted;
    }

    public function setPartnerName(string $partnerName)
    {
        $this->data['partner_name'] = $partnerName;
    }
}
