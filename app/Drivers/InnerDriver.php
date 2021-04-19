<?php

namespace App\Drivers;

use App\Drivers\Traits\PrintPdfTrait;
use App\Exceptions\Drivers\InnerDriverException;
use App\Models\Contracts;
use App\Models\Programs;
use Carbon\Carbon;
use Exception;

/**
 * Class InnerDriver
 * @package App\Drivers
 */
class InnerDriver extends BaseDriver
{
    use PrintPdfTrait;

    /**
     * @inheritdoc
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function calculate(array $data): array
    {
        $program = Programs::active()
            ->whereProgramCode($data['programCode'])
            ->where('insured_sum', '>=', $data['insuredSum'])
            ->orderBy('insured_sum')
            ->first();
        if (!$program) {
            self::abortLog('Program not found for data', InnerDriverException::class);
        }
        self::log("Found Program with ID {$program->id}");
        $matrix = $program->matrix;
        $conditions = $program->conditions;
        $objectsCount = count($data['objects']);
        if ($objectsCount > $conditions->maxInsuredCount) {
            self::abortLog(
                "Количество страхуемых {$objectsCount} больше максимального возможного {$conditions->maxInsuredCount}",
                InnerDriverException::class
            );
        }
        $premium = $matrix->tariff->premium * count($data['objects']);
        $duration = self::getDuration($data);
        foreach ($data['objects'] as $object) {
            $birthDate = Carbon::parse($object['birthDate']);
            $age = $birthDate->floatDiffInYears(Carbon::today());
            if ($age < $conditions->minAges) {
                self::abortLog(
                    "Возраст одного из застрахованных меньше допустимого в программе {$conditions->minAges}",
                    InnerDriverException::class
                );
            }
            if ($age > $conditions->maxAges) {
                self::abortLog(
                    "Возраст одного из застрахованных больше допустимого в программе {$conditions->maxAges}",
                    InnerDriverException::class
                );
            }
        }
        $calcCoeff['insuredSum'] = $program->insuredSum;
        $calcCoeff['fullPremium'] = $premium;

        return [
            'premium' => $premium,
            'duration' => $duration,
            'insuredSum' => $program->insuredSum,
            'programId' => $program->id,
            'calcCoeff' => $calcCoeff,
        ];
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        $validActiveFromMax = Carbon::now()->startOfDay()->addMonths(3);
        if ($this->activeFrom > $validActiveFromMax) {
            self::abortLog(
                "Дата начала полиса не может быть позже чем дата заключения (сегодня) + 3 месяца",
                InnerDriverException::class
            );

            return false;
        }

        return parent::validate();
    }

    /**
     * @param $data
     * @return string
     * @throws Exception
     */
    protected static function getDuration($data): string
    {
        $activeFrom = Carbon::parse($data['activeFrom']);
        $activeTo = Carbon::parse($data['activeTo'])->addDays(1);

        $diffInMonth = $activeTo->diffInMonths($activeFrom);
        $diffInDays = $activeTo->diffInDays($activeFrom->addMonths($diffInMonth));
        if ($diffInMonth == 0 && $diffInDays <= 15) {
            $duration = '15d';
        } elseif ($diffInMonth == 0) {
            $duration = '1m';
        } else {
            $months = $diffInMonth + ($diffInDays > 0);
            if ($months > 12) {
                self::abortLog('Длительность полиса больше 12m', InnerDriverException::class);
            }
            $duration = $months . 'm';
        }

        return $duration;
    }

    /**
     * @inheritdoc
     * @param Contracts $contract
     * @param bool $sample
     * @param bool $reset
     * @param string|null $filePath
     * @return string
     */
    public function printPolicy(
        Contracts $contract,
        bool $sample,
        bool $reset = false,
        ?string $filePath = null
    ): string {
        $sampleText = $sample ? '_sample' : '';
        if (!$filePath) {
            $filename = public_path() . '/' . config('ns.pdf.path') . sha1(
                    $contract->id . $contract->number
                ) . $sampleText . '.pdf';
        } else {
            $filename = $filePath;
        }
        if (!file_exists($filename) || $reset) {
            $filename = self::generatePdf($contract, $sample, $filename);
        }

        return self::generateBase64($filename);
    }

    /**
     * @inheritdoc
     */
    public function triggerGetLink(Contracts $contract): void
    {
    }
}
