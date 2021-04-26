<?php

namespace App\Drivers\Source\Alpha;

use App\Exceptions\Drivers\AlphaException;
use Illuminate\Support\Carbon;

class AlphaCalculator
{
    const SALE_TYPE_FIRST = 'FIRST';
    const BILDING_TYPE = 'FLAT';
    const SALE_TYPE_SECOND = 'SECOND';
    
    const GENDER_MAN = 'M';
    const GENDER_WOMAN = 'W';
    
    
    protected array $data = [];
    
    public function __construct(\Illuminate\Config\Repository $repository, string $prefix = '')
    {
        throw_if(
            !$repository->get($prefix . 'agentContractId', false),
            new AlphaException('Not set agentContractId property')
        );
        throw_if(
            !$repository->get($prefix . 'managerId', false),
            new AlphaException('Not set agentContractId property')
        );
        
        $this->data['agent'] = [
            'agentContractId' => $repository->get($prefix . 'agentContractId'),
            'managerId' => $repository->get($prefix . 'managerId')
        ];
    }
    
    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    public function setBank(string $name, float $creditSum)
    {
        $this->data['bank'] = [
            'bankName' => $name,
            'creditValue' => $creditSum
        ];
    }
    
    public function setCalcDate(string $beginDate)
    {
        $this->data['calculation'] = [
            'beginDate' => Carbon::parse($beginDate)->format('Y-m-d'),
            'calcDate' => (new Carbon())->format('Y-m-d')
        ];
    }
    
    public function setInsurance(string $saleType = self::SALE_TYPE_SECOND)
    {
        $this->data['insurance'] = [
            'buildingType' => self::BILDING_TYPE,
            'saleType' => $saleType
        ];
    }
    
    public function setInsurant(bool $gender, string $dateBirth)
    {
        $this->data['insurant'] = [
            'gender' => !empty($gender) ? self::GENDER_MAN : self::GENDER_WOMAN,
            'insurantDateBirth' => Carbon::parse($dateBirth)->format('Y-m-d'),
        ];
    }
    
    public function setLifeRisk(array $profession = [], array $sport = [])
    {
        $this->data['lifeRisk'] = [
            'illness' => false,
            'profession' => $profession,
            'sport' => $sport
        ];
    }
    
    public function setPropertyRisk(string $address, bool $goruch, ?int $year)
    {
        throw_if($year && ($year < 1950 || $year > 2100), new AlphaException('Год постройки больше максимального или меньше минимального порога'));
        $this->data['propertyRisk'] = [
            'address' => $address,
            'goruch' => $goruch,
            'year' => $year
        ];
    }
}