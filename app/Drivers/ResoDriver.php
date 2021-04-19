<?php

namespace App\Drivers;

use App\Drivers\Source\Reso\RegionCompiler;
use App\Drivers\Source\Reso\TokenService;
use App\Drivers\Traits\LoggerTrait;
use App\Drivers\Traits\PrintPdfTrait;
use App\Exceptions\Drivers\ResoDriverException;
use App\Models\Contracts;
use App\Models\Programs;
use App\Services\HttpClientService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Strahovka\Payment\PayService;

class ResoDriver extends BaseDriver
{
    use LoggerTrait;
    use PrintPdfTrait;

    const URL_CREATE_POLICY = '/policy/v1/tm/createPolicy';

    const URL_SAVE_TO_ACCOUNTING = '/policy/v1/tm/saveToAccounting';

    const URL_GET_POLICY_DATA = '/policy/v1/tm/getPolicyData';

    const URL_PRINT = '/am/main/v2/report';

    const URL_PAY = '/policy/v1/tm/getPayLink';

    /** @var string Todo: заполнить */
    const DOC_TYPE = 35;

    const INPUT_MODE = 3;

    /** @var string Todo: заполнить */
    protected ?string $reportId;
    /** @var string Todo: заполнить */
    protected ?string $agentKey;
    /** @var string Todo: заполнить */
    protected ?string $agencyKey;

    protected array $sendData = [];

    protected HttpClientService $httpClient;

    protected RegionCompiler $regionCompiler;

