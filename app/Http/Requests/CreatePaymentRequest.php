<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"contract_id", "order_id", "invoice_num"},
 *     schema="CreatePaymentRequest",
 *
 * @OA\Property(property="contract_id", type="integer", format="int64", example=1, description="Идентификатор договора"),
 * @OA\Property(property="order_id", type="string", example="0e544646-4207-7ba1-8999-d9e304b053bd", description="Номер заказа эквайринга"),
 * @OA\Property(property="invoice_num", type="string", example="158702176500009997", description="Номер чека"),
 * )
 */

class CreatePaymentRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contract_id' => 'required|exists:contracts,id',
            'order_id' => 'required|string|min:1',
            'invoice_num' => 'required|string'
        ];
    }
}
