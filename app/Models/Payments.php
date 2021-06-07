<?php

namespace App\Models;


use App\Drivers\DriverResults\PayLinkInterface;
use Strahovka\LaravelFilterable\Filterable;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int $contractId Id контракта в таблице contracts
 * @property string $orderId order_id для оплаты uuid
 * @property string $invoiceNum invoice_num для оплаты
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property \Illuminate\Support\Carbon|null $deletedAt
 * @property-read Contracts $contracts
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereInvoiceNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payments whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Payments extends BaseModel
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
        return $this->belongsTo('App\Models\Contracts', 'contract_id', 'id');
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

    public static function createPayment(PayLinkInterface $payLink, Contracts $contract): void
    {
        $payment = self::whereContractId($contract->id)->firstOrCreate(
            ['contract_id' => $contract->id],
            [
                'invoice_num' => $payLink->getInvoiceNum(),
                'order_id' => $payLink->getOrderId()
            ]
        );
        $payment->contract()->associate($contract);
        $payment->saveOrFail();
    }
}