    public function __construct()
    {
        $user = config('sc.reso.login');
        $pass = config('sc.reso.pass');
        $host = config('sc.reso.host');
        $this->agentKey = config('sc.reso.agent');
        $this->agencyKey = config('sc.reso.agency');
        $this->reportId = config('sc.reso.reportId');
        if (!$this->reportId) {
            self::abortLog('Not set Reso report id.', ResoDriverException::class, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($this->agencyKey === null) {
            self::abortLog('Not set Reso agency key.', ResoDriverException::class, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($this->agentKey === null) {
            self::abortLog('Not set Reso agent key.', ResoDriverException::class, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$host) {
            self::abortLog('Not set Reso host.', ResoDriverException::class, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->sendData = [
            'Agent' => (int)$this->agentKey,
            'Agency' => (int)$this->agencyKey,
            'InputMode' => self::INPUT_MODE, // read doc
        ];
        $token = TokenService::getToken(
            HttpClientService::create(
                $host,
                [
                    'headers' => [
                        'Authorization' => "Basic ZWx0dXNlcjp2YWVGZXUxRQ=="
                    ]
                ]
            ),
            $user,
            $pass
        );
        $client = HttpClientService::create(
            $host,
            [
                'headers' => [
                    'Authorization' => "Bearer {$token}"
                ]
            ]
        );
        $this->regionCompiler = new RegionCompiler($client);
        $this->httpClient = $client;
    }

    public function getPayLink(PayService $service, Contracts $contract, Request $request): array
    {
        $result = $this->httpClient->sendJson(
            self::URL_PAY,
            [
                'PolicyID' => (integer)$contract->integration_id
            ]
        );
        if (!$result) {
            self::abortLog($this->httpClient->getLastError()['MESSAGE'], ResoDriverException::class, Response::HTTP_BAD_REQUEST);
        }

        $invoiceNum = sprintf("%s%03d%06d/%s", 'NS', $contract->company_id, $contract->id, Carbon::now()->format('His'));

        if (in_array(env('APP_ENV'), ['local', 'testing'])) {
            $invoiceNum = time() % 100 . $invoiceNum;
        }
        $link = current($result);
        parse_str(parse_url($link['LINK'],PHP_URL_QUERY), $out);

        return [
            'invoice_num' => $invoiceNum,
            'order_id' => $out['mdOrder'],
            'form_url' => $link['LINK'],
        ];
    }

    public function calculate(array $data): array
    {
        $program = Programs::where('program_code', $data['programCode'])->firstOrFail();
        $duration = self::getDuration($data);

        return [
            'programId' => $program->id,
            'duration' => $duration,
            'calcCoeff' => []
        ];
    }

    public function getStatus(Contracts $contract): array
    {
        $data = $this->httpClient->sendJson(
            self::URL_GET_POLICY_DATA,
            [
                'PolicyID' => (int)$contract->integration_id
            ]
        );
        if (!$data) {
            self::abortLog('RESO server not available', ResoDriverException::class, Response::HTTP_BAD_REQUEST);
        }
        $errors = [];
        if (empty(current($data)['POLICYID'])) {
            $errorList = $data['ERRORLIST'];
            if (!empty($errorList)) {
                foreach ($errorList as ['ERRORTEXT' => $message]) {
                    $errors[] = $message;
                }
            }
        }

        if ($errors) {
            throw new  ResoDriverException(current($errors));
        }

        $status = current($data)['PAYSTATUS'];
        if ($status !== 'Не оплачен') {
            if ($contract->status == Contracts::STATUS_DRAFT) {
                $contract->status = Contracts::STATUS_CONFIRMED;
                $contract->saveOrFail();
            }
        }

        return parent::getStatus($contract);
    }

    /**
     * @param $data
     * @return string
     * @throws \Exception
     */
    protected static function getDuration($data): string
    {
        $activeFrom = Carbon::parse($data['activeFrom']);
        $activeTo = Carbon::parse($data['activeTo'])->addDays(1);

        $diffInMonth = $activeTo->diffInMonths($activeFrom);
        $diffInDays = $activeTo->diffInDays($activeFrom->addMonths($diffInMonth));
        if ($diffInMonth == 0 && $diffInDays <= 15) {
            self::abortLog(
                'Длительность полиса должна быть равной году',
                ResoDriverException::class,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } elseif ($diffInMonth == 0) {
            self::abortLog(
                'Длительность полиса должна быть равной году',
                ResoDriverException::class,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } else {
            $months = $diffInMonth + ($diffInDays > 0);
            if ($months != 12) {
                self::abortLog(
                    'Длительность полиса должна быть равной году',
                    ResoDriverException::class,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $duration = $months . 'm';
        }

        return $duration;
    }

    public function printPolicy(Contracts $contract, bool $sample, bool $reset, ?string $filePath = null): string
    {
        $sampleText = $sample ? '_sample' : '';
        if (!$filePath) {
            $filename = config('ns.pdf.path') . sha1($contract->id . $contract->number) . $sampleText . '.pdf';
        } else {
            $filename = ltrim($filePath, '/');
        }
        $filenameWithPath = public_path($filename);

        if (!file_exists($filenameWithPath) || $reset) {
            $fileContent = $this->httpClient->sendJsonGetFile(
                self::URL_PRINT,
                [
                    'id' => $contract->integration_id,
                    'idreport' => $this->reportId
                ]
            );
            if (!$fileContent) {
                self::abortLog(
                    "File not received id: {$contract->integration_id} idreport: {$this->reportId}",
                    ResoDriverException::class,
                    Response::HTTP_BAD_REQUEST
                );
            }
            $isSaved = self::saveFileFromContent($filenameWithPath, $fileContent);
            if ($isSaved && $sample) {
                $wmPath = public_path('ns/images/sample_white.png');
                self::setWatermark($filenameWithPath, $wmPath, $filenameWithPath);
            }
        }

        return self::generateBase64($filenameWithPath);
    }

    public function triggerGetLink(Contracts $contract): void
    {
    }

    protected function beforeSaveOrUpdate(): bool
    {
        $outData = $this->httpClient->sendJson(self::URL_CREATE_POLICY, $this->collectSendData());
        if ($errors = $this->resetData(current($outData))) {
            throw new ResoDriverException(current($errors), Response::HTTP_BAD_REQUEST);
        }

        return parent::beforeSaveOrUpdate();
    }

    protected function collectSendData(): array
    {
        $this->init();
        $this->sendData['FromDate'] = $this->activeFrom->format('Y-m-d');
        $this->sendData['ToDate'] = $this->activeTo->format('Y-m-d');
        $this->sendData['HOLDER'] = [
            'IP' => false,
            'FullName' => $this->getFullName($this->data['subject']),
            'Birthdate' => Carbon::parse($this->data['subject']['birthDate'])->format('Y-m-d'),
            'Sex' => (bool)$this->data['subject']['gender'],
            'IdDocType' => self::DOC_TYPE,
            'DocSeries' => $this->data['subject']['docSeries'],
            'DocNumber' => $this->data['subject']['docNumber'],
            'FullAddress' => $this->getFullAddress($this->data['subject']),
            'CellPhone' => $this->data['subject']['phone'],
        ];
        $this->sendData['PROFILES'] = [];
        foreach ($this->data['objects'] as $obj) {
            $this->sendData['PROFILES'][] = [
                'INSURED' => [
                    'IP' => false,
                    'FullName' => $this->getFullName($obj),
                    'Birthdate' => Carbon::parse($obj['birthDate'])->format('Y-m-d'),
                    'Sex' => (bool)$obj['gender'],
                    'IdDocType' => self::DOC_TYPE,
                    'DocSeries' => $obj['docSeries'],
                    'DocNumber' => $obj['docNumber'],
                    'FullAddress' => $this->getFullAddress($obj),
                    'CellPhone' => $obj['phone'],
                ],
                'Relation' => null,
                'Region' => $this->regionCompiler->compile(
                    $this->data['subject']['kladr'],
                    $this->data['subject']['city']
                )
            ];
        }

        return $this->sendData;
    }

    protected function init()
    {
        $this->request = request();
        $this->data = $this->request->all();
        $this->activeFrom = Carbon::parse($this->data['activeFrom']);
        $this->activeTo = Carbon::parse($this->data['activeTo']);
    }

    protected function getFullName(array $data): string
    {
        return implode(
            ' ',
            array_filter(
                [
                    $data['lastName'],
                    $data['firstName'],
                    $data['middleName'] ?? '',
                ]
            )
        );
    }

    protected function getFullAddress(array $data): string
    {
        return implode(
            ' ',
            [
                'г.',
                $data['city'],
                'ул.',
                $data['street'],
                'д.',
                $data['house'],
            ]
        );
    }

    protected function resetData(?array $outData): array
    {
        if (!$outData) {
            self::abortLog('RESO server not available', ResoDriverException::class, Response::HTTP_BAD_REQUEST);
        }
        $errors = [];
        if (empty($outData['POLICYID'])) {
            $errorList = $outData['ERRORLIST'];
            if (!empty($errorList)) {
                foreach ($errorList as ['ERRORTEXT' => $message]) {
                    $errors[] = $message;
                }
            }
        }
        if ($errors) {
            return $errors;
        }
        $this->getResetPoliceData($outData['POLICYID']);

        $this->data['integrationId'] = $outData['POLICYID'];

        return [];
    }

    public function getResetPoliceData(string $policeId)
    {
        $data = $this->httpClient->sendJson(
            self::URL_GET_POLICY_DATA,
            [
                'PolicyID' => (int)$policeId
            ]
        );

        if (!$data) {
            self::abortLog('RESO server not available', ResoDriverException::class, Response::HTTP_BAD_REQUEST);
        }
        $errors = [];
        if (empty(current($data)['POLICYID'])) {
            $errorList = $data['ERRORLIST'];
            if (!empty($errorList)) {
                foreach ($errorList as ['ERRORTEXT' => $message]) {
                    $errors[] = $message;
                }
            }
        }

        if ($errors) {
            throw new  ResoDriverException(current($errors));
        }
        $data = current($data);
        $this->calcData['premium'] = $data['PREMIUM'];
        $this->data['integrationId'] = $policeId;
        $this->data['number'] = $data['PNUMBER'];
    }

    protected function saveOrUpdate(): bool
    {
        return parent::saveOrUpdate();
    }

    protected function afterSaveOrUpdate(): void
    {
        try {
            $result = $this->httpClient->sendJson(
                self::URL_SAVE_TO_ACCOUNTING,
                [
                    'PolicyID' => $this->data['integrationId']
                ]
            );
        } catch (\GuzzleHttp\Exception\ServerException $t) {
            $content = json_decode($t->getResponse()->getBody()->getContents(), true);
            self::abortLog(implode(' ', $content['UNDERWRITELIST']));
        }
        if (!empty($result) && !empty($result['UNDERWRITELIST'])) {
            $this->contract->delete();
            throw new ResoDriverException('В получении полиса отказано', Response::HTTP_BAD_REQUEST);
        }

        parent::afterSaveOrUpdate();
    }
}
