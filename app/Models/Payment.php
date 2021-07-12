<?php

namespace App\Models;

use App\Drivers\DriverResults\PayLinkInterface;
use Strahovka\LaravelFilterable\Filterable;

/**
 * App\Models\Payment
 *
 * @property int                             $id
 * @property int                             $contractId Id контракта в таблице contracts
 * @property string                          $orderId    order_id для оплаты uuid
 * @property string                          $invoiceNum invoice_num для оплаты
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property \Illuminate\Support\Carbon|null $deletedAt
 * @property-read Contract                  $contracts
 * @mixin \Eloquent
 */
class Payment extends BaseModel
{
    use Filterable;

    const NAME = 'Платежи';
    protected $fillable = [
        'contract_id',
        'order_id',
        'invoice_num',
    ];
    protected $visible = [
        'id',
        'contractId',
        'orderId',
        'invoiceNum',
    ];
    protected $appends = [
        'contractId',
        'orderId',
        'invoiceNum',
    ];

    public function contract()
    {
        return $this->belongsTo('App\Models\Contract', 'contract_id', 'id');
    }

    public function getContractIdAttribute()
    {
        return $this->attributes['contract_id'];
    }

    public function getOrderIdAttribute()
    {
        return $this->attributes['order_id'];
    }

    public function getInvoiceNumAttribute()
    {
        return $this->attributes['invoice_num'];
    }

    public static function savePayment(PayLinkInterface $payLink, Contract $contract): void
    {
        $payment = self::query()->where('contract_id', '=', $contract->id)->updateOrCreate(
            ['contract_id' => $contract->id],
            [
                'invoice_num' => $payLink->getInvoiceNum(),
                'order_id' => $payLink->getOrderId(),
            ]
        );
        $payment->contract()->associate($contract);
        $payment->saveOrFail();
    }
}
