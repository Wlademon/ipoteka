<?php

namespace App\Drivers\Source\Alpha;

use App\Exceptions\Drivers\AlphaException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * Class AlphaCalculator
 * @package App\Drivers\Source\Alpha
 */
class AlphaCalculator
{
    const BILDING_TYPE = 'FLAT';
    const SALE_TYPE_SECOND = 'SECOND';

    const GENDER_MAN = 'M';
    const GENDER_FEMALE = 'F';


    protected array $data = [];

    /**
     * AlphaCalculator constructor.
     * @throws \Throwable
     */
    public function __construct()
    {
        if (empty(config('mortgage.alfa_msk.agentContractId'))) {
            throw new AlphaException('Not set agentContractId property');
        }
        if (empty(config('mortgage.alfa_msk.managerId'))) {
            throw new AlphaException('Not set managerId property');
        }

        $this->data['agent'] = [
            'agentContractId' => ((int)config('mortgage.alfa_msk.agentContractId')),
            'managerId' => ((int)config('mortgage.alfa_msk.managerId'))
        ];
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $name
     * @param float $creditSum
     */
    public function setBank(string $name, float $creditSum): void
    {
        $this->data['bank'] = [
            'bankName' => $name,
            'creditValue' => $creditSum
        ];
    }

    /**
     * @param string $beginDate
     */
    public function setCalcDate(string $beginDate): void
    {
        $this->data['calculation'] = [
            'beginDate' => Carbon::parse($beginDate)->format('Y-m-d'),
            'calcDate' => (new Carbon())->format('Y-m-d')
        ];
    }

    /**
     * @param string $saleType
     */
    public function setInsurance(string $saleType = self::SALE_TYPE_SECOND): void
    {
        $this->data['insurance'] = [
            'buildingType' => self::BILDING_TYPE,
            'saleType' => $saleType
        ];
    }

    /**
     * @param bool $gender
     * @param string $dateBirth
     */
    public function setInsurant(bool $gender, string $dateBirth): void
    {
        $this->data['insurant'] = [
            'gender' => $gender ? self::GENDER_MAN : self::GENDER_FEMALE,
            'insurantDateBirth' => Carbon::parse($dateBirth)->format('Y-m-d'),
        ];
    }

    /**
     * @param array $profession
     * @param array $sport
     */
    public function setLifeRisk(array $profession = [], array $sport = []): void
    {
        $this->data['lifeRisk'] = [
            'illness' => false,
            'profession' => $profession,
            'sport' => $sport
        ];
    }

    /**
     * @param string|null $address
     * @param bool $goruch
     * @param int|null $year
     * @throws \Throwable
     */
    public function setPropertyRisk(?string $address, bool $goruch, ?int $year): void
    {
        if ($year && ($year < 1950 || $year > 2100)) {
            throw new AlphaException('Год постройки больше максимального или меньше минимального порога');
        }

        $this->data['propertyRisk'] = [
            'address' => $address,
            'goruch' => $goruch,
            'year' => $year
        ];
    }

    /**
     * @param string $city
     * @param string $street
     */
    public function setInsurerAddress(string $city, string $street): void
    {
        $this->data['insurer']['address'] = [
            'city' => $city,
            'street' => $street
        ];
    }

    /**
     * @param string $email
     */
    public function setInsurerEmail(string $email): void
    {
        $this->data['insurer']['email'] = $email;
    }

    /**
     * @param string $name
     * @param string $lastName
     * @param string|null $middleName
     */
    public function setInsurerFullName(string $name, string $lastName, ?string $middleName): void
    {
        $this->data['insurer']['firstName'] = $name;
        $this->data['insurer']['lastName'] = $lastName;
        $this->data['insurer']['middleName'] = $middleName ?? '';
    }

    /**
     * @param string $dateOfIssue
     * @param int $number
     * @param int $seria
     */
    public function setInsurerPersonDocument(string $dateOfIssue,int $number,int $seria): void
    {
        $this->data['insurer']['personDocument'] = [
            'dateOfIssue' => $dateOfIssue,
            'number' => $number,
            'seria' => $seria
        ];
    }

    /**
     * @param string $phone
     */
    public function setInsurerPhone(string $phone): void
    {
        $this->data['insurer']['phone'] = $phone;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->data['address'] = $address;
    }

    /**
     * @param float $addressSquare
     */
    public function setAddressSquare(float $addressSquare): void
    {
        $this->data['addressSquare'] = $addressSquare;
    }

    /**
     * @param string $dateCreditDoc
     */
    public function setDateCreditDoc(string $dateCreditDoc): void
    {
        $this->data['dateCreditDoc'] = $dateCreditDoc;
    }

    /**
     * @param string $numberCreditDoc
     */
    public function setNumberCreditDoc(string $numberCreditDoc): void
    {
        $this->data['numberCreditDoc'] = $numberCreditDoc;
    }
}
